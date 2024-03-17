<?php

namespace App\Entity;

use App\Repository\PictoRollupRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: PictoRollupRepository::class)]
#[Table]
#[UniqueConstraint(name: 'picto_rollup_assoc_unique', columns: ['user_id','prototype_id','old','imported','total'])]
class PictoRollup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?PictoPrototype $prototype = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?int $count = null;

    #[ORM\Column]
    private ?bool $imported = null;

    #[ORM\Column]
    private ?bool $old = null;

    #[ORM\Column]
    private ?bool $total = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrototype(): ?PictoPrototype
    {
        return $this->prototype;
    }

    public function setPrototype(?PictoPrototype $prototype): static
    {
        $this->prototype = $prototype;

        return $this;
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

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function isImported(): ?bool
    {
        return $this->imported;
    }

    public function setImported(bool $imported): static
    {
        $this->imported = $imported;

        return $this;
    }

    public function isOld(): ?bool
    {
        return $this->old;
    }

    public function setOld(bool $old): static
    {
        $this->old = $old;

        return $this;
    }

    public function isTotal(): ?bool
    {
        return $this->total;
    }

    public function setTotal(bool $total): static
    {
        $this->total = $total;

        return $this;
    }
}
