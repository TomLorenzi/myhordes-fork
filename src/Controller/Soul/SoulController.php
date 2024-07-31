<?php

namespace App\Controller\Soul;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractController;
use App\Entity\AccountRestriction;
use App\Entity\AdminReport;
use App\Entity\Announcement;
use App\Entity\AntiSpamDomains;
use App\Entity\AutomaticEventForecast;
use App\Entity\Award;
use App\Entity\CauseOfDeath;
use App\Entity\Changelog;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\ExternalApp;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\ForumPollAnswer;
use App\Entity\FoundRolePlayText;
use App\Entity\GlobalPoll;
use App\Entity\HeroSkillPrototype;
use App\Entity\OfficialGroup;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\RememberMeTokens;
use App\Entity\ShoutboxEntry;
use App\Entity\ShoutboxReadMarker;
use App\Entity\SocialRelation;
use App\Entity\Statistic;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\TwinoidImport;
use App\Entity\User;
use App\Entity\RolePlayTextPage;
use App\Entity\Season;
use App\Entity\UserDescription;
use App\Entity\UserGroupAssociation;
use App\Entity\UserPendingValidation;
use App\Entity\UserReferLink;
use App\Entity\UserSponsorship;
use App\Enum\AdminReportSpecification;
use App\Enum\DomainBlacklistType;
use App\Enum\StatisticType;
use App\Enum\UserSetting;
use App\Response\AjaxResponse;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\ErrorHelper;
use App\Service\EternalTwinHandler;
use App\Service\EventProxyService;
use App\Service\HookExecutor;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Service\RandomGenerator;
use App\Service\RateLimitingFactoryProvider;
use App\Service\UserFactory;
use App\Service\UserHandler;
use App\Service\AdminHandler;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\TimeKeeperService;
use App\Structures\MyHordesConf;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validation;

/**
 * @method User getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
class SoulController extends CustomAbstractController
{
    const ErrorUserEditPasswordIncorrect     = ErrorHelper::BaseSoulErrors + 1;
    const ErrorTwinImportInvalidResponse     = ErrorHelper::BaseSoulErrors + 2;
    const ErrorTwinImportNoToken             = ErrorHelper::BaseSoulErrors + 3;
    const ErrorTwinImportProfileMismatch     = ErrorHelper::BaseSoulErrors + 4;
    const ErrorTwinImportProfileInUse        = ErrorHelper::BaseSoulErrors + 5;
    const ErrorETwinImportProfileInUse       = ErrorHelper::BaseSoulErrors + 6;
    const ErrorETwinImportServerCrash        = ErrorHelper::BaseSoulErrors + 7;
    const ErrorUserEditUserName              = ErrorHelper::BaseSoulErrors + 8;
    const ErrorUserEditTooSoon               = ErrorHelper::BaseSoulErrors + 9;
    const ErrorUserUseEternalTwin            = ErrorHelper::BaseSoulErrors + 10;
    const ErrorUserConfirmToken              = ErrorHelper::BaseSoulErrors + 11;
    const ErrorUserEditUserNameTooLong       = ErrorHelper::BaseSoulErrors + 12;

    const ErrorCoalitionAlreadyMember        = ErrorHelper::BaseSoulErrors + 20;
    const ErrorCoalitionNotSet               = ErrorHelper::BaseSoulErrors + 21;
    const ErrorCoalitionUserAlreadyMember    = ErrorHelper::BaseSoulErrors + 22;
    const ErrorCoalitionFull                 = ErrorHelper::BaseSoulErrors + 23;
    const ErrorCoalitionNameTooLong          = ErrorHelper::BaseSoulErrors + 24;
    const ErrorCoalitionNameTooShort          = ErrorHelper::BaseSoulErrors + 25;


    protected UserFactory $user_factory;
    protected UserHandler $user_handler;
    protected KernelInterface $kernel;
    protected Packages $asset;
    protected CrowService $crow;

    public function __construct(EntityManagerInterface $em, UserFactory $uf, Packages $a, UserHandler $uh, TimeKeeperService $tk, TranslatorInterface $translator, ConfMaster $conf, CitizenHandler $ch, InventoryHandler $ih, KernelInterface $kernel, CrowService $crow, HookExecutor $hookExecutor)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator, $hookExecutor);
        $this->user_factory = $uf;
        $this->asset = $a;
        $this->user_handler = $uh;
        $this->kernel = $kernel;
        $this->crow = $crow;
    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null ): array {
        $data = parent::addDefaultTwigArgs($section, $data);

        $user = $this->getUser();

        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy( [
            'user' => $user,
            'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]
        ]);

        $user_invitations = $user_coalition ? [] : $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
            'user' => $user,
            'associationType' => UserGroupAssociation::GroupAssociationTypeCoalitionInvitation ]
        );

        $sb = $this->user_handler->getShoutbox($user);
        $messages = false;
        if ($sb) {
            $last_entry = $this->entity_manager->getRepository(ShoutboxEntry::class)->findOneBy(['shoutbox' => $sb], ['timestamp' => 'DESC', 'id' => 'DESC']);
            if ($last_entry) {
                $marker = $this->entity_manager->getRepository(ShoutboxReadMarker::class)->findOneBy(['user' => $user]);
                if (!$marker || $last_entry !== $marker->getEntry()) $messages = true;
            }
        }

        $data["soul_tab"] = $section;
        $data["new_message"] = !empty($user_invitations) || $messages;
        $data["new_news"] = $this->entity_manager->getRepository(Announcement::class)->countUnreadByUser($user, $this->getUserLanguage()) > 0;
        $data["season"] = $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]);
        return $data;
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/soul/disabled_message', name: 'soul_disabled')]
    public function soul_disabled(): Response
    {
        $user = $this->getUser();
        if (!$this->user_handler->isRestricted( $user, AccountRestriction::RestrictionGameplay ))
            return $this->redirect($this->generateUrl( 'soul_me' ));

        $largest = null;

        /** @var AccountRestriction[] $restrictions */
        $restrictions = $this->entity_manager->getRepository(AccountRestriction::class)->findBy(['user' => $user, 'active' => true, 'confirmed' => true]);
        foreach ($restrictions as $restriction) {
            if (($restriction->getRestriction() & AccountRestriction::RestrictionGameplay) === AccountRestriction::RestrictionGameplay) {
                if ($largest === null || $restriction->getExpires() === null || ($largest->getExpires() !== null && $restriction->getExpires() > $largest->getExpires()))
                    $largest = $restriction;
            }
        }

        return $this->render( 'ajax/soul/acc_disabled.html.twig', $this->addDefaultTwigArgs(null, ['restriction' => $largest]));
    }

    /**
     * @param HTMLService $html
     * @return Response
     */
    #[Route(path: 'jx/soul/me', name: 'soul_me')]
    public function soul_me(HTMLService $html): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $latestSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getLatestUnlocked($user->getAllHeroDaysSpent());
        $nextSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getNextUnlockable($user->getAllHeroDaysSpent());

        $factor1 = $latestSkill !== null ? $latestSkill->getDaysNeeded() : 0;

        $progress = $nextSkill !== null ? ($user->getAllHeroDaysSpent() - $factor1) / ($nextSkill->getDaysNeeded() - $factor1) * 100.0 : 0;

        $desc = $this->entity_manager->getRepository(UserDescription::class)->findOneBy(['user' => $user]);

        $features = [];
        $season = $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]);
        foreach ($this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findAll() as $p)
            if ($ff = $this->entity_manager->getRepository(FeatureUnlock::class)->findOneActiveForUser($user,$season,$p))
                $features[] = $ff;

        return $this->render( 'ajax/soul/me.html.twig', $this->addDefaultTwigArgs("soul_me", [
            'user' => $user,
            'features' => $features,
            'latestSkill' => $latestSkill,
            'progress' => floor($progress),
            'seasons' => $this->entity_manager->getRepository(Season::class)->findPastAndPresent(),
            'user_desc' => $desc ? $html->prepareEmotes($desc->getText(), $this->getUser()) : null
        ]));
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/soul/pictos/{id}', name: 'soul_pictos_all', requirements: ['id' => '\d+'])]
    public function soul_pictos_all(int $id): Response
    {
        $current_user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($current_user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if($user === null)
            return $this->redirect($this->generateUrl('soul_me'));

        return $this->render( 'ajax/soul/pictos.html.twig', $this->addDefaultTwigArgs(null, [
            'user' => $user,
        ]));
    }

    /**
     * @param Request $request
     * @param ConfMaster $conf
     * @param int $opt
     * @return Response
     */
    #[Route(path: 'jx/soul/refer', name: 'soul_refer')]
    #[Route(path: 'jx/soul/contacts/{opt}', name: 'soul_contacts', requirements: ['opt' => '\d'])]
    public function soul_refer(Request $request, ConfMaster $conf, int $opt = 0): Response {
        $refer = $this->entity_manager->getRepository(UserReferLink::class)->findOneBy(['user' => $this->getUser()]);
        if ($refer === null && !$this->user_handler->hasRole($this->getUser(), 'ROLE_DUMMY')) {

            $name_base = strtolower($this->getUser()->getUsername());

            $refer = (new UserReferLink)->setUser($this->getUser())->setActive(true)->setName($name_base);
            $n = 2;

            while ($this->entity_manager->getRepository(UserReferLink::class)->findOneBy(['name' => $refer->getName()]) && $n <= 999)
                $refer->setName( sprintf("%s.%03u", $name_base, $n++) );

            if ($n > 999) $refer = null;
            else try {
                $this->entity_manager->persist($refer);
                $this->entity_manager->flush();
            } catch (Exception $e) {
                $refer = null;
            }
        }

        $sponsored = array_filter(
            $this->entity_manager->getRepository(UserSponsorship::class)->findBy(['sponsor' => $this->getUser()]),
            fn(UserSponsorship $s) => !$this->user_handler->hasRole($s->getUser(), 'ROLE_DUMMY') && $s->getUser()->getValidated()
        );

        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->user_handler->getCoalitionMembership($this->getUser());

        $coa_full = false;

        $coa_members = [];

        if ($user_coalition) {
            $all_users = $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'association' => $user_coalition->getAssociation(),
                'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive, UserGroupAssociation::GroupAssociationTypeCoalitionInvitation] ]
            );

            foreach ($all_users as $coa_user)
                $coa_members[$coa_user->getUser()->getId()] = $coa_user->getAssociationType();

            $coa_full = count($all_users) >= $conf->getGlobalConf()->get(MyHordesConf::CONF_COA_MAX_NUM, 5);
        }

        $reverse_friends = $this->entity_manager->getRepository(User::class)->findInverseFriends($this->getUser(), true);
        $reverse_friends = array_filter( $reverse_friends, fn(User $u) => !str_contains($u->getEmail(), '$ deleted'));
        $all_reverse_friends = count($reverse_friends);
        if ($opt !== 1) $reverse_friends = array_filter( $reverse_friends, fn(User $friend) => !$this->user_handler->checkRelation( $this->getUser(), $friend, SocialRelation::SocialRelationTypeNotInterested ) );

        return $this->render( 'ajax/soul/social.html.twig', $this->addDefaultTwigArgs("soul_refer", [
            'tab' => $request->attributes->get('_route') === 'soul_refer' ? 'refer' : 'friends',

            'refer' => $refer,
            'sponsored' => $sponsored,
            'lang' => $this->getUserLanguage(),

            'friends' => $this->getUser()->getFriends(),
            'reverse_friends' => $reverse_friends,
            'reverse_friends_hidden' => count($reverse_friends) < $all_reverse_friends,
            'opt' => $opt,

            'blocklist' => $this->entity_manager->getRepository( SocialRelation::class )->findBy( ['owner' => $this->getUser(), 'type' => SocialRelation::SocialRelationTypeBlock ] ),

            'coa' => $user_coalition,
            'coa_leader' => $user_coalition && $user_coalition->getAssociationLevel() === UserGroupAssociation::GroupAssociationLevelFounder,
            'coa_full' => $coa_full,
            'coa_members' => $coa_members]));
    }

    #[Route(path: 'jx/soul/exists', name: 'user_exists')]
    #[GateKeeperProfile(allow_during_attack: true, record_user_activity: false)]
    public function users_exists(JSONRequestParser $parser, TranslatorInterface $translator): Response {
        $return = [];

        $fixed_account_translators = [
            66 => 'Der Rabe',
            67 => 'Animateur-Team',
        ];

        $add = function(?User $u, string $name, int $id) use (&$return, &$translator, &$fixed_account_translators) {
            $name_fixed = ($fixed_account_translators[$u?->getId() ?? -1] ?? null)
                ? $translator->trans($fixed_account_translators[$u?->getId() ?? -1], [], 'global')
                : null;

            $return[] =
                [
                    'exists' => $u !== null ? 1 : 0,
                    'id' => $u?->getId() ?? $id,
                    'displayName' => $name_fixed ?? $u?->getName() ?? $name,
                    'queryName' => $name
                ];
        };

        $missing_name = $this->translator->trans( 'Specify using player search', [], 'soul' );

        $addMultiple = function(int $count, string $name) use (&$return, $missing_name) {
            if ($count > 1)
                $return[] =
                    [
                        'exists' => $count,
                        'id' => -1,
                        'displayName' => $missing_name,
                        'queryName' => $name
                    ];
        };

        foreach ( array_slice( $parser->get_array( 'names', [] ), 0, 100 ) as $name ) {
            if ($name === 'me') $add( $this->getUser(), 'me', $this->getUser()->getId() );
            elseif (($count = $this->entity_manager->getRepository(User::class)->countByNameOrDisplayName( trim($name) )) < 2)
                $add( $this->entity_manager->getRepository(User::class)->findOneByNameOrDisplayName( trim($name) ), $name, -1 );
            else $addMultiple( $count, $name );
        }


        foreach ( array_slice( $parser->get_array( 'ids', [] ), 0, 100 ) as $id )
            $add( $this->entity_manager->getRepository(User::class)->find( (int)$id ), '', $id );

        return AjaxResponse::success( true, ['data' => $return] );
    }


    /**
     * @return Response
     */
    #[Route(path: 'jx/soul/heroskill', name: 'soul_heroskill')]
    public function soul_heroskill(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        // Get all the picto & count points
        $latestSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getLatestUnlocked($user->getAllHeroDaysSpent());
        $nextSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getNextUnlockable($user->getAllHeroDaysSpent());

        $allSkills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->findAll();

        $factor1 = $latestSkill !== null ? $latestSkill->getDaysNeeded() : 0;
        $progress = $nextSkill !== null ? ($user->getAllHeroDaysSpent() - $factor1) / ($nextSkill->getDaysNeeded() - $factor1) * 100.0 : 0;

        return $this->render( 'ajax/soul/heroskills.html.twig', $this->addDefaultTwigArgs("soul_me", [
            'latestSkill' => $latestSkill,
            'nextSkill' => $nextSkill,
            'progress' => floor($progress),
            'skills' => $allSkills
        ]));
    }

    /**
     * @param Request $request
     * @param UserHandler $userHandler
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/soul/future/{id}', name: 'soul_future')]
    public function soul_future(Request $request, UserHandler $userHandler, HTMLService $html, int $id = 0): Response
    {
        $user = $this->getUser();

        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $lang = $user->getLanguage() ?? $request->getLocale() ?? 'de';
        $news = $this->entity_manager->getRepository(Changelog::class)->findByLang($lang);

        $selected = $id > 0 ? $this->entity_manager->getRepository(Changelog::class)->find($id) : null;
        if ($selected === null)
            $selected = $news[0] ?? null;



        try {
            $userHandler->setSeenLatestChangelog( $user, $lang );
            $this->entity_manager->flush();
        } catch (Exception $e) {}

        $selected?->setText( $html->prepareEmotes( $selected?->getText() ) );

        return $this->render( 'ajax/soul/future.html.twig', $this->addDefaultTwigArgs("soul_future", [
            'news' => $news, 'selected' => $selected,
            'has_polls' => !empty($this->entity_manager->getRepository(GlobalPoll::class)->findByState(true, true, false))
        ]) );
    }

    /**
     * @param int $id
     * @param string $group
     * @param string $tag
     * @return Response
     */
    #[Route(path: 'jx/soul/polls/{id}/{group}/{tag}', name: 'soul_polls')]
    public function soul_polls(int $id = 0, string $group = '', string $tag = ''): Response
    {
        if (($group !== '' || $tag !== '') && !$this->isGranted('ROLE_SUB_ADMIN'))
            return $this->redirect($this->generateUrl( 'soul_polls' ));

        if (!$this->isGranted('ROLE_SUB_ADMIN')) {
            $group = 'antigrief';
            $tag = 'pass';
        }

        $polls = $this->entity_manager->getRepository(GlobalPoll::class)->findByState(true, true, $this->isGranted('ROLE_ORACLE'));

        $selected = ($id > 0 ? $this->entity_manager->getRepository(GlobalPoll::class)->find($id) : null);
        if ($selected && $selected->getStartDate() > new DateTime() && !$this->isGranted('ROLE_ORACLE'))
            $selected = null;

        if ($selected === null && $id > 0)
            return $this->redirect($this->generateUrl( 'soul_polls' ));

        $selected = $selected ?? $polls[0] ?? null;

        return $this->render( 'ajax/soul/polls.html.twig', $this->addDefaultTwigArgs("soul_future", [
            'all_tags' => $this->isGranted('ROLE_SUB_ADMIN') ? $selected?->getPoll()?->getAllAnswerTags() : [],
            'group' => $group, 'tag' => $tag,
            'polls' => $polls, 'selected' => $selected
        ]) );
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/soul/stats', name: 'soul_stats')]
    public function soul_stats(): Response
    {
        $byLang = $this->entity_manager->getRepository(Statistic::class)->createQueryBuilder('s')
            ->select(
                array_merge(
                    array_map(fn(string $lang) => "AVG(JSON_EXTRACT(s.payload, '$.by_lang.{$lang}')) as {$lang}", $this->generatedLangsCodes),
                    ["AVG(JSON_EXTRACT(s.payload, '$.total')) as total"]
                ),
            )
            ->andWhere('s.type = :type')->setParameter('type', StatisticType::PlayerStatsDaily->value)
            ->andWhere('s.created >= :date')->setParameter('date', (new DateTime('today-7day')))
            ->getQuery()->execute()[0] ?? [];

        $byLangMonthly = $this->entity_manager->getRepository(Statistic::class)->createQueryBuilder('s')
            ->select(
                array_merge(
                    array_map(fn(string $lang) => "JSON_EXTRACT(s.payload, '$.by_lang.{$lang}') as {$lang}", $this->generatedLangsCodes),
                    ["JSON_EXTRACT(s.payload, '$.total') as total"]
                ),
            )
            ->andWhere('s.type = :type')->setParameter('type', StatisticType::PlayerStatsMonthly->value)
            ->orderBy('s.created', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->execute()[0] ?? [];

        return $this->render( 'ajax/soul/stats.html.twig', $this->addDefaultTwigArgs("soul_future", [

            'byLang' => array_map(
                fn(string $l) => $byLang[$l] ?? 0,
                array_combine( $this->generatedLangsCodes, $this->generatedLangsCodes )
            ),
            'byLang_total' => $byLang['total'] ?? 0,

            'byLang_month' => array_map(
                fn(string $l) => $byLangMonthly[$l] ?? 0,
                array_combine( $this->generatedLangsCodes, $this->generatedLangsCodes )
            ),
            'byLang_total_month' => $byLangMonthly['total'] ?? 0,

        ]) );
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/soul/events', name: 'soul_events')]
    public function soul_events(): Response
    {
        $now = new DateTimeImmutable();
        $cutoff = new DateTimeImmutable('today+400days');

        $all_events = $this->entity_manager->getRepository(AutomaticEventForecast::class)->matching(
            (new Criteria())
                ->where( Criteria::expr()->gte( 'end', $now ) )
                ->andWhere( Criteria::expr()->lt( 'start', $cutoff ) )
                ->orderBy(['start' => Criteria::ASC])
        )->filter(fn(AutomaticEventForecast $f) => $this->conf->eventIsPublic( $f->getEvent() ));

        return $this->render( 'ajax/soul/events.html.twig', $this->addDefaultTwigArgs("soul_future", [
            'active_events' => $all_events->filter( fn(AutomaticEventForecast $f) => $f->getStart() <= $now ),
            'future_events' => $all_events->filter( fn(AutomaticEventForecast $f) => $f->getStart() > $now ),
        ]) );
    }


    /**
     * @param JSONRequestParser $parser
     * @param int $id
     * @param bool $multi
     * @param int|null $answer
     * @return Response
     * @throws Exception
     */
    #[Route(path: 'api/soul/polls/{id<\d+>}/{answer<\d+>}', name: 'soul_poll_participate_single', defaults: ['multi' => false])]
    #[Route(path: 'api/soul/polls/{id<\d+>}/multi', name: 'soul_poll_participate_multi', defaults: ['multi' => true])]
    public function soul_poll_participate(JSONRequestParser $parser, int $id, bool $multi, ?int $answer = null): Response
    {
        $user = $this->getUser();
        $now = new DateTime();

        $poll = $this->entity_manager->getRepository(GlobalPoll::class)->find($id);
        if (!$poll || $poll->getStartDate() > $now || $poll->getEndDate() < $now || $poll->getPoll()->getParticipants()->contains($user))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if ($poll->isMultipleChoice() !== $multi) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $answers = $multi ? $parser->get_array('answers') : [$answer];

        foreach ($answers as $selection)
            if ($selection > 0) {

                $answer = $this->entity_manager->getRepository(ForumPollAnswer::class)->find($selection);
                if (!$answer || !$poll->getPoll()->getAnswers()->contains($answer))
                    return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

                $answer->setNum( $answer->getNum() + 1 );

                $main = 'none';
                foreach ($user->getTwinoidImports() as $import)
                    if ($import->getMain()) $main = $import->getScope() ?? 'none';

                $sp = $user->getAllSoulPoints();
                $anti_grief =
                    $user->getAllSoulPoints() < $this->conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_SP, 20) ||
                    $this->user_handler->isRestricted( $user, AccountRestriction::RestrictionGameplay ) ||
                    $this->isGranted( 'ROLE_DUMMY' );

                $answer->incTagNumber('origin', $main);
                $answer->incTagNumber('lang', $user->getLanguage());
                if ($sp < 100)       $answer->incTagNumber('sp', '0_99');
                elseif ($sp < 1000)  $answer->incTagNumber('sp', '100_999');
                elseif ($sp < 10000) $answer->incTagNumber('sp', '1000_9999');
                else                 $answer->incTagNumber('sp', '10000');
                $answer->incTagNumber('antigrief', $anti_grief ? 'fail' : 'pass');

                $this->entity_manager->persist($answer);
            }

        $poll->getPoll()->addParticipant( $user );
        $this->entity_manager->persist( $poll->getPoll() );
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/soul/news', name: 'soul_news')]
    public function soul_news(): Response
    {
        return $this->render( 'ajax/soul/news.html.twig', $this->addDefaultTwigArgs("soul_news", []) );
    }


    /**
     * @param EternalTwinHandler $etwin
     * @return Response
     */
    #[Route(path: 'jx/soul/settings', name: 'soul_settings')]
    public function soul_settings(EternalTwinHandler $etwin): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $user_desc = $this->entity_manager->getRepository(UserDescription::class)->findOneBy(['user' => $user]);

        $a_max_size = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_AVATAR_SIZE_UPLOAD, 3145728);
        $b_max_size = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_AVATAR_SIZE_STORAGE, 1048576);

        if     ($a_max_size >= 1048576) $a_max_size = round($a_max_size/1048576, 2) . ' MB';
        elseif ($a_max_size >= 1024)    $a_max_size = round($a_max_size/1024, 0) . ' KB';
        else                            $a_max_size = $a_max_size . ' B';

        if     ($b_max_size >= 1048576) $b_max_size = round($b_max_size/1048576, 2) . ' MB';
        elseif ($b_max_size >= 1024)    $b_max_size = round($b_max_size/1024, 0) . ' KB';
        else                            $b_max_size = $b_max_size . ' B';

        $season = $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]);

        return $this->render( 'ajax/soul/settings.html.twig', $this->addDefaultTwigArgs("soul_settings", [
            'et_ready' => $etwin->isReady(),
            'user_desc' => $user_desc ? $user_desc->getText() : null,
            'flags' => $this->getFlagList(),
            'next_name_change_days' => $user->getLastNameChange() ? max(0, (30 * 4) - $user->getLastNameChange()->diff(new DateTime())->days ) : 0,
            'show_importer'     => $user->getTwinoidID() !== null && $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_ENABLED, true),
            'avatar_max_size' => [$a_max_size, $b_max_size,$this->conf->getGlobalConf()->get(MyHordesConf::CONF_AVATAR_SIZE_UPLOAD, 3145728)],
            'langs' => $this->generatedLangs,
            'team_tickets_in' => $season ? $user->getTeamTicketsFor( $season, '' )->count() : 0,
            'team_tickets_out' => $season ? $user->getTeamTicketsFor( $season, '!' )->count() : 0,
            'team_tickets_limit' => $this->conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_FOREIGN_CAP, 3),
        ]) );
    }

    protected function getFlagList(): array {
        $flags = [];
        foreach (scandir("{$this->kernel->getProjectDir()}/assets/img/lang/any") as $f)
            if ($f !== '.' && $f !== '..' && str_ends_with( strtolower($f), '.svg' )) $flags[] = substr( $f, 0, -4);
        return $flags;
    }

    /**
     * @param JSONRequestParser $parser
     * @param HTMLService $html
     * @return Response
     */
    #[Route(path: 'api/soul/settings/header', name: 'api_soul_header')]
    public function soul_set_header(JSONRequestParser $parser, HTMLService $html): Response {
        $user = $this->getUser();

        $title = $parser->get_int('title', -1);
        $title_lang = $parser->get('title_lang', null);
        $icon  = $parser->get_int('icon', -1);
        $flag  = $parser->get('flag', '');
        $desc  = mb_substr(trim($parser->get('desc')) ?? '', 0, 256);
        $displayName = mb_substr(trim($parser->get('displayName')) ?? '', 0, 30);
        $pronoun = $parser->get('pronoun','none', ['male','female','none']);
        $pronounTitle = $parser->get('pronounTitle','none', ['male','female','none']);

        if ($pronoun !== 'none' && $user->getUseICU() !== true)
            $user->setUseICU(true);

        if ($title_lang !== null) {
            if ( !in_array( $title_lang, array_merge( $this->generatedLangsCodes, ['_me', '_them'] ) ) )
                return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
            $user->setSetting( UserSetting::TitleLanguage, $title_lang );
        }

        switch ($pronoun) {
            case 'male': $user->setPreferredPronoun( User::PRONOUN_MALE ); break;
            case 'female': $user->setPreferredPronoun( User::PRONOUN_FEMALE ); break;
            default: $user->setPreferredPronoun( User::PRONOUN_NONE ); break;
        }

        switch ($pronounTitle) {
            case 'male': $user->setPreferredPronounTitle( User::PRONOUN_MALE ); break;
            case 'female': $user->setPreferredPronounTitle( User::PRONOUN_FEMALE ); break;
            default: $user->setPreferredPronounTitle( User::PRONOUN_NONE ); break;
        }

        if ($title < 0 && $icon >= 0)
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($flag !== '' && !in_array( $flag, $this->getFlagList() ))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $name_change = ($displayName !== $user->getDisplayName() && $user->getDisplayName() !== null) || ($displayName !== $user->getUsername() && $user->getDisplayName() === null);

        if ($name_change && !$this->user_handler->isNameValid($displayName, $too_long))
            return AjaxResponse::error(!$too_long ? self::ErrorUserEditUserName : self::ErrorUserEditUserNameTooLong);

        if ($name_change && $user->getLastNameChange() !== null && $user->getLastNameChange()->diff(new DateTime())->days < (30 * 4)) { // 6 months
            return  AjaxResponse::error(self::ErrorUserEditTooSoon);
        }
        if ($name_change && $user->getEternalID() !== null && !$user->getNoAutomaticNameManagement())
            return AjaxResponse::error(self::ErrorUserUseEternalTwin);

        if ($this->user_handler->isRestricted($user, AccountRestriction::RestrictionProfileDisplayName) && $name_change)
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if ($title < 0)
            $user->setActiveTitle(null);
        else {
            $award = $this->entity_manager->getRepository(Award::class)->find( $title );
            if ($award === null || $award->getUser() !== $user || ($award->getCustomTitle() === null && ($award->getPrototype() === null || $award->getPrototype()->getTitle() === null) ))
                return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            if ($this->user_handler->isRestricted($user, AccountRestriction::RestrictionProfileTitle) && $user->getActiveTitle() !== $award)
                return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

            $user->setActiveTitle($award);
        }

        if ($icon < 0)
            $user->setActiveIcon(null);
        else {
            $award = $this->entity_manager->getRepository(Award::class)->find( $icon );
            if ($award === null || $award->getUser() !== $user || ($award->getPrototype() === null && $award->getCustomIcon() === null) || ($award->getPrototype() !== null && $award->getPrototype()->getIcon() === null))
                return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            if ($this->user_handler->isRestricted($user, AccountRestriction::RestrictionProfileTitle) && $user->getActiveIcon() !== $award)
                return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

            $user->setActiveIcon($award);
        }

        $user->setFlag($flag === '' ? null : $flag);

        $desc_obj = $this->entity_manager->getRepository(UserDescription::class)->findOneBy(['user' => $user]);
        if (!empty($desc) && $html->htmlPrepare($user, 0, ['extended','emote'], $desc, null, $insight) && $insight->text_length > 0) {
            if (!$this->user_handler->isRestricted($user, AccountRestriction::RestrictionProfileDescription)) {
                if (!$desc_obj) $desc_obj = (new UserDescription())->setUser($user);
                $desc_obj->setText($desc);
                $this->entity_manager->persist($desc_obj);
            }
        } elseif ($desc_obj) $this->entity_manager->remove($desc_obj);

        if(!empty($displayName) && $displayName !== $user->getName() && ($user->getEternalID() === null || $user->getNoAutomaticNameManagement())) {
            $history = $user->getNameHistory() ?? [];
            if(!in_array($user->getName(), $history))
                $history[] = $user->getName();
            $user->setNameHistory(array_filter(array_unique($history)));
            if ($displayName === $user->getUsername())
                $user->setDisplayName(null);
            else {
                $user->setDisplayName($displayName);
            }

            $user->setLastNameChange(new DateTime());
        }

        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param HTMLService $html
     * @return Response
     */
    #[Route(path: 'api/soul/settings/team', name: 'api_soul_team')]
    public function soul_set_team(JSONRequestParser $parser, HTMLService $html): Response {
        $user = $this->getUser();

        $team  = $parser->get('team', '');

        if (!$this->user_handler->isRestricted($user, AccountRestriction::RestrictionGameplayLang ))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($user->getTeam() != null && $this->user_handler->isRestricted($user, AccountRestriction::RestrictionGameplayLang ))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($team !== '' && ($team === 'ach' || !in_array( $team, $this->allLangsCodes )))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        // if ($team !== '' && $team !== $user->getTeam()) {
        //     $season = $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]);
        //     $cap = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_FOREIGN_CAP, 3);
        //     if ($cap >= 0 && $cap <= $user->getTeamTicketsFor( $season, '' )->count())
        //         return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        // }

        if ($team !== '') $user->setTeam($team);

        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/soul/rps', name: 'soul_rps')]
    public function soul_rps(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $rps = $this->entity_manager->getRepository(FoundRolePlayText::class)->findByUser($user);
        foreach ($rps as $rp) {
            // We mark as new RPs found in the last 5 minutes
            /** @var FoundRolePlayText $rp */
            $elapsed = $rp->getDateFound()->diff(new \DateTime());
            if ($elapsed->y == 0 && $elapsed->m == 0 && $elapsed->d == 0 && $elapsed->h == 0 && $elapsed->i <= 5)
                $rp->setNew(true);
        }
        return $this->render( 'ajax/soul/rps.html.twig', $this->addDefaultTwigArgs("soul_rps", array(
            'rps' => $rps
        )));
    }

    /**
     * @param int $id
     * @param int $page
     * @return Response
     */
    #[Route(path: 'jx/soul/rps/read/{id}-{page}', name: 'soul_rp', requirements: ['id' => '\d+', 'page' => '\d+'])]
    public function soul_view_rp(int $id, int $page): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));
        /** @var FoundRolePlayText $rp */
        $rp = $this->entity_manager->getRepository(FoundRolePlayText::class)->find($id);
        if($rp === null || !$user->getFoundTexts()->contains($rp)){
            return $this->redirect($this->generateUrl('soul_rps'));
        }

        if($page > count($rp->getText()->getPages()))
            return $this->redirect($this->generateUrl('soul_rps'));

        $rp->setNew(false);
        $this->entity_manager->persist($rp);

        $pageContent = $this->entity_manager->getRepository(RolePlayTextPage::class)->findOneByRpAndPageNumber($rp->getText(), $page);

        preg_match('/{asset}([a-zA-Z0-9.\/]+){endasset}/', $pageContent->getContent(), $matches);

        if(count($matches) > 0) {
            $pageContent->setContent(preg_replace("={$matches[0]}=", "<img src='" . $this->asset->getUrl($matches[1]) . "' alt='' />", $pageContent->getContent()));
        }
        
        $this->entity_manager->flush();

        return $this->render( 'ajax/soul/view_rp.html.twig', $this->addDefaultTwigArgs("soul_rps", array(
            'page' => $pageContent,
            'rp' => $rp,
            'current' => $page
        )));
    }


    /**
     * @param string $sid
     * @param int $idtown
     * @param string $return_path
     * @return Response
     */
    #[Route(path: 'jx/soul/{sid}/town/{idtown}/{return_path}', name: 'soul_view_town')]
    public function soul_view_town(
        #[MapEntity(id: 'idtown')]
        TownRankingProxy $town,
        string $sid = 'me', string $return_path = "soul_me"): Response
    {
        $user = $this->getUser();

        try {
            $this->generateUrl($return_path);
        } catch (RouteNotFoundException $e) {
            $return_path = "soul_me";
        }

        if ($sid !== 'me' && !is_numeric($sid))
            return $this->redirect($this->generateUrl('soul_me'));

        $id = $sid === 'me' ? -1 : (int)$sid;
        if ($id === $user->getId())
            return $this->redirect($this->generateUrl( 'soul_view_town', ['idtown' => $town->getId()] ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $target_user = $this->entity_manager->getRepository(User::class)->find($id);
        if($target_user === null && $id !== -1)  return $this->redirect($this->generateUrl('soul_me'));

        if ($target_user === null) $target_user = $user;

        $pictoname = $town->getType()->getName() == 'panda' ? 'r_suhard_#00' : 'r_surlst_#00';
        $proto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoname]);

        $picto = $this->entity_manager->getRepository(Picto::class)->findOneBy(['townEntry' => $town, 'prototype' => $proto]);

        return $this->render(  $user === $target_user ? 'ajax/soul/view_town.html.twig' : 'ajax/soul/view_town_foreign.html.twig', $this->addDefaultTwigArgs("soul_visit", array(
            'user' => $target_user,
            'town' => $town,
            'last_user_standing' => $picto !== null ? $picto->getUser() : null,
            'return_path' => $return_path,
            'self_path' => $this->generateUrl('soul_view_town', ['sid' => $sid, 'idtown' => $town->getId(), 'return_path' => $return_path])
        )));
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/soul/town/add_comment', name: 'soul_add_comment')]
    public function soul_add_comment(JSONRequestParser $parser): Response
    {
        $user = $this->getUser();

        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionComments )) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $id = $parser->get("id", -1);
        /** @var CitizenRankingProxy $citizenProxy */
        $citizenProxy = $this->entity_manager->getRepository(CitizenRankingProxy::class)->find($id);
        if ($citizenProxy === null || $citizenProxy->getUser() !== $user || $citizenProxy->getCommentLocked() )
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if ($citizenProxy->getCitizen() !== null && $citizenProxy->getCitizen()->getAlive())
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $comment = $parser->get("comment");
        $citizenProxy->setComment($comment);
        $citizenProxy->getCitizen()?->setComment($comment ?? '');

        $this->entity_manager->persist($citizenProxy);
        $this->entity_manager->flush();

        $this->addFlash('notice', $this->translator->trans('Deine Nachricht wurde gespeichert.', [], 'game'));

        return AjaxResponse::success();
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route(path: 'api/soul/settings/generateid', name: 'api_soul_settings_generateid')]
    public function soul_settings_generate_id(EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $user->setExternalId(md5($user->getEmail() . mt_rand()));
        $entityManager->persist( $user );
        $entityManager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route(path: 'api/soul/settings/deleteid', name: 'api_soul_settings_deleteid')]
    public function soul_settings_deleteid(EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $user->setExternalId('');
        $entityManager->persist( $user );
        $entityManager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param SessionInterface $session
     * @return Response
     */
    #[Route(path: 'api/soul/settings/common', name: 'api_soul_common')]
    public function soul_settings_common(JSONRequestParser $parser, SessionInterface $session): Response {
        $user = $this->getUser();

        $user->setPreferSmallAvatars( (bool)$parser->get('sma', false) );
        $user->setDisableFx( (bool)$parser->get('disablefx', false) );
        $user->setUseICU( (bool)$parser->get('useicu', false) );
        $user->setNoAutoFollowThreads( !$parser->get('autofollow', true) );
        $user->setClassicBankSort( (bool)$parser->get('clasort', false) );
        $user->setSetting( UserSetting::LimitTownListSize, (bool)$parser->get('town10', true) );
        $user->setSetting( UserSetting::NotifyMeWhenMentioned, (int)$parser->get('notify', 0) );
        $user->setSetting( UserSetting::NotifyMeOnFriendRequest, (bool)$parser->get('notifyFriend', true) );
        $user->setSetting( UserSetting::ReorderActionButtonsBeyond, (bool)$parser->get('beyondAltLayout', false) );
        $user->setSetting( UserSetting::ReorderTownLocationButtons, (bool)$parser->get('townAltLayout', true) );
        $user->setSetting( UserSetting::PrivateForumsOnTop, (bool)$parser->get('privateForumsOnTop', true) );
        $user->setSetting( UserSetting::LargerPMIcon, (bool)$parser->get('largerPMIcon', false) );
        $user->setAdminLang($parser->get("adminLang", null));
        $session->set('_admin_lang',$user->getAdminLang() ?? $user->getLanguage());
        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        $this->addFlash('notice', $this->translator->trans('Du hast die Seiteneinstellungen geändert.', [], 'global'));

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param Request $request
     * @param UserHandler $userHandler
     * @param SessionInterface $session
     * @return Response
     */
    #[Route(path: 'api/soul/settings/setlanguage', name: 'api_soul_set_language')]
    public function soul_settings_set_language(JSONRequestParser $parser, Request $request, UserHandler $userHandler, SessionInterface $session, InvalidateTagsInAllPoolsAction $invalidate): Response {
        $user = $this->getUser();

        if (!$parser->has('lang', true))
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        
        $lang = $parser->get('lang', 'de');
        if (!in_array($lang, $this->allLangsCodes))
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);

        // Check if the user has seen all news in the previous language
        $previous_lang = $user->getLanguage() ?? $request->getLocale() ?? 'de';
        $seen_news = $userHandler->hasSeenLatestChangelog($user, $previous_lang);

        $user->setLanguage( $lang );
        $session->set('_user_lang',$lang);

        $invalidate("user_#{$user->getId()}");

        if ($seen_news) $userHandler->setSeenLatestChangelog($user, $lang);
        else $user->setLatestChangelog(null);

        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param AdminHandler $admh
     * @return Response
     */
    #[Route(path: 'api/soul/settings/defaultrole', name: 'api_soul_defaultrole')]
    public function soul_settings_default_role(JSONRequestParser $parser, AdminHandler $admh): Response {
        $user = $this->getUser();

        $asDev = $parser->get('dev', false);
        if ($admh->setDefaultRoleDev($user->getId(), $asDev))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @param JSONRequestParser $parser
     * @param AdminHandler $admh
     * @return Response
     */
    #[Route(path: 'api/soul/settings/mod_tools_window', name: 'api_soul_mod_tools_window')]
    public function soul_mod_tools_window(JSONRequestParser $parser, AdminHandler $admh): Response {
        $user = $this->getUser();

        $new_window = $parser->get('same_window', false);
        $user->setOpenModToolsSameWindow($new_window);

        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param UserPasswordHasherInterface $passwordEncoder
     * @param JSONRequestParser $parser
     * @param TokenStorageInterface $token
     * @return Response
     */
    #[Route(path: 'api/soul/settings/change_account_details', name: 'api_soul_change_account_details')]
    public function soul_settings_change_account_details(UserPasswordHasherInterface $passwordEncoder, JSONRequestParser $parser, TokenStorageInterface $token): Response
    {
        $user = $this->getUser();
        $new_pw = $parser->trimmed('pw_new', '');
        $new_email = $parser->trimmed('email_new', '');
        $confirm_token = $parser->trimmed('email_token', '');
        $message = [];

        if ($this->isGranted('ROLE_DUMMY') && !$this->isGranted( 'ROLE_CROW' ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if ($this->isGranted('ROLE_ETERNAL') && $user->getPassword() === null && !empty($new_pw))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $change = false;

        if(!empty($new_pw)) {
            if (mb_strlen($new_pw) < 6) return AjaxResponse::error(self::ErrorUserEditPasswordIncorrect);

            if (!$passwordEncoder->isPasswordValid( $user, $parser->trimmed('pw') ))
                return AjaxResponse::error(self::ErrorUserEditPasswordIncorrect );

            $user
                ->setPassword( $passwordEncoder->hashPassword($user, $parser->trimmed('pw_new')) )
                ->setCheckInt($user->getCheckInt() + 1);

            if ($rm_token = $this->entity_manager->getRepository(RememberMeTokens::class)->findOneBy(['user' => $user]))
                $this->entity_manager->remove($rm_token);
            $change = true;

        }

        if (!empty($new_email) && $this->isGranted('ROLE_NATURAL')) {
            if ($this->entity_manager->getRepository(User::class)->findOneByMail( $new_email ))
                return AjaxResponse::error(UserFactory::ErrorMailExists);

            if ($this->entity_manager->getRepository(AntiSpamDomains::class)->findActive( DomainBlacklistType::EmailAddress, $new_email ))
                return AjaxResponse::error(UserFactory::ErrorMailExists);

            $user->setPendingEmail($new_email);
            if (!$this->user_factory->announceValidationToken($this->user_factory->ensureValidation($user, UserPendingValidation::ChangeEmailValidation, true)))
                return AjaxResponse::error(ErrorHelper::ErrorSendingEmail);
            $change = true;
        } elseif (!empty($confirm_token) && $this->isGranted('ROLE_NATURAL')) {
            if (($pending = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByTokenAndUserandType(
                    $confirm_token, $user, UserPendingValidation::ChangeEmailValidation)) === null) {
                return AjaxResponse::error(self::ErrorUserConfirmToken);
            }

            if ($pending->getUser() === null || ($user !== null && !$user->isEqualTo( $pending->getUser() ))) {
                return AjaxResponse::error(self::ErrorUserConfirmToken);
            }

            if ($pending->getPkey() !== $confirm_token) {
                return AjaxResponse::error(self::ErrorUserConfirmToken);
            }

            $user->setEmail($user->getPendingEmail());
            $user->setPendingEmail(null);
            $user->setPendingValidation(null);
            $this->entity_manager->remove( $pending );
            $change = true;
            $message[] = $this->translator->trans('Deine E-Mail-Adresse wurde erfolgreich geändert.', [], 'login');
        }

        if ($change){
            $this->entity_manager->persist($user);
            $this->entity_manager->flush();

            if (!empty($new_pw)) {
                $message[] = $this->translator->trans('Dein Passwort wurde erfolgreich geändert. Bitte logge dich mit deinem neuen Passwort ein.', [], 'login');
                $token->setToken(null);
            }

            if (!empty($new_email)) {
                $message[] = $this->translator->trans('Deine E-Mail Adresse wurde geändert. Bitte validiere die neue Adresse, damit du sie verwenden kannst.', [], 'login');
            }

            $this->addFlash('notice', implode('<hr />', $message));
        }

        return AjaxResponse::success();
    }

    /**
     * @return Response
     */
    #[Route(path: 'api/soul/settings/resend_token_email', name: 'api_soul_resend_token_email')]
    #[IsGranted('ROLE_NATURAL')]
    public function soul_settings_resend_token_email(): Response
    {
        $user = $this->getUser();

        if ($this->user_factory->announceValidationToken($this->user_factory->ensureValidation($user, UserPendingValidation::ChangeEmailValidation, true))) {
            return AjaxResponse::success();
        }

        return AjaxResponse::error();
    }

    /**
     * @return Response
     */
    #[Route(path: 'api/soul/settings/cancel_token_email', name: 'api_soul_cancel_email_token')]
    #[IsGranted('ROLE_NATURAL')]
    public function soul_settings_cancel_token_email(): Response
    {
        $user = $this->getUser();

        $token = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByUserAndType($user,UserPendingValidation::ChangeEmailValidation);

        if ($token) {
            $this->entity_manager->remove( $token );
        }
        $user->setPendingEmail(null);
        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param TokenStorageInterface $token
     * @return Response
     */
    #[Route(path: 'api/soul/settings/unremember_me', name: 'api_soul_unremember_me')]
    public function soul_settings_unremember(TokenStorageInterface $token): Response
    {
        $user = $this->getUser();
        $user->setCheckInt($user->getCheckInt() + 1);

        if ($rm_token = $this->entity_manager->getRepository(RememberMeTokens::class)->findOneBy(['user' => $user]))
            $this->entity_manager->remove($rm_token);

        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        $this->addFlash( 'notice', $this->translator->trans('Du wurdest erfolgreich von allen Geräten abgemeldet.', [], 'login') );
        $token->setToken(null);
        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @param HTMLService $html
     * @return Response
     */
    #[Route(path: 'jx/soul/{id}', name: 'soul_visit', requirements: ['id' => '\d+'])]
    public function soul_visit(int $id, HTMLService $html): Response
    {
        $current_user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($current_user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var User $user */
    	$user = $this->entity_manager->getRepository(User::class)->find($id);
    	if($user === null || $user === $current_user) 
            return $this->redirect($this->generateUrl('soul_me'));

        $returnUrl = null; // TODO: get the referer, it can be empty!
        if(empty($returnUrl))
            $returnUrl = $this->generateUrl('soul_me');

        $cac = $current_user->getActiveCitizen();
        $uac = $user->getActiveCitizen();
        $citizen_id = ($cac && $uac && $cac->getAlive() && !$cac->getZone() && $cac->getTown() === $uac->getTown()) ? $uac->getId() : null;

        $desc = $this->entity_manager->getRepository(UserDescription::class)->findOneBy(['user' => $user]);

        return $this->render( 'ajax/soul/visit.html.twig', $this->addDefaultTwigArgs("soul_visit", [
        	'user' => $user,
            'seasons' => $this->entity_manager->getRepository(Season::class)->findPastAndPresent(),
            'returnUrl' => $returnUrl,
            'citizen_id' => $citizen_id,
            'user_desc' => $desc ? $html->prepareEmotes($desc->getText(), $this->getUser()) : null
        ]));
    }

    /**
     * @param int $id
     * @param int $action
     * @return Response
     */
    #[Route(path: 'api/soul/{id}/block/{action}', name: 'soul_block_control', requirements: ['id' => '\d+', 'action' => '\d+'])]
    #[GateKeeperProfile(allow_during_attack: true)]
    public function soul_control_block(int $id, int $action): Response
    {
        if ($action !== 0 && $action !== 1) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $target_user = $this->entity_manager->getRepository(User::class)->find($id);
        if ($target_user === null || $target_user === $this->getUser())
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($this->user_handler->hasRole($target_user, 'ROLE_CROW'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $is_blocked = $this->user_handler->checkRelation($this->getUser(), $target_user, SocialRelation::SocialRelationTypeBlock);
        if (($is_blocked && $action === 1) || (!$is_blocked && $action === 0)) return AjaxResponse::success();

        if ($action === 1) {
            $this->entity_manager->persist((new SocialRelation())->setType(SocialRelation::SocialRelationTypeBlock)->setOwner($this->getUser())->setRelated($target_user));
            $this->entity_manager->persist( $this->getUser()->removeFriend( $target_user ) );
            $this->entity_manager->persist( $target_user->removeFriend( $this->getUser() ) );
        } else $this->entity_manager->remove( $this->entity_manager->getRepository( SocialRelation::class )->findOneBy( ['owner' => $this->getUser(), 'related' => $target_user, 'type' => SocialRelation::SocialRelationTypeBlock ] ) );

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) { return AjaxResponse::error( ErrorHelper::ErrorDatabaseException ); }

        if ($action === 1) $this->addFlash('notice', $this->translator->trans('Du hast diesen Spieler auf deine Blacklist gesetzt.', [], 'global'));
        else $this->addFlash('notice', $this->translator->trans('Du hast diesen Spieler von deiner Blacklist genommen.', [], 'global'));

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param SessionInterface $session
     * @param InvalidateTagsInAllPoolsAction $clearCache
     * @return Response
     */
    #[Route(path: 'api/soul/unsubscribe', name: 'api_unsubscribe')]
    public function unsubscribe_api(JSONRequestParser $parser, SessionInterface $session, InvalidateTagsInAllPoolsAction $clearCache): Response {
        $this->user_handler->confirmNextDeath( $this->getUser(), $parser->get('lastwords', '') );

        if ($session->has('_town_lang')) {
            $session->remove('_town_lang');
            $clearCache('distinction_ranking');
            return AjaxResponse::success()->setAjaxControl(AjaxResponse::AJAX_CONTROL_RESET);
        } else return AjaxResponse::success();
    }

    /**
     * @param bool $latest
     * @param CitizenRankingProxy|null $nextDeath
     * @return Response
     */
    #[Route(path: 'jx/soul/welcomeToNowhere', name: 'soul_death', defaults: ['latest' => true])]
    #[Route(path: 'jx/soul/obituary/{id}', name: 'soul_obituary', defaults: ['latest' => false])]
    public function soul_death_page(bool $latest, ?CitizenRankingProxy $nextDeath = null): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($latest) $nextDeath = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user);

        if ($nextDeath === null || $nextDeath?->getTown()?->getImported() || ($nextDeath->getCitizen() && $nextDeath->getCitizen()->getAlive()) || $nextDeath->getUser() !== $user)
            return $this->redirectToRoute('initial_landing');

        $pictosDuringTown = $this->entity_manager->getRepository(Picto::class)->findPictoByUserAndTown($user, $nextDeath->getTown());
        $pictosWonDuringTown = [];
        $pictosNotWonDuringTown = [];

        foreach ($pictosDuringTown as $picto) {
            if ($picto->getPrototype()->getName() == "r_ptame_#00") continue;
            if ($picto->getPersisted() > 0)
                $pictosWonDuringTown[] = $picto;
            else
                $pictosNotWonDuringTown[] = $picto;
        }

        $canSeeGazette = $latest && $nextDeath->getTown() !== null;
        if($canSeeGazette){
            $citizensAlive = false;
            foreach ($nextDeath->getTown()->getCitizens() as $citizen) {
                if($citizen->getCod() === null){
                    $citizensAlive = true;
                    break;
                }
            }
            if($citizensAlive || $nextDeath->getCod()->getRef() === CauseOfDeath::Radiations) {
                $canSeeGazette = true;
            } else {
                $canSeeGazette = false;
            }
        }


        return $this->render( 'ajax/soul/death.html.twig', $this->addDefaultTwigArgs(null, [
            'dead_citizen' => $nextDeath,
            'citizen' => $nextDeath,
            'sp' => $nextDeath->getPoints(),
            'day' => $nextDeath->getDay(),
            'pictos' => $pictosWonDuringTown,
            'gazette' => $canSeeGazette,
            'denied_pictos' => $pictosNotWonDuringTown,
            'obituary' => !$latest
        ]) );
    }

    /**
     * @param int $user_id
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/soul/{user_id}/towns_all', name: 'soul_get_towns')]
    public function soul_town_list(int $user_id, JSONRequestParser $parser): Response {
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($user_id);
        if ($user === null) return new Response("");

        $season_id = $parser->get('season', '');
        if(empty($season_id)) return new Response("");

        $season = $this->entity_manager->getRepository(Season::class)->findOneBy(['id' => $season_id]);

        $limit = (bool)$parser->get('limit10', $user->getSetting( UserSetting::LimitTownListSize ));

        $commonTowns = [];
        $citizens = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findPastByUserAndSeason($user, $season, $limit, true);

        if ($this->getUser() !== $user) {
            /** @var CitizenRankingProxy $citizen */
            foreach ($citizens as $citizen) {
                foreach ($citizen->getTown()->getCitizens() as $c) {
                    if ($c->getUser() === $this->getUser()) {
                        $commonTowns[] = $citizen->getId();
                        break;
                    }
                }
            }
        }

        return $this->render( 'ajax/soul/town_list.html.twig', [
            'towns' => $citizens,
            'commonTowns' => $commonTowns,
            'editable' => $user->getId() === $this->getUser()->getId()
        ]);
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/help', name: 'help_me')]
    public function help_me(): Response
    {
        $support_groups = $this->entity_manager->getRepository(OfficialGroup::class)->findBy(['lang' => $this->getUserLanguage(), 'semantic' => OfficialGroup::SEMANTIC_SUPPORT]);
        return $this->render( 'ajax/help/shell.html.twig', [
            'support' => count($support_groups) === 1 ? $support_groups[0] : null
        ]);
    }

    /**
     * @param int $id
     * @param JSONRequestParser $parser
     * @param RandomGenerator $rand
     * @return Response
     */
    #[Route(path: 'api/soul/app/{id<\d+>}', name: 'soul_own_app_update')]
    public function api_update_own_app(int $id, JSONRequestParser $parser, RandomGenerator $rand) {
        /** @var ExternalApp $app */
        $app = $this->entity_manager->getRepository(ExternalApp::class)->find($id);

        if ($app === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if ($app->getOwner() === null || $app->getOwner() !== $this->getUser()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (!$parser->has_all( ['contact','url'], true )) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $violations = Validation::createValidator()->validate( array_merge($parser->all( true ), [
            'url' => preg_replace('/\{.*?\}/', 'SYMBOL', $parser->get('url')),
            'devurl' => preg_replace('/\{.*?\}/', 'SYMBOL', $parser->get('devurl', '')),
        ]), new Constraints\Collection([
            'url' => [ new Constraints\Url( ['relativeProtocol' => false, 'protocols' => ['http', 'https'], 'message' => 'a' ] ) ],
            'devurl' => [
                new Constraints\AtLeastOneOf([
                    new Constraints\Url( ['relativeProtocol' => false, 'protocols' => ['http', 'https'], 'message' => 'a' ] ),
                    new Constraints\Blank( ['message' => 'a' ] )
                ])
            ],
            'contact' => [
                new Constraints\AtLeastOneOf([
                    new Constraints\Url( ['relativeProtocol' => false, 'protocols' => ['http', 'https'], 'message' => 'a' ] ),
                    new Constraints\Email( ['message' => 'v'])
                ])
            ],
            'sk' => [  ]
        ]) );

        if ($violations->count() > 0) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $app->setUrl( $parser->trimmed('url') )->setContact( $parser->trimmed('contact') )->setDevurl( $parser->trimmed('devurl') ?: null );
        if ( !$app->getLinkOnly() && $parser->get('sk', null) ) {
            $s = '';
            for ($i = 0; $i < 32; $i++) $s .= $rand->pick(['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f']);
            $app->setSecret( $s );
        }

        $this->entity_manager->persist($app);
        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param RandomGenerator $rand
     * @return Response
     */
    #[Route(path: 'jx/soul/game_history', name: 'soul_game_history')]
    public function soul_game_history(JSONRequestParser $parser, RandomGenerator $rand) {
        $lifes = $this->getUser()->getPastLifes()->getValues();
        usort($lifes, fn(CitizenRankingProxy $b, CitizenRankingProxy $a) =>
            ($a->getTown()->getSeason() ? $a->getTown()->getSeason()->getNumber() : 0) <=> ($b->getTown()->getSeason() ? $b->getTown()->getSeason()->getNumber() : 0) ?:
            ($a->getTown()->getSeason() ? $a->getTown()->getSeason()->getSubNumber() : 15) <=> ($b->getTown()->getSeason() ? $b->getTown()->getSeason()->getSubNumber() : 15) ?:
            ($a->getImportID() ?? 0) <=> ($b->getImportID() ?? 0) ?:
            $a->getID() <=> $b->getID()
        );
        return $this->render( 'ajax/soul/game_history.html.twig', $this->addDefaultTwigArgs('soul_me', [
            'towns' => $lifes
        ]));
    }

    /**
     * @param int $id
     * @param HTMLService $html
     * @return Response
     */
    #[Route(path: 'api/soul/tooltip', name: 'soul_tooltip')]
    #[GateKeeperProfile(allow_during_attack: true)]
    public function api_soul_tooltip(JSONRequestParser $parser, HTMLService $html, TimeKeeperService $timeKeeper) {
        $id = $parser->get("id");
        $user = $id ? $this->entity_manager->getRepository(User::class)->find($id) : null;

        if (!$user) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $desc = $this->entity_manager->getRepository(UserDescription::class)->findOneBy(['user' => $user]);
        $isFriend = $this->getUser()->getFriends()->contains($user);

        $is_dummy = $this->user_handler->hasRole($user,'ROLE_DUMMY');

        if ($user->getEmail() !== null && !str_contains($user->getEmail(), '@'))
            $is_dummy = true;

        $is_deleted = strstr($user->getEmail(), '$ deleted') !== false;

        return $this->render("ajax/soul/user_tooltip.html.twig", [
            'user' => $user,
            'userDesc' => $desc ? $html->prepareEmotes($desc->getText(), $this->getUser()) : null,
            'isFriend' => $isFriend,
            'dummy' => $is_dummy,
            'is_deleted' => $is_deleted,
            'during_attack' => $timeKeeper->isDuringAttack(),
            'crow'   => $this->user_handler->hasRole($user,'ROLE_CROW'),
            'admin'  => $this->user_handler->hasRole($user,'ROLE_SUB_ADMIN'),
            'super'  => $this->user_handler->hasRole($user,'ROLE_SUPER'),
            'oracle' => $this->user_handler->hasRole($user,'ROLE_ORACLE'),
            'anim'   => $this->user_handler->hasRole($user,'ROLE_ANIMAC'),
            'team'   => $this->user_handler->hasRole($user,'ROLE_TEAM'),
            'dev'    => $this->user_handler->hasRole($user, 'ROLE_DEV')
        ]);
    }

    /**
     * @param int $action
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/soul/friend/{action}', name: 'soul_friend_control')]
    #[GateKeeperProfile(allow_during_attack: true)]
    public function api_friend_control(int $action, JSONRequestParser $parser, EventProxyService $proxy) {
        if ($action !== 0 && $action !== 1) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $id = $parser->get("id");
        $user = $this->entity_manager->getRepository(User::class)->find($id ?? -1);

        if (!$user || ($action === 1 && ($this->user_handler->hasRole($user, 'ROLE_DUMMY') || !str_contains($user->getEmail(), '@'))))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($action && $this->user_handler->checkRelation($this->getUser(), $user,SocialRelation::SocialRelationTypeBlock, true))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        if ($action && $this->getUser()->getFriends()->contains( $user )) return AjaxResponse::success();
        if (!$action && !$this->getUser()->getFriends()->contains( $user )) return AjaxResponse::success();

        if ($action) {
            $this->getUser()->addFriend($user);
            $ignoreRelation = $this->entity_manager->getRepository( SocialRelation::class )->findOneBy( ['owner' => $this->getUser(), 'related' => $user, 'type' => SocialRelation::SocialRelationTypeNotInterested ] );
            if ($ignoreRelation) $this->entity_manager->remove( $ignoreRelation );
        } else
            $this->getUser()->removeFriend($user);

        $this->entity_manager->persist($this->getUser());
        $this->entity_manager->flush();

        $proxy->friendListUpdatedEvent( $this->getUser(), $user, !!$action );
        try { $this->entity_manager->flush(); } catch (\Throwable) {}

        if($action){
            $this->addFlash("notice", $this->translator->trans("Du hast {username} zu deinen Kontakten hinzugefügt!", ['{username}' => $user], "soul"));
        } else {
            $this->addFlash("notice", $this->translator->trans("Du hast {username} aus deinen Kontakten gelöscht!", ['{username}' => $user], "soul"));
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $action
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/soul/ignore/{action}', name: 'soul_ignore_control')]
    #[GateKeeperProfile(allow_during_attack: true)]
    public function api_ignore_control(int $action, JSONRequestParser $parser) {
        if ($action !== 0 && $action !== 1) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $id = $parser->get("id");
        $user = $this->entity_manager->getRepository(User::class)->find($id);

        if (!$user || $this->user_handler->hasRole($user, 'ROLE_DUMMY') || !str_contains($user->getEmail(), '@'))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($this->getUser()->getFriends()->contains($user) && $action)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($action)
            $this->entity_manager->persist((new SocialRelation())->setType(SocialRelation::SocialRelationTypeNotInterested)->setOwner($this->getUser())->setRelated($user));
        else $this->entity_manager->remove( $this->entity_manager->getRepository( SocialRelation::class )->findOneBy( ['owner' => $this->getUser(), 'related' => $user, 'type' => SocialRelation::SocialRelationTypeNotInterested ] ) );
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

}
