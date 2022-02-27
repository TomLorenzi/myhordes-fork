<?php

namespace App\Entity;

use App\Repository\GameProfilerEntryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GameProfilerEntryRepository::class)
 */
class GameProfilerEntry
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    /**
     * @ORM\ManyToOne(targetEntity=TownRankingProxy::class)
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $town;

    /**
     * @ORM\ManyToOne(targetEntity=CitizenRankingProxy::class)
     */
    private $citizen;

    /**
     * @ORM\Column(type="integer")
     */
    private $day;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $foreign1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $foreign2;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $data = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;

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

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getTown(): ?TownRankingProxy
    {
        return $this->town;
    }

    public function setTown(?TownRankingProxy $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function getCitizen(): ?CitizenRankingProxy
    {
        return $this->citizen;
    }

    public function setCitizen(?CitizenRankingProxy $citizen): self
    {
        $this->citizen = $citizen;

        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getForeign1(): ?int
    {
        return $this->foreign1;
    }

    public function setForeign1(?int $foreign1): self
    {
        $this->foreign1 = $foreign1;

        return $this;
    }

    public function getForeign2(): ?int
    {
        return $this->foreign2;
    }

    public function setForeign2(?int $foreign2): self
    {
        $this->foreign2 = $foreign2;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
