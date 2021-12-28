<?php

namespace App\Entity;

use App\Repository\CouncilEntryTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
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
    const CouncilBranchModeNone = 0;
    const CouncilBranchModeStructured = 1;
    const CouncilBranchModeRandom = 2;

    const CouncilNodeContextOnly                   =    0;

    const CouncilRootNodeGenericMCIntro                =  990001;

    const CouncilNodeGenericMCIntro                =    1;
    const CouncilNodeGenericFollowUpAny            =   20;
    const CouncilNodeGenericBeginVoteAny           =   30;
    const CouncilNodeGenericVoteAny                =   40;
    const CouncilNodeGenericVoteResponseAny        =   45;
    const CouncilNodeGenericEndVoteAny             =   50;
    const CouncilNodeGenericEndVoteResponseA       =   55;
    const CouncilNodeGenericEndVoteResponseB       =   56;
    const CouncilNodeGenericStrawAny               =   60;
    const CouncilNodeGenericStrawInitAny           =   65;
    const CouncilNodeGenericStrawResponseAny       =   70;
    const CouncilNodeGenericStrawFinalAny          =   80;
    const CouncilNodeGenericStrawResultResponseAny =  100;
    
    const CouncilNodeRootShamanFirst                   = 991001;
    const CouncilNodeRootShamanNext                    = 991002;
    const CouncilRootNodeShamanIntroFirst              = 991011;
    const CouncilRootNodeShamanIntroNext               = 991012;
    const CouncilRootNodeShamanFollowUpFirst           = 991021;
    const CouncilRootNodeShamanFollowUpNext            = 991022;
    const CouncilRootNodeShamanBeginVoteAny            = 991030;
    const CouncilRootNodeShamanVoteAny                 = 991040;
    const CouncilRootNodeShamanEndVoteAny              = 991050;
    const CouncilRootNodeShamanStrawAny                = 991060;
    const CouncilRootNodeShamanStrawResponseAny        = 991070;
    const CouncilRootNodeShamanStrawFinalAny           = 991080;
    const CouncilRootNodeShamanStrawResultAny          = 991090;
    const CouncilRootNodeShamanStrawResultResponseAny  = 991100;
    const CouncilRootNodeShamanFinalAny                = 991110;
    
    const CouncilNodeShamanIntroFirst              = 1011;
    const CouncilNodeShamanIntroNext               = 1012;
    const CouncilNodeShamanFollowUpAny             = 1020;
    const CouncilNodeShamanFollowUpFirst           = 1021;
    const CouncilNodeShamanFollowUpNext            = 1022;
    const CouncilNodeShamanBeginVoteAny            = 1030;
    const CouncilNodeShamanVoteAny                 = 1040;
    const CouncilNodeShamanVoteResponseAny         = 1045;
    const CouncilNodeShamanEndVoteAny              = 1050;
    const CouncilNodeShamanEndVoteResponseA        = 1055;
    const CouncilNodeShamanEndVoteResponseB        = 1056;
    const CouncilNodeShamanStrawAny                = 1060;
    const CouncilNodeShamanStrawFinalAny           = 1070;
    const CouncilNodeShamanStrawResponseAny        = 1080;
    const CouncilNodeShamanStrawResultAny          = 1090;
    const CouncilNodeShamanStrawResultResponseAny  = 1100;
    const CouncilNodeShamanFinalAny                = 1110;

    const CouncilNodeRootGuideFirst                   = 992001;
    const CouncilNodeRootGuideNext                    = 992002;
    const CouncilRootNodeGuideIntroFirst              = 992011;
    const CouncilRootNodeGuideIntroNext               = 992012;
    const CouncilRootNodeGuideFollowUpAny             = 992020;
    const CouncilRootNodeGuideFollowUpFirst           = 992021;
    const CouncilRootNodeGuideFollowUpNext            = 992022;
    const CouncilRootNodeGuideBeginVoteAny            = 992030;
    const CouncilRootNodeGuideVoteAny                 = 992040;
    const CouncilRootNodeGuideVoteResponseAny         = 992050;
    const CouncilRootNodeGuideEndVoteAny              = 992060;
    const CouncilRootNodeGuideStrawAny                = 992070;
    const CouncilRootNodeGuideStrawResponseAny        = 992080;
    const CouncilRootNodeGuideStrawResultAny          = 992090;
    const CouncilRootNodeGuideStrawResultResponseAny  = 992100;
    const CouncilRootNodeGuideFinalAny                = 992110;

    const CouncilNodeGuideIntroFirst               = 2011;
    const CouncilNodeGuideIntroNext                = 2012;
    const CouncilNodeGuideFollowUpAny              = 2020;
    const CouncilNodeGuideFollowUpFirst            = 2021;
    const CouncilNodeGuideFollowUpNext             = 2022;
    const CouncilNodeGuideBeginVoteAny             = 2030;
    const CouncilNodeGuideVoteAny                  = 2040;
    const CouncilNodeGuideVoteResponseAny          = 2050;
    const CouncilNodeGuideEndVoteAny               = 2060;
    const CouncilNodeGuideStrawAny                 = 2070;
    const CouncilNodeGuideStrawResponseAny         = 2080;
    const CouncilNodeGuideStrawResultAny           = 2090;
    const CouncilNodeGuideStrawResultResponseAny   = 2100;
    const CouncilNodeGuideFinalAny                 = 2110;

    
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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $semantic;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $variableTypes = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $vocal;

    /**
     * @ORM\Column(type="boolean")
     */
    private $createReference;

    /**
     * @ORM\ManyToMany(targetEntity=CouncilEntryTemplate::class)
     * @OrderBy({"semantic" = "ASC"})
     */
    private $branches;

    /**
     * @ORM\Column(type="integer")
     */
    private $branchMode;

    /**
     * @ORM\Column(type="integer")
     */
    private $branchSizeMin;

    /**
     * @ORM\Column(type="integer")
     */
    private $branchSizeMax;

    /**
     * @ORM\Column(type="json")
     */
    private $variableDefinitions = [];

    public function __construct()
    {
        $this->continueBranch = new ArrayCollection();
        $this->branches = new ArrayCollection();
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

    public function getSemantic(): ?int
    {
        return $this->semantic;
    }

    public function setSemantic(?int $semantic): self
    {
        $this->semantic = $semantic;

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

    /**
     * @return Collection|self[]
     */
    public function getBranches(): Collection
    {
        return $this->branches;
    }

    public function addBranch(self $branch): self
    {
        if (!$this->branches->contains($branch)) {
            $this->branches[] = $branch;
        }

        return $this;
    }

    public function removeBranch(self $branch): self
    {
        $this->branches->removeElement($branch);

        return $this;
    }

    public function getBranchMode(): ?int
    {
        return $this->branchMode;
    }

    public function setBranchMode(int $branchMode): self
    {
        $this->branchMode = $branchMode;

        return $this;
    }

    public function getBranchSizeMin(): ?int
    {
        return $this->branchSizeMin;
    }

    public function setBranchSizeMin(int $branchSizeMin): self
    {
        $this->branchSizeMin = $branchSizeMin;

        return $this;
    }

    public function getBranchSizeMax(): ?int
    {
        return $this->branchSizeMax;
    }

    public function setBranchSizeMax(int $branchSizeMax): self
    {
        $this->branchSizeMax = $branchSizeMax;

        return $this;
    }

    public function getVariableDefinitions(): ?array
    {
        return $this->variableDefinitions;
    }

    public function setVariableDefinitions(array $variableDefinitions): self
    {
        $this->variableDefinitions = $variableDefinitions;

        return $this;
    }
}
