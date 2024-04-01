<?php

namespace App\Entity;

use App\Enum\NotificationSubscriptionType;
use App\Repository\NotificationSubscriptionRepository;
use ArrayHelpers\Arr;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NotificationSubscriptionRepository::class)]
#[ORM\Index(columns: ['type'], name: 'ns_by_type_idx')]
#[UniqueEntity(['type','subscription_hash'])]
#[Table]
#[UniqueConstraint(name: 'ns_type_hash_unique', columns: ['type','subscription_hash'])]
class NotificationSubscription implements UserSubscriptionInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue("CUSTOM")]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'integer', enumType: NotificationSubscriptionType::class)]
    private NotificationSubscriptionType $type = NotificationSubscriptionType::Invalid;

    #[ORM\ManyToOne(inversedBy: 'notificationSubscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 198)]
    private ?string $subscriptionHash = null;

    #[ORM\Column]
    private array $subscription = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $expired = false;

    #[ORM\Column(nullable: true)]
    private ?int $maxPaddingLength = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User|UserInterface
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSubscriptionHash(): string
    {
        return $this->subscriptionHash;
    }

    public function setSubscriptionHash(string $subscriptionHash): static
    {
        $this->subscriptionHash = $subscriptionHash;

        return $this;
    }

    public function getSubscription(): array
    {
        return $this->subscription ?? [];
    }

    public function setSubscription(array $subscription): static
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getType(): NotificationSubscriptionType
    {
        return $this->type;
    }

    public function setType(NotificationSubscriptionType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEndpoint(): string
    {
        return match($this->type) {
            NotificationSubscriptionType::WebPush => Arr::get($this->getSubscription(), 'endpoint', ''),
            default => ''
        };
    }

    public function getPublicKey(): string
    {
        return match($this->type) {
            NotificationSubscriptionType::WebPush => Arr::get($this->getSubscription(), 'keys.p256dh', ''),
            default => ''
        };
    }

    public function getAuthToken(): string
    {
        return match($this->type) {
            NotificationSubscriptionType::WebPush => Arr::get($this->getSubscription(), 'keys.auth', ''),
            default => ''
        };
    }

    public function getContentEncoding(): string
    {
        return match($this->type) {
            NotificationSubscriptionType::WebPush => Arr::get($this->getSubscription(), 'content-encoding', 'aesgcm'),
            default => ''
        };
    }

    public function calculateHash(): string
    {
        return match($this->type) {
            NotificationSubscriptionType::WebPush => md5( $this->getEndpoint() ),
            default => ''
        };
    }

    public function isExpired(): bool
    {
        return $this->expired;
    }

    public function setExpired(bool $expired): static
    {
        $this->expired = $expired;

        return $this;
    }

    public function getMaxPaddingLength(): ?int
    {
        return $this->maxPaddingLength;
    }

    public function setMaxPaddingLength(?int $maxPaddingLength): static
    {
        $this->maxPaddingLength = $maxPaddingLength;

        return $this;
    }
}
