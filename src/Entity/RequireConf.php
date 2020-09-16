<?php

namespace App\Entity;

use App\Repository\RequireConfRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=RequireConfRepository::class)
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="require_building_name_unique",columns={"name"})
 * })
 */
class RequireConf
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private $conf;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $boolVal;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConf(): ?string
    {
        return $this->conf;
    }

    public function setConf(string $conf): self
    {
        $this->conf = $conf;

        return $this;
    }

    public function getBoolVal(): ?bool
    {
        return $this->boolVal;
    }

    public function setBoolVal(?bool $boolVal): self
    {
        $this->boolVal = $boolVal;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
