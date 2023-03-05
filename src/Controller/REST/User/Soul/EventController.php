<?php

namespace App\Controller\REST\User\Soul;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\CommunityEvent;
use App\Entity\CommunityEventMeta;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\User;
use App\Enum\UserSetting;
use App\Service\JSONRequestParser;
use App\Service\UserHandler;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
                    'delete' => $this->translator->trans('Löschen', [], 'global'),

                    'flags' => array_map( fn($l) => $assets->getUrl("build/images/lang/{$l}.png"), ['de'=>'de','en'=>'en','fr'=>'fr','es'=>'es','multi'=>'multi'] ),
                    'langs' => array_map( fn($l) => $this->translator->trans( $l, [], 'global' ), ['de'=>'Deutsch','en'=>'Englisch','fr'=>'Französisch','es'=>'Spanisch','multi'=>'???'] ),
                ],

                'list' => [
                    'no_events' => $this->translator->trans('Aktuell sind keine Community-Events geplant.', [], 'global'),
                    'default_event' => $this->translator->trans('Neues Event', [], 'global'),

                    'edit_icon' => $assets->getUrl('build/images/forum/edit.png'),

                    'delete_icon' => $assets->getUrl('build/images/icons/small_remove.gif'),
                    'delete_confirm' => $this->translator->trans('Bist du sicher, dass du dieses Event löschen möchtest?', [], 'global')
                ],

                'editor' => [
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
}
