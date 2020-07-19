<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FoundRolePlayTextRepository")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="user_text_unique",columns={"user_id", "text_id"})
 * })
 */
class FoundRolePlayText
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="foundTexts")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RolePlayText")
     */
    private $text;

    /**
     * @ORM\Column(type="datetime", length=32)
     */
    private $datefound;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $imported = false;

    public function __construct(){
        $this->datefound = new \DateTime(); 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getText(): ?RolePlayText
    {
        return $this->text;
    }

    public function setText(RolePlayText $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getDateFound(): ?\DateTime
    {
        return $this->datefound;
    }

    public function setDateFound(\DateTime $datefound): self
    {
        $this->datefound = $datefound;

        return this;
    }

    public function getImported(): ?bool
    {
        return $this->imported;
    }

    public function setImported(?bool $imported): self
    {
        $this->imported = $imported;

        return $this;
    }
}
