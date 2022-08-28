<?php

namespace App\Entity;

use App\Repository\GitVersionsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: GitVersionsRepository::class)]
#[UniqueEntity('version')]
#[Table]
#[UniqueConstraint(name: 'git_versions_unique', columns: ['version'])]
class GitVersions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 96)]
    private $version;
    #[ORM\Column(type: 'boolean')]
    private $installed = false;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getVersion(): ?string
    {
        return $this->version;
    }
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }
    public function getInstalled(): ?bool
    {
        return $this->installed;
    }
    public function setInstalled(bool $installed): self
    {
        $this->installed = $installed;

        return $this;
    }
}
