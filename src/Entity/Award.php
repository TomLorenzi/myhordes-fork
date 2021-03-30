<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AwardRepository")
 * @package App\Entity
 */
class Award {

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="awards")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AwardPrototype")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $prototype;

    public function getUser(): ?User {
        return  $this->user;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getPrototype(): ?AwardPrototype {
        return $this->prototype;
    }

    public function setPrototype(AwardPrototype $value): self {
        $this->prototype = $value;
        return $this;
    }

    public function setUser(User $value): self {
        $this->user = $value;
        return $this;
    }

}