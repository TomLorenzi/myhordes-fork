<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ActionCounterRepository")
 * @Table(uniqueConstraints={@UniqueConstraint(name="action_counter_assoc_unique",columns={"citizen_id","type"})})
 */
class ActionCounter
{
    const ActionTypeWell        = 1;
    const ActionTypeHomeKitchen = 2;
    const ActionTypeHomeLab     = 3;
    const ActionTypeTrash       = 4;
    const ActionTypeComplaint   = 5;
    const ActionTypeRemoveLog   = 6;
    const ActionTypeSendPMItem  = 7;
    const ActionTypeSandballHit = 8;

    const PerGameActionTypes = [
        self::ActionTypeRemoveLog,
    ];

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

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $last;

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

        return $this->setLast($count === 0 ? null :new \DateTime());
    }

    public function increment(int $by = 1): self {
        $this->count += max(0,$by);
        return $this->setLast(new \DateTime());
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

    public function getLast(): ?\DateTimeInterface
    {
        return $this->last;
    }

    public function setLast(?\DateTimeInterface $last): self
    {
        $this->last = $last;

        return $this;
    }

    public function getDaily(): ?bool
    {
        return !in_array($this->type, self::PerGameActionTypes);
    }
}
