<?php

namespace App\Entity;

use App\Repository\ComplaintReasonRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ComplaintReasonRepository::class)]
#[UniqueEntity('name')]
#[UniqueConstraint(name: 'complaint_reason_unique', columns: ['name'])]
class ComplaintReason
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 255)]
    private $text;
    #[ORM\Column(type: 'string', length: 255)]
    private $name;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getText(): ?string
    {
        return $this->text;
    }
    public function setText(string $text): self
    {
        $this->text = $text;

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
