<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AwardPrototypeRepository")
 * @UniqueEntity("title")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="title_unique",columns={"title"})
 *     })
 */
class AwardPrototype {

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * Number of pictos required to unlock this Award
     * @ORM\Column(type="integer")
     */
    private $unlockQuantity;

    /**
     * This field matches to the tag field from Emotes
     * @ORM\Column(type="string", length=32)
     */
    private $associatedTag;

    /**
     * @ORM\ManyToOne(targetEntity=PictoPrototype::class, inversedBy="awards")
     * @ORM\JoinColumn(nullable=false)
     */
    private $associatedPicto;

    public function __construct() {
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function getUnlockQuantity(): ?int {
        return $this->unlockQuantity;
    }

    public function getAssociatedTag(): ?string {
        return $this->associatedTag;
    }

    public function getAssociatedPicto(): ?PictoPrototype {
        return $this->associatedPicto;
    }

    public function setTitle(string $value): self {
        $this->title = $value;

        return $this;
    }

    public function setUnlockQuantity(int $value) {
        $this->unlockQuantity = $value;
    }

    public function setAssociatedTag(string $value): self {
        $this->associatedTag = $value;

        return $this;
    }

    public function setAssociatedPicto(?PictoPrototype $associatedPicto): self
    {
        $this->associatedPicto = $associatedPicto;

        return $this;
    }

}