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
     * @ORM\JoinColumn(nullable=false)
     */
    private $prototype;

    public function getUser(): ?User {
        return  $this->user;
    }

    public function getPrototype(): ?AwardPrototype {
        return $this->prototype;
    }

    public function setPrototype(AwardPrototype $value) {
        $this->prototype = $value;
    }

    public function setUser(User $value) {
        $this->user = $value;
    }

}