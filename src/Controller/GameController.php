<?php

namespace App\Controller;

use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CauseOfDeath;
use App\Entity\Gazette;
use App\Entity\GazetteLogEntry;
use App\Entity\HeroicActionPrototype;
use App\Entity\HeroSkillPrototype;
use App\Entity\ItemPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\SpecialActionPrototype;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class GameController extends CustomAbstractController implements GameInterfaceController
{
    protected $translator;
    protected $logTemplateHandler;
    protected TownHandler $town_handler;
    private $user_handler;
    protected PictoHandler $picto_handler;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, LogTemplateHandler $lth, TimeKeeperService $tk, CitizenHandler $ch, UserHandler $uh, TownHandler $th, ConfMaster $conf, PictoHandler $ph, InventoryHandler $ih)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih);
        $this->translator = $translator;
        $this->logTemplateHandler = $lth;
        $this->user_handler = $uh;
        $this->town_handler = $th;
        $this->picto_handler = $ph;
    }

    protected function getActiveCitizen(): Citizen {
        return $this->entity_manager->getRepository(Citizen::class)->findActiveByUser($this->getUser());
    }

    protected function renderLog( ?int $day, $citizen = null, $zone = null, ?int $type = null, ?int $max = null ): Response {
        $entries = [];
        /** @var TownLogEntry $entity */
        foreach ($this->entity_manager->getRepository(TownLogEntry::class)->findByFilter($this->getActiveCitizen()->getTown(),$day, $citizen, $zone, $type, $max ) as $idx=>$entity) {
            /** @var LogEntryTemplate $template */
            $template = $entity->getLogEntryTemplate();
            if (!$template)
                continue;
            $entityVariables = $entity->getVariables();
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

        return $this->render( 'ajax/game/log_content.html.twig', [
            'entries' => $entries,
            'canHideEntry' => $this->getActiveCitizen()->getAlive() && $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getUser(), 'manipulator'),
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
        elseif (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));
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

        $has_living_citizens = false;
        foreach ( $town->getCitizens() as $c )
            if ($c->getAlive()) {
                $has_living_citizens = true;
                break;
            }

        if (!$has_living_citizens)
            return $this->redirect($this->generateUrl('game_landing'));

        /** @var Gazette $gazette */
        $gazette = $town->findGazette( $day );
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
            if ($citizen->getCauseOfDeath()->getRef() == CauseOfDeath::NightlyAttack)
                $death_inside[] = $citizen;
            else
                $death_outside[] = $citizen;
        }

        if (count($death_inside) > $gazette->getDeaths()) {
            $gazette->setDeaths(count($death_inside));
        }

        $gazette_logs = $this->entity_manager->getRepository(GazetteLogEntry::class)->findByFilter($gazette, LogEntryTemplate::TypeGazetteTown);
        $text = '';
        $wind = "";
        if (count($gazette_logs) == 0) {
            // No Gazette texts! Let's generate some...
            if ($day == 1) {
                // TODO: Turn into LogEntryTemplate
                $text = "<p>" . $this->translator->trans('Heute Morgen ist kein Artikel erschienen...', [], 'game') . "</p>";
                if ($town->isOpen()){
                    $text .= "<p>" . $this->translator->trans('Die Stadt wird erst starten, wenn sie <strong>%population% Bürger hat</strong>.', ['%population%' => $town->getPopulation()], 'game') . "</p>" . "<a class='help-button'>" . "<div class='tooltip help'>" . $this->translator->trans("Falls sich dieser Zustand auch um Mitternacht noch nicht geändert hat, findet kein Zombieangriff statt. Der Tag wird dann künstlich verlängert.", [], 'global') . "</div>" . $this->translator->trans("Hilfe", [], 'global') . "</a>";
                } else {
                    $text .= $this->translator->trans('Fangt schon mal an zu beten, Bürger - die Zombies werden um Mitternacht angreifen!', [], 'game');
                }
            } else {
                // 1. TOWN
                if($town->getDevastated()){
                    $criteria = [
                        'name' => 'gazetteTownDevastated'
                    ];
                } else {
                    $criteria = [
                        'type'  => LogEntryTemplate::TypeGazetteTown,
                        'class' => LogEntryTemplate::ClassGazetteNoDeaths + (count($death_inside) < 3 ? count($death_inside) : 3),
                    ];
                }

                $applicableEntryTemplates = $this->entity_manager->getRepository(LogEntryTemplate::class)->findBy($criteria);
                shuffle($applicableEntryTemplates);
                /** @var LogEntryTemplate $townTemplate */
                $townTemplate = $applicableEntryTemplates[array_key_first($applicableEntryTemplates)];
                $requirements = $townTemplate->getSecondaryType();
                $variables = [];
                if ($requirements == GazetteLogEntry::RequiresNothing) {
                    $variables = [];
                }
                elseif (intval(floor($requirements / 10)) === 1) {
                    $citizens = $survivors;
                    shuffle($citizens);
                    for ($i = 1; $i <= $requirements - 10; $i++) {
                        $variables['citizen' . $i] = (array_shift($citizens))->getId();
                    }
                }
                elseif (intval(floor($requirements / 10)) === 2) {
                    $cadavers = $death_inside;
                    shuffle($cadavers);
                    for ($i = 1; $i <= $requirements - 20; $i++) {
                        $variables['cadaver' . $i] = (array_shift($cadavers))->getId();
                    }
                }
                elseif (intval(floor($requirements / 10)) === 3) {
                    $citizens = $survivors;
                    shuffle($citizens);
                    $cadavers = $death_inside;
                    shuffle($cadavers);
                    for ($i = 1; $i <= $requirements - 30; $i++) {
                        $variables['citizen' . $i] = (array_shift($citizens))->getId();
                    }
                    for ($i = 1; $i <= $requirements - 30; $i++) {
                        $variables['cadaver' . $i] = (array_shift($cadavers))->getId();
                    }
                }
                elseif ($requirements == GazetteLogEntry::RequiresAttack) {
                    $attack = $gazette->getAttack();
                    $variables['attack'] = $attack < 2000 ? 10 * (round($attack / 10)) : 100 * (round($attack / 100));
                }
                elseif ($requirements == GazetteLogEntry::RequiresDefense) {
                    $defense = $gazette->getDefense();
                    $variables['defense'] = $defense < 2000 ? 10 * (round($defense / 10)) : 100 * (round($defense / 100));
                }
                elseif ($requirements == GazetteLogEntry::RequiresDeaths) {
                    $variables['deaths'] = $gazette->getDeaths();
                }

                $news = new GazetteLogEntry();
                $news->setDay($day)->setGazette($gazette)->setLogEntryTemplate($townTemplate)->setVariables($variables);
                $this->entity_manager->persist($news);
                $text .= '<p>' . $this->parseGazetteLog($news) . '</p>';

                // 2. INDIVIDUAL DEATHS
                if (!$town->getDevastated() && count($death_outside) > 0) {
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

                // 3. FLAVOURS
                // 4. ELECTION
                // 5. SEARCH TOWER
                if($gazette->getWindDirection() !== 0) {
                    $criteria = [
                        'type' => LogEntryTemplate::TypeGazetteTownInfo,
                        'class' => LogEntryTemplate::ClassGazetteWind,
                    ];
                    $applicableEntryTemplates = $this->entity_manager->getRepository(LogEntryTemplate::class)->findBy($criteria);
                    shuffle($applicableEntryTemplates);
                    /** @var LogEntryTemplate $townTemplate */
                    $townTemplate = $applicableEntryTemplates[array_key_first($applicableEntryTemplates)];
                    switch ($gazette->getWindDirection()){
                        case Zone::DirectionNorthWest:
                            $variables['sector'] = T::__('Nordwesten', 'game');
                            $variables['sector2'] = T::__('im Nordwesten', 'game');
                            break;
                        case Zone::DirectionNorth:
                            $variables['sector'] = T::__('Norden', 'game');
                            $variables['sector2'] = T::__('im Norden', 'game');
                            break;
                        case Zone::DirectionNorthEast:
                            $variables['sector'] = T::__('Nordosten', 'game');
                            $variables['sector2'] = T::__('im Nordosten', 'game');
                            break;
                        case Zone::DirectionWest:
                            $variables['sector'] = T::__('Westen', 'game');
                            $variables['sector2'] = T::__('im Westen', 'game');
                            break;
                        case Zone::DirectionEast:
                            $variables['sector'] = T::__('Osten', 'game');
                            $variables['sector2'] = T::__('im Osten', 'game');
                            break;
                        case Zone::DirectionSouthWest:
                            $variables['sector'] = T::__('Südwesten', 'game');
                            $variables['sector2'] = T::__('im Südwesten', 'game');
                            break;
                        case Zone::DirectionSouth:
                            $variables['sector'] = T::__('Süden', 'game');
                            $variables['sector2'] = T::__('im Süden', 'game');
                            break;
                        case Zone::DirectionSouthEast:
                            $variables['sector'] = T::__('Südosten', 'game');
                            $variables['sector2'] = T::__('im Südosten', 'game');
                            break;
                    }
                    $news = new GazetteLogEntry();
                    $news->setDay($day)->setGazette($gazette)->setLogEntryTemplate($townTemplate)->setVariables($variables);
                    $this->entity_manager->persist($news);
                    $wind = $this->parseGazetteLog($news);
                }
                $this->entity_manager->flush();
            }
        }
        else {
            while (count($gazette_logs) > 0) {
                $text .= '<p>' . $this->parseGazetteLog(array_shift($gazette_logs)) . '</p>';
            }

            $wind_log = $this->entity_manager->getRepository(GazetteLogEntry::class)->findByFilter($gazette, LogEntryTemplate::TypeGazetteTownInfo, LogEntryTemplate::ClassGazetteWind);
            if(count($wind_log) > 0)
                $wind = $this->parseGazetteLog($wind_log[0]);

        }
        $textClass = "day$day";

        $days = [
            'final' => $day % 5,
            'repeat' => floor($day / 5),
        ];

        $citizensWithRole = $this->entity_manager->getRepository(Citizen::class)->findCitizenWithRole($town);

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
            'wind' => $wind,
            'windDirection' => intval($gazette->getWindDirection()),
            'waterlost' => intval($gazette->getWaterlost()),
        ];

        $show_register = $in_town || !$this->getActiveCitizen()->getAlive();

        $this->getActiveCitizen()->setHasSeenGazette(true);
        $this->entity_manager->persist($this->getActiveCitizen());
        $this->entity_manager->flush();

        return $this->render( 'ajax/game/newspaper.html.twig', [
            'show_register'  => $show_register,
            'show_town_link'  => $in_town,
            'day' => $town->getDay(),
            'log' => $show_register ? $this->renderLog( -1, null, false, null, 50 )->getContent() : "",
            'gazette' => $gazette_info,
            'citizensWithRole' => $citizensWithRole,
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
     * @Route("jx/game/jobcenter", name="game_jobs")
     * @param ConfMaster $cf
     * @return Response
     */
    public function job_select(ConfMaster $cf): Response
    {
        if ($this->getActiveCitizen()->getProfession()->getName() !== CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_landing'));

        $jobs = $this->entity_manager->getRepository(CitizenProfession::class)->findSelectable();

        $disabledJobs = $cf->getTownConfiguration($this->getActiveCitizen()->getTown())->get(TownConf::CONF_DISABLED_JOBS, ['shaman']);

        $selectablesJobs = [];
        $prof_count = [];

        foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $c) {
            if ($c->getProfession()->getName() === CitizenProfession::DEFAULT) continue;

            if (!isset($prof_count[ $c->getProfession()->getId() ])) {
                $prof_count[ $c->getProfession()->getId() ] = [
                    1,
                    $c->getProfession()
                ];
            } else $prof_count[ $c->getProfession()->getId() ][0]++;
        }

        foreach($jobs as $job){
            if(!in_array($job->getName(), $disabledJobs))
                $selectablesJobs[] = $job;
        }

        return $this->render( 'ajax/game/jobs.html.twig', [
            'professions' => $selectablesJobs,
            'prof_count' => $prof_count
        ] );
    }

    const ErrorJobAlreadySelected = ErrorHelper::BaseJobErrors + 1;
    const ErrorJobInvalid         = ErrorHelper::BaseJobErrors + 2;

    /**
     * @Route("api/game/job", name="api_jobcenter")
     * @param JSONRequestParser $parser
     * @param ItemFactory $if
     * @param ConfMaster $cf
     * @return Response
     */
    public function job_select_api(JSONRequestParser $parser, ItemFactory $if, ConfMaster $cf): Response {

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
            $skills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getUnlocked($citizen->getUser()->getAllHeroDaysSpent());
            $inventory = $citizen->getInventory();
            $item = $if->createItem( "photo_3_#00" );
            $item->setEssential(true);
            $null = null;
            $this->inventory_handler->transferItem($citizen,$item,$null,$inventory);
            foreach ($skills as $skill) {
                switch($skill->getName()){
                    case "brothers":
                        //TODO: add the heroic power
                        break;
                    case "resourcefulness":
                        $this->inventory_handler->forceMoveItem( $citizen->getHome()->getChest(), $if->createItem( 'chest_hero_#00' ) );
                        break;
                    case "largechest1":
                    case "largechest2":
                        $citizen->getHome()->setAdditionalStorage($citizen->getHome()->getAdditionalStorage() + 1);
                        break;
                    case "secondwind":
                        $heroic_action = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => "hero_generic_ap"]);
                        $citizen->addHeroicAction($heroic_action);
                        $this->entity_manager->persist($citizen);
                        break;
                    case 'breakfast1':
                        $this->inventory_handler->forceMoveItem( $citizen->getHome()->getChest(), $if->createItem( 'food_bag_#00' ) );
                        break;
                    case 'medicine1':
                        $this->inventory_handler->forceMoveItem( $citizen->getHome()->getChest(), $if->createItem( 'disinfect_#00' ) );
                        break;
                    case "cheatdeath":
                        $heroic_action = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => "hero_generic_immune"]);
                        $citizen->addHeroicAction($heroic_action);
                        $this->entity_manager->persist($citizen);
                        break;
                    case 'architect':
                        $this->inventory_handler->forceMoveItem( $citizen->getHome()->getChest(), $if->createItem( 'bplan_c_#00' ) );
                        break;
                    case 'luckyfind':
                        $oldfind = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => "hero_generic_find"]);
                        $citizen->removeHeroicAction($oldfind);
                        $newfind = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => "hero_generic_find_lucky"]);
                        $citizen->addHeroicAction($newfind);
                        break;
                }
            }
        }

        if($this->picto_handler->has_picto($citizen, 'r_armag_#00')) {
            $armag = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_armag"]);
            $citizen->addSpecialAction($armag);
            $this->inventory_handler->forceMoveItem($citizen->getHome()->getChest(), $if->createItem( 'food_armag_#00' ));
            $doggy = $this->inventory_handler->fetchSpecificItems( $citizen->getHome()->getChest(), [new ItemRequest('food_bag_#00')] );
            $this->inventory_handler->forceRemoveItem($doggy[0]);
        }

        if($this->picto_handler->has_picto($citizen, 'r_ginfec_#00')) {
            $this->citizen_handler->inflictStatus($citizen, 'tg_infect_wtns');
        }

        try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        if(!$citizen->getTown()->isOpen()) {
            // We add voting capability to every heroic citizen
            foreach ($citizen->getTown()->getCitizens() as $foreign) {
                /** @var Citizen $foreign */
                if(!$foreign->getProfession()->getHeroic()) continue;

                $vote_shaman = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_generic_shaman"]);
                $vote_guide = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_generic_guide"]);
                $foreign->addSpecialAction($vote_shaman);
                $foreign->addSpecialAction($vote_guide);
                $this->entity_manager->persist( $foreign );
            }
            $this->entity_manager->flush();
        }

        $item_spawns = $cf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_DEFAULT_CHEST_ITEMS, []);
        $chest = $citizen->getHome()->getChest();
        foreach ($item_spawns as $spawn)
            $this->inventory_handler->placeItem($citizen, $if->createItem($this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $spawn])), [$chest]);
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
     * @return Response
     */
    public function delete_log_entry(JSONRequestParser $parser): Response {

        $citizen = $this->getActiveCitizen();
        $counter = $citizen->getSpecificActionCounter(ActionCounter::ActionTypeRemoveLog);

        if(!$citizen->getAlive() || !$citizen->getProfession()->getHeroic() || !$this->user_handler->hasSkill($citizen->getUser(), 'manipulator')){
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
        }

        if(!$parser->has('log_entry_id'))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $log = $this->entity_manager->getRepository(TownLogEntry::class)->find($parser->get('log_entry_id'));
        if($log->getHidden()){
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
        }

        if($log->getTown() !== $citizen->getTown())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        if($log->getLogEntryTemplate()->getType() == LogEntryTemplate::TypeNightly)
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $limit = 0;
        if($this->user_handler->hasSkill($citizen->getUser(), 'manipulator'))
            $limit = 2;

        if($this->user_handler->hasSkill($citizen->getUser(), 'treachery'))
            $limit = 4;

        if($counter->getCount() < $limit){
            $counter->setCount($counter->getCount() + 1);
        } else {
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
        }

        $log->setHidden(true);
        $this->addFlash( 'notice', $this->translator->trans('Du hast heimlich einen Eintrag im Register unkenntlich gemacht... Du kannst das noch %times% mal tun.', ['%times%' => $limit - $counter->getCount()], 'game') );

        try {
            $this->entity_manager->persist( $log );
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }
}
