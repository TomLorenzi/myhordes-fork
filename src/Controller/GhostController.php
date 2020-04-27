<?php

namespace App\Controller;

use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\JSONRequestParser;
use App\Structures\Conf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class GhostController extends AbstractController implements GhostInterfaceController
{
    /**
     * @Route("jx/ghost/welcome", name="ghost_welcome")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function welcome(EntityManagerInterface $em, ConfMaster $conf): Response
    {
        return $this->render( 'ajax/ghost/intro.html.twig', [
            'townClasses' => $em->getRepository(TownClass::class)->findAll(),
            'userCanJoin' => $this->getUserTownClassAccess($conf->getGlobalConf()),
        ] );
    }

    /**
     * @Route("api/ghost/join", name="api_join")
     * @param JSONRequestParser $parser
     * @param GameFactory $factory
     * @param EntityManagerInterface $em
     * @param ConfMaster $conf
     * @return Response
     */
    public function join_api(JSONRequestParser $parser, GameFactory $factory, EntityManagerInterface $em, ConfMaster $conf) {
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
                    // Fetch min/max pop from config for this townClass
                    $class = $em->getRepository(TownClass::class)->findOneByName($townClass);
                    $townTpl = new Town();
                    $townTpl->setType($class);
                    $townConf = $conf->getTownConfiguration($townTpl);
                    $min = $townConf->get(TownConf::CONF_POPULATION_MIN, 0);
                    $max = $townConf->get(TownConf::CONF_POPULATION_MAX, 0);
                    // Create the count we need
                    for($i = 0 ; $i < $minOpenTown[$townClass] - $openCount ; $i++){
                        $newTown = $factory->createTown(null, $townLang, mt_rand($min, $max), $townClass);
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
            'small' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_SMALL, 2 ),
            'remote' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_REMOTE, 4 ),
            'panda' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_SMALL, 2 ),
            'custom' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_CUSTOM, 0 ),
        ];
    }

}
