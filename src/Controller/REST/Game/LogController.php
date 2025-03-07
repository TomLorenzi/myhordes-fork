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
use App\Entity\Zone;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\Game\LogHiddenType;
use App\Response\AjaxResponse;
use App\Service\Actions\Cache\CalculateBlockTimeAction;
use App\Service\Actions\Cache\InvalidateLogCacheAction;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\UserHandler;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


#[Route(path: '/rest/v1/game/log', name: 'rest_game_log_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
class LogController extends CustomAbstractCoreController
{

    public function __construct(
        ConfMaster $conf,
        TranslatorInterface $translator,
        private readonly LogTemplateHandler $handler,
        private readonly TagAwareCacheInterface $gameCachePool,
        private readonly CalculateBlockTimeAction $blockTime
    )
    {
        parent::__construct($conf, $translator);
    }

    /**
     * @param Packages $asset
     * @return JsonResponse
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    #[Route(path: '/index', name: 'base_index', methods: ['GET'])]
    #[GateKeeperProfile('skip')]
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
                'purge' => $this->translator->trans('Klick hier, wenn du mit deiner Heldenfähigkeit <strong>diesen Registereintrag löschen</strong> möchtest...', [], 'game'),
                'hidden' => $this->translator->trans('Dieser Registereintrag wurde durchgestrichen und ist nicht mehr lesbar! Wer wollte damit etwas verbergen?', [], 'game'),

                'warning' => $asset->getUrl( 'build/images/icons/small_warning.gif' ),
                'falsify' => $asset->getUrl( 'build/images/heroskill/small_falsify.gif' ),
                'purgify' => $asset->getUrl( 'build/images/heroskill/small_purgify.gif' ),

                'protected' => $this->translator->trans('Dieser Registereintrag kann <strong>nicht</strong> gefälscht werden.', [], 'game'),
                'manipulated' => $this->translator->trans('Du hast heimlich einen Eintrag im Register unkenntlich gemacht... Du kannst das noch {times} mal tun.', [], 'game'),

                'hiddenBy' => $this->translator->trans('Versteckt von {player}', [], 'admin'),

                'more' => $this->translator->trans('Alle Einträge anzeigen', [], 'game'),
                'noMore' => $this->translator->trans('Es konnten nicht alle Einträge geladen werden, da dieses Register zu viele Einträge enthält.', [], 'game'),
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
    protected function applyFilters(Request $request, Citizen|Zone|Town $context, ?Criteria $criteria = null, bool $limits = true, bool $allow_inline_days = false, bool $admin = false, string &$identifier = null ): Criteria {
        $day = $request->query->get('day', 0);
        $limit = min((int)$request->query->get('limit', -1), 1500);
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
            ->setMaxResults(($limit > 0 && $limits) ? $limit : 1500)
            ->orderBy( ['timestamp' => Order::Descending, 'id' => Order::Descending] );

        $identifier = "t{$town->getId()}_";
        if ($zone) $identifier .= "z{$zone->getId()}_";
        else $identifier .= "zz_";

        if ($day <= 0 && !$allow_inline_days) $day = $town->getDay();
        if ($day > 0) {
            $criteria->andWhere(Criteria::expr()->eq('day', $day));
            $identifier .= "d{$day}_";
        } else $identifier .= "dd_";

        if (!$admin) {
            $criteria->andWhere(Criteria::expr()->eq('adminOnly', false));
            $criteria->andWhere(Criteria::expr()->neq('hidden', LogHiddenType::Deleted));
            $identifier .= 'p';
        } else $identifier = 'a';

        return $criteria;
    }

    /**
     * @param Collection<int, TownLogEntry> $entries
     * @param bool $canHide
     * @param bool $admin
     * @return array
     */
    protected function renderLogEntries(Collection $entries, bool $canHide = false, bool $admin = false): array {
        return array_values($entries->map( function( TownLogEntry $entry ) use ($canHide, $admin): ?array {

            if (!$admin && $entry->getHidden() === LogHiddenType::Deleted)
                return null;

            /** @var LogEntryTemplate $template */
            $template = $entry->getLogEntryTemplate();

            $entityVariables = $entry->getVariables();
            $json = [
                'timestamp'  => $entry->getTimestamp()->getTimestamp(),
                'timestring' => $entry->getTimestamp()->format('G:i'),
                'class'     => $template?->getClass() ?? LogEntryTemplate::ClassNone,
                'type'      => $template?->getType() ?? LogEntryTemplate::TypeVarious,
                'protected' => $template?->getType() === LogEntryTemplate::TypeNightly,
                'id'        => $entry->getId(),
                'hidden'    => $entry->getHidden()->hidden(),
                'hideable'  => !$admin && $entry->getHidden()->visible() && $canHide,
                'day'       => $entry->getDay(),
                'retro'     => $template?->getName() === 'smokeBombUsage'
            ];

            if ($entry->getHidden()->hidden() && !$admin) $json['text'] = null;
            elseif (!$template) $json['text'] = "-- error: [{$entry->getId()}] unable to load template --";
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

            if ($admin && $entry->getHidden()->hidden()) $json['hiddenBy'] = [
                'name' => $entry->getHiddenBy()?->getName(),
                'id' => $entry->getHiddenBy()?->getUser()->getId()
            ];

            return $json;
        } )->filter(fn($v) => $v !== null)->toArray());
    }

    protected function renderCachedLogEntries( EntityManagerInterface $em, Criteria $filters, string $cache_ident, Zone $zone = null, bool $canHide = false, bool $admin = false): array {
        if ($admin)
            return $this->renderLogEntries(
                $em->getRepository(TownLogEntry::class)->matching(
                    $filters
                ), $canHide, $admin
            );

        /** @var Collection<TownLogEntry> $entries */
        $entries = $em->getRepository(TownLogEntry::class)->matching( $filters );
        if ($entries->isEmpty()) return [];

        $result = [];
        $current_block = ($this->blockTime)(new \DateTime());
        $first = true;
        $h = $canHide ? 'h' : 'n';
        while (!$entries->isEmpty()) {
            $nextElement = $entries->first();
            if ($nextElement === false) break;
            $next = ($this->blockTime)($nextElement->getTimestamp());
            [$c, $entries] = $entries->partition( fn(int $i, TownLogEntry $t) => $t->getTimestamp() >= $next );

            if ($first || $current_block->getTimestamp() === $next->getTimestamp()) $result = $this->renderLogEntries( $c, $canHide, $admin );
            else {
                $key = "logs_{$this->getUser()?->getLanguage()}_{$cache_ident}_{$h}__{$next->getTimestamp()}";
                $cached = $this->gameCachePool->get($key, function (ItemInterface $item) use ($cache_ident, $next, $admin, $canHide, &$c, &$zone) {
                    $tid = $c->first()?->getTown()?->getId();
                    $item->expiresAfter(4320000)->tag([
                                                          'logs', "logs_{$cache_ident}", "logs_{$cache_ident}__{$next->getTimestamp()}", "logs__{$next->getTimestamp()}", "logs__{$tid}__{$next->getTimestamp()}",
                                                          ...($zone ? ["logs__z{$zone->getId()}", "logs__z{$zone->getId()}__{$next->getTimestamp()}"] : [])
                                                      ]);
                    return $this->renderLogEntries($c, $canHide, $admin);
                });

                if (count($cached) !== $c->count()) {
                    $cached = $this->renderLogEntries($c, $canHide, $admin);
                    $this->gameCachePool->delete($key);
                }

                $result = [
                    ...$result, ...$cached
                ];
            }
            $first = false;
        }

        return $result;
    }

    /**
     * @param Zone $zone
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws Exception
     */
    #[Route(path: '/beyond/{id}', name: 'beyond', methods: ['GET'])]
    #[GateKeeperProfile(only_alive: true, only_beyond: true)]
    #[Toaster]
    public function beyond(Zone $zone, Request $request, EntityManagerInterface $em): JsonResponse {
        if ($this->getUser()->getActiveCitizen()?->getZone() !== $zone)
            return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);

        $criteria = $this->applyFilters( $request, $this->getUser()->getActiveCitizen(), allow_inline_days: true, identifier: $cache_ident );

        return new JsonResponse([
            'entries' => $this->renderCachedLogEntries(
                $em, $criteria, $cache_ident, zone: $zone, canHide: false
            ),
            'total' => $em->getRepository(TownLogEntry::class)->matching(
                $this->applyFilters( $request, $this->getUser()->getActiveCitizen(), limits: false, allow_inline_days: true )
            )->count(),
            'manipulations' => 0
        ]);
    }

    protected function getManipulationsLeft(Citizen $citizen, LogHiddenType $type = LogHiddenType::Hidden): int {
        if (!$citizen->getAlive() || $type === LogHiddenType::Visible) return 0;

        [$prop, $counter] = match($type) {
            LogHiddenType::Hidden => [CitizenProperties::LogManipulationLimit, ActionCounter::ActionTypeRemoveLog],
            LogHiddenType::Deleted => [CitizenProperties::LogPurgeLimit, ActionCounter::ActionTypePurgeLog],
            default => [null,null]
        };

        return max(
            0,
                   $citizen->property( $prop ) - $citizen->getSpecificActionCounterValue($counter)
        );
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param Citizen|null $citizen
     * @param UserHandler $userHandler
     * @return JsonResponse
     * @throws Exception
     */
    #[Route(path: '/town', name: 'town', methods: ['GET'])]
    #[Route(path: '/citizen/{id}', name: 'town_citizen', methods: ['GET'])]
    #[GateKeeperProfile(only_with_profession: true, only_in_town: true)]
    #[Toaster]
    public function town(Request $request, EntityManagerInterface $em, ?Citizen $citizen = null): JsonResponse {

        $active_citizen = $this->getUser()->getActiveCitizen();

        $filter = $request->query->get('filter', '');
        $criteria = $this->applyFilters( $request, $active_citizen, identifier: $cache_ident );
        $countCriteria = $this->applyFilters( $request, $active_citizen, limits: false );

        $templates = !empty($filter) ? $em->getRepository(LogEntryTemplate::class)->findByTypes(
            explode(',', $filter)
        ) : null;

        foreach ([$criteria,$countCriteria] as &$c) {
            if ($citizen) $c
                ->andWhere(Criteria::expr()->eq('citizen', $citizen))
                ->andWhere(Criteria::expr()->neq('hidden', true));
            if (!empty($filter)) $c->andWhere(Criteria::expr()->in('logEntryTemplate', $templates));
        }

        if ($citizen) $cache_ident .= "_c{$citizen->getId()}";
        else  $cache_ident .= '_cc';

        if (!empty($filter)) $cache_ident .= '_t' . implode( '+', array_map( fn(LogEntryTemplate $t) => $t->getId(), $templates ) );
        else $cache_ident .= '_tt';

        return new JsonResponse([
            'entries' => $this->renderCachedLogEntries( $em, $criteria, $cache_ident, canHide: true ),
            'total' => $em->getRepository(TownLogEntry::class)->matching( $countCriteria )->count(),
            'manipulations' => $this->getManipulationsLeft( $active_citizen, LogHiddenType::Hidden ),
            'purges' => $this->getManipulationsLeft( $active_citizen, LogHiddenType::Deleted )
        ]);

    }

    /**
     * @param bool $purge
     * @param TownLogEntry $entry
     * @param UserHandler $userHandler
     * @param EntityManagerInterface $em
     * @param InvalidateLogCacheAction $invalidate
     * @return JsonResponse
     */
    #[Route(path: '/{id}', name: 'delete_log_hide', defaults: ['purge' => false], methods: ['DELETE'])]
    #[Route(path: '/{id}/full', name: 'delete_log_purge', defaults: ['purge' => true], methods: ['DELETE'])]
    #[GateKeeperProfile(only_alive: true, only_with_profession: true, only_in_town: true)]
    #[Toaster]
    public function delete_log(bool $purge, TownLogEntry $entry, UserHandler $userHandler, EntityManagerInterface $em, InvalidateLogCacheAction $invalidate): JsonResponse {
        $active_citizen = $this->getUser()->getActiveCitizen();

        $type = $purge ? LogHiddenType::Deleted : LogHiddenType::Hidden;
        $manipulations = $this->getManipulationsLeft($active_citizen, LogHiddenType::Hidden);
        $purges = $this->getManipulationsLeft($active_citizen, LogHiddenType::Deleted);

        if (($purge ? $purges : $manipulations) <= 0)
            return new JsonResponse([], Response::HTTP_FORBIDDEN);

        if ($entry->getZone() || $entry->getHidden()->hidden() || $entry->getTown() !== $active_citizen->getTown() || $entry->getLogEntryTemplate()->getType() === LogEntryTemplate::TypeNightly)
            return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);

        $invalidate($entry);
        $entry->setHidden( $type )->setHiddenBy( $active_citizen );
        $em->persist( $entry );
        $em->persist( $active_citizen->getSpecificActionCounter($purge ? ActionCounter::ActionTypePurgeLog : ActionCounter::ActionTypeRemoveLog)->increment() );

        $em->flush();

        return new JsonResponse([
            'entries' => $this->renderLogEntries( new ArrayCollection([$entry]) ),
            'total' => 1,
            'manipulations' => $manipulations - ($purge ? 0 : 1),
            'purges' => $purges - ($purge ? 1 : 0),
        ]);
    }

    /**
     * @param Zone $zone
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws Exception
     */
    #[Route(path: '/admin/zone/{id}', name: 'admin_zone', methods: ['GET'])]
    #[GateKeeperProfile('skip')]
    #[IsGranted('ROLE_CROW')]
    public function adminZone(Zone $zone, Request $request, EntityManagerInterface $em): JsonResponse {
        $criteria = $this->applyFilters( $request, $zone, allow_inline_days: true, admin: true, identifier: $cache_ident );
        return new JsonResponse([
                                    'entries' => $this->renderCachedLogEntries(
                                        $em, $criteria, $cache_ident, canHide: false, admin: true
                                    ),
                                    'total' => $em->getRepository(TownLogEntry::class)->matching(
                                        $this->applyFilters( $request, $zone, limits: false, allow_inline_days: true, admin: true )
                                    )->count(),
                                    'manipulations' => 0,
                                    'purges' => 0,
                                ]);
    }

    /**
     * @param Town $town
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws Exception
     */
    #[Route(path: '/admin/town/{id}', name: 'admin_town', methods: ['GET'])]
    #[GateKeeperProfile('skip')]
    #[IsGranted('ROLE_CROW')]
    public function adminTown(Town $town, Request $request, EntityManagerInterface $em): JsonResponse {

        $filter = $request->query->get('filter', '');
        $criteria = $this->applyFilters( $request, $town, admin: true, identifier: $cache_ident );
        $countCriteria = $this->applyFilters( $request, $town, limits: false, admin: true );

        foreach ([$criteria,$countCriteria] as &$c) {
            if (!empty($filter)) $c->andWhere(Criteria::expr()->in('logEntryTemplate', $em->getRepository(LogEntryTemplate::class)->findByTypes(
                explode(',', $filter)
            )));
        }

        return new JsonResponse([
                                    'entries' => $this->renderCachedLogEntries( $em, $criteria, $cache_ident, canHide: false, admin: true ),
                                    'total' => $em->getRepository(TownLogEntry::class)->matching( $countCriteria )->count(),
                                    'manipulations' => 0,
                                    'purges' => 0,
                                ]);
    }

    /**
     * @param Zone $zone
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param HTMLService $html
     * @param CitizenHandler $citizenHandler
     * @param LogTemplateHandler $log
     * @return JsonResponse
     */
    #[Route(path: '/chat/{id}', name: 'chat', methods: ['PUT'])]
    #[GateKeeperProfile(only_alive: true, only_beyond: true)]
    #[Toaster]
    public function chat(Zone $zone, JSONRequestParser $parser, EntityManagerInterface $em, HTMLService $html, CitizenHandler $citizenHandler, LogTemplateHandler $log): JsonResponse {
        $active_citizen = $this->getUser()->getActiveCitizen();
        if ($active_citizen->getZone() !== $zone) return new JsonResponse([], Response::HTTP_NOT_ACCEPTABLE);

        $message = $parser->get('msg', null);
        if (!$message || mb_strlen($message) < 2 || !$html->htmlPrepare($this->getUser(), 0, ['core_rp','core_rp_town','core_user'], $message, $active_citizen->getTown(), $insight) || $insight->text_length < 2 || $insight->text_length > 256 )
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
