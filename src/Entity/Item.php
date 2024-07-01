<?php

namespace App\Entity;

use App\Enum\ItemPoisonType;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;

#[ORM\Entity(repositoryClass: 'App\Repository\ItemRepository')]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'boolean')]
    private $broken;
    #[ORM\Column(type: 'integer', enumType: ItemPoisonType::class)]
    private ItemPoisonType $poison = ItemPoisonType::None;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\ItemPrototype', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private $prototype;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Inventory', inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: true)]
    private $inventory = null;
    #[ORM\Column(type: 'boolean')]
    private $essential = false;
    #[ORM\Column(type: 'integer')]
    private $count = 1;
    #[ORM\Column(type: 'boolean')]
    private $hidden = false;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $firstPick = false;

    private ?int $random = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getBroken(): ?bool
    {
        return $this->broken;
    }
    public function setBroken(bool $broken): self
    {
        $this->broken = $broken;

        return $this;
    }
    public function getPoison(): ItemPoisonType
    {
        return $this->poison;
    }
    public function setPoison(ItemPoisonType $poison): self
    {
        $this->poison = $poison;

        return $this;
    }
    public function getPrototype(): ?ItemPrototype
    {
        return $this->prototype;
    }
    public function setPrototype(?ItemPrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }
    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }
    public function setInventory(?Inventory $inventory): self
    {
        $this->inventory = $inventory;

        return $this;
    }
    public function getEssential(): ?bool
    {
        return $this->essential;
    }
    public function setEssential(bool $essential): self
    {
        $this->essential = $essential;

        return $this;
    }
    public function getCount(): ?int
    {
        return $this->count;
    }
    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }
    public function getHidden(): ?bool
    {
        return $this->hidden;
    }
    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }
    public function getFirstPick(): ?bool
    {
        return $this->firstPick;
    }
    public function setFirstPick(?bool $firstPick): self
    {
        $this->firstPick = $firstPick;

        return $this;
    }

    public function getRandom(): int {
        return $this->random ?? ($this->random = mt_rand());
    }
}
