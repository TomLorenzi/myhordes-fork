<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Picto;
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
	protected $entity_manager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entity_manager = $em;
    }

    /**
     * @Route("jx/soul/me", name="soul_me")
     * @return Response
     */
    public function my_soul(): Response
    {
    	// Get all the picto & count points
    	$pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($this->getUser());
    	$points = 0;

        return $this->render( 'ajax/soul/me.html.twig', [
        	'pictos' => $pictos,
        	'points' => $points
        ]);
    }
}
