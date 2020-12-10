<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GazetteLogEntryRepository")
 */
class GazetteLogEntry
{
    const TypeGazette       = 12;
    const TypeGazetteTown   = 13;
    const TypeGazetteBeyond = 14;

    const RequiresNothing       =  0;
    const RequiresOneCitizen    = 11;
    const RequiresTwoCitizens   = 12;
    const RequiresThreeCitizens = 13;
    const RequiresOneCadaver    = 21;
    const RequiresTwoCadavers   = 22;
    const RequiresOneOfEach     = 31;
    const RequiresTwoOfEach     = 32;
    const RequiresAttack        = 40;
    const RequiresDefense       = 41;
    const RequiresDeaths        = 42;
    const RequiresInvasion      = 43;
    const RequiresAttackDeaths  = 44;
    const RequiresDevastated    = 45;

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
}
