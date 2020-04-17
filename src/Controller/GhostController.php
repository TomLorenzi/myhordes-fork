<?php

namespace App\Controller;

use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\JSONRequestParser;
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
    public function welcome(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $available_town_classes = [
            'small' => false,
            'remote' => false,
            'panda' => false,
            'custom' => false,
        ];
        if ($user->getSoulPoints() < 100 || $user->getSoulPoints() >= 500) {
            $available_town_classes['small'] = true;
        }
        if ($user->getSoulPoints() >= 100) {
            $available_town_classes['remote'] = true;
        }
        if ($user->getSoulPoints() >= 500) {
            $available_town_classes['panda'] = true;
        }
        if ($user->getSoulPoints() >= 1000) {
            $available_town_classes['custom'] = true;
        }

        return $this->render( 'ajax/ghost/intro.html.twig', [
            'townClasses' => $em->getRepository(TownClass::class)->findAll(),
            'userCanJoin' => $available_town_classes,
        ] );
    }

    /**
     * @Route("api/ghost/join", name="api_join")
     * @param JSONRequestParser $parser
     * @param GameFactory $factory
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function join_api(JSONRequestParser $parser, GameFactory $factory, EntityManagerInterface $em) {
        if (!$parser->has('town')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $town_id = (int)$parser->get('town', -1);
        if ($town_id <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $town = $em->getRepository(Town::class)->find( $town_id );
        $user = $this->getUser();

        if (!$town || !$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $citizen = $factory->createCitizen($town, $user, $error);
        if (!$citizen) return AjaxResponse::error($error);

        try {
            $em->persist($town);
            $em->persist($citizen);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

}
