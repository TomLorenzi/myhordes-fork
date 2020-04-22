<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FoundRolePlayerTextRepository")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="user_text_unique",columns={"user_id", "text_id"})
 * })
 */
class FoundRolePlayerText
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RolePlayerText")
     */
    private $text;

    /**
     * @ORM\Column(type="datetime", length=32)
     */
    private $datefound;

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

    public function getText(): ?RolePlayerText
    {
        return $this->text;
    }

    public function setText(RolePlayerText $text): self
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
}
