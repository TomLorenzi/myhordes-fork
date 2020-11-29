<?php

namespace App\Entity;

use App\Repository\EventActivationMarkerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EventActivationMarkerRepository::class)
 */
class EventActivationMarker
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Citizen::class)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $citizen;

    /**
     * @ORM\ManyToOne(targetEntity=Town::class)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $town;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $event;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $enabledAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $disabledAt;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(?Town $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        if     (!$this->active && $active) $this->setEnabledAt(new \DateTime());
        elseif ($this->active && !$active) $this->setDisabledAt(new \DateTime());
        $this->active = $active;

        return $this;
    }

    public function getEnabledAt(): ?\DateTimeInterface
    {
        return $this->enabledAt;
    }

    public function setEnabledAt(?\DateTimeInterface $enabledAt): self
    {
        $this->enabledAt = $enabledAt;

        return $this;
    }

    public function getDisabledAt(): ?\DateTimeInterface
    {
        return $this->disabledAt;
    }

    public function setDisabledAt(?\DateTimeInterface $disabledAt): self
    {
        $this->disabledAt = $disabledAt;

        return $this;
    }
}
