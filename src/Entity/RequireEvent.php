<?php

namespace App\Entity;

use App\Repository\RequireEventRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: RequireEventRepository::class)]
#[UniqueEntity('name')]
#[UniqueConstraint(name: 'require_event_unique', columns: ['name'])]
class RequireEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 255)]
    private $name;
    #[ORM\Column(type: 'string', length: 255)]
    private $eventName;
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
    public function getEventName(): ?string
    {
        return $this->eventName;
    }
    public function setEventName(string $eventName): self
    {
        $this->eventName = $eventName;

        return $this;
    }
}
