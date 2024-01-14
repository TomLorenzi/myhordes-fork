<?php

namespace App\Entity;

use App\Repository\GlobalPollRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GlobalPollRepository::class)]
class GlobalPoll
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'json')]
    private $title = [];
    #[ORM\Column(type: 'json')]
    private $description = [];
    #[ORM\Column(type: 'json')]
    private $answers = [];
    #[ORM\Column(type: 'json')]
    private $shortDescription = [];
    #[ORM\Column(type: 'datetime')]
    private $startDate;
    #[ORM\Column(type: 'datetime')]
    private $endDate;
    #[ORM\OneToOne(targetEntity: ForumPoll::class, inversedBy: 'globalPoll', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $poll;
    #[ORM\Column(type: 'boolean')]
    private $showResultsImmediately;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $reveal_date = null;

    #[ORM\Column]
    private ?bool $multipleChoice = null;
    public function getId(): ?int
    {
        return $this->id;
    }
    protected function getTitle(): ?array
    {
        return $this->title;
    }
    protected function setTitle(array $title): self
    {
        $this->title = $title;

        return $this;
    }
    protected function getDescription(): array
    {
        return $this->description;
    }
    protected function setDescription(array $description): self
    {
        $this->description = $description;

        return $this;
    }
    protected function getAnswers(): array
    {
        return $this->answers;
    }
    protected function setAnswers(array $answers): self
    {
        $this->answers = $answers;

        return $this;
    }
    protected function getShortDescription(): array
    {
        return $this->shortDescription;
    }
    protected function setShortDescription(array $shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }
    public function getTitleByLang(string $lang): ?string
    {
        return $this->getTitle()[$lang] ?? null;
    }
    public function setTitleByLang(string $lang, string $title): self
    {
        $d = $this->getTitle();
        $d[$lang] = $title;
        return $this->setTitle( $d );
    }
    public function getDescriptionByLang(string $lang): ?string
    {
        return $this->getDescription()[$lang] ?? null;
    }
    public function setDescriptionByLang(string $lang, string $description): self
    {
        $d = $this->getDescription();
        $d[$lang] = $description;
        return $this->setDescription( $d );
    }
    public function getShortDescriptionByLang(string $lang): ?string
    {
        return $this->getShortDescription()[$lang] ?? null;
    }
    public function setShortDescriptionByLang(string $lang, string $shortDescription): self
    {
        $d = $this->getShortDescription();
        $d[$lang] = $shortDescription;
        return $this->setShortDescription( $d );
    }
    public function getAnswerTitleByLang(ForumPollAnswer|int $answer, string $lang): ?string
    {
        return (($this->getAnswers()[is_object( $answer ) ? $answer->getId() : $answer] ?? [])[$lang] ?? [])['title'] ?? null;
    }
    public function setAnswerTitleByLang(ForumPollAnswer|int $answer, string $lang, string $title): self
    {
        $id = is_object( $answer ) ? $answer->getId() : $answer;
        $data = $this->getAnswers();
        if (!array_key_exists( $id, $data)) $data[$id] = [$lang => ['title' => $title, 'description' => null]];
        elseif (!array_key_exists( $lang, $data[$id])) $data[$id][$lang] = ['title' => $title, 'description' => null];
        else $data[$id][$lang]['title'] = $title;
        return $this->setAnswers($data);
    }
    public function getAnswerDescriptionByLang(ForumPollAnswer|int $answer, string $lang): ?string
    {
        return (($this->getAnswers()[is_object( $answer ) ? $answer->getId() : $answer] ?? [])[$lang] ?? [])['description'] ?? null;
    }
    public function setAnswerDescriptionByLang(ForumPollAnswer|int $answer, string $lang, string $description): self
    {
        $id = is_object( $answer ) ? $answer->getId() : $answer;
        $data = $this->getAnswers();
        if (!array_key_exists( $id, $data)) $data[$id] = [$lang => ['title' => null, 'description' => $description]];
        elseif (!array_key_exists( $lang, $data[$id])) $data[$id][$lang] = ['title' => null, 'description' => $description];
        else $data[$id][$lang]['description'] = $description;
        return $this->setAnswers($data);
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

    public function getRevealDate(): ?\DateTimeInterface
    {
        return $this->reveal_date;
    }

    public function setRevealDate(?\DateTimeInterface $reveal_date): static
    {
        $this->reveal_date = $reveal_date;

        return $this;
    }

    public function isMultipleChoice(): ?bool
    {
        return $this->multipleChoice;
    }

    public function setMultipleChoice(bool $multipleChoice): static
    {
        $this->multipleChoice = $multipleChoice;

        return $this;
    }
}
