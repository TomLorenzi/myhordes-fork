<?php

namespace App\Entity;

use App\Repository\ActivityClusterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[UniqueConstraint(name: 'activity_cluster_id_unique', columns: ['identifier'])]
#[ORM\Entity(repositoryClass: ActivityClusterRepository::class)]
class ActivityCluster
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue("CUSTOM")]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 32)]
    private ?string $identifier = null;

    #[ORM\Column]
    private ?bool $ipv6 = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $firstSeen = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastSeen = null;

    #[ORM\OneToMany(mappedBy: 'cluster', targetEntity: 'App\Entity\ActivityClusterEntry', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function isIpv6(): ?bool
    {
        return $this->ipv6;
    }

    public function setIpv6(bool $ipv6): static
    {
        $this->ipv6 = $ipv6;

        return $this;
    }

    public function getFirstSeen(): ?\DateTimeInterface
    {
        return $this->firstSeen;
    }

    public function setFirstSeen(\DateTimeInterface $firstSeen): static
    {
        $this->firstSeen = $firstSeen;

        return $this;
    }

    public function getLastSeen(): ?\DateTimeInterface
    {
        return $this->lastSeen;
    }

    public function setLastSeen(?\DateTimeInterface $lastSeen): static
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    /**
     * @return Collection<ActivityClusterEntry>
     */
    public function getEntries(): Collection {
        return $this->entries;
    }

    /**
     * @return Collection<ActivityClusterEntry>
     */
    public function getEntriesBy(?User $user = null, ?int $cutoff = null): Collection {
        if ($user === null && $cutoff === null) return $this->getEntries();

        $criteria = Criteria::create();
        if ($user !== null) $criteria->andWhere( Criteria::expr()->eq('user', $user) );
        if ($user !== null) $criteria->andWhere( Criteria::expr()->eq('cutoff', $cutoff) );
        return $this->entries->matching($criteria);
    }

    /**
     * @param User $user
     * @param int $cutoff
     * @return ?ActivityClusterEntry
     */
    public function getEntryBy(User $user, int $cutoff): ?ActivityClusterEntry {
        return $this->getEntriesBy($user, $cutoff)->first() ?: null;
    }

    /**
     * @return Collection<User>
     */
    public function getUsers(): Collection {
        $users = new ArrayCollection();
        $map = $this->getEntries()
            ->map( fn(ActivityClusterEntry $entry) => $entry->getUser() );
        foreach ($map as $u)
            if (!$users->contains($u)) $users->add($u);

        return $users->matching( Criteria::create()->orderBy(['id' => Order::Ascending]) );
    }
}
