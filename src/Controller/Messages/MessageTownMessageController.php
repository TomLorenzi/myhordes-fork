<?php

namespace App\Controller\Messages;

use App\Command\Info\ResolveCommand;
use App\Entity\AccountRestriction;
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
use App\Service\CrowService;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\RateLimitingFactoryProvider;
use App\Service\UserHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method User getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[IsGranted('ROLE_USER')]
class MessageTownMessageController extends MessageController
{

    public const ErrorMessageOrTitleEmpty = ErrorHelper::BaseMessageErrors + 1;

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $t
     * @param UserHandler $userHandler
     * @return Response
     */
    #[Route(path: 'api/town/house/sendpm', name: 'town_house_send_pm_controller')]
    public function send_pm_api(EntityManagerInterface $em, JSONRequestParser $parser, TranslatorInterface $t, UserHandler $userHandler): Response {
        if ($userHandler->isRestricted($this->getUser(), AccountRestriction::RestrictionTownCommunication))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $type      = $parser->get('type', "");
        $recipient = $parser->get('recipient', '');
        $title     = $parser->get('title', '');
        $content   = $parser->get('content', '');
        $items     = $parser->get('items', '');
        $tid       = $parser->get('tid', -1);

        $allowed_types = ['pm', 'global'];

        if(!in_array($type, $allowed_types) || mb_strlen( $content ) > 16384) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if($type === 'pm' && (empty($recipient) && $tid === -1))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if(($tid === -1 && empty($title)) || empty($content)) {
            return AjaxResponse::error(self::ErrorMessageOrTitleEmpty);
        }

        $sender = $this->getUser()->getActiveCitizen();

        if ($type === "global" && !$sender->getProfession()->getHeroic() && !$userHandler->hasSkill($sender->getUser(), 'writer'))
            return AjaxResponse::error(ErrorHelper::ErrorMustBeHero);

        if ($type === "global" && !$sender->getTown()->isOpen() && $sender->getTown()->getAliveCitizenCount() <= 1)
            return AjaxResponse::errorMessage( $this->translator->trans('Du bist ganz allein. Niemand wird dir antworten, du kannst es also getrost sein lassen. Außerdem spielt das eh keine Rolle mehr, du wirst heute Nacht bestimmt sterben...', [], 'game') );

        if ($type === "global" && $sender->getBanished())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $linked_items = array();

        if(is_array($items)){
            foreach ($items as $item_id) {
                $valid = false;
                $item = $em->getRepository(Item::class)->find($item_id);

                if (!$item) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                // Check if item is blacklisted from being sent via post
                if ($item->getPrototype()->hasProperty('no_post'))
                    return AjaxResponse::errorMessage( $this->translator->trans('Du kannst kein(en) {item} per Post versenden.',
                        ['item' => "<span class='tool'><img alt='' src='{$this->asset->getUrl( "build/images/item/item_{$item->getPrototype()->getIcon()}.gif" )}'> {$this->translator->trans($item->getPrototype()->getLabel(), [], 'items')}</span>"], 'game') );

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

        $correct_receiver = null;
        $incorrect_receiver = null;

        if ($type === 'pm') {
            $recipient = $global_recipient ?? $em->getRepository(Citizen::class)->find($recipient);

            if (count($linked_items) > 0) {
                if ($recipient->getBanished() != $sender->getBanished() && !$this->citizen_handler->hasStatusEffect($sender,'drunk'))
                    return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
                if ($sender->getTown()->getChaos()){
                    if($recipient->getZone())
                        return AjaxResponse::error(self::ErrorPMItemChaosOut);
                    else {
                        $counter = $sender->getSpecificActionCounter(ActionCounter::ActionTypeSendPMItem, 0);
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
                if ($this->inventory_handler->getFreeSize($recipient->getHome()->getChest()) < count($linked_items))
                    return AjaxResponse::error(InventoryHandler::ErrorTargetChestFull);
            }

            // Special drunk handler
            if ($recipient && $this->citizen_handler->hasStatusEffect($sender,'drunk')) {

                // Filter possible recipients. A sender can only send to someone who has the same banishment status.
                $list = $sender->getTown()
                               ->getCitizens()
                               ->filter(fn(Citizen $c) => $c !== $sender && $c !== $recipient && $c->getAlive() && ($sender->getBanished() === $c->getBanished() || empty($linked_items)))
                               ->getValues();

                // If there's no recipient, we have to send an error. No MP will be sent.
                if (empty($list)) {
                    return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
                }

                shuffle($list);

                foreach ($list as $incorrect_receiver_candidate)
                    if ($this->inventory_handler->getFreeSize($incorrect_receiver_candidate->getHome()->getChest()) >= count($linked_items)) {
                        $correct_receiver = $recipient;
                        $incorrect_receiver = $incorrect_receiver_candidate;
                        $global_thread = null;
                        break;
                    }

                // If there's no recipient, we have to send an error. No MP will be sent.
                if (!$incorrect_receiver)
                    return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

                $recipients[] = $incorrect_receiver;
            } else if ($recipient) $recipients[] = $recipient;

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
                    ->setTitle($title ?: "...")
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
                ->setOriginalRecipient( $correct_receiver )
                ->setRecipient($recipient);

            $items_prototype = [];
            foreach ($linked_items as $item) {
                $items_prototype[] = $item->getPrototype()->getId();
                $this->inventory_handler->forceMoveItem($recipient->getHome()->getChest(), $item);
            }

            if ( !empty($items_prototype) ) {
                $personal_counter = $sender->getSpecificActionCounter(ActionCounter::ActionTypeSendPMItem, $recipient->getId());
                $personal_counter->increment();
                $em->persist($personal_counter);
            }

            $post->setItems($items_prototype);

            if (!$this->preparePost($this->getUser(),null,$post, $recipient->getTown()))
                return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            $thread
                ->setLastMessage($post->getDate())
                ->addMessage($post);

            $success++;
            $em->persist($thread);
            $em->persist($post);
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }


        if ($success === 0) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        } else {
            // Show confirmation
            if(count($linked_items) > 0)
                $message = $t->trans("Deine Nachricht und deine ausgewählten Gegenstände wurden überbracht.", [], 'game');
            else
                $message = $t->trans('Deine Nachricht wurde korrekt übermittelt!', [], 'game');

            $this->addFlash( 'notice',  $message);
            return AjaxResponse::success( true, ['url' => $this->generateUrl('town_house_messages', ['subtab' => 'received'])] );
        }


    }

    /**
     * @param int $tid
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/town/house/pm/{tid<\d+>}/view', name: 'home_view_thread_controller')]
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
                    $thread->setTitle( $this->translator->trans('Anonyme Beschwerde ({num} insgesamt)', ['num' => $post->getAdditionalData() ? $post->getAdditionalData()['num'] ?? 0 : 0], 'game') );
                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . $this->translator->trans( 'Es wurde eine neue anonyme Beschwerde gegen dich eingelegt: "{reason}"', ['{reason}' => $reason ? $this->translator->trans( $reason->getText(), [], 'game' ) : '???'], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_COMPLAINT_OFF:
                    /** @var Complaint $complaint */
                    $reason = $this->entity_manager->getRepository(ComplaintReason::class)->find( $post->getForeignID() );
                    $thread->setTitle( $this->translator->trans('Beschwerde zurückgezogen (es bleiben noch {num} Stück)', ['num' => $post->getAdditionalData() ? $post->getAdditionalData()['num'] ?? 0 : 0], 'game') );
                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . $this->translator->trans( 'Es gibt gute Nachrichten! Folgende Beschwerde wurde zurückgezogen: "{reason}"', ['{reason}' => $reason ? $this->translator->trans( $reason->getText(), [], 'game' ) : '???'], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_TERROR:
                case PrivateMessage::TEMPLATE_CROW_NIGHTWATCH_TERROR:
                    $thread->setTitle( $this->translator->trans('Du bist vor Angst erstarrt!!', [], 'game') );
                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . $this->translator->trans( 'Wir haben zwei Neuigkeiten für dich. Eine gute und eine schlechte. Zuerst die gute: Trotz ihrer hartnäckigen Versuche, ist es den {num} Zombie(s) nicht gelungen, dich aufzufressen. Du hast dich wacker geschlagen. Bravo! Die schlechte: Das Erlebnis war so schlimm, dass du in eine Angststarre verfallen bist. So etwas möchtest du nicht wieder erleben...', ['{num}' => $post->getForeignID()], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_AVOID_TERROR:
                    $thread->setTitle( $this->translator->trans('Was für eine schreckliche Nacht!', [], 'game') );
                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . $this->translator->trans( 'Heute Nacht ist dir der Arsch so richtig auf Grundeis gegangen! Als du ihr Grunzen und Stöhnen gehört hattest, war dir klar: Sie würden bei dir daheim eindringen. So kam es dann auch: Deine Haustür splitterte unter der Last ihrer Angriffe. Panisch bist du ins Schlafzimmer gerannt, um dich unter deinem Bett zu verstecken. Sie blieben ein paar Minuten, die dir wie eine Ewigkeit vorkamen, und schnüffelten sich durch alle Zimmer. Innerlich zitternd, hast du zu Gott gebetet, dass sie dich verschonen mögen. Dann war plötzlich wieder alles still. Hechelnd und schnaufend bist du aus deinem Versteck hervorgekrochen und heulend auf deinem Bett zusammengesunken.', [], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_THEFT:
                    /** @var ItemPrototype $item */
                    $item = $this->entity_manager->getRepository(ItemPrototype::class)->find( $post->getForeignID() );
                    $thread->setTitle( $this->translator->trans('Haltet den Dieb!', [], 'game') );

                    $img = "<img src='{$this->asset->getUrl('build/images/item/item_' . ($item ? $item->getIcon() : 'none') . '.gif')}' alt='' />";
                    $name = $this->translator->trans( $item ? $item->getLabel() : '', [], 'items' );
                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . $this->translator->trans( 'Es scheint so, als ob ein anderer Bürger Gefallen an deinem Inventar gefunden hätte... Dir wurde folgendes gestohlen: {icon} {item}', ['{icon}' => $img, '{item}' => $name], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_CATAPULT:
                    $thread->setTitle( $this->translator->trans('Du bist für das Katapult verantwortlich', [], 'game') );
                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . $this->translator->trans( 'Du bist zum offiziellen Katapult-Bediener der Stadt ernannt worden. Diese Ernennung erfolgte durch Auslosung; Herzlichen Glückwunsch! Finde dich so bald wie Möglich beim städtischen Katapult ein.', [], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_AGGRESSION_FAIL:
                    /** @var Citizen $aggressor */
                    $aggressor = $this->entity_manager->getRepository(Citizen::class)->find( $post->getForeignID() );
                    $thread->setTitle( $this->translator->trans('{username} hat dich angegriffen!', ['{username}' => $aggressor->getName()], 'game') );
                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . $this->translator->trans( 'Als du es dir gerade gemütlich machen wolltest, wurdest du von {username} übel angegangen. Du hast einiges abbekommen, aber auch ordentlich ausgeteilt! Zum Glück hast du dir nichts gebrochen.', ['{username}' => $aggressor->getName()], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_AGGRESSION_SUCCESS:
                    /** @var Citizen $aggressor */
                    $aggressor = $this->entity_manager->getRepository(Citizen::class)->find( $post->getForeignID() );
                    $thread->setTitle( $this->translator->trans('{username} hat dich angegriffen und verletzt!', ['{username}' => $aggressor->getName()], 'game') );
                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . $this->translator->trans( 'Als du es dir gerade gemütlich machen wolltest, wurdest du von {username} übel angegangen. Du hast einiges abbekommen, aber auch ordentlich ausgeteilt! Leider wurdest du bei dem Angriff verletzt!', ['{username}' => $aggressor->getName()], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_NIGHTWATCH_WOUND:
                    $thread->setTitle( $this->translator->trans('Verletzt', [], 'game') );
                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . $this->translator->trans( 'Wir haben zwei Neuigkeiten für dich. Die Gute: du konntest die {count} Zombie(s) abwehren! Die Schlechte: du wurdest dabei verletzt...', ['{count}' => $post->getForeignID()], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_INTRUSION:
                    $intruder = $this->entity_manager->getRepository(Citizen::class)->find( $post->getForeignID() );

                    $time = (int)$post->getText();
                    $thread->setTitle( $this->translator->trans("Alarm (Bürger {citizen})", ['citizen' =>  $intruder ?? '???'], 'game') );
                    $post->setText( $this->translator->trans( '{citizen} hat bei dir daheim den Alarm ausgelöst, als er (sie) um {time} versuchte bei dir einzubrechen!', ['citizen' => $intruder ?? '???', 'time' => date('H:i', $time)], 'game' ) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_BANISHMENT:
                    /** @var ItemPrototype $item */
                    $items = [];
                    foreach ( $post->getAdditionalData() as $item_id) {
                        if (!isset($items[$item_id]))
                            $items[$item_id] = [0, $this->entity_manager->getRepository(ItemPrototype::class)->find( $item_id ) ];
                        $items[$item_id][0]++;
                    }

                    $thread->setTitle( $this->translator->trans('Du wurdest verbannt', [], 'game') );
                    $item_list = implode( ', ', array_map( fn( $entry ) => "<span class=\"tool\"><img src='{$this->asset->getUrl('build/images/item/item_' . ($entry[1]?->getIcon() ?? 'none') . '.gif')}' alt='' /> <strong>{$this->translator->trans( $entry[1]?->getLabel() ?? '', [], 'items' )}</strong> x {$entry[0]}</span>", $items ) );

                    $messages = [ $this->translator->trans( 'Da sich zu viele Beschwerden gegen dich angesammelt haben, wurdest du aus der Gemeinschaft verbannt.', [], 'game' ) ];
                    if (!empty($item_list)) $messages[] = $this->translator->trans( 'Die folgenden Gegenstände wurden aus deinem Inventar entfernt: {item_list}', ['item_list' => $item_list], 'game' );

                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . implode(' ', $messages) );
                    break;
                case PrivateMessage::TEMPLATE_CROW_REDUCED_AP_REGEN:
                    $thread->setTitle( $this->translator->trans('Du bist erschöpft!', [], 'game') );
                    $post->setText( $this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()) . $this->translator->trans( 'Du hast dich gestern so sehr verausgabt, dass du in der Nacht nicht genug Kraft schöpfen konntest, um deine Aktionspunkte vollständig zu regenerieren.', [], 'game' ) );
                    break;
                default:
                    $post->setText($this->html->prepareEmotes($post->getText(), $this->getUser(), $citizen->getTown()));
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
     * @param int $tid
     * @param int $action
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'api/town/house/pm/{tid<\d+>}/archive/{action<\d+>}', name: 'home_archive_pm_controller')]
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
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $ti
     * @return Response
     */
    #[Route(path: 'api/town/house/pm/report', name: 'home_report_pm_controller')]
    public function pm_report_api(JSONRequestParser $parser, EntityManagerInterface $em, TranslatorInterface $ti, CrowService $crow, RateLimitingFactoryProvider $rateLimiter): Response {
        $user = $this->getUser();

        $id = $parser->get('pmid', null);
        if ($id === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $reason = $parser->get_int('reason', 0, 0, 13);
        if ($reason === 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

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

        if (!($limit = $rateLimiter->reportLimiter( $user )->create( $user->getId() )->consume())->isAccepted())
            return AjaxResponse::error( ErrorHelper::ErrorRateLimited, ['detail' => 'report', 'retry_in' => $limit->getRetryAfter()->getTimestamp() - (new DateTime())->getTimestamp()]);

        $details = $parser->trimmed('details');
        $newReport = (new AdminReport())
            ->setSourceUser($user)
            ->setTs(new DateTime('now'))
            ->setReason( $parser->get_int('reason', 0, 0, 13) )
            ->setDetails( $details ?: null )
            ->setPm($post);

        $em->persist($newReport);
        $em->flush();

        try {
            $crow->triggerExternalModNotification( 'A town PM has been reported.', $post, $newReport );
        } catch (\Throwable $e) {}

        $message = $ti->trans('Du hast die Nachricht von {username} dem Raben gemeldet. Wer weiß, vielleicht wird {username} heute Nacht stääärben...', ['{username}' => '<span>' . $post->getOwner()->getName() . '</span>'], 'game');
        $this->addFlash('notice', $message);

        return AjaxResponse::success();
    }

    /**
     * @param int $tid
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/town/house/pm/{tid<\d+>}/editor', name: 'home_answer_post_editor_controller')]
    public function home_answer_editor_post_api(int $tid, EntityManagerInterface $em): Response {
        $user = $this->getUser();

        if ($this->userHandler->isRestricted($user, AccountRestriction::RestrictionTownCommunication))
            return new Response("");

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
            'username' => $user->getActiveCitizen()->getName(),
            'type' => 'pm',
            'target_url' => 'town_house_send_pm_controller',
            'town_controls' => true,
            'langsCodes' => $this->generatedLangsCodes
        ] );
    }

    /**
     * @param string $type
     * @return Response
     */
    #[Route(path: 'jx/town/house/pm/{type}/editor', name: 'home_new_post_editor_controller')]
    public function home_new_editor_post_api(string $type): Response {
        $user = $this->getUser();

        if ($this->userHandler->isRestricted($user, AccountRestriction::RestrictionTownCommunication))
            return new Response("");

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
            'username' => $user->getActiveCitizen()->getName(),
            'type' => $type,
            'target_url' => 'town_house_send_pm_controller',
            'town_controls' => true,
            'langsCodes' => $this->generatedLangsCodes
        ] );
    }

    /**
     * @param string $type
     * @return Response
     */
    #[Route(path: 'jx/admin/pm/{type}/editor', name: 'admin_pm_editor_controller')]
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
            'username' => $user->getName(),
            'type' => $type,
            'target_url' => 'admin_send_pm_controller',
            'town_controls' => true,
            'langsCodes' => $this->generatedLangsCodes
        ] );
    }

    /**
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $t
     * @return Response
     */
    #[Route(path: 'api/admin/sendpm', name: 'admin_send_pm_controller')]
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

            if (!$this->preparePost($this->getUser(),null,$post, $recipient->getTown()))
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
                    : $this->generateUrl('admin_town_dashboard', ['id' => $parser->get('recipient', '')])
            ] );
        }
    }
}