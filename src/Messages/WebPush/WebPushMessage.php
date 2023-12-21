<?php

namespace App\Messages\WebPush;

use App\Entity\NotificationSubscription;
use App\Messages\AsyncMessageInterface;
use Symfony\Component\Uid\Uuid;

readonly class WebPushMessage implements AsyncMessageInterface
{
    public Uuid $subscription;
    public \DateTimeImmutable $timestamp;

    public function __construct(
        NotificationSubscription $subscription,
        public string $title,
        public string $body,
        public ?int $avatar = null,
    ) {
        $this->subscription = $subscription->getId();
        $this->timestamp = new \DateTimeImmutable('now');
    }
}