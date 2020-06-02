<?php

namespace App\Entity;

use App\Repository\HeroSkillRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HeroSkillRepository::class)
 */
class HeroSkill
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="heroSkills")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=HeroSkillPrototype::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $prototype;

    /**
     * @ORM\Column(type="date")
     */
    private $dateUnlock;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPrototype(): ?HeroSkillPrototype
    {
        return $this->prototype;
    }

    public function setPrototype(?HeroSkillPrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }

    public function getDateUnlock(): ?\DateTimeInterface
    {
        return $this->dateUnlock;
    }

    public function setDateUnlock(\DateTimeInterface $dateUnlock): self
    {
        $this->dateUnlock = $dateUnlock;

        return $this;
    }
}
