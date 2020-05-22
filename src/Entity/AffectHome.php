<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AffectHomeRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="affect_home_name_unique",columns={"name"})
 * })
 */
class AffectHome
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $additionalDefense = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $additionalStorage = 0;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdditionalDefense(): ?int
    {
        return $this->additionalDefense;
    }

    public function setAdditionalDefense(int $additionalDefense): self
    {
        $this->additionalDefense = $additionalDefense;

        return $this;
    }

    public function getAdditionalStorage(): ?int
    {
        return $this->additionalStorage;
    }

    public function setAdditionalStorage(int $additionalStorage): self
    {
        $this->additionalStorage = $additionalStorage;

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
}
