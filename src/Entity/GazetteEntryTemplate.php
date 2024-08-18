<?php

namespace App\Entity;

use App\Repository\GazetteEntryTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GazetteEntryTemplateRepository::class)]
class GazetteEntryTemplate
{
    const TypeGazetteNews               = 1;
    const TypeGazetteNoDeaths           = 2;
    const TypeGazetteOneDeath           = 3;
    const TypeGazetteTwoDeaths          = 4;
    const TypeGazetteMultiDeaths        = 5;
    const TypeGazetteSuicide            = 6;
    const TypeGazetteAddiction          = 7;
    const TypeGazetteDehydration        = 8;
    const TypeGazettePoison             = 9;
    const TypeGazetteVanished           = 10;
    const TypeGazetteWind               = 11;
    const TypeGazetteReactor            = 12;
    const TypeGazetteShamanDeath        = 13;
    const TypeGazetteGuideDeath         = 14;
    const TypeGazetteGuideShamanDeath   = 15;
    const TypeGazetteRedSoul            = 16;
    const TypeGazetteMultiDehydration   = 17;
    const TypeGazetteHanging            = 18;
    const TypeGazetteMultiSuicide       = 19;
    const TypeGazetteMultiInfection     = 20;
    const TypeGazetteInfection          = 21;
    const TypeGazetteDeathWithDoorOpen  = 22;
    const TypeGazetteFlavour            = 23;
    const TypeGazetteMultiVanished      = 24;
    const TypeGazetteChocolateCross     = 25;
    const TypeGazetteMultiHanging       = 26;
    const TypeGazetteMultiChocolateCross = 27;
    const TypeGazetteMultiRedSoul       = 28;
    const TypeGazetteDayOne             = 29;
    const TypeGazetteShamanDeathChaos   = 30;
    const TypeGazetteGuideDeathChaos    = 31;
    const TypeGazetteGuideShamanDeathChaos = 32;

    const RequiresNothing       =  0;
    const BaseRequirementCitizen = 10;
    const RequiresOneCitizen    = 11;
    const RequiresTwoCitizens   = 12;
    const RequiresThreeCitizens = 13;
    const BaseRequirementCadaver = 20;
    const RequiresOneCadaver    = 21;
    const RequiresTwoCadavers   = 22;
    const BaseRequirementCitizenCadaver = 30;
    const RequiresOneOfEach     = 31;
    const RequiresTwoOfEach     = 32;
    const RequiresAttack        = 40;
    const RequiresDefense       = 41;
    const RequiresDeaths        = 42;
    const RequiresInvasion      = 43;
    const RequiresAttackDeaths  = 44;
    const RequiresMultipleDehydrations  = 45;
    const RequiresMultipleSuicides      = 46;
    const RequiresMultipleInfections    = 47;
    const RequiresMultipleVanished      = 48;
    const RequiresMultipleHangings      = 49;
    const BaseRequirementCitizenInTown = 50;
    const RequiresTwoCitizensInTown = 52;
    const RequiresMultipleCrosses       = 60;
    const RequiresMultipleRedSouls      = 61;
    const RequiresAttackDeathsC1        = 441;
    const RequiresMultipleInfectionsC1  = 471;
    const RequiresMultipleVanishedC1    = 481;
    const RequiresMultipleHangingsC1    = 491;
    const FollowUpTypeNone  = 0;
    const FollowUpTypeDoubt = 1;
    const FollowUpTypeBad   = 2;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 255)]
    private $name;
    #[ORM\Column(type: 'text')]
    private $text;
    #[ORM\Column(type: 'integer')]
    private $type;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $requirement;
    #[ORM\Column(type: 'array', nullable: true)]
    private $variableTypes = [];
    #[ORM\Column(type: 'integer')]
    private $followUpType;
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
    public function getFollowUpType(): ?int
    {
        return $this->followUpType;
    }
    public function setFollowUpType(int $followUpType): self
    {
        $this->followUpType = $followUpType;

        return $this;
    }
}
