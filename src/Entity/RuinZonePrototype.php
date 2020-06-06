<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RuinZonePrototypeRepository")
 */
class RuinZonePrototype
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private $label;

    /**
     * @ORM\ManyToOne(targetEntity=ItemPrototype::class)
     */
    private $keyImprint;

    /**
     * @ORM\ManyToOne(targetEntity=ItemPrototype::class)
     */
    private $keyItem;


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

    public function getKeyImprint(): ?ItemPrototype
    {
        return $this->keyImprint;
    }

    public function setKeyImprint(?ItemPrototype $keyImprint): self
    {
        $this->keyImprint = $keyImprint;

        return $this;
    }

    public function getKeyItem(): ?ItemPrototype
    {
        return $this->keyItem;
    }

    public function setKeyItem(?ItemPrototype $keyItem): self
    {
        $this->keyItem = $keyItem;

        return $this;
    }
}
