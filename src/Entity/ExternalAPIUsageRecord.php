<?php

namespace App\Entity;

use App\Enum\ExternalAPIInterface;
use App\Repository\ExternalAPIUsageRecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExternalAPIUsageRecordRepository::class)]
#[ORM\Table]
#[ORM\Index(name: 'eapi_usage_ts_idx', columns: ['timestamp'])]
class ExternalAPIUsageRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer', enumType: ExternalAPIInterface::class)]
    private ExternalAPIInterface $api = ExternalAPIInterface::GENERIC;
    #[ORM\ManyToOne(targetEntity: ExternalApp::class)]
    private $app;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;
    #[ORM\Column(type: 'datetime')]
    private $timestamp;
    #[ORM\Column(type: 'boolean')]
    private bool $debug = false;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getApi(): ExternalAPIInterface
    {
        return $this->api;
    }
    public function setApi(ExternalAPIInterface $api): self
    {
        $this->api = $api;

        return $this;
    }
    public function getApp(): ?ExternalApp
    {
        return $this->app;
    }
    public function setApp(?ExternalApp $app): self
    {
        $this->app = $app;

        return $this;
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
    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }
    public function setTimestamp(\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
    public function getDebug(): ?bool
    {
        return $this->debug;
    }
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }
}
