<?php

namespace App\Controller\Messages;

use App\Entity\ActionCounter;
use App\Entity\AdminReport;
use App\Entity\Citizen;
use App\Entity\Complaint;
use App\Entity\ForumUsagePermissions;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\PrivateMessage;
use App\Entity\PrivateMessageThread;
use App\Entity\Town;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @method User getUser
 */
class MessageGlobalPMController extends MessageController
{

    /**
     * @Route("api/pm/ping", name="api_pm_ping")
     * @return Response
     */
    public function ping_check_new_message(): Response {
        return new AjaxResponse(['new' => 0, 'connected' => false]);
    }

    /**
     * @Route("jx/pm/view", name="pm_view")
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $p
     * @return Response
     */
    public function pm_view(EntityManagerInterface $em, JSONRequestParser $p): Response
    {
        return $this->render( 'ajax/pm/view.html.twig', $this->addDefaultTwigArgs(null, [

        ] ));
    }

}