<?php

namespace App\Entity;

use App\Repository\CouncilEntryTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=CouncilEntryTemplateRepository::class)
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="council_entry_template_name_unique",columns={"name"})
 * })
 */
class CouncilEntryTemplate
{
    const CouncilNodeGenericMCIntro             = 10;
    const CouncilNodeGenericMCComplaint         = 20;
    const CouncilNodeGenericDiscussion          = 21;
    const CouncilNodeGenericMCDrawStraw         = 30;

    const CouncilNodeGenericChatterIntro   = 41;
    const CouncilNodeGenericInsult         = 42;

    const CouncilNodeRootShamanFirst       = 101;
    const CouncilNodeRootShamanReplace     = 102;
    const CouncilNodeShamanFirstIntro      = 111;
    const CouncilNodeShamanReplaceIntro    = 112;
    const CouncilNodeShamanDiscussionRoot  = 120;
    const CouncilNodeShamanDiscussion      = 121;
    const CouncilNodeShamanDiscussionFollowUp = 122;
    const CouncilNodeShamanResult          = 130;
    const CouncilNodeShamanChatterIntro    = 141;

    const CouncilNodeRootGuideFirst        = 201;
    const CouncilNodeRootGuideReplace      = 202;
    const CouncilNodeGuideFirstIntro       = 211;
    const CouncilNodeGuideReplaceIntro     = 212;
    const CouncilNodeGuideDiscussionRoot   = 220;
    const CouncilNodeGuideDiscussion       = 221;
    const CouncilNodeGuideDiscussionFollowUp = 222;
    const CouncilNodeGuideResult           = 230;
    const CouncilNodeGuideChatterIntro     = 241;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $text;

    /**
     * @ORM\ManyToMany(targetEntity=CouncilEntryTemplate::class)
     */
    private $continueBranch;

    /**
     * @ORM\Column(type="float")
     */
    private $continueChance;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $semantic;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $subStructure = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $variableTypes = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minBranchSize;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxBranchSize;

    /**
     * @ORM\Column(type="integer")
     */
    private $citizenReference;

    /**
     * @ORM\Column(type="integer")
     */
    private $citizenReferenceIncrement;

    /**
     * @ORM\Column(type="boolean")
     */
    private $citizenReferenceRelative;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $repartition;

    /**
     * @ORM\Column(type="boolean")
     */
    private $vocal;

    /**
     * @ORM\Column(type="boolean")
     */
    private $createReference;

    public function __construct()
    {
        $this->continueBranch = new ArrayCollection();
    }

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

    /**
     * @return Collection|self[]
     */
    public function getContinueBranch(): Collection
    {
        return $this->continueBranch;
    }

    public function addContinueBranch(self $continueBranch): self
    {
        if (!$this->continueBranch->contains($continueBranch)) {
            $this->continueBranch[] = $continueBranch;
        }

        return $this;
    }

    public function removeContinueBranch(self $continueBranch): self
    {
        $this->continueBranch->removeElement($continueBranch);

        return $this;
    }

    public function getContinueChance(): ?float
    {
        return $this->continueChance;
    }

    public function setContinueChance(float $continueChance): self
    {
        $this->continueChance = $continueChance;

        return $this;
    }

    public function getSemantic(): ?int
    {
        return $this->semantic;
    }

    public function setSemantic(?int $semantic): self
    {
        $this->semantic = $semantic;

        return $this;
    }

    public function getSubStructure(): ?array
    {
        return $this->subStructure;
    }

    public function setSubStructure(?array $subStructure): self
    {
        $this->subStructure = $subStructure;

        return $this;
    }

    public function getVariableTypes(): ?array
    {
        return $this->variableTypes;
    }

    public function setVariableTypes(array $variableTypes): self
    {
        $this->variableTypes = $variableTypes;

        return $this;
    }

    public function getMinBranchSize(): ?int
    {
        return $this->minBranchSize;
    }

    public function setMinBranchSize(?int $minBranchSize): self
    {
        $this->minBranchSize = $minBranchSize;

        return $this;
    }

    public function getMaxBranchSize(): ?int
    {
        return $this->maxBranchSize;
    }

    public function setMaxBranchSize(?int $maxBranchSize): self
    {
        $this->maxBranchSize = $maxBranchSize;

        return $this;
    }

    public function getCitizenReference(): ?int
    {
        return $this->citizenReference;
    }

    public function setCitizenReference(int $citizenReference): self
    {
        $this->citizenReference = $citizenReference;

        return $this;
    }

    public function getCitizenReferenceIncrement(): ?int
    {
        return $this->citizenReferenceIncrement;
    }

    public function setCitizenReferenceIncrement(int $citizenReferenceIncrement): self
    {
        $this->citizenReferenceIncrement = $citizenReferenceIncrement;

        return $this;
    }

    public function getCitizenReferenceRelative(): ?bool
    {
        return $this->citizenReferenceRelative;
    }

    public function setCitizenReferenceRelative(bool $citizenReferenceRelative): self
    {
        $this->citizenReferenceRelative = $citizenReferenceRelative;

        return $this;
    }

    public function getRepartition(): ?int
    {
        return $this->repartition;
    }

    public function setRepartition(?int $repartition): self
    {
        $this->repartition = $repartition;

        return $this;
    }

    public function getVocal(): ?bool
    {
        return $this->vocal;
    }

    public function setVocal(bool $vocal): self
    {
        $this->vocal = $vocal;

        return $this;
    }

    public function getCreateReference(): ?bool
    {
        return $this->createReference;
    }

    public function setCreateReference(bool $createReference): self
    {
        $this->createReference = $createReference;

        return $this;
    }
}
