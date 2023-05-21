<?php

namespace App\Controller\REST\Game;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Toaster;
use App\Controller\BeyondController;
use App\Controller\CustomAbstractCoreController;
use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\LogEntryTemplate;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Entity\ZoneTag;
use App\Response\AjaxResponse;
use App\Service\Actions\Game\RenderMapAction;
use App\Service\Actions\Game\RenderMapRouteAction;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @Route("/rest/v1/game/log", name="rest_game_log_", condition="request.headers.get('Accept') === 'application/json'")
 * @IsGranted("ROLE_USER")
 */
class LogController extends CustomAbstractCoreController
{

    public function __construct(
        ConfMaster $conf,
        TranslatorInterface $translator,
        private readonly LogTemplateHandler $handler,
    )
    {
        parent::__construct($conf, $translator);
    }

    /**
     * @Route("", name="base", methods={"GET"})
     * @Route("/index", name="base_index", methods={"GET"})
     * @param Packages $asset
     * @return JsonResponse
     */
    public function index(Packages $asset): JsonResponse {
        return new JsonResponse([
            'wrapper' => [
                'day' => $this->translator->trans('Tag', [], 'game'),
            ],

            'chat' => [
                'placeholder' => $this->translator->trans('Deine Nachricht', [], 'global'),
                'send' => $this->translator->trans('Senden', [], 'game'),
            ],

            'content' => [
                'header' => $this->translator->trans('Tag {d} {today}', [], 'game'),
                'header_part_today' => $this->translator->trans('heute', [], 'game'),
                'empty' => $this->translator->trans('Heute ist nichts Erwähnenswertes passiert...', [], 'game'),
                'silence_hours' => $this->translator->trans('(seit {num} Stunden herrscht völlige Flaute)', [], 'game'),
                'silence_hour' => $this->translator->trans('(über eine Stunde ohne besondere Vorkommnisse)', [], 'game'),
                'silence' => $this->translator->trans('({num} Minuten Stille)', [], 'game'),

                'hide' => $this->translator->trans('Klick hier, wenn du mit deiner Heldenfähigkeit <strong>diesen Registereintrag fälschen</strong> möchtest...', [], 'game'),
                'hidden' => $this->translator->trans('Dieser Registereintrag wurde durchgestrichen und ist nicht mehr lesbar! Wer wollte damit etwas verbergen?', [], 'game'),

                'warning' => $asset->getUrl( 'build/images/icons/small_warning.gif' ),
                'falsify' => $asset->getUrl( 'build/images/heroskill/small_falsify.gif' ),

                'protected' => $this->translator->trans('Dieser Registereintrag kann <strong>nicht</strong> gefälscht werden.', [], 'game'),
                'manipulated' => $this->translator->trans('Du hast heimlich einen Eintrag im Register unkenntlich gemacht... Du kannst das noch {times} mal tun.', [], 'game'),

                'hiddenBy' => $this->translator->trans('Versteckt von {player}', [], 'admin'),

                'more' => $this->translator->trans('Alle Einträge anzeigen', [], 'game'),
                'flavour' => [
                    $this->translator->trans('Akteneinsicht wird beantragt...', [], 'game'),
                    $this->translator->trans('Protokolle werden analysiert...', [], 'game'),
                    $this->translator->trans('Überwachungsapparat wird ausgewertet...', [], 'game'),
                    $this->translator->trans('Belastende Beweise werden übermittelt...', [], 'game'),
                ]
            ]
        ]);
    }

    /**
     * @throws Exception
     */
    protected function applyFilters(Request $request, Citizen|Zone|Town $context, ?Criteria $criteria = null, bool $limits = true, bool $allow_inline_days = false, bool $admin = false ): Criteria {
        $day = $request->query->get('day', 0);
        $limit = $request->query->get('limit', -1);
        $threshold_top = $request->query->get('below', PHP_INT_MAX);
        $threshold_bottom = $request->query->get('above', 0);

        if (!$admin && (is_a($context, Zone::class) || is_a($context, Town::class)))
            throw new Exception('Cannot use non-citizen bound context when not in admin mode!');

        $town = is_a($context, Town::class) ? $context : $context->getTown();
        $zone = match (true) {
            is_a($context, Zone::class) => $context,
            is_a($context, Citizen::class) => $context->getZone(),
            default => null
        };

        $criteria = ($criteria ?? Criteria::create())
            ->andWhere( Criteria::expr()->eq('town', $town) )
            ->andWhere( Criteria::expr()->gt('id', $threshold_bottom) )
            ->andWhere( Criteria::expr()->lt('id', $threshold_top) )
            ->andWhere( Criteria::expr()->eq('zone', $zone ) )
            ->setMaxResults(($limit > 0 && $limits) ? $limit : null)
            ->orderBy( ['timestamp' => Criteria::DESC, 'id' => Criteria::DESC] );

        if ($day <= 0 && !$allow_inline_days) $day = $context->getTown()->getDay();
        if ($day > 0) $criteria->andWhere( Criteria::expr()->eq('day', $day) );

        if (!$admin) $criteria->andWhere( Criteria::expr()->eq('adminOnly', false) );

        return $criteria;
    }

    /**
     * @param Collection<int, TownLogEntry> $entries
     * @param bool $canHide
     * @param bool $admin
     * @return array
     */
    protected function renderLogEntries(Collection $entries, bool $canHide = false, bool $admin = false): array {
        return $entries->map( function( TownLogEntry $entry ) use ($canHide, $admin): ?array {
            /** @var LogEntryTemplate $template */
            $template = $entry->getLogEntryTemplate();
            if (!$template) return null;

            $entityVariables = $entry->getVariables();
            $json = [
                'timestamp'  => $entry->getTimestamp()->getTimestamp(),
                'timestring' => $entry->getTimestamp()->format('G:i'),
                'class'     => $template->getClass(),
                'type'      => $template->getType(),
                'protected' => $template->getType() === LogEntryTemplate::TypeNightly,
                'id'        => $entry->getId(),
                'hidden'    => $entry->getHidden(),
                'hideable'  => !$admin && !$entry->getHidden() && $canHide,
                'day'       => $entry->getDay(),
                'retro'     => $template->getName() === 'smokeBombUsage'
            ];

            if ($entry->getHidden() && !$admin) $json['text'] = null;
            else {
                $variableTypes = $template->getVariableTypes();
                $transParams = $this->handler->parseTransParams($variableTypes, $entityVariables);
                try {
                    $json['text'] = $this->translator->trans($template->getText(), $transParams, 'game');
                }
                catch (\Throwable) {
                    $json['text'] = "null";
                }
            }

            if ($admin && $entry->getHidden()) $json['hiddenBy'] = [
                'name' => $entry->getHiddenBy()?->getName(),
                'id' => $entry->getHiddenBy()?->getUser()->getId()
            ];

            return $json;
        } )->filter(fn($v) => $v !== null)->toArray();
    }

    /**
     * @Route("/beyond", name="beyond", methods={"GET"})
     * @Toaster()
     * @GateKeeperProfile(only_alive=true, only_beyond=true)
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws Exception
     */
    public function beyond(Request $request, EntityManagerInterface $em): JsonResponse {
        return new JsonResponse([
            'entries' => $this->renderLogEntries(
                $em->getRepository(TownLogEntry::class)->matching(
                    $this->applyFilters( $request, $this->getUser()->getActiveCitizen(), allow_inline_days: true )
                ), canHide: false
            ),
            'total' => $em->getRepository(TownLogEntry::class)->matching(
                $this->applyFilters( $request, $this->getUser()->getActiveCitizen(), limits: false, allow_inline_days: true )
            )->count(),
            'manipulations' => 0
        ]);
    }

    protected function getManipulationsLeft(Citizen $citizen, UserHandler $userHandler): int {
        return $citizen->getAlive() && $citizen->getProfession()->getHeroic() && $userHandler->hasSkill($this->getUser(), 'manipulator')
            ? max(0, ($userHandler->getMaximumEntryHidden($this->getUser()) - $citizen->getSpecificActionCounterValue(ActionCounter::ActionTypeRemoveLog)))
            : 0;
    }

    /**
     * @Route("/town", name="town", methods={"GET"})
     * @Route("/citizen/{id}", name="town_citizen", methods={"GET"})
     * @Toaster()
     * @GateKeeperProfile(only_in_town=true, only_alive=true, only_with_profession=true)
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param Citizen|null $citizen
     * @param UserHandler $userHandler
     * @return JsonResponse
     * @throws Exception
     */
    public function town(Request $request, EntityManagerInterface $em, UserHandler $userHandler, ?Citizen $citizen = null): JsonResponse {

        $active_citizen = $this->getUser()->getActiveCitizen();

        $filter = $request->query->get('filter', '');
        $criteria = $this->applyFilters( $request, $active_citizen );
        $countCriteria = $this->applyFilters( $request, $active_citizen, limits: false );

        foreach ([$criteria,$countCriteria] as &$c) {
            if ($citizen) $c
                ->andWhere(Criteria::expr()->eq('citizen', $citizen))
                ->andWhere(Criteria::expr()->neq('hidden', true));
            if (!empty($filter)) $c->andWhere(Criteria::expr()->in('logEntryTemplate', $em->getRepository(LogEntryTemplate::class)->findByTypes(
                explode(',', $filter)
            )));
        }

        return new JsonResponse([
            'entries' => $this->renderLogEntries( $em->getRepository(TownLogEntry::class)->matching( $criteria ), canHide: true ),
            'total' => $em->getRepository(TownLogEntry::class)->matching( $countCriteria )->count(),
            'manipulations' => $this->getManipulationsLeft( $active_citizen, $userHandler )
        ]);

    }

    /**
     * @Route("/{id}", name="delete_log", methods={"DELETE"})
     * @Toaster()
     * @GateKeeperProfile(only_in_town=true, only_alive=true, only_with_profession=true)
     * @param TownLogEntry $entry
     * @param UserHandler $userHandler
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function delete_log(TownLogEntry $entry, UserHandler $userHandler, EntityManagerInterface $em): JsonResponse {
        $active_citizen = $this->getUser()->getActiveCitizen();

        $manipulations = $this->getManipulationsLeft($active_citizen, $userHandler);

        if ($manipulations <= 0)
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        if ($entry->getZone() || $entry->getHidden() || $entry->getTown() !== $active_citizen->getTown() || $entry->getLogEntryTemplate()->getType() === LogEntryTemplate::TypeNightly)
            return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);

        $entry->setHidden( true )->setHiddenBy( $active_citizen );
        $em->persist( $entry );
        $em->persist( $active_citizen->getSpecificActionCounter(ActionCounter::ActionTypeRemoveLog)->increment() );

        $em->flush();

        return new JsonResponse([
            'entries' => $this->renderLogEntries( new ArrayCollection([$entry]) ),
            'total' => 1,
            'manipulations' => $manipulations - 1
        ]);
    }

    /**
     * @Route("/admin/zone/{id}", name="admin_zone", methods={"GET"})
     * @IsGranted("ROLE_CROW")
     * @param Zone $zone
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws Exception
     */
    public function adminZone(Zone $zone, Request $request, EntityManagerInterface $em): JsonResponse {
        return new JsonResponse([
                                    'entries' => $this->renderLogEntries(
                                        $em->getRepository(TownLogEntry::class)->matching(
                                            $this->applyFilters( $request, $zone, allow_inline_days: true, admin: true )
                                        ), canHide: false, admin: true
                                    ),
                                    'total' => $em->getRepository(TownLogEntry::class)->matching(
                                        $this->applyFilters( $request, $zone, limits: false, allow_inline_days: true, admin: true )
                                    )->count(),
                                    'manipulations' => 0
                                ]);
    }

    /**
     * @Route("/admin/town/{id}", name="admin_town", methods={"GET"})
     * @IsGranted("ROLE_CROW")
     * @param Town $town
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws Exception
     */
    public function adminTown(Town $town, Request $request, EntityManagerInterface $em): JsonResponse {

        $filter = $request->query->get('filter', '');
        $criteria = $this->applyFilters( $request, $town, admin: true );
        $countCriteria = $this->applyFilters( $request, $town, limits: false, admin: true );

        foreach ([$criteria,$countCriteria] as &$c) {
            if (!empty($filter)) $c->andWhere(Criteria::expr()->in('logEntryTemplate', $em->getRepository(LogEntryTemplate::class)->findByTypes(
                explode(',', $filter)
            )));
        }

        return new JsonResponse([
                                    'entries' => $this->renderLogEntries( $em->getRepository(TownLogEntry::class)->matching( $criteria ), canHide: false, admin: true ),
                                    'total' => $em->getRepository(TownLogEntry::class)->matching( $countCriteria )->count(),
                                    'manipulations' => 0
                                ]);
    }

    /**
     * @Route("/chat/{id}", name="chat", methods={"PUT"})
     * @Toaster()
     * @GateKeeperProfile(only_alive=true, only_beyond=true)
     * @param Zone $zone
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param HTMLService $html
     * @param CitizenHandler $citizenHandler
     * @param LogTemplateHandler $log
     * @return JsonResponse
     */
    public function chat(Zone $zone, JSONRequestParser $parser, EntityManagerInterface $em, HTMLService $html, CitizenHandler $citizenHandler, LogTemplateHandler $log): JsonResponse {
        $active_citizen = $this->getUser()->getActiveCitizen();
        if ($active_citizen->getZone() !== $zone) return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);

        $message = $parser->get('msg', null);
        if (!$message || mb_strlen($message) < 2 || !$html->htmlPrepare($this->getUser(), 0, ['core_rp'], $message, $active_citizen->getTown(), $insight) || $insight->text_length < 2 || $insight->text_length > 256 )
            return new JsonResponse(['error' => BeyondController::ErrorChatMessageInvalid], Response::HTTP_NOT_ACCEPTABLE);

        $message = $html->htmlDistort( $message,
                                       ($citizenHandler->hasStatusEffect($active_citizen, 'drunk') ? HTMLService::ModulationDrunk : HTMLService::ModulationNone) |
                                       ($citizenHandler->hasStatusEffect($active_citizen, 'terror') ? HTMLService::ModulationTerror : HTMLService::ModulationNone) |
                                       ($citizenHandler->hasStatusEffect($active_citizen, 'wound1') ? HTMLService::ModulationHead : HTMLService::ModulationNone)
            , $active_citizen->getTown()->getRealLanguage($this->generatedLangsCodes) ?? $this->getUserLanguage(), $d );


        $em->persist( $log->beyondChat( $active_citizen, $message ) );
        $em->flush(  );

        return AjaxResponse::success();
    }
}
