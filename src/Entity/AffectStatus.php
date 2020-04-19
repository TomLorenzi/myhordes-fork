<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AffectStatusRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="name_unique",columns={"name"})
 * })
 */
class AffectStatus
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CitizenStatus")
     */
    private $initial;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CitizenStatus")
     */
    private $result;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $resetThirstCounter;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $counter;

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

    public function getInitial(): ?CitizenStatus
    {
        return $this->initial;
    }

    public function setInitial(?CitizenStatus $initial): self
    {
        $this->initial = $initial;

        return $this;
    }

    public function getResult(): ?CitizenStatus
    {
        return $this->result;
    }

    public function setResult(?CitizenStatus $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getResetThirstCounter(): ?bool
    {
        return $this->resetThirstCounter;
    }

    public function setResetThirstCounter(?bool $resetThirstCounter): self
    {
        $this->resetThirstCounter = $resetThirstCounter;

        return $this;
    }

    public function getCounter(): ?int
    {
        return $this->counter;
    }

    public function setCounter(?int $counter): self
    {
        $this->counter = $counter;

        return $this;
    }
}
