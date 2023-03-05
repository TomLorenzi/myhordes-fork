<?php

namespace App\Entity;

use App\Repository\CommunityEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CommunityEventRepository::class)]
class CommunityEvent
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue("CUSTOM")]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expires = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $starts = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: CommunityEventMeta::class, orphanRemoval: true)]
    private Collection $metas;

    public function __construct()
    {
        $this->metas = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getExpires(): ?\DateTimeInterface
    {
        return $this->expires;
    }

    public function setExpires(?\DateTimeInterface $expires): self
    {
        $this->expires = $expires;

        return $this;
    }

    public function getStarts(): ?\DateTimeInterface
    {
        return $this->starts;
    }

    public function setStarts(?\DateTimeInterface $starts): self
    {
        $this->starts = $starts;

        return $this;
    }

    /**
     * @return ArrayCollection<CommunityEventMeta>|PersistentCollection<CommunityEventMeta>
     */
    public function getMetas(): ArrayCollection|PersistentCollection
    {
        return $this->metas;
    }

    public function addMeta(CommunityEventMeta $meta): self
    {
        if (!$this->metas->contains($meta)) {
            $this->metas->add($meta);
            $meta->setEvent($this);
        }

        return $this;
    }

    public function removeMeta(CommunityEventMeta $meta): self
    {
        if ($this->metas->removeElement($meta)) {
            // set the owning side to null (unless already changed)
            if ($meta->getEvent() === $this) {
                $meta->setEvent(null);
            }
        }

        return $this;
    }

    public function getMeta(string $name, bool $withFallback = false): ?CommunityEventMeta {
        return $withFallback
            ? array_reduce( match ($name) {
                'en' => ['en','fr','de','es'],
                'fr' => ['fr','en','de','es'],
                'es' => ['es','en','fr','de'],
                default => ['de','en','fr','es'],
            }, fn($c, $lang) => $c ?? ($this->getMetas()->matching( (new Criteria())->where( Criteria::expr()->eq( 'lang', $lang ) ) )->first() ?: null), null )
            : ($this->getMetas()->matching( (new Criteria())->where( Criteria::expr()->eq( 'lang', $name ) ) )->first() ?: null);
    }
}
