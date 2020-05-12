<?php

namespace App\Controller;

use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CauseOfDeath;
use App\Entity\Gazette;
use App\Entity\GazetteLogEntry;
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
    protected $logTemplateHandler;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, LogTemplateHandler $lth)
    {
        $this->entity_manager = $em;
        $this->translator = $translator;
        $this->logTemplateHandler = $lth;
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
                // Baguette text:
                // Aucun article ce matin...
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
                    $cadavers = $death_inside;
                    shuffle($cadavers);
                    $variables = [];
                    for ($i = 1; $i <= $requirements - 20; $i++) {
                        $variables['cadaver' . $i] = (array_shift($cadavers))->getId();
                    }
                }
                elseif (floor($requirements / 10) == 3) {
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

        return $this->render( 'ajax/game/newspaper.html.twig', [
            'show_register'  => $in_town,
            'show_town_link'  => $in_town,
            'log' => $in_town ? $this->renderLog( -1, null, false, null, 50 )->getContent() : "",
            'gazette' => $gazette_info,
            'citizenWithRole' => $citizenWithRole,
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
