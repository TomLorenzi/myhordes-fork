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
     * @Route("api/x/json", name="api_x_json")
     * @return Response
     */
    public function api_json(): Response
    {
        $test_array = [
            'citizen' => $this->getUser()->getUsername(),
        ];
        return $this->json( $test_array );
    }

    /**
     * @Route("api/x/xml", name="api_x_xml")
     * @return Response
     */
    public function api_xml(): Response
    {
        $test_array = [
            'citizen' => $this->getUser()->getUsername(),
        ];
        return $this->json( $test_array );
    }


}
