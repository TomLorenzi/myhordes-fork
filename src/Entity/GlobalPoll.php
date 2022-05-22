<?php

namespace App\Entity;

use App\Repository\GlobalPollRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GlobalPollRepository::class)
 */
class GlobalPoll
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="json")
     */
    private $title = [];

    /**
     * @ORM\Column(type="json")
     */
    private $description = [];

    /**
     * @ORM\Column(type="json")
     */
    private $answers = [];

    /**
     * @ORM\Column(type="json")
     */
    private $shortDescription = [];

    /**
     * @ORM\Column(type="datetime")
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $endDate;

    /**
     * @ORM\OneToOne(targetEntity=ForumPoll::class, inversedBy="globalPoll", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $poll;

    /**
     * @ORM\Column(type="boolean")
     */
    private $showResultsImmediately;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?array
    {
        return $this->title;
    }

    public function setTitle(array $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?array
    {
        return $this->description;
    }

    public function setDescription(array $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAnswers(): ?array
    {
        return $this->answers;
    }

    public function setAnswers(array $answers): self
    {
        $this->answers = $answers;

        return $this;
    }

    public function getShortDescription(): ?array
    {
        return $this->shortDescription;
    }

    public function setShortDescription(array $shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPoll(): ?ForumPoll
    {
        return $this->poll;
    }

    public function setPoll(ForumPoll $poll): self
    {
        $this->poll = $poll;

        return $this;
    }

    public function getShowResultsImmediately(): ?bool
    {
        return $this->showResultsImmediately;
    }

    public function setShowResultsImmediately(bool $showResultsImmediately): self
    {
        $this->showResultsImmediately = $showResultsImmediately;

        return $this;
    }
}
