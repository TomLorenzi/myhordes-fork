<?php

namespace App\Entity;

use App\Interfaces\RandomEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ItemGroupEntryRepository")
 */
class ItemGroupEntry implements RandomEntry
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $chance;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemPrototype", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $prototype;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemGroup", inversedBy="entries", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $itemGroup;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChance(): ?int
    {
        return $this->chance;
    }

    public function setChance(int $chance): self
    {
        $this->chance = $chance;

        return $this;
    }

    public function getPrototype(): ?ItemPrototype
    {
        return $this->prototype;
    }

    public function setPrototype(ItemPrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }

    public function getItemGroup(): ?ItemGroup
    {
        return $this->itemGroup;
    }

    public function setItemGroup(?ItemGroup $itemGroup): self
    {
        $this->itemGroup = $itemGroup;

        return $this;
    }
}
