<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\EmotesRepository')]
#[UniqueEntity('tag')]
#[Table]
#[UniqueConstraint(name: 'tag_unique', columns: ['tag'])]
class Emotes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 32)]
    private $tag;
    #[ORM\Column(type: 'string', length: 64)]
    private $path;
    #[ORM\Column(type: 'boolean')]
    private $isActive;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $requiresUnlock;
    #[ORM\Column(type: 'integer')]
    private $orderIndex;
    #[ORM\Column(type: 'boolean')]
    private $i18n = false;
    public function getOrderIndex(): ?int {
        return $this->orderIndex;
    }
    public function getId(): ?int {
        return $this->id;
    }
    public function getTag(): ?string {
        return $this->tag;
    }
    public function getPath(): ?string {
        return $this->path;
    }
    public function setOrderIndex(int $value): self {
        $this->orderIndex = $value;

        return $this;
    }
    public function setTag(string $value): self {
        $this->tag = $value;

        return $this;
    }
    public function setPath(string $value): self {
        $this->path = $value;

        return $this;
    }
    public function setIsActive(bool $value): self {
        $this->isActive = $value;

        return $this;
    }
    public function setRequiresUnlock(bool $value): self {
        $this->requiresUnlock = $value;

        return $this;
    }
    public function getI18n(): ?bool
    {
        return $this->i18n;
    }
    public function setI18n(bool $i18n): self
    {
        $this->i18n = $i18n;

        return $this;
    }
}