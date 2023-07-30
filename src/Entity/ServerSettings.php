<?php

namespace App\Entity;

use App\Enum\ServerSetting;
use App\Repository\ServerSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: ServerSettingsRepository::class)]
#[Table]
#[UniqueConstraint(name: 'server_setting_unique', columns: ['setting'])]
class ServerSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: false, enumType: ServerSetting::class)]
    private ?ServerSetting $setting = null;

    #[ORM\Column(nullable: true)]
    private ?array $data = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSetting(): ServerSetting
    {
        return $this->setting;
    }

    public function setSetting(ServerSetting $setting): static
    {
        $this->setting = $setting;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
