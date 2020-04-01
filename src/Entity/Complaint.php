<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ComplaintRepository")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="assoc_unique",columns={"autor_id","culprit_id"})
 * })
 */
class Complaint
{

    const SeverityNone = 0;
    const SeverityBanish = 1;
    const SeverityKill = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Citizen")
     * @ORM\JoinColumn(nullable=false)
     */
    private $autor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Citizen", inversedBy="complaints")
     * @ORM\JoinColumn(nullable=false)
     */
    private $culprit;

    /**
     * @ORM\Column(type="integer")
     */
    private $count;

    /**
     * @ORM\Column(type="integer")
     */
    private $severity;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAutor(): ?Citizen
    {
        return $this->autor;
    }

    public function setAutor(?Citizen $autor): self
    {
        $this->autor = $autor;

        return $this;
    }

    public function getCulprit(): ?Citizen
    {
        return $this->culprit;
    }

    public function setCulprit(?Citizen $culprit): self
    {
        $this->culprit = $culprit;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getSeverity(): ?int
    {
        return $this->severity;
    }

    public function setSeverity(int $severity): self
    {
        $this->severity = $severity;

        return $this;
    }
}
