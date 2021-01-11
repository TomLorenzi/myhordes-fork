<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AffectItemSpawnRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="affect_item_spawn_name_unique",columns={"name"})
 * })
 */
class AffectItemSpawn
{
    const DropTargetDefault = 0;
    const DropTargetRucksack = 1;
    const DropTargetFloor = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemPrototype")
     */
    private $prototype;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemGroup")
     */
    private $itemGroup;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $count;

    /**
     * @ORM\Column(type="integer")
     */
    private $spawnTarget = self::DropTargetDefault;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getItemGroup(): ?ItemGroup
    {
        return $this->itemGroup;
    }

    public function setItemGroup(?ItemGroup $itemGroup): self
    {
        $this->itemGroup = $itemGroup;

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

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getSpawnTarget(): ?int
    {
        return $this->spawnTarget;
    }

    public function setSpawnTarget(int $spawnTarget): self
    {
        $this->spawnTarget = $spawnTarget;

        return $this;
    }
}
