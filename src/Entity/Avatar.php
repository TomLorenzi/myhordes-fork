<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AvatarRepository")
 */
class Avatar
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=9)
     */
    private $format;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $filename;

    /**
     * @ORM\Column(type="datetime")
     */
    private $changed;

    /**
     * @ORM\Column(type="blob")
     */
    private $image;

    /**
     * @ORM\Column(type="blob", nullable=true)
     */
    private $smallImage = null;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $smallName;

    /**
     * @ORM\Column(type="integer")
     */
    private $x;

    /**
     * @ORM\Column(type="integer")
     */
    private $y;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getChanged(): ?\DateTimeInterface
    {
        return $this->changed;
    }

    public function setChanged(\DateTimeInterface $changed): self
    {
        $this->changed = $changed;

        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getSmallImage()
    {
        return $this->smallImage;
    }

    public function setSmallImage($smallImage): self
    {
        $this->smallImage = $smallImage;

        return $this;
    }

    public function getSmallName(): ?string
    {
        return $this->smallName ?? $this->filename;
    }

    public function setSmallName(?string $smallName): self
    {
        $this->smallName = $smallName;

        return $this;
    }

    public function getX(): ?int
    {
        return max(1,$this->x);
    }

    public function setX(int $x): self
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): ?int
    {
        return max(1,$this->y);
    }

    public function setY(int $y): self
    {
        $this->y = $y;

        return $this;
    }

    public function isClassic(): bool {
        $aspect = (float)$this->getX() / (float)$this->getY();
        return $aspect > 2.75 && $aspect < 3.25;
    }
}
