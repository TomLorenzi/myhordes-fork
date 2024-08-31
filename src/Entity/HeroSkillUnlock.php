<?php

namespace App\Entity;

use App\Repository\HeroSkillUnlockRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: HeroSkillUnlockRepository::class)]
#[Table]
#[UniqueConstraint(name: 'hero_skill_unlock_unique', columns: ['user_id', 'season_id', 'skill_id'])]
class HeroSkillUnlock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Season $season = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?HeroSkillPrototype $skill = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;

        return $this;
    }

    public function getSkill(): ?HeroSkillPrototype
    {
        return $this->skill;
    }

    public function setSkill(?HeroSkillPrototype $skill): static
    {
        $this->skill = $skill;

        return $this;
    }
}
