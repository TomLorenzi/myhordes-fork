<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\Game\GameInteractionEvent;
use App\Response\AjaxResponse;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use App\Structures\MyHordesConf;
use App\Traits\Controller\EventChainProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CustomAbstractCoreController
 * @method User getUser
 */
class CustomAbstractCoreEventController extends AbstractController {

    use EventChainProcessor;

    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly EventDispatcherInterface $ed,
        protected readonly EventFactory $ef,
    ) { }

    /**
     * @param string|GameInteractionEvent $firstEvent
     * @param string|GameInteractionEvent|string[]|GameInteractionEvent[] $subsequentEvents
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function processEventChain(string|GameInteractionEvent $firstEvent, array|string|GameInteractionEvent $subsequentEvents ): JsonResponse {
        $error_code = $this->processEventChainUsing( $this->ef, $this->ed, $this->em, $firstEvent, $subsequentEvents, true, $error_messages );

        if ($error_code !== null)
            return AjaxResponse::error(empty($error_messages) ? $error_code : 'message', [
                'message' => empty($error_messages) ? null : implode('<hr/>', $error_messages)
            ]);
        else return AjaxResponse::success();
    }
}