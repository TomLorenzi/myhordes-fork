<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GazetteLogEntryRepository")
 */
class GazetteLogEntry
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $day;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Gazette", inversedBy="_log_entries")
     * @ORM\JoinColumn(nullable=false)
     */
    private $gazette;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogEntryTemplate")
     * @ORM\JoinColumn(nullable=true)
     */
    private $logEntryTemplate;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $variables = [];

    /**
     * @ORM\ManyToOne(targetEntity=GazetteEntryTemplate::class)
     */
    private $template;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getGazette(): ?Gazette
    {
        return $this->gazette;
    }

    public function setGazette(?Gazette $gazette): self
    {
        $this->gazette = $gazette;

        return $this;
    }

    public function getLogEntryTemplate(): ?LogEntryTemplate
    {
        return $this->logEntryTemplate;
    }

    public function setLogEntryTemplate(?LogEntryTemplate $logEntryTemplate): self
    {
        $this->logEntryTemplate = $logEntryTemplate;

        return $this;
    }

    public function getVariables(): ?array
    {
        return $this->variables;
    }

    public function setVariables(?array $variables): self
    {
        $this->variables = $variables;

        return $this;
    }

    public function getTemplate(): ?GazetteEntryTemplate
    {
        return $this->template;
    }

    public function setTemplate(?GazetteEntryTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }
}
