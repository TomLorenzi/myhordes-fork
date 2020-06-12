<?php

namespace App\Controller;

use App\Entity\CitizenRankingProxy;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\TimeKeeperService;
use App\Service\UserHandler;
use App\Structures\Conf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class GhostController extends AbstractController implements GhostInterfaceController
{
    protected $entity_manager;
    protected $translator;
    protected $time_keeper;
    private $user_handler;

    public function __construct(EntityManagerInterface $em, UserHandler $uh, TimeKeeperService $tk, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->entity_manager = $em;
        $this->user_handler = $uh;
        $this->time_keeper = $tk;
    }

    protected function addDefaultTwigArgs( ?array $data = null ): array {
        $data = $data ?? [];

        $data['clock'] = [
            'desc'      => $this->translator->trans('Worauf warten Sie noch?', [], 'ghost'),
            'day'       => "",
            'timestamp' => new \DateTime('now'),
            'attack'    => $this->time_keeper->secondsUntilNextAttack(null, true),
            'towntype'  => "",
        ];

        return $data;
    }

    /**
     * @Route("jx/ghost/welcome", name="ghost_welcome")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function welcome(EntityManagerInterface $em, ConfMaster $conf, UserHandler $uh): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        return $this->render( 'ajax/ghost/intro.html.twig', $this->addDefaultTwigArgs([
            'townClasses' => $em->getRepository(TownClass::class)->findAll(),
            'userCanJoin' => $this->getUserTownClassAccess($conf->getGlobalConf()),
            'canCreateTown' => $uh->hasSkill($user, 'mayor'),
        ] ));
    }

    /**
     * @Route("jx/ghost/create_town", name="ghost_create_town")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function create_town(EntityManagerInterface $em, ConfMaster $conf, UserHandler $uh): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        if(!$uh->hasSkill($user, 'mayor')){
            return $this->redirect($this->generateUrl( 'initial_landing' ));
        }

        return $this->render( 'ajax/ghost/create_town.html.twig', $this->addDefaultTwigArgs([
            'townClasses' => $em->getRepository(TownClass::class)->findBy(['hasPreset' => true]),
        ]));
    }

    /**
     * @Route("api/ghost/create_town", name="ghost_process_create_town")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function process_create_town(JSONRequestParser $parser, EntityManagerInterface $em, ConfMaster $conf, UserHandler $uh, GameFactory $gf, LogTemplateHandler $log): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return AjaxResponse::success( true, ['url' => $this->generateUrl('soul_death')] );

        if(!$uh->hasSkill($user, 'mayor')){
            return AjaxResponse::success( false, ['url' => $this->generateUrl('initial_landing')] );
        }

        $townname = $parser->get('townName', '');
        $password = $parser->get('password', '');
        $lang = $parser->get('lang', '');
        $townType = $parser->get('townType', '');
        $ghoulType = $parser->get('ghoulType', '');
        $well = $parser->get('well', '');
        $disablexml = $parser->get('disablexml', '');
        $rules = $parser->get('rules', '');
        $ruins = $parser->get('ruins', '');
        $shaman = $parser->get('shaman', '');
        $escorts = $parser->get('escorts', '');
        $shunned = $parser->get('shunned', '');
        $nightmode = $parser->get('nightmode', '');
        $camp = $parser->get('camp', '');
        $ghouls = $parser->get('ghouls', '');
        $buildingdamages = $parser->get('buildingdamages', '');
        $nightwatch = $parser->get('nightwatch', '');
        $improveddump = $parser->get('improveddump', '');
        $attacks = $parser->get('attacks', '');
        $allpictos = $parser->get('allpictos', '');
        $allsoulpoints = $parser->get('allsoulpoints', '');

        $customConf = [];
        if(!empty($well) && is_numeric($well) && $well <= 300){
            $customConf[TownConf::CONF_WELL_MIN] = $well;
            $customConf[TownConf::CONF_WELL_MAX] = $well;
        }

        // $customConf[TownConf::CONF_FEATURE_XML] = !$disablexml;
        // $customConf[TownConf::CONF_FEATURE_GHOUL_MODE] = $ghoulType;
        switch($rules) {
            case 'nobuilding':
                $customConf[TownConf::CONF_BUILDINGS_UNLOCKED] = [];
                break;
            case 'poison':
                // $customConf[TownConf::CONF_FEATURE_ALL_POISON] = true;
                break;
        }

        $customConf[TownConf::CONF_FEATURE_NIGHTMODE] = $nightmode;
        if (!$ruins) $customConf[TownConf::CONF_NUM_EXPLORABLE_RUINS] = 0;
        //$customConf[TownConf::CONF_SHAMAN_ROLE] = $shaman;
        $customConf[TownConf::CONF_FEATURE_ESCORT] = $escorts;
        //$customConf[TownConf::CONF_FEATURE_SHUN] = $shunned;
        $customConf[TownConf::CONF_FEATURE_NIGHTMODE] = $nightmode;
        $customConf[TownConf::CONF_FEATURE_CAMPING] = $camp;
        // $customConf[TownConf::CONF_FEATURE_GHOUL] = $ghouls;
        // $customConf[TownConf::CONF_FEATURE_NIGHTWATCH] = $nightwatch;
        // $customConf[TownConf::CONF_FEATURE_IMPROVEDDUMP] = $improveddump;
        // $customConf[TownConf::CONF_FEATURE_ATTACKS] = $attacks;
        // $customConf[TownConf::CONF_FEATURE_GIVE_ALL_PICTOS] = $allpictos;
        // $customConf[TownConf::CONF_FEATURE_GIVE_SOULPOINTS] = $allsoulpoints;

        $town = $gf->createTown($townname, $lang, null, 'custom', $customConf);
        $town->setPassword($password);
        $em->persist($town);

        $citizen = $gf->createCitizen($town, $user, $error);
        if (!$citizen) return AjaxResponse::error($error);
        try {
            $em->persist($citizen);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        $em->persist( $log->citizenJoin( $citizen ) );
        try {
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success( true, ['url' => $this->generateUrl('game_jobs')] );
    }

    /**
     * @Route("api/ghost/join", name="api_join")
     * @param JSONRequestParser $parser
     * @param GameFactory $factory
     * @param EntityManagerInterface $em
     * @param ConfMaster $conf
     * @return Response
     */
    public function join_api(JSONRequestParser $parser, GameFactory $factory, EntityManagerInterface $em, ConfMaster $conf, LogTemplateHandler $log) {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has('town')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $town_id = (int)$parser->get('town', -1);
        if ($town_id <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var Town $town */
        $town = $em->getRepository(Town::class)->find( $town_id );
        /** @var User $user */
        $user = $this->getUser();

        if (!$town || !$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $allowedTownClasses = $this->getUserTownClassAccess($conf->getGlobalConf());
        if (!$allowedTownClasses[$town->getType()->getName()]) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $citizen = $factory->createCitizen($town, $user, $error);
        if (!$citizen) return AjaxResponse::error($error);

        // Let's check if there is enough opened town
        $openTowns = $em->getRepository(Town::class)->findOpenTown();
        $count = array(
            "fr" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0
            ),
            "de" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0
            ),
            "en" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0
            ),
            "es" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0
            ),
        );
        foreach ($openTowns as $openTown) {
            $count[$openTown->getLanguage()][$openTown->getType()->getName()]++;
        }

        $minOpenTown = $this->getMinOpenTownClass($conf->getGlobalConf());

        foreach ($count as $townLang => $array) {
            foreach ($array as $townClass => $openCount) {
                if($openCount < $minOpenTown[$townClass]){

                    // Create the count we need
                    for($i = 0 ; $i < $minOpenTown[$townClass] - $openCount ; $i++){
                        $newTown = $factory->createTown(null, $townLang, null, $townClass);
                        $em->persist($newTown);
                    }
                }
            }
        }

        try {
            $em->persist($town);
            $em->persist($citizen);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }
        try {
            $em->persist( $log->citizenJoin( $citizen ) );
            $em->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    public function getUserTownClassAccess(MyHordesConf $conf): array {
        /** @var User $user */
        $user = $this->getUser();
        return [
            'small' =>
                ($user->getSoulPoints() < $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 )
                || $user->getSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_BACK_TO_SMALL, 500 )),
            'remote' => ($user->getSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 )),
            'panda' => ($user->getSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 500 )),
            'custom' => ($user->getSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_CUSTOM, 1000 )),
        ];
    }

    public function getMinOpenTownClass(MyHordesConf $conf): array {
        return [
            'small' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_SMALL, 1 ),
            'remote' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_REMOTE, 1 ),
            'panda' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_PANDA, 1 ),
            'custom' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_CUSTOM, 0 ),
        ];
    }

}
