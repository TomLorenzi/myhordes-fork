<?php

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Entity\ActionCounter;
use App\Entity\Announcement;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenRole;
use App\Entity\CouncilEntry;
use App\Entity\HeroicActionPrototype;
use App\Entity\HeroSkillPrototype;
use App\Entity\ItemPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\Season;
use App\Entity\SpecialActionPrototype;
use App\Entity\TownLogEntry;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\GazetteService;
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
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @GateKeeperProfile(only_incarnated=true)
 * @method User getUser()
 */
class GameController extends CustomAbstractController
{
    private LogTemplateHandler $logTemplateHandler;
    private UserHandler $user_handler;
    private TownHandler $town_handler;
    private PictoHandler $picto_handler;
    private GazetteService $gazette_service;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, LogTemplateHandler $lth,
                                TimeKeeperService $tk, CitizenHandler $ch, UserHandler $uh, TownHandler $th,
                                ConfMaster $conf, PictoHandler $ph, InventoryHandler $ih, GazetteService $gs)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator);
        $this->logTemplateHandler = $lth;
        $this->user_handler = $uh;
        $this->town_handler = $th;
        $this->picto_handler = $ph;
        $this->gazette_service = $gs;
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
            $entries[$idx] = [
                'timestamp' => $entity->getTimestamp(),
                'class'     => $template->getClass(),
                'type'      => $template->getType(),
                'id'        => $entity->getId(),
                'hidden'    => $entity->getHidden(),
            ];

            $variableTypes = $template->getVariableTypes();
            $transParams = $this->logTemplateHandler->parseTransParams($variableTypes, $entityVariables);

            try {
                $entries[$idx]['text'] = $this->translator->trans($template->getText(), $transParams, 'game');
            }
            catch (Exception $e) {
                $entries[$idx]['text'] = "null";
            }             
        }

        if ($day < 0) $day = $this->getActiveCitizen()->getTown()->getDay();

        $args = $this->addDefaultTwigArgs(null, [
                'day' => $day,
                'today' => $day === $this->getActiveCitizen()->getTown()->getDay(),
                'entries' => $entries,
                'canHideEntry' => $this->getActiveCitizen()->getAlive() && $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getUser(), 'manipulator') && $this->getActiveCitizen()->getSpecificActionCounterValue(ActionCounter::ActionTypeRemoveLog) < $this->user_handler->getMaximumEntryHidden($this->getUser()),
            ]
        );

        return $this->render( 'ajax/game/log_content.html.twig', $args);
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
     * @Route("api/game/expert_toggle", name="game_toggle_expert_mode")
     * @param LogTemplateHandler $log
     * @return Response
     */
    public function toggle_expert_mode(LogTemplateHandler $log): Response
    {
        $this->entity_manager->persist( $this->getUser()->setExpert( !$this->getUser()->getExpert() ) );
        if ( !$this->getUser()->getExpert() && $this->getUser()->getActiveCitizen())
            foreach ($this->getUser()->getActiveCitizen()->getValidLeadingEscorts() as $escort) {
                $this->entity_manager->persist($log->beyondEscortReleaseCitizen($this->getUser()->getActiveCitizen(), $escort->getCitizen()));
                $escort->setLeader(null);
                $this->entity_manager->persist($escort);
            }
        try {
            $this->entity_manager->flush();
        } catch (Exception $e) { return AjaxResponse::error(ErrorHelper::ErrorDatabaseException); }
        return AjaxResponse::success();
    }

    /**
     * @Route("jx/game/raventimes", name="game_newspaper")
     * @return Response
     */
    public function newspaper(): Response {
        if ($this->getActiveCitizen()->getAlive() && $this->getActiveCitizen()->getProfession()->getName() === CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_landing'));

        $in_town = $this->getActiveCitizen()->getZone() === null;
        $town = $this->getActiveCitizen()->getTown();

        $has_living_citizens = false;
        foreach ( $town->getCitizens() as $c )
            if ($c->getAlive()) {
                $has_living_citizens = true;
                break;
            }

        if (!$has_living_citizens && $this->getActiveCitizen()->getCauseOfDeath()->getRef() != CauseOfDeath::Radiations)
            return $this->redirect($this->generateUrl('game_landing'));

        $citizensWithRole = $this->entity_manager->getRepository(Citizen::class)->findCitizenWithRole($town);

        $roles = $this->entity_manager->getRepository(CitizenRole::class)->findVotable();

        $votesNeeded = array();
        foreach ($roles as $role)
            if ( $this->town_handler->is_vote_needed($town, $role) )
                $votesNeeded[$role->getName()] = $role;

        $show_register = $in_town || !$this->getActiveCitizen()->getAlive();

        $citizen = $this->getActiveCitizen();
        $citizen->setHasSeenGazette(true);
        $this->entity_manager->persist($citizen);
        $this->entity_manager->flush();

        $citizenRoleList = [];
        /** @var Citizen $citizen */
        foreach ($citizensWithRole as $citizen) {
            foreach ($citizen->getRoles() as $role) {
                if(isset($citizenRoleList[$role->getId()])) {
                    $citizenRoleList[$role->getId()]['citizens'][] = $citizen;
                } else {
                    $citizenRoleList[$role->getId()] = [
                        'role' => $role,
                        'citizens' => [
                            $citizen
                        ]
                    ];
                }
            }
        }

        $latest_announcement = $this->entity_manager->getRepository(Announcement::class)->findLatestByLang( $this->getUserLanguage() );

        return $this->render( 'ajax/game/newspaper.html.twig', $this->addDefaultTwigArgs(null, [
            'show_register'  => $show_register,
            'show_town_link'  => $in_town,
            'day' => $town->getDay(),
            'log' => $show_register ? $this->renderLog( -1, null, false, null, 50 )->getContent() : "",
            'gazette' => $this->gazette_service->renderGazette($town),
            'citizensWithRole' => $citizenRoleList,
            'votesNeeded' => $in_town ? $votesNeeded : [],
            'town' => $town,
            'announcement' => $latest_announcement?->getTimestamp() < new \DateTime('-4weeks') ? null : $latest_announcement,
            'season' => $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]),
            'council' => array_map( fn(CouncilEntry $c) => [$this->gazette_service->parseCouncilLog( $c ), $c->getCitizen()], array_filter( $this->entity_manager->getRepository(CouncilEntry::class)->findBy(['town' => $town, 'day' => $town->getDay()], ['ord' => 'ASC']),
                fn(CouncilEntry $c) => ($c->getTemplate() && $c->getTemplate()->getText() !== null)
            ))
        ]));
    }

    /**
     * @Route("api/game/raventimes/log", name="game_newspaper_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_newspaper_api(JSONRequestParser $parser): Response {
        if ($this->getActiveCitizen()->getZone())
            return $this->renderLog((int)$parser->get('day', -1), null, false, -1, 0);

        $citizen_id = $parser->get('citizen', -1);
        $citizen = null;
        if($citizen_id > 0) {
            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($citizen_id);
            if ($citizen->getTown() !== $this->getActiveCitizen()->getTown())
                $citizen = null;
        }

        $type_id = $parser->get('category', -1);
        return $this->renderLog((int)$parser->get('day', -1), $citizen, false, $type_id >= 0 ? $type_id : null, null);
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

        $town = $this->getActiveCitizen()->getTown();

        $disabledJobs = $cf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_JOBS, ['shaman']);

        $selectablesJobs = [];
        $prof_count = [];

        foreach ($town->getCitizens() as $c) {
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

        $args = $this->addDefaultTwigArgs(null, [
                'professions' => $selectablesJobs,
                'prof_count' => $prof_count,
                'town' => $town,
                'conf' => $this->getTownConf()
            ]
        );
        
        return $this->render( 'ajax/game/jobs.html.twig', $args);
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
    public function job_select_api(JSONRequestParser $parser, ItemFactory $if, ConfMaster $cf, TranslatorInterface $translator): Response {

        $citizen = $this->getActiveCitizen();
        if ($citizen->getProfession()->getName() !== CitizenProfession::DEFAULT)
            return AjaxResponse::error(self::ErrorJobAlreadySelected);

        if (!$parser->has('job')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $job_id = (int)$parser->get('job', -1);
        if ($job_id <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var CitizenProfession $new_profession */
        $new_profession = $this->entity_manager->getRepository(CitizenProfession::class)->find( $job_id );
        if (!$new_profession) return AjaxResponse::error(self::ErrorJobInvalid);

        $town_conf = $cf->getTownConfiguration($citizen->getTown());

        $citizen_alias_active = $town_conf->get(TownConf::CONF_FEATURE_CITIZEN_ALIAS, false);
        if($citizen_alias_active) {
            $apply_result = $this->citizen_handler->applyAlias( $citizen, $parser->trimmed('citizenalias', '') );
            if($apply_result == -1) {
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            }
        }

        $this->citizen_handler->applyProfession( $citizen, $new_profession );
        $inventory = $citizen->getInventory();

        if($new_profession->getHeroic()) {
            $skills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getUnlocked($citizen->getUser()->getAllHeroDaysSpent());

            $null = null;

            if ($this->user_handler->checkFeatureUnlock( $citizen->getUser(), 'f_cam', true ) ) {
                $item = ($if->createItem( "photo_3_#00" ))->setEssential(true);
                $this->inventory_handler->transferItem($citizen,$item,$null,$inventory);
            }

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
                        $already_used = $citizen->getUsedHeroicActions()->contains($oldfind);
                        $citizen->removeHeroicAction($oldfind);
                        $citizen->removeUsedHeroicAction($oldfind);
                        $newfind = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => "hero_generic_find_lucky"]);
                        if ($already_used)
                            $citizen->addUsedHeroicAction($newfind);
                        else $citizen->addHeroicAction($newfind);
                        break;
                    case 'apag':
                        // Only give the APAG via Hero XP if it is not unlocked via Soul Inventory
                        if (!$this->user_handler->checkFeatureUnlock( $citizen->getUser(), 'f_cam', false ) ) {
                            $item = ($if->createItem( "photo_3_#00" ))->setEssential(true);
                            $this->inventory_handler->transferItem($citizen,$item,$null,$inventory);
                        }
                        break;
                }
            }
        }

        if ($this->user_handler->checkFeatureUnlock( $citizen->getUser(), 'f_alarm', true ) ) {
            $item = ($if->createItem( "alarm_off_#00" ))->setEssential(true);
            $this->inventory_handler->transferItem($citizen,$item,$null,$inventory);
        }

        if ($this->user_handler->checkFeatureUnlock( $citizen->getUser(), 'f_arma', true ) ) {
            $armag_day   = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_armag_d"]);
            $armag_night = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_armag_n"]);
            $citizen->addSpecialAction($armag_day);
            $citizen->addSpecialAction($armag_night);
            $this->inventory_handler->forceMoveItem($citizen->getHome()->getChest(), $if->createItem( 'food_armag_#00' ));
            $doggy = $this->inventory_handler->fetchSpecificItems( $citizen->getHome()->getChest(), [new ItemRequest('food_bag_#00')] );
            if (!empty($doggy)) $this->inventory_handler->forceRemoveItem($doggy[0]);
        }

        $vote_shaman = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_vote_shaman"]);
        $vote_guide = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_vote_guide"]);
        if ($vote_shaman) $citizen->addSpecialAction($vote_shaman);
        if ($vote_guide) $citizen->addSpecialAction($vote_guide);

        if ($this->user_handler->checkFeatureUnlock( $citizen->getUser(), 'f_wtns', true ) )
            $this->citizen_handler->inflictStatus($citizen, 'tg_infect_wtns');

        try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        $item_spawns = $town_conf->get(TownConf::CONF_DEFAULT_CHEST_ITEMS, []);

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

        if ($log->getLogEntryTemplate()->getType() == LogEntryTemplate::TypeNightly)
            return AjaxResponse::errorMessage( $this->translator->trans('Dieser Registereintrag kann <strong>nicht</strong> gefälscht werden.', [], 'game') );

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
        $log->setHiddenBy($citizen);
        $this->addFlash( 'notice', $this->translator->trans('Du hast heimlich einen Eintrag im Register unkenntlich gemacht... Du kannst das noch {times} mal tun.', ['{times}' => $limit - $counter->getCount()], 'game') );

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
