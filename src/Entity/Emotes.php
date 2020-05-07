<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EmotesRepository")
 * @UniqueEntity("tag")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="tag_unique",columns={"tag"})
 * })
 */
class Emotes {

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $tag;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $path;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $requiresUnlock;

    public function getId(): ?int {
        return $this->id;
    }

    public function getTag(): ?string {
        return $this->tag;
    }

    public function getPath(): ?string {
        return $this->path;
    }

    public function setTag(string $value) {
        $this->tag = $value;
    }

    public function setPath(string $value) {
        $this->path = $value;
    }

    public function setIsActive(bool $value) {
        $this->isActive = $value;
    }

    public function setRequiresUnlock(bool $value) {
        $this->requiresUnlock = $value;
    }

}