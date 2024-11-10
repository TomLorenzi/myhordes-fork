<?php

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Entity\Announcement;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenRole;
use App\Entity\CouncilEntry;
use App\Entity\Season;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\GazetteService;
use App\Service\HookExecutor;
use App\Service\InventoryHandler;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Traits\Controller\ActiveCitizen;
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
    use ActiveCitizen;

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

        $show_register = ($in_town || !$activeCitizen->getAlive()) && $activeCitizen->getProfession()->getName() !== CitizenProfession::DEFAULT;

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
        if (!$activeCitizen->getAlive() || $activeCitizen->getProfession()->getName() !== CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_landing'));

        $args = $this->addDefaultTwigArgs(null, [
            'town' => $activeCitizen->getTown(),
        ]);
        
        return $this->render( 'ajax/game/jobs.html.twig', $args);
    }
}
