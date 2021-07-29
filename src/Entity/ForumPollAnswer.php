<?php

namespace App\Entity;

use App\Repository\ForumPollAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ForumPollAnswerRepository::class)
 */
class ForumPollAnswer
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
    private $num;

    /**
     * @ORM\ManyToOne(targetEntity=ForumPoll::class, inversedBy="answers")
     * @ORM\JoinColumn(nullable=false,onDelete="CASCADE")
     */
    private $poll;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNum(): ?int
    {
        return $this->num;
    }

    public function setNum(int $num): self
    {
        $this->num = $num;

        return $this;
    }

    public function getPoll(): ?ForumPoll
    {
        return $this->poll;
    }

    public function setPoll(?ForumPoll $poll): self
    {
        $this->poll = $poll;

        return $this;
    }
}
