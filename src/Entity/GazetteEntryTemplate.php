<?php

namespace App\Entity;

use App\Repository\GazetteEntryTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GazetteEntryTemplateRepository::class)
 */
class GazetteEntryTemplate
{
    const TypeGazetteNews          = 1;
    const TypeGazetteNoDeaths      = 2;
    const TypeGazetteOneDeath      = 3;
    const TypeGazetteTwoDeaths     = 4;
    const TypeGazetteMultiDeaths   = 5;
    const TypeGazetteSuicide       = 6;
    const TypeGazetteAddiction     = 7;
    const TypeGazetteDehydration   = 8;
    const TypeGazettePoison        = 9;
    const TypeGazetteVanished      = 10;
    const TypeGazetteWind          = 11;

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

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $requirement;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $variableTypes = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

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

    public function getRequirement(): ?int
    {
        return $this->requirement;
    }

    public function setRequirement(int $requirement): self
    {
        $this->requirement = $requirement;

        return $this;
    }

    public function getVariableTypes(): ?array
    {
        return $this->variableTypes;
    }

    public function setVariableTypes(?array $variableTypes): self
    {
        $this->variableTypes = $variableTypes;

        return $this;
    }
}
