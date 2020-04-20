<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CitizenVoteRepository")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="assoc_unique",columns={"autor_id","voted_citizen_id", "role_id"})
 * })
 */
class CitizenVote
{

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Citizen")
     * @ORM\JoinColumn(nullable=false)
     */
    private $autor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Citizen")
     * @ORM\JoinColumn(nullable=false)
     */
    private $votedCitizen;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\CitizenRole")
     */
    private $role;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAutor(): ?Citizen
    {
        return $this->autor;
    }

    public function setAutor(?Citizen $autor): self
    {
        $this->autor = $autor;

        return $this;
    }

    public function getVotedCitizen(): ?Citizen
    {
        return $this->votedCitizen;
    }

    public function setVotedCitizen(?Citizen $votedCitizen): self
    {
        $this->votedCitizen = $votedCitizen;

        return $this;
    }

    public function getRole(): ?CitizenRole
    {
        return $this->role;
    }

    public function setRole(CitizenRole $role): self
    {
        $this->role = $role;

        return $this;
    }
}
