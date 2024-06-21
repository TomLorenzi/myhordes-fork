<?php

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Controller\Soul\SoulController;
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
use App\Service\EventProxyService;
use App\Service\GazetteService;
use App\Service\HookExecutor;
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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method User getUser()
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(only_incarnated: true)]
#[Semaphore('town', scope: 'town')]
class GameController extends CustomAbstractController
{
    private LogTemplateHandler $logTemplateHandler;
    private UserHandler $user_handler;
    private TownHandler $town_handler;
    private PictoHandler $picto_handler;
    private GazetteService $gazette_service;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, LogTemplateHandler $lth,
                                TimeKeeperService $tk, CitizenHandler $ch, UserHandler $uh, TownHandler $th,
                                ConfMaster $conf, PictoHandler $ph, InventoryHandler $ih, GazetteService $gs, HookExecutor $hookExecutor)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator, $hookExecutor);
        $this->logTemplateHandler = $lth;
        $this->user_handler = $uh;
        $this->town_handler = $th;
        $this->picto_handler = $ph;
        $this->gazette_service = $gs;
    }

    protected function getActiveCitizen(): Citizen {
        return $this->entity_manager->getRepository(Citizen::class)->findActiveByUser($this->getUser());
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/game/landing', name: 'game_landing')]
    public function landing(): Response
    {
        $activeCitizen = $this->getActiveCitizen();
        if (!$activeCitizen->getAlive())
            return $this->redirect($this->generateUrl('soul_death'));
        elseif ($activeCitizen->getProfession()->getName() === CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_jobs'));
        elseif (!$activeCitizen->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));
        elseif ($activeCitizen->getZone() && !$activeCitizen->activeExplorerStats())
            return $this->redirect($this->generateUrl('beyond_dashboard'));
        elseif ($activeCitizen->getZone() && $activeCitizen->activeExplorerStats())
            return $this->redirect($this->generateUrl('exploration_dashboard'));
        else return $this->redirect($this->generateUrl('town_dashboard'));
    }

    /**
     * @param LogTemplateHandler $log
     * @return Response
     */
    #[Route(path: 'api/game/expert_toggle', name: 'game_toggle_expert_mode')]
    public function toggle_expert_mode(LogTemplateHandler $log): Response
    {
        $this->entity_manager->persist( $this->getUser()->setExpert( !$this->getUser()->getExpert() ) );
        if ( !$this->getUser()->getExpert() && $this->getUser()->getActiveCitizen())
            foreach ($this->getUser()->getActiveCitizen()->getLeadingEscorts() as $escort) {
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
     * @return Response
     */
    #[Route(path: 'jx/game/raventimes', name: 'game_newspaper')]
    public function newspaper(): Response {
        $activeCitizen = $this->getActiveCitizen();
        if ($activeCitizen->getAlive() && $activeCitizen->getProfession()->getName() === CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_landing'));

        $in_town = $activeCitizen->getZone() === null;
        $town = $activeCitizen->getTown();

        $has_living_citizens = false;
        foreach ( $town->getCitizens() as $c )
            if ($c->getAlive()) {
                $has_living_citizens = true;
                break;
            }

        if (!$has_living_citizens && $activeCitizen->getCauseOfDeath()->getRef() != CauseOfDeath::Radiations)
            return $this->redirect($this->generateUrl('game_landing'));

        $citizensWithRole = $this->entity_manager->getRepository(Citizen::class)->findCitizenWithRole($town);

        $roles = $this->entity_manager->getRepository(CitizenRole::class)->findVotable();

        $votesNeeded = array();
        foreach ($roles as $role)
            if ( $this->town_handler->is_vote_needed($town, $role) )
                $votesNeeded[$role->getName()] = $role;

        $show_register = $in_town || !$activeCitizen->getAlive();

        $activeCitizen->setHasSeenGazette(true);
        $this->entity_manager->persist($activeCitizen);
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
     * @param ConfMaster $cf
     * @return Response
     */
    #[Route(path: 'jx/game/jobcenter', name: 'game_jobs')]
    public function job_select(ConfMaster $cf): Response
    {
        $activeCitizen = $this->getActiveCitizen();
        if ($activeCitizen->getProfession()->getName() !== CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_landing'));

        $jobs = $this->entity_manager->getRepository(CitizenProfession::class)->findSelectable();

        $town = $activeCitizen->getTown();

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
     * @param JSONRequestParser $parser
     * @param ItemFactory $if
     * @param ConfMaster $cf
     * @param EventProxyService $proxy
     * @return Response
     */
    #[Route(path: 'api/game/job', name: 'api_jobcenter')]
    public function job_select_api(JSONRequestParser $parser, ItemFactory $if, ConfMaster $cf, EventProxyService $proxy): Response {

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
            if (!$this->user_handler->isNameValid( $alias = $parser->trimmed('citizenalias', ''), custom_length: 22, disable_preg: true ))
                return AjaxResponse::error(SoulController::ErrorUserEditUserName);
            $citizen->setAlias( $alias );
        }

        $this->citizen_handler->applyProfession( $citizen, $new_profession );
        $inventory = $citizen->getInventory();
		$null = null;

        if($new_profession->getHeroic()) {
            $skills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getUnlocked($citizen->getUser()->getAllHeroDaysSpent());

            if ($this->user_handler->checkFeatureUnlock( $citizen->getUser(), 'f_cam', true ) ) {
                $item = ($if->createItem( "photo_3_#00" ))->setEssential(true);
                $proxy->transferItem($citizen, $item, to: $inventory);
            }

			/** @var HeroSkillPrototype $skill */
			foreach ($skills as $skill) {
                switch($skill->getName()){
                    case "largechest1":
                    case "largechest2":
                        $citizen->getHome()->setAdditionalStorage($citizen->getHome()->getAdditionalStorage() + 1);
                        break;
                    case 'apag':
                        // Only give the APAG via Hero XP if it is not unlocked via Soul Inventory
                        if (!$this->user_handler->checkFeatureUnlock( $citizen->getUser(), 'f_cam', false ) ) {
                            $item = ($if->createItem( "photo_3_#00" ))->setEssential(true);
                            $proxy->transferItem($citizen, $item, to: $inventory);
                        }
                        break;
                }

				// If we have Start Items linked to the Skill, add them to the chest
				if ($skill->getStartItems()->count() > 0) {
					foreach ($skill->getStartItems() as $prototype) {
						$this->inventory_handler->forceMoveItem($citizen->getHome()->getChest(), $if->createItem($prototype));
					}
				}

				// If the HeroSkill unlocks a Heroic Action, give it
				if ($skill->getUnlockedAction()) {
					$previouslyUsed = false;
					// A heroic action can replace one. Let's handle it!
					if ($skill->getUnlockedAction()->getReplacedAction() !== null) {
						$proto = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => $skill->getUnlockedAction()->getReplacedAction()]);
						$previouslyUsed = $citizen->getUsedHeroicActions()->contains($proto);
						$citizen->removeHeroicAction($proto);
						$citizen->removeUsedHeroicAction($proto);
					}
					if ($previouslyUsed)
						$citizen->addUsedHeroicAction($skill->getUnlockedAction());
					else
						$citizen->addHeroicAction($skill->getUnlockedAction());
					$this->entity_manager->persist($citizen);
				}
            }
        }

        if ($this->user_handler->checkFeatureUnlock( $citizen->getUser(), 'f_alarm', true ) ) {
            $item = ($if->createItem( "alarm_off_#00" ))->setEssential(true);
            $proxy->transferItem($citizen, $item, to: $inventory);
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
            $proxy->placeItem($citizen, $if->createItem($this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $spawn])), [$chest]);
        try {
            $this->entity_manager->persist( $chest );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            
        }

        return AjaxResponse::success();
    }
}
