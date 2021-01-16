<?php

namespace App\Controller\Messages;

use App\Entity\ActionCounter;
use App\Entity\AdminReport;
use App\Entity\Citizen;
use App\Entity\Complaint;
use App\Entity\ComplaintReason;
use App\Entity\ForumUsagePermissions;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\PrivateMessage;
use App\Entity\PrivateMessageThread;
use App\Entity\Town;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\UserHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @IsGranted("ROLE_USER")
 * @method User getUser
 */
class MessageTownMessageController extends MessageController
{

    /**
     * @Route("api/town/house/sendpm", name="town_house_send_pm_controller")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $t
     * @return Response
     */
    public function send_pm_api(EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $t, UserHandler $userHandler): Response {
        $type      = $parser->get('type', "");
        $recipient = $parser->get('recipient', '');
        $title     = $parser->get('title', '');
        $content   = $parser->get('content', '');
        $items     = $parser->get('items', '');
        $tid       = $parser->get('tid', -1);

        $allowed_types = ['pm', 'global'];

        if(!in_array($type, $allowed_types)) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if($type === 'pm' && (empty($recipient) && $tid === -1))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if(($tid === -1 && empty($title)) || empty($content)) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $sender = $this->getUser()->getActiveCitizen();

        if ($type === "global" && !$sender->getProfession()->getHeroic() && !$userHandler->hasSkill($sender->getUser(), 'writer'))
            return AjaxResponse::error(ErrorHelper::ErrorMustBeHero);

        if ($type === "global" && $sender->getBanished())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $linked_items = array();

        if(is_array($items)){
            foreach ($items as $item_id) {
                $valid = false;
                $item = $em->getRepository(Item::class)->find($item_id);

                if (in_array($item->getPrototype()->getName(), ['bagxl_#00', 'bag_#00', 'cart_#00', 'pocket_belt_#00'])) {
                    // We cannot send bag expansion
                    continue;
                }

                if($item->getInventory()->getHome() !== null && $item->getInventory()->getHome()->getCitizen() === $sender){
                    // This is an item from a chest
                    $valid = true;
                } else if($item->getInventory()->getCitizen() === $sender){
                    // This is an item from the rucksack
                    $valid = true;
                }

                if($sender->getTown()->getChaos() && count($linked_items) > 3) {
                    return AjaxResponse::error(self::ErrorPMItemLimitHit);
                }

                if($valid)
                    $linked_items[] = $item;
            }
        }
        $global_thread = null;
        if ($tid !== -1) {
            $global_thread = $em->getRepository(PrivateMessageThread::class)->find($tid);
            if ($global_thread === null || $global_thread->getSender() === null)
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

            if ($global_thread->getSender() !== $sender && $global_thread->getRecipient() !== $sender)
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
        }
        $global_recipient = $global_thread ? (
        $global_thread->getSender() === $sender ? $global_thread->getRecipient() : $global_thread->getSender()
        ) : null;

        $recipients = [];
        if ($type === 'pm') {
            $recipient = $global_recipient ?? $em->getRepository(Citizen::class)->find($recipient);

            if (count($linked_items) > 0) {
                if ($recipient->getBanished() != $sender->getBanished())
                    return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
                if ($sender->getTown()->getChaos()){
                    if($recipient->getZone())
                        return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
                    else {
                        $counter = $sender->getSpecificActionCounter(ActionCounter::ActionTypeSendPMItem);
                        if($counter->getCount() > 3)
                            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
                        else if ($counter->getCount() + count($linked_items) > 3)
                            return AjaxResponse::error(self::ErrorPMItemLimitHit);
                        else {
                            $counter->setCount(min($counter->getCount() + count($linked_items), 3));
                            $em->persist($counter);
                        }
                    }
                }

                // Check inventory size
                $max_size = $this->inventory_handler->getSize($recipient->getHome()->getChest());
                if ($max_size > 0 && count($recipient->getHome()->getChest()->getItems()) + count($linked_items) > $max_size)
                    return AjaxResponse::error(InventoryHandler::ErrorInventoryFull);
            }

            if ($recipient) $recipients[] = $recipient;

        } else {

            if ($global_thread) return AjaxResponse::errorMessage( ErrorHelper::ErrorActionNotAvailable );

            foreach ($sender->getTown()->getCitizens() as $citizen)
                $recipients[] = $citizen;

            if (count($linked_items) > 0) return AjaxResponse::error(self::ErrorPMItemLimitHit);

        }

        if (empty($recipients)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $success = 0;
        foreach ($recipients as $recipient) {
            if(!$recipient->getAlive()) continue;
            if($recipient == $sender) continue;

            if (!$global_thread) {
                $thread = new PrivateMessageThread();

                $thread->setSender($sender)
                    ->setTitle($title)
                    ->setLocked(false)
                    ->setLastMessage(new DateTime('now'))
                    ->setRecipient($recipient);
            } else
                $thread = $global_thread;

            $post = new PrivateMessage();
            $post->setDate(new DateTime('now'))
                ->setText($content)
                ->setPrivateMessageThread($thread)
                ->setOwner($sender)
                ->setNew(true)
                ->setRecipient($recipient);

            $items_prototype = [];
            foreach ($linked_items as $item) {
                $items_prototype[] = $item->getPrototype()->getId();
                $this->inventory_handler->forceMoveItem($recipient->getHome()->getChest(), $item);
            }

            $post->setItems($items_prototype);

            $tx_len = 0;
            if (!$this->preparePost($this->getUser(),null,$post,$tx_len, $recipient->getTown()))
                return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            $thread
                ->setLastMessage($post->getDate())
                ->addMessage($post);

            $success++;
            $em->persist($thread);
            $em->persist($post);
        }

        $em->flush();

        if ($success === 0) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        } else {
            // Show confirmation
            if(count($linked_items) > 0)
                $message = $t->trans("Deine Nachricht und deine ausgewählten Gegenstände wurden überbracht.", [], 'game');
            else
                $message = $t->trans('Deine Nachricht wurde korrekt übermittelt!', [], 'game');

            $this->addFlash( 'notice',  $message);
            return AjaxResponse::success( true, ['url' => $this->generateUrl('town_house', ['tab' => 'messages', 'subtab' => 'received'])] );
        }


    }

    /**
     * @Route("api/town/house/pm/{tid<\d+>}/view", name="home_view_thread_controller")
     * @param int $tid
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function pm_viewer_api(int $tid, EntityManagerInterface $em): Response {
        $user = $this->getUser();

        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();

        $thecrow = $em->getRepository(User::class)->find(66);

        /** @var PrivateMessageThread $thread */
        $thread = $em->getRepository(PrivateMessageThread::class)->find( $tid );
        if (!$thread) return new Response('');

        $valid = false;
        foreach ($thread->getMessages() as $message)
            if ($message->getRecipient() === $citizen)
                $valid = true;

        if(!$valid) return new Response('');

        $thread->setNew(false);

        $posts = $thread->getMessages();

        foreach ($posts as $message) {
            if($message->getRecipient() === $citizen) {
                $message->setNew(false);
                $em->persist($message);
            }
        }

        $em->persist($thread);
        $em->flush();
        $items = [];
        foreach ($posts as &$post) {
            if($post->getItems() !== null && count($post->getItems()) > 0) {
                $items[$post->getId()] = [];
                foreach ($post->getItems() as $proto_id) {
                    $items[$post->getId()][] = $em->getRepository(ItemPrototype::class)->find($proto_id);
                }
            }

            switch ($post->getTemplate()) {

                case PrivateMessage::TEMPLATE_CROW_COMPLAINT_ON:
                    /** @var Complaint $complaint */
                    $reason = $this->entity_manager->getRepository(ComplaintReason::class)->find( $post->getForeignID() );
                    $thread->setTitle( $this->translator->trans('Anonyme Beschwerde', [], 'game') );
                    $post->setText( $this->prepareEmotes($post->getText()) . $this->translator->trans( 'Es wurde eine neue anonyme Beschwerde gegen dich eingelegt: "%reason%"', ['%reason%' => $reason ? $this->translator->trans( $reason->getText(), [], 'game' ) : '???'], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_COMPLAINT_OFF:
                    /** @var Complaint $complaint */
                    $reason = $this->entity_manager->getRepository(ComplaintReason::class)->find( $post->getForeignID() );
                    $thread->setTitle( $this->translator->trans('Beschwerde zurückgezogen', [], 'game') );
                    $post->setText( $this->prepareEmotes($post->getText()) . $this->translator->trans( 'Es gibt gute Nachrichten! Folgende Beschwerde wurde zurückgezogen: "%reason%"', ['%reason%' => $reason ? $this->translator->trans( $reason->getText(), [], 'game' ) : '???'], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_TERROR:
                    $thread->setTitle( $this->translator->trans('Du bist vor Angst erstarrt!!', [], 'game') );
                    $post->setText( $this->prepareEmotes($post->getText()) . $this->translator->trans( 'Wir haben zwei Neuigkeiten für dich. Eine gute und eine schlechte. Zuerst die gute: Trotz ihrer hartnäckigen Versuche, ist es den %num% Zombie(s) nicht gelungen, dich aufzufressen. Du hast dich wacker geschlagen. Bravo! Die schlechte: Das Erlebnis war so schlimm, dass du in eine Angststarre verfallen bist. So etwas möchtest du nicht wieder erleben...', ['%num%' => $post->getForeignID()], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_AVOID_TERROR:
                    $thread->setTitle( $this->translator->trans('Was für eine schreckliche Nacht!', [], 'game') );
                    $post->setText( $this->prepareEmotes($post->getText()) . $this->translator->trans( 'Heute Nacht ist dir der Arsch so richtig auf Grundeis gegangen! Als du ihr Grunzen und Stöhnen gehört hattest, war dir klar: Sie würden bei dir daheim eindringen. So kam es dann auch: Deine Haustür splitterte unter der Last ihrer Angriffe. Panisch bist du ins Schlafzimmer gerannt, um dich unter deinem Bett zu verstecken. Sie blieben ein paar Minuten, die dir wie eine Ewigkeit vorkamen, und schnüffelten sich durch alle Zimmer. Innerlich zitternd, hast du zu Gott gebetet, dass sie dich verschonen mögen. Dann war plötzlich wieder alles still. Hechelnd und schnaufend bist du aus deinem Versteck hervorgekrochen und heulend auf deinem Bett zusammengesunken.', [], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_THEFT:
                    /** @var ItemPrototype $item */
                    $item = $this->entity_manager->getRepository(ItemPrototype::class)->find( $post->getForeignID() );
                    $thread->setTitle( $this->translator->trans('Haltet den Dieb!', [], 'game') );

                    $img = "<img src='{$this->asset->getUrl('build/images/item/item_' . ($item ? $item->getIcon() : 'none') . '.gif')}' alt='' />";
                    $name = $this->translator->trans( $item ? $item->getLabel() : '', [], 'items' );
                    $post->setText( $this->prepareEmotes($post->getText()) . $this->translator->trans( 'Es scheint so, als ob ein anderer Bürger Gefallen an deinem Inventar gefunden hätte... Dir wurde folgendes gestohlen: %icon% %item%', ['%icon%' => $img, '%item%' => $name], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_CATAPULT:
                    $thread->setTitle( $this->translator->trans('Du bist für das Katapult verantwortlich', [], 'game') );
                    $post->setText( $this->prepareEmotes($post->getText()) . $this->translator->trans( 'Du bist zum offiziellen Katapult-Bediener der Stadt ernannt worden. Diese Ernennung erfolgte durch Auslosung; Herzlichen Glückwunsch! Finde dich so bald wie Möglich beim städtischen Katapult ein.', [], 'game' ) );
                    break;
                default:
                    $post->setText($this->prepareEmotes($post->getText()));
            }

        }

        return $this->render( 'ajax/game/town/posts.html.twig', [
            'thread' => $thread,
            'posts' => $posts,
            'items' => $items,
            'thecrow' => $thecrow,
            'emotes' => $this->getEmotesByUser($user,true),
        ] );
    }

    /**
     * @Route("api/town/house/pm/{tid<\d+>}/archive/{action<\d+>}", name="home_archive_pm_controller")
     * @param int $tid
     * @param int $action
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function pm_archive_api(int $tid, int $action, EntityManagerInterface $em): Response {
        $user = $this->getUser();

        /** @var Citizen $citizen */
        if (!($citizen = $user->getActiveCitizen())) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var PrivateMessageThread $thread */
        $thread = $em->getRepository(PrivateMessageThread::class)->find( $tid );
        if (!$thread || !$thread->getSender() || ($thread->getRecipient()->getId() !== $citizen->getId() && $thread->getSender()->getId() !== $citizen->getId())) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $thread->setArchived($action !== 0);

        $em->persist($thread);
        $em->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/house/pm/report", name="home_report_pm_controller")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $ti
     * @return Response
     */
    public function pm_report_api(JSONRequestParser $parser, EntityManagerInterface $em, TranslatorInterface $ti): Response {
        $user = $this->getUser();

        $id = $parser->get('pmid', null);
        if ($id === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var Citizen $citizen */
        if (!($citizen = $user->getActiveCitizen())) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var PrivateMessage $post */
        $post = $em->getRepository(PrivateMessage::class)->find( $id );
        if ($post === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $thread = $post->getPrivateMessageThread();
        if (!$thread || $post->getOwner() === $citizen || !$thread->getSender() || ($thread->getRecipient()->getId() !== $citizen->getId() && $thread->getSender()->getId() !== $citizen->getId())) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $reports = $post->getAdminReports();
        foreach ($reports as $report)
            if ($report->getSourceUser()->getId() == $user->getId())
                return AjaxResponse::success();

        $newReport = (new AdminReport())
            ->setSourceUser($user)
            ->setTs(new DateTime('now'))
            ->setPm($post);

        $em->persist($newReport);
        $em->flush();

        $message = $ti->trans('Du hast die Nachricht von %username% dem Raben gemeldet. Wer weiß, vielleicht wird %username% heute Nacht stääärben...', ['%username%' => '<span>' . $post->getOwner()->getUser()->getName() . '</span>'], 'game');
        $this->addFlash('notice', $message);

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/town/house/pm/{tid<\d+>}/editor", name="home_answer_post_editor_controller")
     * @param int $tid
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function home_answer_editor_post_api(int $tid, EntityManagerInterface $em): Response {
        $user = $this->getUser();

        $thread = $em->getRepository( PrivateMessageThread::class )->find( $tid );
        if ($thread === null) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => $tid,
            'pid' => null,

            'permission' => $this->getPermissionObject( ForumUsagePermissions::PermissionCreatePost ),
            'snippets' => [],
            'emotes' => $this->getEmotesByUser($user,true),

            'forum' => false,
            'type' => 'pm',
            'target_url' => 'town_house_send_pm_controller',
            'town_controls' => true,
        ] );
    }

    /**
     * @Route("jx/town/house/pm/{type}/editor", name="home_new_post_editor_controller")
     * @param string $type
     * @return Response
     */
    public function home_new_editor_post_api(string $type): Response {
        $user = $this->getUser();

        $allowed_types = ['pm', 'global'];
        if(!in_array($type, $allowed_types)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,

            'permission' => $this->getPermissionObject( ForumUsagePermissions::PermissionWrite ),
            'snippets' => [],

            'emotes' => $this->getEmotesByUser($user,true),
            'forum' => false,
            'type' => $type,
            'target_url' => 'town_house_send_pm_controller',
            'town_controls' => true,
        ] );
    }

    /**
     * @Route("jx/admin/pm/{type}/editor", name="admin_pm_editor_controller")
     * @param string $type
     * @return Response
     */
    public function admin_pm_new_editor_post_api(string $type): Response {
        $user = $this->getUser();

        $allowed_types = ['pm', 'global'];
        if(!in_array($type, $allowed_types)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        return $this->render( 'ajax/forum/editor.html.twig', [
            'fid' => null,
            'tid' => null,
            'pid' => null,

            'permission' => $this->getPermissionObject( ForumUsagePermissions::PermissionOwn ),
            'snippets' => [],

            'emotes' => $this->getEmotesByUser($user,true),
            'forum' => false,
            'type' => $type,
            'target_url' => 'admin_send_pm_controller',
            'town_controls' => true,
        ] );
    }

    /**
     * @Route("api/admin/sendpm", name="admin_send_pm_controller")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $t
     * @return Response
     */
    public function admin_pm_api(EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $t): Response {
        $type      = $parser->get('type', "");
        $recipient = $parser->get('recipient', '');
        $title     = $parser->get('title', '');
        $content   = $parser->get('content', '');

        $allowed_types = ['pm', 'global'];

        if(!in_array($type, $allowed_types))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if(empty($recipient) || empty($title) || empty($content))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $sender = null;

        $recipients = [];

        if ($type === 'pm') {

            $recipient = $em->getRepository(Citizen::class)->find($recipient);
            if ($recipient)
                $recipients[] = $recipient;

        } else {

            $town = $em->getRepository( Town::class )->find( $recipient );
            if ($town)
                foreach ($town->getCitizens() as $citizen)
                    $recipients[] = $citizen;

        }

        $success = 0;
        foreach ($recipients as $recipient) {
            if(!$recipient->getAlive()) continue;

            $thread = new PrivateMessageThread();

            $thread
                ->setTitle($title)
                ->setLocked(false)
                ->setLastMessage(new DateTime('now'))
                ->setRecipient($recipient);

            $post = new PrivateMessage();
            $post->setDate(new DateTime('now'))
                ->setText($content)
                ->setPrivateMessageThread($thread)
                ->setNew(true)
                ->setRecipient($recipient);

            $tx_len = 0;
            if (!$this->preparePost($this->getUser(),null,$post,$tx_len, $recipient->getTown()))
                return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            $thread
                ->setLastMessage($post->getDate())
                ->addMessage($post);

            $success++;
            $em->persist($thread);
            $em->persist($post);
        }

        $em->flush();

        if ($success === 0) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        } else {
            // Show confirmation
            $message = $t->trans('Deine Nachricht wurde korrekt übermittelt!', [], 'game');

            $this->addFlash( 'notice',  $message);
            return AjaxResponse::success( true, ['url' =>
                $type === 'pm'
                    ? $this->generateUrl('admin_users_citizen_view', ['id' => $recipients[0]->getUser()->getId()])
                    : $this->generateUrl('admin_town_explorer', ['id' => $parser->get('recipient', '')])
            ] );
        }
    }
}