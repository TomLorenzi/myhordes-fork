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
    const ErrorWrongTownPassword          = ErrorHelper::BaseGhostErrors + 1;

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

        if ($user->getShadowBan())
            return $this->redirect($this->generateUrl( 'soul_disabled' ));

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
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable, ['url' => $this->generateUrl('initial_landing')] );
        }

        $townname = $parser->get('townName', '');
        $password = $parser->get('password', null);
        $lang = $parser->get('lang', '');
        $townType = $parser->get('townType', '');
        $ghoulType = $parser->get('ghoulType', '');
        $well = $parser->get('well', '');
        $disablexml = $parser->get('disablexml', '');
        $rules = $parser->get('rules', '');
        $ruins = boolval($parser->get('ruins', ''));
        $shaman = boolval($parser->get('shaman', ''));
        $escorts = boolval($parser->get('escorts', ''));
        $shun = boolval($parser->get('shun', ''));
        $nightmode = boolval($parser->get('nightmode', ''));
        $camp = boolval($parser->get('camp', ''));
        $ghouls = boolval($parser->get('ghouls', ''));
        $buildingdamages = boolval($parser->get('buildingdamages', ''));
        $nightwatch = boolval($parser->get('nightwatch', ''));
        $improveddump = boolval($parser->get('improveddump', ''));
        $attacks = $parser->get('attacks', '');
        $allpictos = $parser->get('allpictos', '');
        $soulpoints = $parser->get('soulpoints', '');
        $seed = $parser->get('seed', -1);
        $incarnated = boolval($parser->get('incarnated', true));

        // Initial: Create town setting from selected type
        $town = new Town();
        $town
            ->setType($em->getRepository(TownClass::class)->findOneBy(['name' => $townType]));

        $conf = $conf->getTownConfiguration($town);

        $customConf = $conf->getData();

        /*$customConf = [
            'features' => []
        ];*/

        if(!empty($well) && is_numeric($well) && $well <= 300){
            $customConf['well'] = [
                'min' => $well,
                'max' => $well
            ];
        }

        if(!empty($disablexml)) $customConf['features']['xml_feed'] = !$disablexml;
        if(!empty($ghoulType)) $customConf['features']['ghoul_mode'] = $ghoulType;
        switch($rules) {
            case 'nobuilding':
                $customConf['features']['unlocked_buildings'] = [];
                break;
            case 'poison':
                $customConf['features']['all_poison'] = true;
                break;
        }

        if (!$ruins) $customConf['features'][''] = 0;
        $customConf['features']['shaman'] = $shaman;
        $customConf['features']['escort'] = ['enabled' => $escorts];
        $customConf['features']['shun'] = $shun;
        $customConf['features']['nightmode'] = $nightmode;
        $customConf['features']['camping'] = $camp;
        $customConf['features']['ghoul'] = $ghouls;
        $customConf['features']['nightwatch'] = $nightwatch;
        $customConf['features']['improveddump'] = $improveddump;
        $customConf['features']['attacks'] = $attacks;

        $customConf['features']['give_all_pictos'] = $allpictos;
        $customConf['features']['give_soulpoints'] = $soulpoints;

        $town = $gf->createTown($townname, $lang, null, 'custom', $customConf, intval($seed));
        $town->setCreator($user);
        if(!empty($password) && $password != null)
            $town->setPassword($password);
        $em->persist($town);

        if($incarnated) {
            $citizen = $gf->createCitizen($town, $user, $error);
            if (!$citizen) return AjaxResponse::error($error);
            try {
                $em->persist($citizen);
                $em->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }

            $em->persist( $log->citizenJoin( $citizen ) );
        }

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

        if ($user->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

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
                "small" => 0,
                'custom' => 0
            ),
            "de" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0,
                'custom' => 0
            ),
            "en" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0,
                'custom' => 0
            ),
            "es" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0,
                'custom' => 0
            ),
            "multi" => array(
                "remote" => 100,
                "panda" => 100,
                "small" => 100,
                'custom' => 100
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

    /**
     * @Route("api/ghost/check_town_pw", name="api_check_town_pw")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function check_town_pw_api(JSONRequestParser $parser, EntityManagerInterface $em) {

        if (!$parser->has('town') || !$parser->has('pass')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $town_id = (int)$parser->get('town', -1);
        $pass = $parser->get('pass', '');
        if ($town_id <= 0 || empty($pass)) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var Town $town */
        $town = $em->getRepository(Town::class)->find( $town_id );

        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if($town->getPassword() != $pass)
            return AjaxResponse::error(self::ErrorWrongTownPassword);

        return $this->redirectToRoute("api_join", ['town' => $town_id], 307);
    }

    public function getUserTownClassAccess(MyHordesConf $conf): array {
        /** @var User $user */
        $user = $this->getUser();
        return [
            'small' =>
                ($user->getAllSoulPoints() < $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 )
                || $user->getAllSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_BACK_TO_SMALL, 500 )),
            'remote' => ($user->getAllSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 )),
            'panda' => ($user->getAllSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 500 )),
            'custom' => ($user->getAllSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_CUSTOM, 1000 )),
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
