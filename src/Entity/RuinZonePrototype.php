<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RuinZonePrototypeRepository")
 */
class RuinZonePrototype
{
    const LOCKTYPE_NONE     = 0;
    const LOCKTYPE_BOTTLE   = 1;
    const LOCKTYPE_BUMP     = 2;
    const LOCKTYPE_MAGNET   = 3;

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
     * @ORM\Column(type="integer")
     */
    private $lock_type;


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

    public function getLockType(): ?int
    {
        return $this->lock_type;
    }

    public function setLockType(int $lock_type): self
    {
        $this->lock_type = $lock_type;

        return $this;
    }
}
