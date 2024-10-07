<?php

namespace App\Entity;

use App\Repository\CitizenPropertiesRepository;
use App\Enum\Configuration\CitizenProperties as CitizenPropertiesEnum;
use App\Structures\CitizenConf;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CitizenPropertiesRepository::class), ORM\HasLifecycleCallbacks]
class CitizenProperties
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private array $props = [];

    private ?CitizenConf $conf = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProps(): array
    {
        return $this->props;
    }

    public function setProps(array $props): static
    {
        $this->conf = (new CitizenConf( $this->props = $props ))->complete();

        return $this;
    }

    #[ORM\PostLoad]
    public function lifeCycle_postLoad(PostLoadEventArgs $eventArgs): void
    {
        $this->conf = (new CitizenConf( $this->getProps() ))->complete();
    }

    public function get(CitizenPropertiesEnum $v): mixed {
        if (!$this->conf) $this->conf = (new CitizenConf( $this->getProps() ))->complete();
        return $this->conf->get( $v );
    }

    public function has(CitizenPropertiesEnum $v): mixed {
        if (!$this->conf) $this->conf = (new CitizenConf( $this->getProps() ))->complete();
        return $this->conf->get( $v );
    }
}
