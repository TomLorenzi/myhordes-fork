<?php

namespace App\Entity;

use App\Enum\TownRevisionType;
use App\Repository\TownAspectRevisionRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: TownAspectRevisionRepository::class)]
#[ORM\Index(columns: ['type'], name: 'tar_type')]
#[ORM\Index(columns: ['type','identifier'], name: 'tar_type_id')]
#[Table]
#[UniqueConstraint(name: 'tar_unique', columns: ['town_id', 'type', 'identifier'])]
class TownAspectRevision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', enumType: TownRevisionType::class)]
    private TownRevisionType $type = TownRevisionType::Fallback;

    #[ORM\Column]
    private ?int $revision = 0;

    #[ORM\Column(nullable: true)]
    private ?int $identifier = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'revisions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Town $town = null;

    private bool $dirty = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): TownRevisionType
    {
        return $this->type;
    }

    public function setType(TownRevisionType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRevision(): ?int
    {
        return $this->revision;
    }

    public function setRevision(int $revision): self
    {
        $this->revision = $revision;

        return $this;
    }

    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    public function setIdentifier(?int $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(?Town $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function touch(): self {
        if (!$this->dirty) {
            $this->setRevision( $this->getRevision() + 1 );
            $this->dirty = true;
        }

        return $this;
    }
}
