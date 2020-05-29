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
     * This field matches to the name field of Picto
     * @ORM\Column(type="string", length=64)
     */
    private $associatedPicto;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $iconPath;

    /**
     * This is the text that displays when the mouse hovers over the title, I.E. "Thefts x10"
     * @ORM\Column(type="string", length=65)
     */
    private $titleHoverText;

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

    public function getAssociatedPicto(): ?string {
        return $this->associatedPicto;
    }

    public function getIconPath(): ?string {
        return $this->iconPath;
    }

    public function getTitleHoverText(): ?string {
        return $this->titleHoverText;
    }

    public function setTitle(string $value) {
        $this->title = $value;
    }

    public function setUnlockQuantity(int $value) {
        $this->unlockQuantity = $value;
    }

    public function setAssociatedTag(string $value) {
        $this->associatedTag = $value;
    }

    public function setAssociatedPicto(string $value) {
        $this->associatedPicto = $value;
    }

    public function setIconPath(string $value) {
        $this->iconPath = $value;
    }

    public function setTitleHoverText(string $value) {
        $this->titleHoverText = $value;
    }

}