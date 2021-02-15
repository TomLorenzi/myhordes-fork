<?php

namespace App\Entity;

use App\Interfaces\NamedEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ItemActionRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="item_action_name_unique",columns={"name"})
 * })
 */
class ItemAction implements NamedEntity
{
    const PoisonHandlerIgnore = 0;
    const PoisonHandlerTransgress = 1;
    const PoisonHandlerConsume = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private $label;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Requirement")
     */
    private $requirements;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Result")
     */
    private $results;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemTargetDefinition", cascade={"persist", "remove"})
     */
    private $target;

    /**
     * @ORM\Column(type="integer")
     */
    private $poisonHandler = self::PoisonHandlerIgnore;

    /**
     * @ORM\Column(type="boolean")
     */
    private $keepsCover;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $confirm;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $allowWhenTerrorized = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $tooltip;

    public function __construct()
    {
        $this->requirements = new ArrayCollection();
        $this->results = new ArrayCollection();
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

    /**
     * @return Collection|Requirement[]
     */
    public function getRequirements(): Collection
    {
        return $this->requirements;
    }

    public function addRequirement(Requirement $requirement): self
    {
        if (!$this->requirements->contains($requirement)) {
            $this->requirements[] = $requirement;
        }

        return $this;
    }

    public function removeRequirement(Requirement $requirement): self
    {
        if ($this->requirements->contains($requirement)) {
            $this->requirements->removeElement($requirement);
        }

        return $this;
    }

    public function clearRequirements(): self
    {
        $this->requirements->clear();
        return $this;
    }

    /**
     * @return Collection|Result[]
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(Result $result): self
    {
        if (!$this->results->contains($result)) {
            $this->results[] = $result;
        }

        return $this;
    }

    public function removeResult(Result $result): self
    {
        if ($this->results->contains($result)) {
            $this->results->removeElement($result);
        }

        return $this;
    }

    public function clearResults(): self
    {
        $this->results->clear();
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

    public function getTarget(): ?ItemTargetDefinition
    {
        return $this->target;
    }

    public function setTarget(?ItemTargetDefinition $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getPoisonHandler(): ?int
    {
        return $this->poisonHandler;
    }

    public function setPoisonHandler(int $poisonHandler): self
    {
        $this->poisonHandler = $poisonHandler;

        return $this;
    }

    public function getKeepsCover(): ?bool
    {
        return $this->keepsCover;
    }

    public function setKeepsCover(bool $keepsCover): self
    {
        $this->keepsCover = $keepsCover;

        return $this;
    }

    public function getConfirm(): ?bool
    {
        return $this->confirm;
    }

    public function setConfirm(?bool $confirm): self
    {
        $this->confirm = $confirm;

        return $this;
    }

    public function getAllowWhenTerrorized(): ?bool
    {
        return $this->allowWhenTerrorized;
    }

    public function setAllowWhenTerrorized(?bool $allowWhenTerrorized): self
    {
        $this->allowWhenTerrorized = $allowWhenTerrorized;

        return $this;
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    public function setTooltip(?string $tooltip): self
    {
        $this->tooltip = $tooltip;

        return $this;
    }
}
