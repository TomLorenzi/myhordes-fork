<?php

namespace App\Entity;

use App\Repository\AntiSpamDomainsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=AntiSpamDomainsRepository::class)
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="anti_spam_domain_unique",columns={"domain"})
 * })
 */
class AntiSpamDomains
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private ?string $domain;

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
}
