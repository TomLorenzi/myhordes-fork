<?php

namespace App\Controller\Messages;

use App\Entity\Announcement;
use App\Entity\Changelog;
use App\Entity\ForumPoll;
use App\Entity\ForumPollAnswer;
use App\Entity\ForumUsagePermissions;
use App\Entity\GlobalPoll;
use App\Entity\User;
use App\Messages\Discord\DiscordMessage;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Structures\MyHordesConf;
use DateTime;
use DiscordWebhooks\Client;
use DiscordWebhooks\Embed;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @method User getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[IsGranted('ROLE_USER')]
class MessageAnnouncementController extends MessageController
{
    /**
     * @param string $tab
     * @return Response
     */
    #[Route(path: 'jx/admin/com/changelogs/{tab}', name: 'admin_changelogs')]
    public function changelogs( string $tab = 'changelog' ): Response
    {
        return $this->render( 'ajax/admin/changelogs/changelogs.html.twig', $this->addDefaultTwigArgs(null, [
            'news' => $this->isGranted('ROLE_CROW') ? $this->entity_manager->getRepository(Changelog::class)->findAll() : [],
            'announces' => $this->entity_manager->getRepository(Announcement::class)->findAll(),
            'tab' => $tab
        ]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/com/changelogs/polls', name: 'admin_polls', priority: 1)]
    public function polls(  ): Response
    {
        return $this->render( 'ajax/admin/changelogs/polls.html.twig', $this->addDefaultTwigArgs(null, [
            'langsCodes' => $this->allLangsCodes,
            'polls' => $this->entity_manager->getRepository(GlobalPoll::class)->findAll(),
            'emotes' => $this->getEmotesByUser($this->getUser(),true),
        ]));
    }

    /**
     * @param JSONRequestParser $parser
     * @param HTMLService $html
     * @return Response
     */
    #[Route(path: 'api/admin/com/changelogs/new_poll', name: 'admin_changelog_new_poll')]
    public function create_poll_api(JSONRequestParser $parser, HTMLService $html): Response {
        if ($this->isGranted('ROLE_ADMIN')) $p = ForumUsagePermissions::PermissionOwn;
        elseif ($this->isGranted('ROLE_CROW')) $p = ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingModerator;
        else $p = ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingOracle;

        $format_html = function(&$data) use ($html, $p): bool {
            foreach ($this->generatedLangsCodes as $lang) {
                $str = trim($data[$lang]);
                if (mb_strlen($str) < 3) return false;
                if (!$html->htmlPrepare( $this->getUser(), $p, false, $data[$lang], null, $insight  )) return false;
                if ($insight->text_length < 3) return false;
            }
            return true;
        };

        $title = $parser->get_array( 'title' );
        $desc = $parser->get_array( 'desc' );
        $premature = (bool)$parser->get( 'premature' );
        $preview = $parser->get_array( 'preview' );

        try {
            $start = new DateTime( $parser->get('start', '-1') );
            $end = new DateTime( $parser->get('end', '-1') );
        } catch (\Throwable $t) { return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest ); }

        if ($end <= new DateTime('now')) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if ($start >= $end) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $answers = array_values( $parser->get_array( 'answers' ) );
        if (count($answers) < 2) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (!$format_html($title)) AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if (!$format_html($desc)) AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if (!$format_html($preview)) AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $answer_data = [];
        foreach ( $answers as &$answer ) {
            if (!$format_html($answer['title'])) AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
            if (!$format_html($answer['desc'])) AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
            $answer_data[] = [$answer, (new ForumPollAnswer())->setNum(0)];
        }

        $poll = (new ForumPoll())->setOwner( $this->getUser() )->setClosed( false );
        foreach ($answer_data as [,$answer_entity]) $poll->addAnswer( $answer_entity );

        try {
            $this->entity_manager->persist($poll);
            $this->entity_manager->flush();

            $global_poll = (new GlobalPoll())
                ->setPoll( $poll )->setStartDate( $start )->setEndDate( $end )->setShowResultsImmediately( $premature );

            foreach ($this->allLangsCodes as $lang) if ($lang !== 'ach') {
                $global_poll
                    ->setTitleByLang( $lang, $title[$lang] )
                    ->setDescriptionByLang( $lang, $desc[$lang] )
                    ->setShortDescriptionByLang( $lang, $preview[$lang] );
                foreach ($answer_data as [['title' => $answer_title, 'desc' => $answer_desc],$entity])
                    $global_poll
                        ->setAnswerTitleByLang( $entity, $lang, $answer_title[$lang] )
                        ->setAnswerDescriptionByLang( $entity, $lang, $answer_desc[$lang] );
            }

            $this->entity_manager->persist($global_poll);
            $this->entity_manager->flush();

        } catch (\Throwable $t) { return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['m' => $t->getMessage()] ); }

        return AjaxResponse::success( true, ['url' => $this->generateUrl('soul_polls', ['id' => $global_poll->getId()])] );
    }

    /**
     * @param int $id
     * @param string $action
     * @return Response
     */
    #[Route(path: 'api/admin/changelogs/poll/{id}/{action}', name: 'admin_changelog_poll_control')]
    public function modify_poll_api(int $id, string $action): Response {

        if (!$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $poll = $this->entity_manager->getRepository(GlobalPoll::class)->find($id);
        if (!$poll) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $now = new DateTime();
        if ($action === 'start' && $poll->getStartDate() > $now)
            $this->entity_manager->persist( $poll->setStartDate($now) );
        elseif ($action === 'close' && $poll->getStartDate() < $now && $poll->getEndDate() > $now)
            $this->entity_manager->persist( $poll->setEndDate($now) );
        elseif ($action === 'delete') {
            $this->entity_manager->remove( $poll );
        }

        $this->entity_manager->flush();

        return AjaxResponse::success( );
    }



    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/changelogs/c/editor', name: 'admin_new_changelog_editor_controller')]
    public function admin_new_changelog_editor_controller(): Response {
        return $this->render( 'ajax/editor/changelog.html.twig', [
            'permission' => $this->getPermissionObject( match(true) {
                $this->isGranted('ROLE_ADMIN') => ForumUsagePermissions::PermissionOwn,
                $this->isGranted('ROLE_CROW') => ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingModerator,
                default => ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingOracle,
            } ),
        ] );
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/com/changelogs/a/editor', name: 'admin_new_announcement_editor_controller')]
    public function admin_new_announcement_editor_controller(): Response {
        return $this->render( 'ajax/editor/announcement.html.twig', [
            'uuid' => Uuid::v4(),
            'permission' => $this->getPermissionObject( match(true) {
                $this->isGranted('ROLE_ADMIN') => ForumUsagePermissions::PermissionOwn,
                $this->isGranted('ROLE_CROW') => ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingModerator,
                default => ForumUsagePermissions::PermissionReadWrite | ForumUsagePermissions::PermissionFormattingOracle,
            } ),
        ] );
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/changelogs/new_changelog', name: 'admin_changelog_new_changelog')]
    public function create_changelog_api(EntityManagerInterface $em, JSONRequestParser $parser): Response {
        $title     = $parser->get('title', '');
        $content   = $parser->get('content', '');
        $version   = $parser->get('version', '');
        $lang      = $parser->get('lang', 'de');

        $author    = $this->getUser();

        if(empty($title) || empty($content) || empty($version)) {
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        $change = new Changelog();
        $change->setTitle($title)->setText($content)->setVersion($version)->setLang($lang)->setAuthor($author)->setDate(new DateTime());

        if (!$this->preparePost($author,null,$change))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $em->persist($change);
        $em->flush();

        return AjaxResponse::success( true, ['url' => $this->generateUrl('admin_changelogs', ['tab' => 'changelog'])] );
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param UrlGeneratorInterface $urlGenerator
     * @param MessageBusInterface $bus
     * @return Response
     */
    #[Route(path: 'api/admin/com/changelogs/new_announcement', name: 'admin_changelog_new_announcement')]
    public function create_announcement_api(EntityManagerInterface $em, JSONRequestParser $parser, UrlGeneratorInterface $urlGenerator, MessageBusInterface $bus): Response {
        $title     = $parser->get('title', '');
        $content   = $parser->get('content', '');
        $lang      = $parser->get('lang', 'de');

        $author = $this->getUser();

        if(empty($title) || empty($content)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $announcement = (new Announcement())
            ->setTitle($title)->setText($content)->setLang($lang)->setSender($author)->setTimestamp(new DateTime())
            ->setValidated( $this->isGranted( 'ROLE_CROW' ) || $this->isGranted( 'ROLE_ADMIN' ) );

        if (!$this->preparePost($author,null,$announcement))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $em->persist($announcement);
        $em->flush();

        if ($endpoint = $this->conf->getGlobalConf()->get( MyHordesConf::CONF_ANIM_MAIL_DCHOOK )) {
            $discord = (new Client($endpoint))
                ->message(":black_joker: **Please validate my announcement.**");

            $discord->embed( (new Embed())
                                 ->color('B434EB')
                                 ->title($announcement->getTitle())
                                 ->description(mb_substr(strip_tags(
                                                   preg_replace(
                                                       ['/(?:<br ?\/?>)+/', '/<span class="quoteauthor">([\w\d ._-]+)<\/span>/',  '/<blockquote>/', '/<\/blockquote>/', '/<a href="(.*?)">(.*?)<\/a>/'],
                                                       ["\n", '${1}:', '[**', '**]', '[${2}](${1})'],
                                                       $this->html->prepareEmotes( $announcement->getText())
                                                   )
                                               ), 0, 2000))
                                 ->field('Language', $announcement->getLang(), true)
                                 ->author(
                                     $this->getUser()->getName(),
                                     $urlGenerator->generate( 'admin_users_account_view', ['id' => $this->getUser()->getId()], UrlGeneratorInterface::ABSOLUTE_URL ),
                                     $this->getUser()->getAvatar() ? $urlGenerator->generate( 'app_web_avatar', ['uid' => $this->getUser()->getId(), 'name' => $this->getUser()->getAvatar()->getFilename(), 'ext' => $this->getUser()->getAvatar()->getFormat()],UrlGeneratorInterface::ABSOLUTE_URL ) : ''
                                 )
            );

            $bus->dispatch( new DiscordMessage( $discord ) );
        }

        return AjaxResponse::success( true, ['url' => $this->generateUrl('admin_changelogs', ['tab' => 'announcement'])] );
    }

    /**
     * @param Changelog $changelog
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/admin/com/changelogs/del_c/{id<\d+>}', name: 'admin_changelog_del_changelog')]
    public function delete_changelog_api(Changelog $changelog, EntityManagerInterface $em): Response {
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $changelog->getAuthor())
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $em->remove($changelog);
        $em->flush();

        return AjaxResponse::success( );
    }

    /**
     * @param Announcement $announcement
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/admin/com/changelogs/del_a/{id<\d+>}', name: 'admin_changelog_del_announcement')]
    public function delete_announcement_api(Announcement $announcement, EntityManagerInterface $em): Response {
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $announcement->getSender())
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $em->remove($announcement);
        $em->flush();

        return AjaxResponse::success( );
    }

    /**
     * @param Announcement $announcement
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/admin/com/changelogs/validate/{id<\d+>}', name: 'admin_changelog_val_announcement')]
    public function validate_announcement_api(Announcement $announcement, EntityManagerInterface $em): Response {
        if (!$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (!$announcement->isValidated()) {
            $em->persist( $announcement->setValidated(true)->setValidatedBy( $this->getUser() ) );
            $em->flush();
        }

        return AjaxResponse::success( );
    }

    /**
     * @param Announcement $announcement
     * @return Response
     */
    #[Route(path: 'api/admin/com/changelogs/render/{id<\d+>}', name: 'admin_changelog_render_announcement')]
    public function render_announcement_api(Announcement $announcement): Response {
        return AjaxResponse::success( additional: [
            'html' => $this->html->prepareEmotes( $announcement->getText(), $announcement->getSender() )
                                                  ] );
    }
}