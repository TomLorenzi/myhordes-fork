<?php

namespace App\Entity;

use App\Repository\TownJoinNotificationAccumulationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TownJoinNotificationAccumulationRepository::class)]
#[UniqueConstraint(name: 'town_join_notification_unique', columns: ['town_id', 'subject_id'])]
#[ORM\Index(columns: ['due', 'handled'], name: 'town_join_notification_unique_idx')]
class TownJoinNotificationAccumulation
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue("CUSTOM")]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Town $town = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $subject = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $due = null;

    #[ORM\Column(type: Types::JSON)]
    private array $friends = [];

    #[ORM\Column]
    private bool $handled = false;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(?Town $town): static
    {
        $this->town = $town;

        return $this;
    }

    public function getSubject(): ?User
    {
        return $this->subject;
    }

    public function setSubject(?User $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getDue(): ?\DateTimeInterface
    {
        return $this->due;
    }

    public function setDue(\DateTimeInterface $due): static
    {
        $this->due = $due;

        return $this;
    }

    public function getFriends(): array
    {
        return $this->friends;
    }

    public function setFriends(array $friends): static
    {
        $this->friends = $friends;

        return $this;
    }

    public function addFriend(User $friend): static
    {
        $this->friends = array_unique( [...$this->friends, $friend->getId()] );

        return $this;
    }

    public function isHandled(): bool
    {
        return $this->handled;
    }

    public function setHandled(bool $handled): static
    {
        $this->handled = $handled;

        return $this;
    }
}
