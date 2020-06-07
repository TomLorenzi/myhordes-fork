<?php

namespace App\Controller;

use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenRankingProxy;
use App\Entity\Gazette;
use App\Entity\GazetteLogEntry;
use App\Entity\HeroicActionPrototype;
use App\Entity\HeroSkillPrototype;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\Picto;
use App\Entity\TownLogEntry;
use App\Entity\User;
use App\Entity\ZombieEstimation;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\Locksmith;
use App\Structures\TownConf;
use App\Service\LogTemplateHandler;
use App\Service\TimeKeeperService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class GameController extends AbstractController implements GameInterfaceController
{
    protected $entity_manager;
    protected $translator;
    protected $logTemplateHandler;
    protected $time_keeper;
    protected $citizen_handler;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, LogTemplateHandler $lth, TimeKeeperService $tk, CitizenHandler $ch)
    {
        $this->entity_manager = $em;
        $this->translator = $translator;
        $this->logTemplateHandler = $lth;
        $this->time_keeper = $tk;
        $this->citizen_handler = $ch;
    }

    protected function getActiveCitizen(): Citizen {
        return $this->entity_manager->getRepository(Citizen::class)->findActiveByUser($this->getUser());
    }

    protected function renderLog( ?int $day, $citizen = null, $zone = null, ?int $type = null, ?int $max = null ): Response {
        $entries = [];
        /** @var TownLogEntry $entity */
        foreach ($this->entity_manager->getRepository(TownLogEntry::class)->findByFilter(
            $this->getActiveCitizen()->getTown(),
            $day, $citizen, $zone, $type, $max ) as $idx=>$entity) {
                /** @var LogEntryTemplate $template */
                $template = $entity->getLogEntryTemplate();
                if (!$template)
                    continue;
                $entityVariables = $entity->getVariables();
                if (!$entityVariables)
                    continue;
                $entries[$idx]['timestamp'] = $entity->getTimestamp();
                $entries[$idx]['class'] = $template->getClass();
                $entries[$idx]['type'] = $template->getType();
                $entries[$idx]['id'] = $entity->getId();
                $entries[$idx]['hidden'] = $entity->getHidden();

                $variableTypes = $template->getVariableTypes();
                $transParams = $this->logTemplateHandler->parseTransParams($variableTypes, $entityVariables);

                try {
                    $entries[$idx]['text'] = $this->translator->trans($template->getText(), $transParams, 'game');
                }
                catch (Exception $e) {
                    $entries[$idx]['text'] = "null";
                }             
            }

        // $entries = array($entity->find($id), $entity->find($id)->findRelatedEntity());

        return $this->render( 'ajax/game/log_content.html.twig', [
            'entries' => $entries,
            'canHideEntry' => $this->getActiveCitizen()->getProfession()->getHeroic() && $this->citizen_handler->hasSkill($citizen !== null ? $citizen : $this->getActiveCitizen(), 'manipulator'),
        ] );
    }

    protected function parseGazetteLog(GazetteLogEntry $gazetteLogEntry) {
        return $this->parseLog($gazetteLogEntry->getLogEntryTemplate(), $gazetteLogEntry->getVariables());
    }

    protected function parseLog( LogEntryTemplate $template, array $variables ): String {
        $variableTypes = $template->getVariableTypes();
        $transParams = $this->logTemplateHandler->parseTransParams($variableTypes, $variables, true);

        try {
            $text = $this->translator->trans($template->getText(), $transParams, 'game');
        }
        catch (Exception $e) {
            $text = "null";
        }

        return $text;
    }

    /**
     * @Route("jx/game/landing", name="game_landing")
     * @return Response
     */
    public function landing(): Response
    {
        if (!$this->getActiveCitizen()->getAlive())
            return $this->redirect($this->generateUrl('soul_death'));
        elseif ($this->getActiveCitizen()->getProfession()->getName() === CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_jobs'));
        elseif ($this->getActiveCitizen()->getZone() && !$this->getActiveCitizen()->activeExplorerStats())
            return $this->redirect($this->generateUrl('beyond_dashboard'));
        elseif ($this->getActiveCitizen()->getZone() && $this->getActiveCitizen()->activeExplorerStats())
            return $this->redirect($this->generateUrl('exploration_dashboard'));
        else return $this->redirect($this->generateUrl('town_dashboard'));
    }

    /**
     * @Route("jx/game/raventimes", name="game_newspaper")
     * @return Response
     */
    public function newspaper(): Response {
        if ($this->getActiveCitizen()->getProfession()->getName() === CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_landing'));

        $in_town = $this->getActiveCitizen()->getZone() === null;
        $town = $this->getActiveCitizen()->getTown();
        $day = $town->getDay();
        $death_outside = $death_inside = [];

        /** @var Gazette $gazette */
        $gazette = $this->entity_manager->getRepository(Gazette::class)->findOneByTownAndDay($town, $day);
        if (!$gazette) {
            $gazette = new Gazette();
            $gazette->setTown($town)->setDay($town->getDay());
            $town->addGazette($gazette);
        }

        $survivors = [];
        foreach ($town->getCitizens() as $citizen) {
            if(!$citizen->getAlive()) continue;
            $survivors[] = $citizen;
        }

        foreach ($gazette->getVictims() as $citizen) {
            if($citizen->getAlive()) continue;
            if($citizen->getSurvivedDays() >= $town->getDay() - 1) {
                if ($citizen->getCauseOfDeath()->getRef() == CauseOfDeath::NightlyAttack && $citizen->getDisposed() == 0) {
                    $death_inside[] = $citizen;
                } else {
                    $death_outside[] = $citizen;
                }
            }
        }

        if (count($death_inside) > $gazette->getDeaths()) {
            $gazette->setDeaths(count($death_inside));
        }

        $gazette_logs = $this->entity_manager->getRepository(GazetteLogEntry::class)->findByFilter($gazette);
        $text = '';
        if (count($gazette_logs) == 0) {
            // No Gazette texts! Let's generate some...
            if ($day == 1) {
                // TODO: Turn into LogEntryTemplate
                $text = "<p>Heute Morgen ist kein Artikel erschienen...</p>";
                if ($town->isOpen()){
                    $text .= "<p>Die Stadt wird erst starten, wenn sie <strong>40 Bürger</strong> hat.</p>";
                } else {
                    // Serrez les fesses, citoyens, les zombies nous attaqueront ce soir à minuit !
                    $text .= "";
                }
            } else {
                // 1. TOWN
                $criteria = [
                    'type'  => LogEntryTemplate::TypeGazetteTown,
                    'class' => LogEntryTemplate::ClassGazetteNoDeaths + (count($death_inside) < 3 ? count($death_inside) : 3),
                ];

                $applicableEntryTemplates = $this->entity_manager->getRepository(LogEntryTemplate::class)->findBy($criteria);
                shuffle($applicableEntryTemplates);
                /** @var LogEntryTemplate $townTemplate */
                $townTemplate = $applicableEntryTemplates[array_key_first($applicableEntryTemplates)];
                $requirements = $townTemplate->getSecondaryType();
                $variables = [];
                if ($requirements == GazetteLogEntry::RequiresNothing) {
                    $variables = [];
                }
                elseif (floor($requirements / 10) === 1) {
                    $citizens = $survivors;
                    shuffle($citizens);
                    $variables = [];
                    for ($i = 1; $i <= $requirements - 10; $i++) {
                        $variables['citizen' . $i] = (array_shift($citizens))->getId();
                    }
                }
                elseif (floor($requirements / 10) === 2) {
                    $cadavers = $death_inside;
                    shuffle($cadavers);
                    $variables = [];
                    for ($i = 1; $i <= $requirements - 20; $i++) {
                        $variables['cadaver' . $i] = (array_shift($cadavers))->getId();
                    }
                }
                elseif (floor($requirements / 10) === 3) {
                    $citizens = $survivors;
                    shuffle($citizens);
                    $cadavers = $death_inside;
                    shuffle($cadavers);
                    $variables = [];
                    for ($i = 1; $i <= $requirements - 30; $i++) {
                        $variables['citizen' . $i] = (array_shift($citizens))->getId();
                    }
                    for ($i = 1; $i <= $requirements - 30; $i++) {
                        $variables['cadaver' . $i] = (array_shift($cadavers))->getId();
                    }
                }
                elseif ($requirements == GazetteLogEntry::RequiresAttack) {
                    $variables = [];
                    $attack = $gazette->getAttack();
                    $variables['attack'] = $attack < 2000 ? 10 * (round($attack / 10)) : 100 * (round($attack / 100));
                }
                elseif ($requirements == GazetteLogEntry::RequiresDefense) {
                    $variables = [];
                    $defense = $gazette->getDefense();
                    $variables['defense'] = $defense < 2000 ? 10 * (round($defense / 10)) : 100 * (round($defense / 100));
                }
                elseif ($requirements == GazetteLogEntry::RequiresDeaths) {
                    $variables = [];
                    $variables['deaths'] = $gazette->getDeaths();
                }

                $news = new GazetteLogEntry();
                $news->setDay($day)->setGazette($gazette)->setLogEntryTemplate($townTemplate)->setVariables($variables);
                $this->entity_manager->persist($news);
                $text .= '<p>' . $this->parseGazetteLog($news) . '</p>';

                // TODO: Add more texts.
                // 2. INDIVIDUAL DEATHS
                if (count($death_outside) > 0) {
                    $other_deaths = $death_outside;
                    shuffle($other_deaths);
                    /** @var Citizen $featured_cadaver */
                    $featured_cadaver = $other_deaths[array_key_first($other_deaths)];
                    switch ($featured_cadaver->getCauseOfDeath()->getId()) {
                        case CauseOfDeath::Cyanide:
                        case CauseOfDeath::Strangulation:
                            $class = LogEntryTemplate::ClassGazetteSuicide;
                            break;

                        case CauseOfDeath::Addiction:
                            $class = LogEntryTemplate::ClassGazetteAddiction;
                            break;

                        case CauseOfDeath::Dehydration:
                            $class = LogEntryTemplate::ClassGazetteDehydration;
                            break;

                        case CauseOfDeath::Poison:
                            $class = LogEntryTemplate::ClassGazettePoison;
                            break;

                        case CauseOfDeath::Vanished:
                        default:
                            $class = LogEntryTemplate::ClassGazetteVanished;
                            break;

                    }
                    $criteria = [
                        'type' => LogEntryTemplate::TypeGazetteTown,
                        'class' => $class,
                    ];
                    $applicableEntryTemplates = $this->entity_manager->getRepository(LogEntryTemplate::class)->findBy($criteria);
                    shuffle($applicableEntryTemplates);
                    /** @var LogEntryTemplate $townTemplate */
                    $townTemplate = $applicableEntryTemplates[array_key_first($applicableEntryTemplates)];
                    $requirements = $townTemplate->getSecondaryType();
                    // TODO: Needs refactoring!
                    if ($requirements == GazetteLogEntry::RequiresNothing) {
                        $variables = [];
                    }
                    elseif (floor($requirements / 10) == 1) {
                        $citizens = $survivors;
                        shuffle($citizens);
                        $variables = [];
                        for ($i = 1; $i <= $requirements - 10; $i++) {
                            $variables['citizen' . $i] = (array_shift($citizens))->getId();
                        }
                    }
                    elseif (floor($requirements / 10) == 2) {
                        $cadavers = $death_outside;
                        shuffle($cadavers);
                        $variables = [];
                        for ($i = 1; $i <= $requirements - 20; $i++) {
                            $variables['cadaver' . $i] = (array_shift($cadavers))->getId();
                        }
                    }
                    elseif (floor($requirements / 10) == 3) {
                        $citizens = $survivors;
                        shuffle($citizens);
                        $cadavers = $death_outside;
                        shuffle($cadavers);
                        $variables = [];
                        for ($i = 1; $i <= $requirements - 30; $i++) {
                            $variables['citizen' . $i] = (array_shift($citizens))->getId();
                        }
                        for ($i = 1; $i <= $requirements - 30; $i++) {
                            $variables['cadaver' . $i] = (array_shift($cadavers))->getId();
                        }
                    }
                    elseif ($requirements == GazetteLogEntry::RequiresAttack) {
                        $variables = [];
                        $attack = $gazette->getAttack();
                        $variables['attack'] = $attack < 2000 ? 10 * (round($attack / 10)) : 100 * (round($attack / 100));
                    }
                    elseif ($requirements == GazetteLogEntry::RequiresDefense) {
                        $variables = [];
                        $defense = $gazette->getDefense();
                        $variables['defense'] = $defense < 2000 ? 10 * (round($defense / 10)) : 100 * (round($defense / 100));
                    }
                    elseif ($requirements == GazetteLogEntry::RequiresDeaths) {
                        $variables = [];
                        $variables['deaths'] = $gazette->getDeaths();
                    }

                    $news = new GazetteLogEntry();
                    $news->setDay($day)->setGazette($gazette)->setLogEntryTemplate($townTemplate)->setVariables($variables);
                    $this->entity_manager->persist($news);
                    $text .= '<p>' . $this->parseGazetteLog($news) . '</p>';
                }

                // 3. TOWN DEVASTATION
                // 4. FLAVOURS
                // 5. ELECTION
                // 6. SEARCH TOWER
                
                $this->entity_manager->flush();
            }
        }
        else {
            while (count($gazette_logs) > 0) {
                $text .= '<p>' . $this->parseGazetteLog(array_shift($gazette_logs)) . '</p>';
            }

        }
        $textClass = "day$day";

        $days = [
            'final' => $day % 5,
            'repeat' => floor($day / 5),
        ];

        $attack = -1;
        $defense = -1;
        $attack = $gazette->getAttack();
        $defense = $gazette->getDefense();

        $citizenWithRole = $this->entity_manager->getRepository(Citizen::class)->findCitizenWithRole($town);

        $gazette_info = [
            'season_version' => 0,
            'season_label' => "Betapropine FTW",
            'name' => $town->getName(),
            'open' => $town->isOpen(),
            'day' => $day,
            'days' => $days,
            'devast' => $town->getDevastated(),
            'chaos' => $town->getChaos(),
            'door' => $gazette->getDoor(),
            'death_outside' => $death_outside,
            'death_inside' => $death_inside,
            'attack' => $gazette->getAttack(),
            'defense' => $gazette->getDefense(),
            'invasion' => $gazette->getInvasion(),
            'deaths' => count($death_inside),
            'terror' => $gazette->getTerror(),
            'text' => $text,
            'textClass' => $textClass,
        ];

        $show_register = $in_town || !$this->getActiveCitizen()->getAlive();

        return $this->render( 'ajax/game/newspaper.html.twig', [
            'show_register'  => $show_register,
            'show_town_link'  => $in_town,
            'day' => $town->getDay(),
            'log' => $show_register ? $this->renderLog( -1, null, false, null, 50 )->getContent() : "",
            'gazette' => $gazette_info,
            'citizenWithRole' => $citizenWithRole,
            'clock' => [
                'desc'      => $this->getActiveCitizen()->getTown()->getName(),
                'day'       => $this->getActiveCitizen()->getTown()->getDay(),
                'timestamp' => new DateTime('now'),
                'attack'    => $this->time_keeper->secondsUntilNextAttack(null, true),
                'towntype'  => $this->getActiveCitizen()->getTown()->getType()->getName(),
            ],
        ] );
    }

    /**
     * @Route("api/game/raventimes/log", name="game_newspaper_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_newspaper_api(JSONRequestParser $parser): Response {
        return $this->renderLog((int)$parser->get('day', -1), null, false, null, null);
    }

    /**
     * @Route("api/game/raventimes/debugtest", name="game_debugtest")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function debug_test(JSONRequestParser $parser, LogTemplateHandler $lth): Response {
        $user = $this->getUser();
        $citizen = $user->getActiveCitizen();
        $town = $citizen->getTown();

        $this->entity_manager->persist($lth->nightlyAttackWatchers($town));
        $this->entity_manager->flush();
        return $this->newspaper();
    }


    /**
     * @Route("jx/game/jobcenter", name="game_jobs")
     * @return Response
     */
    public function job_select(): Response
    {
        if ($this->getActiveCitizen()->getProfession()->getName() !== CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_landing'));

        return $this->render( 'ajax/game/jobs.html.twig', [
            'professions' => $this->entity_manager->getRepository(CitizenProfession::class)->findSelectable()
        ] );
    }

    const ErrorJobAlreadySelected = ErrorHelper::BaseJobErrors + 1;
    const ErrorJobInvalid         = ErrorHelper::BaseJobErrors + 2;

    /**
     * @Route("api/game/job", name="api_jobcenter")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $invh
     * @param ItemFactory $if
     * @param ConfMaster $cf
     * @return Response
     */
    public function job_select_api(JSONRequestParser $parser, InventoryHandler $invh, ItemFactory $if, ConfMaster $cf): Response {

        $citizen = $this->getActiveCitizen();
        if ($citizen->getProfession()->getName() !== CitizenProfession::DEFAULT)
            return AjaxResponse::error(self::ErrorJobAlreadySelected);

        if (!$parser->has('job')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $job_id = (int)$parser->get('job', -1);
        if ($job_id <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var CitizenProfession $new_profession */
        $new_profession = $this->entity_manager->getRepository(CitizenProfession::class)->find( $job_id );
        if (!$new_profession) return AjaxResponse::error(self::ErrorJobInvalid);

        $this->citizen_handler->applyProfession( $citizen, $new_profession );

        if($new_profession->getHeroic()) {
            $skills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getUnlocked($citizen->getUser()->getHeroDaysSpent());
            $inventory = $citizen->getInventory();
            $item = $if->createItem( "photo_3_#00" );
            $item->setEssential(true);
            $null = null;
            $invh->transferItem($citizen,$item,$null,$inventory);
            foreach ($skills as $skill) {
                switch($skill->getName()){
                    case "brothers":
                        //TODO: add the heroic power
                        break;
                    case "resourcefulness":
                        $invh->forceMoveItem( $citizen->getHome()->getChest(), $if->createItem( 'chest_hero_#00' ) );
                        break;
                    case "largechest1":
                    case "largechest2":
                        $citizen->getHome()->setAdditionalStorage($citizen->getHome()->getAdditionalStorage() + 1);
                        break;
                    case "secondwind":
                        $heroic_action = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneByName("hero_generic_ap");
                        $citizen->addHeroicAction($heroic_action);
                        $this->entity_manager->persist($citizen);
                        break;
                    case 'breakfast1':
                        $invh->forceMoveItem( $citizen->getHome()->getChest(), $if->createItem( 'food_bag_#00' ) );
                        break;
                    case 'medicine1':
                        $invh->forceMoveItem( $citizen->getHome()->getChest(), $if->createItem( 'disinfect_#00' ) );
                        break;
                    case "cheatdeath":
                        $heroic_action = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneByName("hero_generic_immune");
                        $citizen->addHeroicAction($heroic_action);
                        $this->entity_manager->persist($citizen);
                        break;
                    case 'architect':
                        $invh->forceMoveItem( $citizen->getHome()->getChest(), $if->createItem( 'bplan_c_#00' ) );
                        break;
                    case 'luckyfind':
                        $oldfind = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneByName("hero_generic_find");
                        $citizen->removeHeroicAction($oldfind);
                        $newfind = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneByName("hero_generic_find_lucky");
                        $citizen->removeHeroicAction($newfind);
                        break;
                }
            }
        }

        try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        $item_spawns = $cf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_DEFAULT_CHEST_ITEMS, []);
        $chest = $citizen->getHome()->getChest();
        foreach ($item_spawns as $spawn)
            $invh->placeItem($citizen, $if->createItem($this->entity_manager->getRepository(ItemPrototype::class)->findOneByName($spawn)), [$chest]);

        try {
            $this->entity_manager->persist( $chest );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/game/delete_log_entry", name="delete_log_entry")
     * @param JSONRequestParser $parser
     * @param CitizenHandler $ch
     * @return Response
     */
    public function delete_log_entry(JSONRequestParser $parser, CitizenHandler $ch): Response {

        $citizen = $this->getActiveCitizen();
        $counter = $citizen->getSpecificActionCounter(ActionCounter::ActionTypeRemoveLog);

        if(!$citizen->getProfession()->getHeroic() || !$this->citizen_handler->hasSkill($citizen, 'manipulator')){
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
        }

        if(!$parser->has('log_entry_id'))
            return AjaxResponse::ErrorInvalidRequest;

        $log = $this->entity_manager->getRepository(TownLogEntry::class)->find($parser->get('log_entry_id'));
        if($log->getHidden()){
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
        }

        $limit = 0;
        if($this->citizen_handler->hasSkill($citizen, 'manipulator'))
            $limit = 2;

        if($this->citizen_handler->hasSkill($citizen, 'treachery'))
            $limit = 4;

        if($counter->getCount() < $limit){
            $counter->setCount($counter->getCount() + 1);
        } else {
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
        }

        $log->setHidden(true);

        try {
            $this->entity_manager->persist( $log );
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        try {
            $this->entity_manager->persist( $chest );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            
        }

        return AjaxResponse::success();
    }
}
