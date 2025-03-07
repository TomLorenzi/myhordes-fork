<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: 'App\Repository\CitizenVoteRepository')]
#[Table]
#[UniqueConstraint(name: 'citizen_vote_assoc_unique', columns: ['autor_id', 'voted_citizen_id', 'role_id'])]
class CitizenVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Citizen')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $autor;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Citizen')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $votedCitizen;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\CitizenRole')]
    #[ORM\JoinColumn(nullable: false)]
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
