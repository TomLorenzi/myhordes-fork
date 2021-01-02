<?php

namespace App\Entity;

use App\Repository\GlobalPrivateMessageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GlobalPrivateMessageRepository::class)
 *
 */
class GlobalPrivateMessage
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $sender;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $receiverUser;

    /**
     * @ORM\ManyToOne(targetEntity=UserGroup::class)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $receiverGroup;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $template;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $intval1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $intval2;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $data = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $text;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceiverUser(): ?User
    {
        return $this->receiverUser;
    }

    public function setReceiverUser(?User $receiverUser): self
    {
        $this->receiverUser = $receiverUser;

        return $this;
    }

    public function getReceiverGroup(): ?UserGroup
    {
        return $this->receiverGroup;
    }

    public function setReceiverGroup(?UserGroup $receiverGroup): self
    {
        $this->receiverGroup = $receiverGroup;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getTemplate(): ?int
    {
        return $this->template;
    }

    public function setTemplate(?int $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getIntval1(): ?int
    {
        return $this->intval1;
    }

    public function setIntval1(?int $intval1): self
    {
        $this->intval1 = $intval1;

        return $this;
    }

    public function getIntval2(): ?int
    {
        return $this->intval2;
    }

    public function setIntval2(?int $intval2): self
    {
        $this->intval2 = $intval2;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }
}
