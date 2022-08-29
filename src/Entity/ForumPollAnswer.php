<?php

namespace App\Entity;

use App\Repository\ForumPollAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumPollAnswerRepository::class)]
class ForumPollAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer')]
    private $num;
    #[ORM\ManyToOne(targetEntity: ForumPoll::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $poll;
    #[ORM\Column(type: 'json', nullable: true)]
    private $tags = [];
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
    protected function getTags(): ?array
    {
        return $this->tags;
    }
    protected function setTags(?array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }
    public function getTagNumber(string $group, string $tag): int {
        if ($group === '' && $tag === '') return $this->getNum();
        return ($this->getTags() ?? [])["$group:$tag"] ?? 0;
    }
    public function setTagNumber(string $group, string $tag, int $num): self {
        $data = $this->getTags() ?? [];
        $data["$group:$tag"] = $num;
        return $this->setTags($data);
    }
    public function incTagNumber(string $group, string $tag): self {
        return $this->setTagNumber( $group, $tag, $this->getTagNumber( $group, $tag ) + 1 );
    }
    public function getTagTitles(): array {
        return array_keys( $this->getTags() ?? [] );
    }
}
