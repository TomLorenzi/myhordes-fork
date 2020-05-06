<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserPendingValidationRepository")
 * @UniqueEntity("pkey")
 * @Table(uniqueConstraints={@UniqueConstraint(name="pkey_unique",columns={"pkey"}),@UniqueConstraint(name="assoc_unique",columns={"user_id","type"})})
 */
class UserPendingValidation
{

    const EMailValidation = 0;
    const ResetValidation = 1;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $pkey;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="pendingValidation", cascade={"persist"})
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $type = self::EMailValidation;

    /**
     * @ORM\Column(type="datetime")
     */
    private $time;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPkey(): ?string
    {
        return $this->pkey;
    }

    public function setPkey(string $pkey): self
    {
        $this->pkey = $pkey;

        return $this;
    }

    public function generatePKey(): string {
        $source = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $key = "";
        for ($i = 0; $i < 16; $i++) $key .= $source[ mt_rand(0, strlen($source) - 1) ];
        $this->setPkey($key);
        return $key;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }
}
