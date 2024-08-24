<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\LogEntryTemplateRepository')]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'log_entry_template_name_unique', columns: ['name'])]
class LogEntryTemplate
{
    const TypeVarious         = 0;
    const TypeCrimes          = 1;
    const TypeBank            = 2;
    const TypeDump            = 3;
    const TypeConstruction    = 4;
    const TypeWorkshop        = 5;
    const TypeDoor            = 6;
    const TypeWell            = 7;
    const TypeCitizens        = 8;
    const TypeNightly         = 9;
    const TypeHome            = 10;
    const TypeChat            = 11;
    const TypeCatapult        = 16;
    const TypeWellFill        = 17;
    const TypeGPMNotification = 100;
    const TypeHXPLog = 200;
    const ClassNone                 = 0;
    const ClassWarning              = 1;
    const ClassCritical             = 2;
    const ClassInfo                 = 3;
    const ClassChat                 = 4;
    const ClassDanger               = 5;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text')]
    private $text;
    #[ORM\Column(type: 'integer')]
    private $type = self::TypeVarious;
    #[ORM\Column(type: 'integer')]
    private $class;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $secondaryType;
    #[ORM\Column(type: 'array', nullable: true)]
    private $variableTypes = [];
    #[ORM\Column(type: 'string', length: 128)]
    private $name;
    #[ORM\Column(type: 'boolean')]
    private $nonVolatile;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getText(): ?string
    {
        return $this->text;
    }
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }
    public function getType(): ?int
    {
        return $this->type;
    }
    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }
    public function getClass(): ?int
    {
        return $this->class;
    }
    public function setClass(int $class): self
    {
        $this->class = $class;

        return $this;
    }
    public function getSecondaryType(): ?int
    {
        return $this->secondaryType;
    }
    public function setSecondaryType(?int $secondaryType): self
    {
        $this->secondaryType = $secondaryType;

        return $this;
    }
    public function getVariableTypes(): ?array
    {
        return $this->variableTypes;
    }
    public function setVariableTypes(?array $variableTypes): self
    {
        $this->variableTypes = $variableTypes;

        return $this;
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
    public function getNonVolatile(): ?bool
    {
        return $this->nonVolatile;
    }
    public function setNonVolatile(bool $nonVolatile): self
    {
        $this->nonVolatile = $nonVolatile;

        return $this;
    }
}
