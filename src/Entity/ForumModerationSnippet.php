<?php

namespace App\Entity;

use App\Repository\ForumModerationSnippetRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: ForumModerationSnippetRepository::class)]
#[Table]
#[UniqueConstraint(name: 'forum_mod_snippet_unique', columns: ['lang', 'short'])]
class ForumModerationSnippet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 4)]
    private $lang;
    #[ORM\Column(type: 'string', length: 32)]
    private $short;
    #[ORM\Column(type: 'text')]
    private $text;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getLang(): ?string
    {
        return $this->lang;
    }
    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }
    public function getShort(): ?string
    {
        return $this->short;
    }
    public function setShort(string $short): self
    {
        $this->short = $short;

        return $this;
    }
    public function getText(): ?string
    {
        return $this->text;
    }
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }
}
