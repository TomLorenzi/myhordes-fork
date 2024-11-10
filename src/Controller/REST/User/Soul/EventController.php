<?php

namespace App\Controller\REST\User\Soul;

use App\Controller\CustomAbstractCoreController;
use App\Entity\CommunityEvent;
use App\Entity\CommunityEventMeta;
use App\Entity\CommunityEventTownPreset;
use App\Entity\ForumUsagePermissions;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Messages\Discord\DiscordMessage;
use App\Service\Actions\Ghost\SanitizeTownConfigAction;
use App\Service\CrowService;
use App\Service\EventProxyService;
use App\Service\JSONRequestParser;
use App\Service\PermissionHandler;
use App\Service\User\UserCapabilityService;
use App\Service\UserHandler;
use App\Structures\MyHordesConf;
use DiscordWebhooks\Client;
use DiscordWebhooks\Embed;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


#[Route(path: '/rest/v1/user/soul/events', name: 'rest_user_soul_events_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class EventController extends CustomAbstractCoreController
{

    /**
     * @param Packages $assets
     * @return JsonResponse
     */
    #[Route(path: '/index', name: 'base', methods: ['GET'])]
    #[Route(path: '/index_data', name: 'base_index', methods: ['GET'])]
    public function index(Packages $assets): JsonResponse {
        return new JsonResponse([
            'strings' => [
                'common' => [
                    'create' => $this->translator->trans('Eigenes Event organisieren', [], 'global'),
                    'cancel_create' => $this->translator->trans('Zurück zur Übersicht', [], 'global'),
                    'init_verification' => $this->translator->trans('Event-Verifizierung beantragen', [], 'global'),
                    'cancel_verification' => $this->translator->trans('Event-Verifizierung abbrechen', [], 'global'),
                    'do_verification' => $this->translator->trans('Event freischalten', [], 'global'),
                    'verification_pending' => $this->translator->trans('Verifizierung beantragt', [], 'global'),
                    'start_pending' => $this->translator->trans('Start geplant', [], 'global'),
                    'mark_end' => $this->translator->trans('Event beenden', [], 'global'),

                    'planned_string' => $this->translator->trans('Vorläufiges Startdatum am {date}', [], 'global'),
                    'start_string_singular' => $this->translator->trans('Event startet morgen, am {date}', [], 'global'),
                    'start_string_plural' => $this->translator->trans('Event startet in {days} Tagen, am {date}', [], 'global'),
                    'start_string_running' => $this->translator->trans('Event läuft seit {date}', [], 'global'),
                    'end_string' => $this->translator->trans('Event beendet', [], 'global'),

                    'save' => $this->translator->trans('Speichern', [], 'global'),
                    'cancel' => $this->translator->trans('Abbrechen', [], 'global'),
                    'edit' => $this->translator->trans('Bearbeiten', [], 'global'),
                    'edit_icon' => $assets->getUrl('build/images/forum/edit.png'),
                    'delete' => $this->translator->trans('Löschen', [], 'global'),
                    'delete_icon' => $assets->getUrl('build/images/icons/small_remove.gif'),

                    'online_icon' => $assets->getUrl('build/images/icons/player_online.gif'),
                    'offline_icon' => $assets->getUrl('build/images/icons/player_offline.gif'),

                    'flags' => array_map( fn($l) => $assets->getUrl("build/images/lang/{$l}.png"), ['de'=>'de','en'=>'en','fr'=>'fr','es'=>'es','multi'=>'multi'] ),
                    'langs' => array_map( fn($l) => $this->translator->trans( $l, [], 'global' ), ['de'=>'Deutsch','en'=>'Englisch','fr'=>'Französisch','es'=>'Spanisch','multi'=>'???'] ),
                ],

                'messages' => [
                    'verification_started' => $this->translator->trans('Verifizierung erfolgreich beantragt. Dein Event wird in Kürze von einem Raben oder Administrator geprüft.', [], 'global'),
                    'verification_cancelled' => $this->translator->trans('Dein Antrag auf Verifizierung wurde erfolgreich zurückgezogen.', [], 'global'),
                    'verification_confirmed' => $this->translator->trans('Event erfolgreich verifiziert.', [], 'global'),
                ],

                'list' => [
                    'no_events' => $this->translator->trans('Aktuell sind keine Community-Events geplant.', [], 'global'),
                    'default_event' => $this->translator->trans('Neues Event', [], 'global'),

                    'delete_confirm' => $this->translator->trans('Bist du sicher, dass du dieses Event löschen möchtest?', [], 'global'),

                                        'more_info' => $this->translator->trans('Weitere Informationen und Regeln anzeigen.', [], 'global'),
                ],

                'towns' => [
                    'title' => $this->translator->trans('Event-Städte', [], 'global'),
                    'help1' => $this->translator->trans('Hier kannst du Städte für dein Event anlegen, welche zusammen mit deinem Event gestartet werden. Dir stehen hierfür fast alle Optionen zur Verfügung, die auch Raben für die Erstellung von Städten haben.', [], 'global'),
                    'help2' => $this->translator->trans('Deiner Kreativität sind somit keine Grenzen gesetzt!', [], 'global'),
                    'password' => $this->translator->trans('Zugangscode', [], 'ghost'),

                    'no_towns' => $this->translator->trans('Aktuell werden keine Städte mit dem Start des Events automatisch angelegt.', [], 'global'),
                    'default_town' => $this->translator->trans('Automatisch generierter Name', [], 'global'),

                    'table_lang' => $this->translator->trans('Sprache', [], 'global'),
                    'table_town' => $this->translator->trans('Stadt', [], 'game'),
                    'table_act' => $this->translator->trans('Aktionen', [], 'global'),

                    'add' => $this->translator->trans('Stadt hinzufügen', [], 'global'),
                    'delete_confirm' => $this->translator->trans('Bist du sicher, dass du dieses Stadt löschen möchtest?', [], 'global'),

                    'expedite' => $this->translator->trans('Städte mit hoher Priorität anlegen', [], 'global'),
                    'expedited' => $this->translator->trans('Städte werden mit hoher Priorität angelegt', [], 'global'),
                    'expedite_help' => $this->translator->trans('Die Städte werden innerhalb der nächsten 30 Minuten erzeugt, statt erst beim nächsten Angriff. Bitte verwendet diese Funktion nur in Notfällen.', [], 'global'),
                    'expedite_confirm' => $this->translator->trans('Bitte verwendet diese Funktion nur in Notfällen. Fortfahren?', [], 'global'),

                    'town_create' => $this->translator->trans('Neue Stadt anlegen', [], 'global'),
                    'town_edit' => $this->translator->trans('Stadt bearbeiten', [], 'global'),

                    'town_instance_online' => $this->translator->trans('Stadtinstanz bereit', [], 'global'),
                    'town_instance_offline' => $this->translator->trans('Stadtinstanz beendet', [], 'global'),

                    'citizens' => $this->translator->trans('Einwohnerzahl', [], 'ghost'),
                    'alive' => $this->translator->trans('Am Leben', [], 'global'),
                    'day' => $this->translator->trans('Tag', [], 'global'),

                    'forum_link' => $this->translator->trans('Zum Forum', [], 'game'),
                    'ranking_link' => $this->translator->trans('Zur Chronik', [], 'global'),
                ],

                'editor' => [
                    'title' => $this->translator->trans('Allgemeine Event-Informationen', [], 'global'),
                    'help' => $this->translator->trans('Hier kannst du allgemeine Optionen für dein Event, wie dessen Name, Beschreibung oder Startzeitpunkt festlegen.', [], 'global'),
                    'edit' => $this->translator->trans('Event bearbeiten', [], 'global'),
                    'add_meta' => $this->translator->trans('Klicke hier, um eine Eventbeschreibung in {lang} hinzuzufügen.', [], 'global'),

                    'schedule' => $this->translator->trans('Geplanter Startzeitpunkt', [], 'global'),

                    'field_title' =>  $this->translator->trans('Event-Titel', [], 'global'),
                    'field_short' =>  $this->translator->trans('Kurzbeschreibung', [], 'global'),
                    'field_description' =>  $this->translator->trans('Beschreibung und Regeln des Events', [], 'global'),
                ],
            ]
        ]);
    }

    protected function eventIsEditable(CommunityEvent $event, bool $forVerificationCancellation = false): bool {
        if (!$this->eventIsExplorable($event) || $event->getStarts() !== null) return false;
        if (
            !$forVerificationCancellation &&
            (
                (!$this->isGranted('ROLE_CROW') && $event->getOwner() === $this->getUser()) ||
                !$this->isGranted('ROLE_ELEVATED')
            ) &&
            $event->isProposed())
            return false;
        return true;
    }

    protected function eventCanBeEnded(CommunityEvent $event): bool {
        if (!$this->eventIsExplorable($event) || $event->getStarts() === null) return false;
        return true;
    }

    protected function eventIsExplorable(CommunityEvent $event): bool {
        return $event->getOwner() === $this->getUser() || ($event->isProposed() && $this->isGranted('ROLE_ELEVATED'));
    }

    /**
     * @param EntityManagerInterface $em
     * @param UserCapabilityService $capability
     * @return JsonResponse
     * @throws Exception
     */
    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function listEvents(
        EntityManagerInterface $em,
        UserCapabilityService $capability
    ): JsonResponse {

        $can_view_proposals = $capability->hasAnyRole( $this->getUser(), ['ROLE_ELEVATED'] );

        $is_owner = Criteria::expr()->eq('owner', $this->getUser() );
        $is_proposed = Criteria::expr()->eq('proposed', true );

        $events = $em->getRepository(CommunityEvent::class)->matching(
            (Criteria::create())
                // Is owner or event is public
                ->andWhere( !$can_view_proposals
                    ? Criteria::expr()->orX(
                        $is_owner,
                        Criteria::expr()->neq('starts', null )
                    )
                    : Criteria::expr()->orX(
                        $is_owner,
                        $is_proposed,
                        Criteria::expr()->neq('starts', null )
                    )
                )
                ->andWhere( Criteria::expr()->orX(
                    Criteria::expr()->isNull('starts' ),
                    Criteria::expr()->gte('starts', (new \DateTime())->modify( '-100day' ) )
                ) )
                // Is owner or is not started or TODO is participant
                ->andWhere( Criteria::expr()->orX(
                    $is_owner,
                    Criteria::expr()->neq('ended', true )
                ) )
        );

        $events = $events->toArray();
        usort( $events, function(CommunityEvent $a, CommunityEvent $b) {
            if ($a->isEnded() !== $b->isEnded()) return $a->isEnded() ? 1 : -1;
            if (($a->getOwner() === $this->getUser()) !== ($b->getOwner() === $this->getUser())) return $a->getOwner() === $this->getUser() ? -1 : 1;
            if (($a->getStarts() === null) !== ($b->getStarts() === null)) return $a->getStarts() === null ? -1 : 1;
            if ($a->getStarts() && $b->getStarts()) return $a->getStarts() <=> $b->getStarts();
            return $b->getCreated() <=> $a->getCreated();
        } );

        return new JsonResponse( ['events' => array_map( function(CommunityEvent $e) {
            $meta = $e->getMeta( $this->getUserLanguage(), true );
            $owning = $e->getOwner() === $this->getUser();

            $started = $e->getStarts() && $e->getStarts() <= (new \DateTime());

            $return = [
                'uuid' => $e->getId(),
                'name' => $meta?->getName(),
                'short' => $meta?->getShort(),
                'description' => $meta?->getDescription(),
                'own' => $owning,
                'start' => ($e->getStarts() ?? $e->getConfiguredStartDate())?->format(\DateTimeInterface::ATOM),
                'daysLeft' => ($e->getStarts() && !$started) ? ceil(($e->getStarts()->getTimestamp() - (new \DateTime())->getTimestamp()) / (60 * 60 * 24)) : null,
                'started' => $started,
                'ended' => $started ? $e->isEnded() : false,
                'proposed' => $e->isProposed(),
                'published' => $e->getStarts() !== null
            ];

            if ($owning) $return['expedited'] = $e->isUrgent();

            if ($this->isGranted('ROLE_ELEVATED')) $return['owner'] = [
                'id' => $e->getOwner()->getId(),
                'name' => $e->getOwner()->getName(),
            ];

            return $return;
        }, $events ) ] );
    }

    /**
     * @param UserCapabilityService $capability
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '', name: 'create', methods: ['PUT'])]
    public function createEvent(
        UserCapabilityService $capability,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$capability->hasAnyRole( $this->getUser(), ['ROLE_ELEVATED'] ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $em->persist( $event = (new CommunityEvent())
            ->setOwner( $this->getUser() )
            ->setCreated( new \DateTime() )
        );

        try {
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['uuid' => $event->getId()]);
    }

    protected function renderConfig(CommunityEvent $event): array {
        $conf = [
            'startDate' => $event->getConfiguredStartDate()?->format('Y-m-d') ?? null
        ];

        return array_filter( $conf, fn($v) => $v !== null );
    }

    /**
     * @param CommunityEvent $event
     * @param bool $option
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param MessageBusInterface $bus
     * @return JsonResponse
     */
    #[Route(path: '/{id}/proposal', name: 'set_proposal', methods: ['PUT'], defaults: ['option' => true])]
    #[Route(path: '/{id}/proposal', name: 'remove_proposal', methods: ['DELETE'], defaults: ['option' => false])]
    public function editEventProposal(
        CommunityEvent $event,
        bool $option,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        MessageBusInterface $bus
    ): JsonResponse {
        if (!$this->eventIsEditable( $event, $option === false ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        if ($event->isProposed() !== $option) {

            if ($option) {
                if (!$event->getMeta( '', true))
                    return new JsonResponse(['message' => $this->translator->trans('Du musst mindestens eine Event-Beschreibung hinzufügen.', [], 'global')], Response::HTTP_NOT_ACCEPTABLE);
                if (!$event->getConfiguredStartDate())
                    return new JsonResponse(['message' => $this->translator->trans('Du musst ein Startdatum für dein Event festlegen.', [], 'global')], Response::HTTP_NOT_ACCEPTABLE);

                $minDate = new \DateTime();
                $maxDate = (new \DateTime())->add( \DateInterval::createFromDateString('194days') );

                if ($event->getConfiguredStartDate() < $minDate || $event->getConfiguredStartDate() > $maxDate)
                    return new JsonResponse(['message' => $this->translator->trans('Das Startdatum deines Events muss mindestens {minDays} Tage und maximal {maxDays} Tage in der Zukunft liegen', ['minDays' => 14, 'maxDays' => 194], 'global')], Response::HTTP_NOT_ACCEPTABLE);
            }

            $event->setProposed( $option );
            $em->persist( $event );

            try {
                $em->flush();
            } catch (\Throwable $e) {
                return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($endpoint = $this->conf->getGlobalConf()->get( MyHordesConf::CONF_ANIM_MAIL_DCHOOK )) {
                $discord = (new Client($endpoint))
                    ->message(
                        $option
                            ? ":black_joker: **Please validate my community event.**"
                            : ':x: I\'m retracting my previous event validation request.'
                    );

                $discord->embed( (new Embed())
                    ->color('B434EB')
                    ->title('Event configuration')
                    ->field('Start date', $event->getConfiguredStartDate()->format( "D, d M Y" ), true)
                    ->field('Towns', $event->getTownPresets()->count(), true)
                    ->footer( $event->getId() )
                    ->author(
                        $event->getOwner()->getName(),
                        $urlGenerator->generate( 'admin_users_account_view', ['id' => $event->getOwner()->getId()], UrlGeneratorInterface::ABSOLUTE_URL ),
                        $event->getOwner()->getAvatar() ? $urlGenerator->generate( 'app_web_avatar', ['uid' => $event->getOwner()->getId(), 'name' => $event->getOwner()->getAvatar()->getFilename(), 'ext' => $event->getOwner()->getAvatar()->getFormat()],UrlGeneratorInterface::ABSOLUTE_URL ) : ''
                    )
                );

                $flag_lang = function (string $lang) {
                    return match ($lang) {
                        'en' => ':flag_us:',
                        default => ":flag_$lang:"
                    };
                };

                if ($option)
                    foreach ($event->getMetas() as $meta)
                        $discord->embed( (new Embed())
                            ->color('6BF2F0')
                            ->title("{$flag_lang($meta->getLang())} Event information")
                            ->field('Name', $meta->getName(), true)
                            ->field('Short Description', mb_substr($meta->getShort(), 0, 1500), true)
                        );

                $bus->dispatch( new DiscordMessage( $discord ) );
            }


        }

        return new JsonResponse(['uuid' => $event->getId()]);
    }

    /**
     * @param CommunityEvent $event
     * @param EntityManagerInterface $em
     * @param CrowService $crow
     * @return JsonResponse
     */
    #[Route(path: '/{id}/publish', name: 'set_published', methods: ['PUT'])]
    public function publishEvent(
        CommunityEvent $event,
        EntityManagerInterface $em,
        CrowService $crow,
        EventProxyService $proxy,
    ): JsonResponse {
        if (!$this->eventIsEditable( $event ) || !$this->isGranted('ROLE_ELEVATED'))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $date = $event->getConfiguredStartDate();
        if ($date < (new \DateTime())->modify( 'tomorrow' ))
            $date = (new \DateTime())->modify( 'tomorrow' );

        $event->setStarts( $date );
        $em->persist( $event );

        $notification = $crow->createPM_moderation(
            $event->getOwner(),
            CrowService::ModerationActionDomainEvents, CrowService::ModerationActionTargetEventValidation, CrowService::ModerationActionSolve,
            $event
        );
        if ($notification) $em->persist($notification);

        try {
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $proxy->newCommunityEventEvent( $event );
        $em->flush();

        return new JsonResponse(['uuid' => $event->getId()]);
    }

    /**
     * @param CommunityEvent $event
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '/{id}/end', name: 'set_ended', methods: ['PUT'])]
    public function endEvent(
        CommunityEvent $event,
        EntityManagerInterface $em
    ): JsonResponse {

        if (!$this->eventCanBeEnded( $event ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $event->setEnded( true );
        $em->persist( $event );

        try {
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['uuid' => $event->getId()]);
    }

    /**
     * @param CommunityEvent $event
     * @return JsonResponse
     */
    #[Route(path: '/{id}/config', name: 'get_config', methods: ['GET'])]
    public function getEventConfig(
        CommunityEvent $event
    ): JsonResponse {
        if (!$this->eventIsExplorable( $event ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        return new JsonResponse($this->renderConfig($event));
    }

    /**
     * @param CommunityEvent $event
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '/{id}/config', name: 'edit_config', methods: ['PATCH'])]
    public function updateEventConfig(
        CommunityEvent $event,
        JSONRequestParser $parser,
        EntityManagerInterface $em
    ): JsonResponse {
        $startDate = $parser->get_dateTime('startDate');
        if ($startDate && ($startDate < new \DateTime() || $startDate > (new \DateTime())->add(\DateInterval::createFromDateString('1year'))))
            $startDate = null;

        if ($startDate) {
            if (!$this->eventIsEditable( $event )) return new JsonResponse([], Response::HTTP_FORBIDDEN);
            $event->setConfiguredStartDate($startDate);
        }

        $expedite = $parser->get('expedited');
        if ($expedite && $event->getStarts() !== null && !$event->isUrgent() && $event->getOwner() === $this->getUser())
            $event->setUrgent(true);

        $em->persist( $event );

        try {
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->renderConfig($event));
    }

    /**
     * @param CommunityEvent $event
     * @param EntityManagerInterface $em
     * @param CrowService $crow
     * @return JsonResponse
     */
    #[Route(path: '/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteEvent(
        CommunityEvent $event,
        EntityManagerInterface $em,
        CrowService $crow
    ): JsonResponse {
        if (!$this->eventIsEditable( $event ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $notification = $event->getOwner() !== $this->getUser() ? $crow->createPM_moderation(
            $event->getOwner(),
            CrowService::ModerationActionDomainEvents, CrowService::ModerationActionTargetEvent, CrowService::ModerationActionDelete,
            $event
        ) : null;
        if ($notification) $em->persist($notification);

        try {
            $em->remove( $event );
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse();
    }

    protected function metaToJSON(CommunityEventMeta $meta): array {
        return [
            'lang' => $meta->getLang(),
            'name' => $meta->getName(),
            'description' => $meta->getDescription(),
            'short' => $meta->getShort(),
        ];
    }

    /**
     * @param CommunityEvent $event
     * @return JsonResponse
     */
    #[Route(path: '/{id}/meta', name: 'list_meta', methods: ['GET'])]
    public function listEventMeta(
        CommunityEvent $event
    ): JsonResponse {
        return new JsonResponse( ['meta' => array_map( function(CommunityEventMeta $meta) {
            return $this->metaToJSON($meta);
        }, $event->getMetas()->toArray() ) ] );
    }

    /**
     * @param CommunityEvent $event
     * @param string $lang
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return JsonResponse
     */
    #[Route(path: '/{id}/meta/{lang<de|en|fr|es>}', name: 'edit_meta', methods: ['PATCH'])]
    public function editEventMeta(
        CommunityEvent $event,
        string $lang,
        EntityManagerInterface $em,
        JSONRequestParser $parser,
    ): JsonResponse {
        if (!$this->eventIsEditable( $event ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        if (!$parser->has_all(['name','desc','short'])) return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $meta = $event->getMeta($lang);
        if (!$meta) $event->addMeta( $meta = (new CommunityEventMeta())
            ->setLang( $lang )
        );

        $meta
            ->setName( mb_substr( $parser->trimmed( 'name' ), 0, 128 ) )
            ->setDescription( $parser->trimmed('desc') )
            ->setShort( $parser->trimmed('short') )
        ;

        $em->persist($meta);
        try {
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['meta' => $this->metaToJSON($meta)]);
    }

    /**
     * @param CommunityEvent $event
     * @param string $lang
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '/{id}/meta/{lang<de|en|fr|es>}', name: 'delete_meta', methods: ['DELETE'])]
    public function deleteEventMeta(
        CommunityEvent $event,
        string $lang,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->eventIsEditable( $event ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $meta = $event->getMeta($lang);
        if ($meta) {
            $event->removeMeta($meta);
            $em->remove($meta);
            $em->persist($event);
            try {
                $em->flush();
            } catch (\Throwable $e) {
                return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new JsonResponse();
    }

    protected function getTownInstanceData(CommunityEventTownPreset $preset, EntityManagerInterface $em, PermissionHandler $perm): ?array {
        if ($preset->getTownId() === null) return null;

        /** @var TownRankingProxy $ranking */
        $ranking = $em->getRepository(TownRankingProxy::class)->findOneBy(['baseID' => $preset->getTownId(), 'imported' => false]);

        $has_forum_access = false;
        if ($preset->getTown()?->getForum()) {
            $permission = $perm->getEffectivePermissions($this->getUser(),$preset->getTown()->getForum());
            $has_forum_access = $perm->isPermitted( $permission, ForumUsagePermissions::PermissionRead );
        }

        return [
            'name' => $ranking?->getName(),
            'ranking_link' => ($ranking && ( $preset->getTown() === null || $preset->getTown()?->getCitizenCount() > 0 ))
                ? $this->generateUrl('soul_view_town', ['sid' => $preset->getEvent()->getOwner()->getId(), 'idtown' => $ranking->getId(), 'return_path' => 'soul_events'])
                : null,
            'forum_link' => $has_forum_access
                ? $this->generateUrl( 'forum_view', ['id' => $preset->getTown()?->getForum()?->getId()] )
                : null,
            'active' => $preset->getTown() !== null,
            'population' => $preset->getTown()?->getPopulation(),
            'filled' => $preset->getTown()?->getCitizenCount(),
            'living' => $preset->getTown()?->getAliveCitizenCount(),
            'day' => $preset->getTown()?->getDay() ?? $ranking?->getDays() ?? null
        ];
    }

    /**
     * @param CommunityEvent $event
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '/{id}/towns', name: 'list-town-presets', methods: ['GET'])]
    public function list_town_presets(
        CommunityEvent $event,
        EntityManagerInterface $em,
        PermissionHandler $perm,
    ): JsonResponse {
        if (!$this->eventIsExplorable( $event ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        return new JsonResponse(['towns' => array_map(
                                    fn(CommunityEventTownPreset $preset) => [
                                        'uuid' => $preset->getId(),
                                        'name' => $preset->getHeader()['townName'] ?? null,
                                        'lang' => $preset->getHeader()['townLang'] ?? 'multi',
                                        'password' => $preset->getHeader()['townCode'] ?? null,
                                        'type' => $this->translator->trans(
                                            $em->getRepository(TownClass::class)->findOneBy(['name' => $preset->getHeader()['townBase'] ?? $preset->getHeader()['townType'] ?? TownClass::DEFAULT])->getLabel() ?? '',
                                            [], 'game'
                                        ),
                                        'instance' => $this->getTownInstanceData( $preset, $em, $perm )
                                    ],
                                    $event->getTownPresets()->toArray()
                                )]);
    }

    /**
     * @param CommunityEvent $event
     * @param CommunityEventTownPreset $preset
     * @param EntityManagerInterface $em
     * @param SanitizeTownConfigAction $sanitizer
     * @return JsonResponse
     */
    #[Route(path: '/{id}/town/{preset}', name: 'get-town-preset', methods: ['GET'])]
    public function get_town_preset(
        #[MapEntity(id: 'id')]
        CommunityEvent $event,
        #[MapEntity(id: 'preset')]
        CommunityEventTownPreset $preset,
        EntityManagerInterface $em,
        SanitizeTownConfigAction $sanitizer
    ): JsonResponse {
        if (!$this->eventIsExplorable( $event ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        if (!$event->getTownPresets()->contains( $preset ))
            return new JsonResponse([], Response::HTTP_NOT_FOUND);

        $fun_resolve_type = fn(string $name) => $em->getRepository(TownClass::class)->findOneBy(['name' => $name])?->getId() ?? null;

        $header = $preset->getHeader();
        if (isset($header['townType'])) $header['townType'] = $fun_resolve_type($header['townType']);
        if (isset($header['townBase'])) $header['townBase'] = $fun_resolve_type($header['townBase']);

        return new JsonResponse([
            'uuid' => $preset->getId(),
            'header' => $header,
            'rules'  => $sanitizer->restore( $preset->getRules() ),
        ]);
    }

    /**
     * @param bool $create
     * @param CommunityEvent $event
     * @param CommunityEventTownPreset|null $preset
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param SanitizeTownConfigAction $sanitizeTownConfigAction
     * @return JsonResponse
     */
    #[Route(path: '/{id}/town', name: 'create-town-preset', methods: ['PUT'], defaults: ['create' => true])]
    #[Route(path: '/{id}/town/{preset}', name: 'update-town-preset', methods: ['PATCH'], defaults: ['create' => false])]
    public function save_town_preset(
        bool $create,
        #[MapEntity(id: 'id')] CommunityEvent $event,
        #[MapEntity(id: 'preset')] ?CommunityEventTownPreset $preset,
        EntityManagerInterface $em,
        JSONRequestParser $parser,
        SanitizeTownConfigAction $sanitizeTownConfigAction
    ): JsonResponse {

        if (!$create && !$preset) return new JsonResponse([], Response::HTTP_NOT_FOUND);
        if (!$this->eventIsEditable( $event ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        if (!$create && !$event->getTownPresets()->contains( $preset ))
            return new JsonResponse([], Response::HTTP_NOT_FOUND);

        $header = $parser->get_array('header');
        $rules = $parser->get_array('rules');

        $user_slots = [];
        if (!$sanitizeTownConfigAction( $header, $rules, $user_slots ) || !empty($user_slots))
            return new JsonResponse($header, Response::HTTP_UNPROCESSABLE_ENTITY);

        if ($create) {
            $preset = (new CommunityEventTownPreset());
            $event->addTownPreset( $preset );
        }

        $preset->setHeader( $header )->setRules( $rules );

        try {
            $em->persist( $preset );
            $em->flush();
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['uuid' => $preset->getId()]);
    }

    /**
     * @param CommunityEvent $event
     * @param CommunityEventTownPreset $preset
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '/{id}/town/{preset}', name: 'delete-town-preset', methods: ['DELETE'])]
    public function remove_town_preset(
        #[MapEntity(id: 'id')]
        CommunityEvent $event,
        #[MapEntity(id: 'preset')]
        CommunityEventTownPreset $preset,
        EntityManagerInterface $em
    ): JsonResponse {

        if (!$this->eventIsEditable( $event ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        if (!$event->getTownPresets()->contains( $preset ))
            return new JsonResponse([], Response::HTTP_NOT_FOUND);

        try {
            $event->removeTownPreset( $preset );
            $em->remove( $preset );
            $em->flush();
        } catch (Exception) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['uuid' => $event->getId()]);
    }
}
