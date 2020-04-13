<?php

namespace App\Controller;

use App\Entity\Citizen;
use App\Entity\User;
use App\Exception\DynamicAjaxResetException;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\UserFactory;
use App\Response\AjaxResponse;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class SoulController extends AbstractController
{

    /**
     * @Route("jx/soul/me", name="soul_me")
     * @return Response
     */
    public function soul_me(): Response
    {
        return $this->render( 'ajax/soul/me.html.twig' );
    }

    /**
     * @Route("jx/soul/news", name="soul_news")
     * @return Response
     */
    public function soul_news(): Response
    {
        return $this->render( 'ajax/soul/news.html.twig' );
    }

    /**
     * @Route("jx/soul/settings", name="soul_settings")
     * @return Response
     */
    public function soul_settings(): Response
    {
        return $this->render( 'ajax/soul/settings.html.twig' );
    }

    /**
     * @Route("api/soul/settings/generateid", name="soul_settings_generateid")
     * @return Response
     */
    public function soul_settings_generateid(): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        #$user->setExternalId('abc789xyz');
        $user->setExternalId(md5($user->getEmail() . mt_rand()));

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/deleteid", name="soul_settings_deleteid")
     * @return Response
     */
    public function soul_settings_deleteid(): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $user->setExternalId('');

        return AjaxResponse::success();
    }
}
