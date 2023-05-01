<?php

namespace App\Controller\REST\Game;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Toaster;
use App\Controller\CustomAbstractCoreController;
use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\LogEntryTemplate;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Entity\ZoneTag;
use App\Service\Actions\Game\RenderMapAction;
use App\Service\Actions\Game\RenderMapRouteAction;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\LogTemplateHandler;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @Cache(smaxage="43200", mustRevalidate=false, public=true)
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function index(Packages $asset): JsonResponse {
        return new JsonResponse([
            'wrapper' => [
                'day' => $this->translator->trans('Tag', [], 'game'),
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

    protected function applyFilters(Request $request, Citizen $context, ?Criteria $criteria = null ): Criteria {
        $day = $request->query->get('day', $context->getTown()->getDay());
        $limit = $request->query->get('limit', -1);
        $threshold_top = $request->query->get('below', PHP_INT_MAX);
        $threshold_bottom = $request->query->get('above', 0);
        
        return ($criteria ?? Criteria::create())
            ->andWhere( Criteria::expr()->eq('town', $context->getTown()) )
            ->andWhere( Criteria::expr()->gt('id', $threshold_bottom) )
            ->andWhere( Criteria::expr()->lt('id', $threshold_top) )
            ->andWhere( Criteria::expr()->eq('day', $day) )
            ->andWhere( Criteria::expr()->eq('zone', $context->getZone()) )
            ->setMaxResults($limit > 0 ? $limit : null)
            ->orderBy( ['timestamp' => Criteria::DESC, 'id' => Criteria::DESC] );
    }

    /**
     * @param Collection<int, TownLogEntry> $entries
     * @return array
     */
    protected function renderLogEntries(Collection $entries, bool $canHide = false, bool $admin = false): array {
        return $entries->map( function( TownLogEntry $entry ) use ($canHide, $admin): ?array {
            /** @var LogEntryTemplate $template */
            $template = $entry->getLogEntryTemplate();
            if (!$template) return null;

            $entityVariables = $entry->getVariables();
            $json = [
                'timestamp'  => $entry->getTimestamp(),
                'timestring' => $entry->getTimestamp()->format('G:i'),
                'class'     => $template->getClass(),
                'type'      => $template->getType(),
                'id'        => $entry->getId(),
                'hidden'    => $entry->getHidden(),
                'hideable'  => !$admin && !$entry->getHidden() && $canHide
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
                'id' => $entry->getHiddenBy()?->getId()
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
     */
    public function beyond(Request $request, EntityManagerInterface $em): JsonResponse {
        return new JsonResponse( 
            $this->renderLogEntries( 
                $em->getRepository(TownLogEntry::class)->matching( 
                    $this->applyFilters( $request, $this->getUser()->getActiveCitizen() ) 
                ), canHide: false
            )
        );
    }

    /**
     * @Route("/town/{id}", name="town", methods={"GET"})
     * @Toaster()
     * @GateKeeperProfile(only_in_town=true, only_alive=true, only_with_profession=true)
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param Citizen|null $citizen
     * @param UserHandler $userHandler
     * @return JsonResponse
     */
    public function town(Request $request, EntityManagerInterface $em, ?Citizen $citizen = null, UserHandler $userHandler): JsonResponse {

        $active_citizen = $this->getUser()->getActiveCitizen();

        $filter = $request->query->get('filter', '');
        $criteria = $this->applyFilters( $request, $active_citizen );

        if ($citizen) $criteria
            ->andWhere( Criteria::expr()->eq('citizen', $citizen) )
            ->andWhere( Criteria::expr()->neq('hidden', true) );
        if (!empty($filter)) $criteria->andWhere( Criteria::expr()->in('logEntryTemplate', $em->getRepository(LogEntryTemplate::class)->findByTypes(
            explode(',', $filter)
        )) );

        return new JsonResponse(
            $this->renderLogEntries( $em->getRepository(TownLogEntry::class)->matching($criteria),
                canHide: $active_citizen->getAlive() && $active_citizen->getProfession()->getHeroic() && $userHandler->hasSkill($this->getUser(), 'manipulator') && $active_citizen->getSpecificActionCounterValue(ActionCounter::ActionTypeRemoveLog) < $userHandler->getMaximumEntryHidden($this->getUser()) )
        );

    }
}
