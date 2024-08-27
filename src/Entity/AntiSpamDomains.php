<?php

namespace App\Entity;

use App\Enum\DomainBlacklistType;
use App\Repository\AntiSpamDomainsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: AntiSpamDomainsRepository::class)]
#[Table]
#[ORM\Index(columns: ['domain'], name: 'nospam_content_idx')]
#[ORM\Index(columns: ['type'], name: 'nospam_type_idx')]
#[ORM\Index(columns: ['until'], name: 'nospam_until_idx')]
#[UniqueConstraint(name: 'anti_spam_domain_unique', columns: ['domain'])]
class AntiSpamDomains
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;
    #[ORM\Column(type: 'string', length: 190)]
    private ?string $domain;

    #[ORM\Column(type: 'integer', enumType: DomainBlacklistType::class)]
    private DomainBlacklistType $type = DomainBlacklistType::EmailDomain;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $until = null;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function __toString(): string
    {
        return $this->getDomain();
    }
    public function getDomain(): ?string
    {
        return $this->domain;
    }
    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getType(): ?DomainBlacklistType
    {
        return $this->type;
    }

    public function setType(DomainBlacklistType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUntil(): ?\DateTimeInterface
    {
        return $this->until;
    }

    public function setUntil(?\DateTimeInterface $until): static
    {
        $this->until = $until;

        return $this;
    }
}
