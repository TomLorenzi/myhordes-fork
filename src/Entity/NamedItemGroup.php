<?php

namespace App\Entity;

use App\Repository\NamedItemGroupRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NamedItemGroupRepository::class)
 */
class NamedItemGroup
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=ItemGroup::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $itemGroup;

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
