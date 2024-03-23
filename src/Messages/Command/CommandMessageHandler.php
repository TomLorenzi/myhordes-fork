<?php

namespace App\Messages\Command;

use App\Entity\Avatar;
use App\Entity\NotificationSubscription;
use App\Enum\NotificationSubscriptionType;
use App\Messages\WebPush\WebPushMessage;
use App\Service\CommandHelper;
use BenTools\WebPushBundle\Model\Message\PushNotification;
use BenTools\WebPushBundle\Sender\PushMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Encryption;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
readonly class CommandMessageHandler
{
    public function __construct(
        private CommandHelper $helper
    ) {}

    /**
     * @throws \Exception
     */
    public function __invoke(CommandMessage $message): void
    {
        if (!$this->helper->capsule( $message->command, new NullOutput() ))
            throw new \Exception("Command execution failed: {$message->command}");
    }
}