<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\RequireZombiePresenceRepository')]
class RequireZombiePresence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 64)]
    private $name;
    #[ORM\Column(type: 'integer')]
    private $number;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $mustBlock;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $tempControlAllowed;
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
    public function getNumber(): ?int
    {
        return $this->number;
    }
    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }
    public function getMustBlock(): ?bool
    {
        return $this->mustBlock;
    }
    public function setMustBlock(?bool $mustBlock): self
    {
        $this->mustBlock = $mustBlock;

        return $this;
    }
    public function getTempControlAllowed(): ?bool
    {
        return $this->tempControlAllowed;
    }
    public function setTempControlAllowed(?bool $tempControlAllowed): self
    {
        $this->tempControlAllowed = $tempControlAllowed;

        return $this;
    }
}
