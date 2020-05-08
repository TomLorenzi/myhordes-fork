<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ActionCounterRepository")
 * @Table(uniqueConstraints={@UniqueConstraint(name="assoc_unique",columns={"citizen_id","type"})})
 */
class ActionCounter
{
    const ActionTypeWell = 1;
    const ActionTypeHomeKitchen = 2;
    const ActionTypeHomeLab = 3;
    const ActionTypeTrash = 4;
    const ActionTypeComplaint = 5;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     */
    private $count = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Citizen", inversedBy="actionCounters")
     * @ORM\JoinColumn(nullable=false)
     */
    private $citizen;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function increment(int $by = 1): self {
        $this->count += max(0,$by);
        return $this;
    }

    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }

    public function setCitizen(?Citizen $citizen): self
    {
        $this->citizen = $citizen;

        return $this;
    }
}
