<?php

namespace App\Messages\WebPush;

use App\Entity\Avatar;
use App\Entity\NotificationSubscription;
use App\Enum\NotificationSubscriptionType;
use BenTools\WebPushBundle\Model\Message\PushNotification;
use BenTools\WebPushBundle\Sender\PushMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Encryption;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
readonly class WebPushMessageHandler
{
    public function __construct(
        private PushMessageSender $sender,
        private EntityManagerInterface $em,
        private Packages $asset,
        private UrlGeneratorInterface $generator,
        private string $uri,
    ) {}

    private function buildPayload( WebPushMessage $message ): array {
        $payload = [
            PushNotification::BODY => $message->body,
            PushNotification::TIMESTAMP => $message->timestamp->getTimestamp(),
            PushNotification::BADGE => $this->uri . $this->asset->getUrl('build/favicon/android-chrome-72x72.png'),
        ];

        if ($message->avatar) {
            $avatar = $this->em->getRepository(Avatar::class)->find( $message->avatar );
            if ($avatar) $payload[ PushNotification::ICON ] = $this->uri . $this->generator->generate( 'app_web_avatar_for_webpush', [
                'uid' => $avatar->getId(), 'name' => $avatar->getFilename() ?? $avatar->getSmallName(), 'ext' => $avatar->getFormat()
            ] );
        }

        //dump($payload);

        return $payload;
    }

    /**
     * @throws \Exception
     */
    public function __invoke(WebPushMessage $message): void
    {
        // Get the subscription
        $subscription = $this->em->getRepository(NotificationSubscription::class)->find( $message->subscription );

        // Only process WebPush subscriptions
        if ($subscription?->getType() !== NotificationSubscriptionType::WebPush) return;

        // We do not process expired subscriptions
        if ($subscription->isExpired()) return;

        // Push notification to subscriber service
        $response = null;
        $responses = $this->sender
            ->setMaxPaddingLength(min($subscription->getMaxPaddingLength() ?? Encryption::MAX_PAYLOAD_LENGTH, Encryption::MAX_PAYLOAD_LENGTH))
            ->push(
                (new PushNotification("MyHordes: {$message->title}", $this->buildPayload( $message )))->createMessage(),
                [$subscription]
            );
        foreach ($responses as $r) $response = $r;

        // If the subscription is expired, blacklist it
        if ($response?->isExpired()) {
            $this->em->persist( $subscription->setExpired( true ) );
            $this->em->flush();
        }
    }
}