<?php

namespace App\Entity;

use App\Repository\ZoneTagRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ZoneTagRepository::class)
 */
class ZoneTag
{
    const TagNone           = 0;
    const TagHelp           = 1;
    const TagResource       = 2;
    const TagItems          = 3;
    const TagImportantItems = 4;
    const TagDepleted       = 5;
    const TagTempSecured    = 6;
    const TagRuinDig        = 7;
    const Tag5To8Zombies    = 8;
    const Tag9OrMoreZombies = 9;
    const TagCamping        = 10;
    const TagExploreRuin    = 11;
    const TagLostSoul       = 12;
    
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $label;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $icon;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $ref;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
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

    public function getRef(): ?int
    {
        return $this->ref;
    }

    public function setRef(int $ref): self
    {
        $this->ref = $ref;

        return $this;
    }
}
