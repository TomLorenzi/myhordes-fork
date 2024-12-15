<?php

namespace App\Entity;

use App\Repository\ChangelogRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: ChangelogRepository::class)]
#[Table]
#[UniqueConstraint(name: 'changelog_version_lang_unique', columns: ['lang', 'version'])]
class Changelog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(type: 'string', length: 64)]
    private ?string $title = null;
    #[ORM\Column(type: 'string', length: 64)]
    private ?string $version = null;
    #[ORM\Column(type: 'text')]
    private ?string $text = null;
    #[ORM\Column(type: 'string', length: 8)]
    private string $lang = "de";
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $author;
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
    public function getVersion(): ?string
    {
        return $this->version;
    }
    public function setVersion(string $version): self
    {
        $this->version = $version;

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
    public function getLang(): ?string
    {
        return $this->lang;
    }
    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }
    public function getAuthor(): ?User
    {
        return $this->author;
    }
    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }
    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
}
