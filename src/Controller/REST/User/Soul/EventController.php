<?php

namespace App\Controller\REST\User\Soul;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\CommunityEvent;
use App\Entity\CommunityEventMeta;
use App\Entity\CommunityEventTownPreset;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\TownClass;
use App\Entity\User;
use App\Enum\UserSetting;
use App\Service\Actions\Ghost\SanitizeTownConfigAction;
use App\Service\JSONRequestParser;
use App\Service\UserHandler;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use function App\Controller\REST\User\mb_strlen;
use function App\Controller\REST\User\str_contains;


/**
 * @Route("/rest/v1/user/soul/events", name="rest_user_soul_events_", condition="request.headers.get('Accept') === 'application/json'")
 * @IsGranted("ROLE_USER")
 */
class EventController extends CustomAbstractCoreController
{

    /**
     * @Route("/index", name="base", methods={"GET"})
     * @Cache(smaxage="43200", mustRevalidate=false, public=true)
     * @param Packages $assets
     * @return JsonResponse
     */
    public function index(Packages $assets): JsonResponse {
        return new JsonResponse([
            'strings' => [
                'common' => [
                    'create' => $this->translator->trans('Eigenes Event organisieren', [], 'global'),
                    'cancel_create' => $this->translator->trans('Zurück zur Übersicht', [], 'global'),

                    'save' => $this->translator->trans('Speichern', [], 'global'),
                    'cancel' => $this->translator->trans('Abbrechen', [], 'global'),
                    'edit' => $this->translator->trans('Bearbeiten', [], 'global'),
                    'edit_icon' => $assets->getUrl('build/images/forum/edit.png'),
                    'delete' => $this->translator->trans('Löschen', [], 'global'),
                    'delete_icon' => $assets->getUrl('build/images/icons/small_remove.gif'),

                    'flags' => array_map( fn($l) => $assets->getUrl("build/images/lang/{$l}.png"), ['de'=>'de','en'=>'en','fr'=>'fr','es'=>'es','multi'=>'multi'] ),
                    'langs' => array_map( fn($l) => $this->translator->trans( $l, [], 'global' ), ['de'=>'Deutsch','en'=>'Englisch','fr'=>'Französisch','es'=>'Spanisch','multi'=>'???'] ),
                ],

                'list' => [
                    'no_events' => $this->translator->trans('Aktuell sind keine Community-Events geplant.', [], 'global'),
                    'default_event' => $this->translator->trans('Neues Event', [], 'global'),

                    'delete_confirm' => $this->translator->trans('Bist du sicher, dass du dieses Event löschen möchtest?', [], 'global')
                ],

                'towns' => [
                    'title' => $this->translator->trans('Event-Städte', [], 'global'),

                    'no_towns' => $this->translator->trans('Aktuell werden keine Städte mit dem Start des Events automatisch angelegt.', [], 'global'),
                    'default_town' => $this->translator->trans('Automatisch generierter Name', [], 'global'),

                    'table_lang' => $this->translator->trans('Sprache', [], 'global'),
                    'table_town' => $this->translator->trans('Stadt', [], 'game'),
                    'table_act' => $this->translator->trans('Aktionen', [], 'global'),

                    'add' => $this->translator->trans('Stadt hinzufügen', [], 'global'),
                    'delete_confirm' => $this->translator->trans('Bist du sicher, dass du dieses Stadt löschen möchtest?', [], 'global')
                ],

                'editor' => [
                    'title' => $this->translator->trans('Allgemeine Event-Informationen', [], 'global'),
                    'edit' => $this->translator->trans('Event bearbeiten', [], 'global'),
                    'add_meta' => $this->translator->trans('Klicke hier, um eine Eventbeschreibung in {lang} hinzuzufügen.', [], 'global'),

                    'field_title' =>  $this->translator->trans('Event-Titel', [], 'global'),
                    'field_description' =>  $this->translator->trans('Beschreibung des Events', [], 'global'),
                ]
            ]
        ]);
    }

    /**
     * @Route("", name="list", methods={"GET"})
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function listEvents(
        EntityManagerInterface $em
    ): JsonResponse {

        $is_owner = Criteria::expr()->eq('owner', $this->getUser() );

        $events = $em->getRepository(CommunityEvent::class)->matching(
            (Criteria::create())
                // Is owner or event is public
                ->andWhere( Criteria::expr()->orX(
                    $is_owner,
                    Criteria::expr()->neq('starts', null )
                ) )
                // Is owner or is not started or TODO is participant
                ->andWhere( Criteria::expr()->orX(
                    $is_owner,
                    Criteria::expr()->gt( 'starts', new \DateTime() )
                ) )
                // Not expired or no expiration
                ->andWhere( Criteria::expr()->orX(
                    Criteria::expr()->gt( 'expires', new \DateTime() ),
                    Criteria::expr()->isNull( 'expires' )
                ) )
        );

        return new JsonResponse( ['events' => array_map( function(CommunityEvent $e) {
            $meta = $e->getMeta( $this->getUserLanguage(), true );
            return [
                'uuid' => $e->getId(),
                'name' => $meta?->getName(),
                'description' => $meta?->getDescription(),
                'own' => $e->getOwner() === $this->getUser(),
                'published' => $e->getStarts() !== null,
                'expires' => $e->getExpires() !== null,
            ];
        }, $events->toArray() ) ] );
    }

    /**
     * @Route("", name="create", methods={"PUT"})
     * @param UserHandler $userHandler
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function createEvent(
        UserHandler $userHandler,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$userHandler->hasRoles( $this->getUser(), ['ROLE_ADMIN','ROLE_CROW','ROLE_ANIMAC','ROLE_ORACLE'], true ))
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        $em->persist( $event = (new CommunityEvent())
            ->setOwner( $this->getUser() )
            ->setCreated( new \DateTime() )
            ->setExpires( (new \DateTime())->add( \DateInterval::createFromDateString('48hours') ) )
        );

        try {
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['uuid' => $event->getId()]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"DELETE"})
     * @param CommunityEvent $event
     * @param UserHandler $userHandler
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function deleteEvent(
        CommunityEvent $event,
        UserHandler $userHandler,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($event->getOwner() !== $this->getUser())
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

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
        ];
    }

    /**
     * @Route("/{id}/meta", name="list_meta", methods={"GET"})
     * @param CommunityEvent $event
     * @return JsonResponse
     */
    public function listEventMeta(
        CommunityEvent $event
    ): JsonResponse {
        return new JsonResponse( ['meta' => array_map( function(CommunityEventMeta $meta) {
            return $this->metaToJSON($meta);
        }, $event->getMetas()->toArray() ) ] );
    }

    /**
     * @Route("/{id}/meta/{lang<de|en|fr|es>}", name="edit_meta", methods={"PATCH"})
     * @param CommunityEvent $event
     * @param string $lang
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @return JsonResponse
     */
    public function editEventMeta(
        CommunityEvent $event,
        string $lang,
        EntityManagerInterface $em,
        JSONRequestParser $parser,
    ): JsonResponse {
        if ($event->getOwner() !== $this->getUser())
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        if (!$parser->has_all(['name','desc'])) return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $meta = $event->getMeta($lang);
        if (!$meta) $event->addMeta( $meta = (new CommunityEventMeta())
            ->setLang( $lang )
            ->setName( mb_substr( $parser->trimmed( 'name' ), 0, 128 ) )
            ->setDescription( $parser->trimmed('desc') )
        );

        $em->persist($meta);
        try {
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['meta' => $this->metaToJSON($meta)]);
    }

    /**
     * @Route("/{id}/meta/{lang<de|en|fr|es>}", name="delete_meta", methods={"DELETE"})
     * @param CommunityEvent $event
     * @param string $lang
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function deleteEventMeta(
        CommunityEvent $event,
        string $lang,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($event->getOwner() !== $this->getUser())
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

    /**
     * @Route("/{id}/towns", name="list-town-presets", methods={"GET"})
     * @param CommunityEvent $event
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function list_town_presets(
        CommunityEvent $event,
        EntityManagerInterface $em,
    ): JsonResponse {
        return new JsonResponse(['towns' => array_map(
                                    fn(CommunityEventTownPreset $preset) => [
                                        'uuid' => $preset->getId(),
                                        'name' => $preset->getHeader()['townName'] ?? null,
                                        'lang' => $preset->getHeader()['townLang'] ?? 'multi',
                                        'type' => $this->translator->trans(
                                            $em->getRepository(TownClass::class)->findOneBy(['name' => $preset->getHeader()['townBase'] ?? $preset->getHeader()['townType'] ?? TownClass::DEFAULT])->getLabel() ?? '',
                                            [], 'game'
                                        )
                                    ],
                                    $event->getTownPresets()->toArray()
                                )]);
    }

    /**
     * @Route("/{id}/town/{preset}", name="get-town-preset", methods={"GET"})
     * @param CommunityEvent $event
     * @param CommunityEventTownPreset $preset
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function get_town_preset(
        #[MapEntity(id: 'id')]
        CommunityEvent $event,
        #[MapEntity(id: 'preset')]
        CommunityEventTownPreset $preset,
    ): JsonResponse {
        if ($event->getOwner() !== $this->getUser())
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        return new JsonResponse([
            'uuid' => $preset->getId(),
            'header' => $preset->getHeader(),
            'rules'  => $preset->getRules(),
        ]);
    }

    /**
     * @Route("/{id}/town", name="create-town-preset", methods={"PUT"}, defaults={"create"=true})
     * @Route("/{id}/town/{preset}", name="update-town-preset", methods={"PATCH"}, defaults={"create"=false})
     * @param bool $create
     * @param CommunityEvent $event
     * @param CommunityEventTownPreset|null $preset
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param SanitizeTownConfigAction $sanitizeTownConfigAction
     * @return JsonResponse
     */
    public function save_template(
        bool $create,
        #[MapEntity(id: 'id')]
        CommunityEvent $event,
        #[MapEntity(id: 'preset')]
        ?CommunityEventTownPreset $preset,
        EntityManagerInterface $em,
        JSONRequestParser $parser,
        SanitizeTownConfigAction $sanitizeTownConfigAction
    ): JsonResponse {

        if (!$create && !$preset) return new JsonResponse([], Response::HTTP_NOT_FOUND);
        if ($event->getOwner() !== $this->getUser())
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

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
     * @Route("/{id}/town/{preset}", name="delete-town-preset", methods={"DELETE"})
     * @param CommunityEvent $event
     * @param CommunityEventTownPreset $preset
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function remove_template(
        #[MapEntity(id: 'id')]
        CommunityEvent $event,
        #[MapEntity(id: 'preset')]
        CommunityEventTownPreset $preset,
        EntityManagerInterface $em
    ): JsonResponse {

        if ($event->getOwner() !== $this->getUser())
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

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
