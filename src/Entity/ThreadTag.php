<?php

namespace App\Entity;

use App\Repository\ThreadTagRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ThreadTagRepository::class)]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'thread_tag_name_unique', columns: ['name'])]
class ThreadTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 16)]
    private $name;
    #[ORM\Column(type: 'string', length: 16)]
    private $label;
    #[ORM\Column(type: 'binary')]
    private $color;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $permissionMap;
    private ?string $color_cache = null;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getLabel(): ?string
    {
        return $this->label;
    }
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
    public function getColor()
    {
        return $this->color_cache ?? ($this->color_cache = is_string($this->color) ? $this->color : stream_get_contents($this->color));
    }
    public function setColor($color): self
    {
        $this->color = $this->color_cache = $color;

        return $this;
    }
    public function getPermissionMap(): ?int
    {
        return $this->permissionMap;
    }
    public function setPermissionMap(?int $permissionMap): self
    {
        $this->permissionMap = $permissionMap;

        return $this;
    }
}
