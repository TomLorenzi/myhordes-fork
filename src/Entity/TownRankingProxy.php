<?php

namespace App\Entity;

use App\Enum\GameProfileEntryType;
use App\Repository\TownRankingProxyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: TownRankingProxyRepository::class)]
#[Table]
#[UniqueConstraint(name: 'town_ranking_proxy_id_unique', columns: ['base_id', 'imported', 'language'])]
#[ORM\Index(columns: ['base_id'], name: 'town_ranking_proxy_by_base_idx')]
#[ORM\Index(columns: ['base_id', 'imported'], name: 'town_ranking_proxy_by_base_imported_idx')]
class TownRankingProxy
{
    const int DISABLE_NOTHING = 0;
    const int DISABLE_RANKING = 1 << 0;
    const int DISABLE_PICTOS = 1 << 1;
    const int DISABLE_SOULPOINTS = 1 << 2;
    const int DISABLE_HXP = 1 << 3;
    const int DISABLE_ALL = self::DISABLE_PICTOS | self::DISABLE_RANKING | self::DISABLE_SOULPOINTS | self::DISABLE_HXP;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 190)]
    private $name;
    #[ORM\Column(type: 'string', length: 8)]
    private $language;
    #[ORM\Column(type: 'integer')]
    private $population;
    #[ORM\Column(name: '`begin`', type: 'datetime', nullable: true)]
    private $begin;
    #[ORM\Column(name: '`end`', type: 'datetime', nullable: true)]
    private $end;
    #[ORM\Column(type: 'integer')]
    private $days = 0;
    #[ORM\Column(type: 'integer')]
    private $baseID;
    #[ORM\OneToMany(targetEntity: CitizenRankingProxy::class, mappedBy: 'town', cascade: ['persist', 'remove'])]
    private $citizens;
    #[ORM\OneToOne(targetEntity: Town::class, inversedBy: 'rankingEntry', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $town;
    #[ORM\OneToMany(targetEntity: Picto::class, mappedBy: 'townEntry', cascade: ['persist', 'remove'])]
    private $distributedPictos;
    #[ORM\ManyToOne(targetEntity: TownClass::class, inversedBy: 'rankedTowns')]
    #[ORM\JoinColumn(nullable: false)]
    private $type;
    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'rankedTowns')]
    private $season;
    #[ORM\Column(type: 'boolean')]
    private $imported = false;
    #[ORM\Column(type: 'integer')]
    private $score = 0;
    #[ORM\Column(type: 'boolean')]
    private $v1 = false;
    #[ORM\Column(type: 'boolean')]
    private $disabled = false;
    #[ORM\Column(type: 'boolean')]
    private $event = false;
    #[ORM\Column(type: 'integer')]
    private $profilerVersion = 0;
    #[ORM\Column(type: 'integer')]
    private $disableFlag = self::DISABLE_NOTHING;

    #[ORM\Column]
    private int $bonusScore = 0;
    public function __construct()
    {
        $this->citizens = new ArrayCollection();
        $this->distributedPictos = new ArrayCollection();
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
    public function getLanguage(): ?string
    {
        return $this->language;
    }
    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }
    public function getPopulation(): ?int
    {
        return $this->population;
    }
    public function setPopulation(int $population): self
    {
        $this->population = $population;

        return $this;
    }
    public function getBegin(): ?\DateTimeInterface
    {
        return $this->begin;
    }
    public function setBegin(?\DateTimeInterface $begin): self
    {
        $this->begin = $begin;

        return $this;
    }
    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }
    public function setEnd(?\DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }
    public function getDays(): ?int
    {
        return $this->days;
    }
    public function setDays(int $days): self
    {
        $this->days = $days;

        return $this;
    }
    public function getBaseID(): ?int
    {
        return $this->baseID;
    }
    public function setBaseID(int $baseID): self
    {
        $this->baseID = $baseID;

        return $this;
    }
    /**
     * @return Collection|CitizenRankingProxy[]
     */
    public function getCitizens(): Collection
    {
        return $this->citizens;
    }
    public function addCitizen(CitizenRankingProxy $citizen): self
    {
        if (!$this->citizens->contains($citizen)) {
            $this->citizens[] = $citizen;
            $citizen->setTown($this);
        }

        return $this;
    }
    public function removeCitizen(CitizenRankingProxy $citizen): self
    {
        if ($this->citizens->contains($citizen)) {
            $this->citizens->removeElement($citizen);
            // set the owning side to null (unless already changed)
            if ($citizen->getTown() === $this) {
                $citizen->setTown(null);
            }
        }

        return $this;
    }
    public function getTown(): ?Town
    {
        return $this->town;
    }
    public function setTown(?Town $town): self
    {
        $this->town = $town;

        // set (or unset) the owning side of the relation if necessary
        $newRankingEntry = null === $town ? null : $this;
        if ($town !== null && $town->getRankingEntry() !== $newRankingEntry) {
            $town->setRankingEntry($newRankingEntry);
        }

        return $this;
    }
    /**
     * @return Collection|Picto[]
     */
    public function getDistributedPictos(): Collection
    {
        return $this->distributedPictos;
    }
    public function addDistributedPicto(Picto $distributedPicto): self
    {
        if (!$this->distributedPictos->contains($distributedPicto)) {
            $this->distributedPictos[] = $distributedPicto;
            $distributedPicto->setTownEntry($this);
        }

        return $this;
    }
    public function removeDistributedPicto(Picto $distributedPicto): self
    {
        if ($this->distributedPictos->contains($distributedPicto)) {
            $this->distributedPictos->removeElement($distributedPicto);
            // set the owning side to null (unless already changed)
            if ($distributedPicto->getTownEntry() === $this) {
                $distributedPicto->setTownEntry(null);
            }
        }

        return $this;
    }
    public static function fromTown(Town $town, bool $update = false): TownRankingProxy {
        if (!$update && $town->getRankingEntry()) return $town->getRankingEntry();

        $a = false;
        foreach ($town->getCitizens() as $c) if ($c->getAlive()) $a = true;

        $obj = (($update && $town->getRankingEntry()) ? $town->getRankingEntry() : (new TownRankingProxy())->setProfilerVersion( $town->getProfilerVersion() ))
            ->setBaseID( $town->getId() )
            ->setName( $town->getName() )
            ->setSeason( $town->getSeason() ?? null )
            ->setDays( $town->getDay() )
            ->setLanguage( $town->getLanguage() )
            ->setPopulation( $town->getPopulation() )
            ->setType( $town->getType() )
            ->setBonusScore( $town->getBonusScore() )
            ->setTown( $town );

        if ($obj->getBegin() === null) $obj->setBegin( new \DateTime('now') );
        if (!$town->isOpen() && !$a && $obj->getEnd() === null) $obj->setEnd( new \DateTime('now') );

        return $obj;
    }
    public function getType(): ?TownClass
    {
        return $this->type;
    }
    public function setType(?TownClass $type): self
    {
        $this->type = $type;

        return $this;
    }
    public function getSeason(): ?Season
    {
        return $this->season;
    }
    public function setSeason(?Season $season): self
    {
        $this->season = $season;

        return $this;
    }
    public function getImported(): ?bool
    {
        return $this->imported;
    }
    public function setImported(bool $imported): self
    {
        $this->imported = $imported;

        return $this;
    }
    public function getScore(): ?int
    {
        return $this->score;
    }
    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }
    public function getV1(): ?bool
    {
        return $this->v1;
    }
    public function setV1(bool $v1): self
    {
        $this->v1 = $v1;

        return $this;
    }
    public function getDisabled(): ?bool
    {
        return $this->disabled;
    }
    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }
    public function getEvent(): ?bool
    {
        return $this->event;
    }
    public function setEvent(bool $event): self
    {
        $this->event = $event;

        return $this;
    }
    public function getProfilerVersion(): ?int
    {
        return $this->profilerVersion;
    }
    public function setProfilerVersion(int $profilerVersion): self
    {
        $this->profilerVersion = $profilerVersion;

        return $this;
    }
    public function getDisableFlag(): ?int
    {
        return $this->disableFlag;
    }
    public function setDisableFlag(int $disableFlag): self
    {
        $this->disableFlag = $disableFlag;

        return $this;
    }
    public function addDisableFlag(int $disableFlag): self {
        $this->setDisableFlag( $this->getDisableFlag() | $disableFlag );
        return $this;
    }
    public function removeDisableFlag(int $disableFlag): self {
        $this->setDisableFlag( $this->getDisableFlag() & ~$disableFlag );
        return $this;
    }
    public function hasDisableFlag(int $disableFlag): bool {
        return ($this->getDisableFlag() & $disableFlag) === $disableFlag;
    }

    public function getBonusScore(): ?int
    {
        return $this->bonusScore ?? 0;
    }

    public function setBonusScore(int $bonusScore): static
    {
        $this->bonusScore = $bonusScore;

        return $this;
    }
}
