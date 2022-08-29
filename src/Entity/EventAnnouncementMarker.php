<?php

namespace App\Entity;

use App\Repository\EventAnnouncementMarkerRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: EventAnnouncementMarkerRepository::class)]
#[Table]
#[UniqueConstraint(name: 'event_announcement_marker_unique', columns: ['name', 'identifier'])]
class EventAnnouncementMarker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 64)]
    private $name;
    #[ORM\Column(type: 'integer')]
    private $identifier;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }
    public function setIdentifier(int $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }
}
