<?php

namespace App\Entity;

use App\Repository\TwinoidImportPreviewRepository;
use App\Structures\TwinoidPayload;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=TwinoidImportPreviewRepository::class)
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="twinoid_import_preview_user_unique",columns={"user_id"}),
 *     @UniqueConstraint(name="twinoid_import_preview_twinoid_unique",columns={"twinoid_id"})
 * })
 */
class TwinoidImportPreview
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="twinoidImportPreview", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $scope;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="json")
     */
    private $payload = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $twinoidID;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getTwinoidID(): ?int
    {
        return $this->twinoidID;
    }

    public function setTwinoidID(int $twinoidID): self
    {
        $this->twinoidID = $twinoidID;

        return $this;
    }

    public function getData(): TwinoidPayload {
        return new TwinoidPayload($this->getPayload());
    }
}
