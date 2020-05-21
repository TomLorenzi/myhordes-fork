<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AffectItemConsumeRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="affect_item_consume_name_unique",columns={"name"})
 * })
 */
class AffectItemConsume
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemPrototype")
     * @ORM\JoinColumn(nullable=false)
     */
    private $prototype;

    /**
     * @ORM\Column(type="integer")
     */
    private $count;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

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

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

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
}
