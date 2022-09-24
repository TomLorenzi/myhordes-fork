<?php

namespace App\Entity;

use App\Interfaces\NamedEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\CitizenRoleRepository')]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'citizen_role_name_unique', columns: ['name'])]
class CitizenRole implements NamedEntity
{
    const DEFAULT = 'none';
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 16)]
    private $name;
    #[ORM\Column(type: 'string', length: 190)]
    private $label;
    #[ORM\Column(type: 'string', length: 32)]
    private $icon;
    #[ORM\Column(type: 'boolean')]
    private $secret;
    #[ORM\Column(type: 'boolean')]
    private $hidden;
    #[ORM\Column(type: 'boolean')]
    private $votable;
    #[ORM\Column(type: 'text', nullable: true)]
    private $message;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $help_section;
    public function __construct()
    {
    }
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
    public function getLabel(): ?string
    {
        return $this->label;
    }
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
    public function getIcon(): ?string
    {
        return $this->icon;
    }
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
    public function getSecret(): ?bool
    {
        return $this->secret;
    }
    public function setSecret(bool $secret): self
    {
        $this->secret = $secret;

        return $this;
    }
    public function getHidden(): ?bool
    {
        return $this->hidden;
    }
    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }
    public function getVotable(): ?bool
    {
        return $this->votable;
    }
    public function setVotable(bool $votable): self
    {
        $this->votable = $votable;

        return $this;
    }
    public function getMessage(): ?string
    {
        return $this->message;
    }
    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }
    public function getHelpSection(): ?string
    {
        return $this->help_section;
    }
    public function setHelpSection(?string $help_section): self
    {
        $this->help_section = $help_section;

        return $this;
    }
    public static function getTranslationDomain(): ?string
    {
        return 'game';
    }
}
