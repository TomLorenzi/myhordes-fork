<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ExternalAppRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="name_unique",columns={"name"})
 * })
 */
class ExternalApp
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active = 1;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=true)
     */
    private $owner = null;

    /**
     * @ORM\Column(type="string")
     */
    private $secret;

    /**
     * @ORM\Column(type="string")
     */
    private $app_url;

    /**
     * @ORM\Column(type="string")
     */
    private $app_icon;

    /**
     * @ORM\Column(type="string")
     */
    private $contact_email;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getAppUrl(): string
    {
        return $this->app_url;
    }

    public function setAppUrl(string $app_url): self
    {
        $this->app_url = $app_url;

        return $this;
    }

    public function getAppIcon(): string
    {
        return $this->app_icon;
    }

    public function setAppIcon(string $app_icon): self
    {
        $this->app_icon = $app_icon;

        return $this;
    }

    public function getContactEmail(): string
    {
        return $this->contact_email;
    }

    public function setContactEmail(string $contact_email): self
    {
        $this->contact_email = $contact_email;

        return $this;
    }
}
