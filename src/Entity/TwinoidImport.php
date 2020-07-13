<?php

namespace App\Entity;

use App\Repository\TwinoidImportRepository;
use App\Structures\TwinoidPayload;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=TwinoidImportRepository::class)
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="twinoid_import_user_source_unique",columns={"user_id","scope"}),
 * })
 */
class TwinoidImport
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="twinoidImports")
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
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\Column(type="json")
     */
    private $payload = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $main = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(\DateTimeInterface $updated): self
    {
        $this->updated = $updated;

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

    public function getMain(): ?bool
    {
        return $this->main;
    }

    public function setMain(bool $main): self
    {
        $this->main = $main;

        return $this;
    }

    public function getData(EntityManagerInterface $em): TwinoidPayload {
        return new TwinoidPayload($this->getPayload(), $em);
    }

    public function fromPreview(TwinoidImportPreview $preview): bool {
        if ($this->getUser()  !== null && $this->getUser()  !== $preview->getUser() ) return false;
        if ($this->getScope() !== null && $this->getScope() !== $preview->getScope()) return false;

        $this
            ->setUser( $preview->getUser() )
            ->setScope( $preview->getScope() )
            ->setPayload( $preview->getPayload() )
            ->setCreated( $this->getCreated() ?? $preview->getCreated() )
            ->setUpdated( $preview->getCreated() );

        return true;
    }
}
