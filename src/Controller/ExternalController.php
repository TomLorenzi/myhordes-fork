<?php

namespace App\Controller;

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
class ExternalController extends AbstractController
{
    /**
     * @Route("jx/api/json", name="api_json")
     * @return Response
     */
    public function api_json(): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ))
            return $this->redirect($this->generateUrl('initial_landing'));
        return $this->render( 'ajax/public/login.html.twig' );
    }

    /**
     * @Route("jx/api/xml", name="api_xml")
     * @return Response
     */
    public function api_xml(): Response
    {
        if ($this->isGranted( 'ROLE_REGISTERED' ))
            return $this->redirect($this->generateUrl('initial_landing'));
        return $this->render( 'ajax/public/login.html.twig' );
    }


}
