<?php

namespace App\Controller;

use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CauseOfDeath;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\TownLogEntry;
use App\Entity\User;
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
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->entity_manager = $em;
        $this->translator = $translator;
    }

    protected function getActiveCitizen(): Citizen {
        return $this->entity_manager->getRepository(Citizen::class)->findActiveByUser($this->getUser());
    }

    protected function renderLog( ?int $day, $citizen = null, $zone = null, ?int $type = null, ?int $max = null ): Response {
        return $this->render( 'ajax/game/log_content.html.twig', [
            'entries' => $this->entity_manager->getRepository(TownLogEntry::class)->findByFilter(
                $this->getActiveCitizen()->getTown(),
                $day, $citizen, $zone, $type, $max
            )
        ] );
    }

    /**
     * @Route("jx/game/landing", name="game_landing")
     * @return Response
     */
    public function landing(): Response
    {
        if (!$this->getActiveCitizen()->getAlive())
            return $this->redirect($this->generateUrl('game_death'));
        elseif ($this->getActiveCitizen()->getProfession()->getName() === CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_jobs'));
        elseif ($this->getActiveCitizen()->getZone()) return $this->redirect($this->generateUrl('beyond_dashboard'));
        else return $this->redirect($this->generateUrl('town_dashboard'));
    }

    /**
     * @Route("jx/game/raventimes", name="game_newspaper")
     * @return Response
     */
    public function newspaper(): Response {
        if (!$this->getActiveCitizen()->getAlive() || $this->getActiveCitizen()->getProfession()->getName() === CitizenProfession::DEFAULT)
            return $this->redirect($this->generateUrl('game_landing'));

        $in_town = $this->getActiveCitizen()->getZone() === null;
        $town = $this->getActiveCitizen()->getTown();
        $death_outside = array();
        $death_in_town = array();

        foreach ($town->getCitizens() as $citizen) {
            if($citizen->getAlive()) continue;
            if($citizen->getSurvivedDays() >= $town->getDay() - 1)
            if($citizen->getCauseOfDeath()->getRef() == CauseOfDeath::NightlyAttack && $citizen->getHome()->getDisposed() == 0) {
                $death_in_town[] = $citizen;
            } else {
                $death_outside[] = $citizen;
            }
        }


        return $this->render( 'ajax/game/newspaper.html.twig', [
            'show_register'  => $in_town,
            'show_town_link'  => $in_town,
            'town_name' => $town->getName(),
            'death_in_town' => $death_in_town,
            'death_outside' => $death_outside,
            'log' => $in_town ? $this->renderLog( -1, null, false, null, 50 )->getContent() : "",
            'day' => $this->getActiveCitizen()->getTown()->getDay()
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

    /**
     * @Route("jx/game/death", name="game_death")
     * @param CitizenHandler $ch
     * @return Response
     */
    public function death(CitizenHandler $ch): Response
    {
        if ($this->getActiveCitizen()->getAlive())
            return $this->redirect($this->generateUrl('game_landing'));

        $pictosDuringTown = $this->entity_manager->getRepository(Picto::class)->findPictoByUserAndTown($this->getUser(), $this->getActiveCitizen()->getTown());
        $pictosWonDuringTown = array();
        $pictosNotWonDuringTown = array();

        foreach ($pictosDuringTown as $picto) {
            if($picto->getPersisted() > 0) {
                $pictosWonDuringTown[] = $picto;
            } else {
                $pictosNotWonDuringTown[] = $picto;
            }
        }

        return $this->render( 'ajax/game/death.html.twig', [
            'citizen' => $this->getActiveCitizen(),
            'sp' => $ch->getSoulpoints($this->getActiveCitizen()),
            'pictos' => $pictosWonDuringTown,
            'denied_pictos' => $pictosNotWonDuringTown
        ] );
    }

    const ErrorJobAlreadySelected = ErrorHelper::BaseJobErrors + 1;
    const ErrorJobInvalid         = ErrorHelper::BaseJobErrors + 2;

    /**
     * @Route("api/game/job", name="api_jobcenter")
     * @param JSONRequestParser $parser
     * @param CitizenHandler $ch
     * @param InventoryHandler $invh
     * @param ItemFactory $if
     * @param ConfMaster $cf
     * @return Response
     */
    public function job_select_api(JSONRequestParser $parser, CitizenHandler $ch, InventoryHandler $invh, ItemFactory $if, ConfMaster $cf): Response {

        $citizen = $this->getActiveCitizen();
        if ($citizen->getProfession()->getName() !== CitizenProfession::DEFAULT)
            return AjaxResponse::error(self::ErrorJobAlreadySelected);

        if (!$parser->has('job')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $job_id = (int)$parser->get('job', -1);
        if ($job_id <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var CitizenProfession $new_profession */
        $new_profession = $this->entity_manager->getRepository(CitizenProfession::class)->find( $job_id );
        if (!$new_profession) return AjaxResponse::error(self::ErrorJobInvalid);

        $ch->applyProfession( $citizen, $new_profession );

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
     * @Route("api/game/unsubscribe", name="api_unsubscribe")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function unsubscribe_api(JSONRequestParser $parser, EntityManagerInterface $em, SessionInterface $session): Response {
        if ($this->getActiveCitizen()->getAlive())
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var User|null $user */
        $user = $this->getUser();
        $active = $em->getRepository(Citizen::class)->findActiveByUser($user);

        if (!$active || $active->getId() !== $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $last_words = $parser->get('lastwords');

        $active->setActive(false);
        if($active->getCauseOfDeath()->getRef() != CauseOfDeath::Posion)
            $active->setLastWords($last_words);
        else
            $active->setLastWords($this->translator->trans("...der Mörder .. ist.. IST.. AAARGHhh..", [], "game"));

        // Here, we delete picto with persisted = 0,
        // and definitively validate picto with persisted = 1
        $pendingPictosOfUser = $this->entity_manager->getRepository(Picto::class)->findPendingByUser($user);
        foreach ($pendingPictosOfUser as $pendingPicto) {
            if($pendingPicto->getPersisted() == 0)
                $this->entity_manager->remove($pendingPicto);
            else {
                $pendingPicto->setPersisted(2);
                $this->entity_manager->persist($pendingPicto);
            }
        }

        $this->entity_manager->persist( $active );
        $this->entity_manager->flush();

        if ($session->has('_town_lang')) {
            $session->remove('_town_lang');
            return AjaxResponse::success()->setAjaxControl(AjaxResponse::AJAX_CONTROL_RESET);
        } else return AjaxResponse::success();
    }

}
