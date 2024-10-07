<?php

namespace App\Entity;

use App\Enum\Game\CitizenPersistentCache;
use App\Repository\CitizenRankingProxyRepository;
use ArrayHelpers\Arr;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: CitizenRankingProxyRepository::class)]
#[Table]
#[UniqueConstraint(name: 'citizen_ranking_proxy_id_unique', columns: ['base_id', 'import_id', 'import_lang'])]
class CitizenRankingProxy
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
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'pastLifes')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $day;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $points;
    #[ORM\Column(type: 'text', nullable: true)]
    private $comment;
    #[ORM\ManyToOne(targetEntity: CauseOfDeath::class)]
    private $cod;
    #[ORM\Column(name: '`begin`', type: 'datetime', nullable: true)]
    private $begin;
    #[ORM\Column(name: '`end`', type: 'datetime', nullable: true)]
    private $end;
    #[ORM\ManyToOne(targetEntity: TownRankingProxy::class, inversedBy: 'citizens', fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(nullable: false)]
    private $town;
    #[ORM\Column(type: 'integer')]
    private $baseID;
    #[ORM\OneToOne(targetEntity: Citizen::class, inversedBy: 'rankingEntry', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $citizen;
    #[ORM\Column(type: 'text', nullable: true)]
    private $lastWords;
    #[ORM\Column(type: 'boolean')]
    private $confirmed = false;
    #[ORM\Column(type: 'integer')]
    private $importID = 0;
    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private $importLang;
    #[ORM\Column(type: 'integer')]
    private $dayOfDeath = 1;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $commentLocked;
    #[ORM\Column(type: 'string', length: 24, nullable: true)]
    private $alias;
    #[ORM\Column(type: 'boolean')]
    private $disabled = false;
    #[ORM\Column(type: 'boolean')]
    private $limitedImport = false;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $cleanup_type;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $cleanup_username;
    #[ORM\OneToOne(targetEntity: SoulResetMarker::class, mappedBy: 'ranking', cascade: ['persist', 'remove'],  fetch: 'EXTRA_LAZY')]
    private $resetMarker;
    #[ORM\Column(type: 'integer')]
    private $disableFlag = self::DISABLE_NOTHING;

    #[ORM\Column]
    private ?int $generosityBonus = 0;

    #[ORM\Column(nullable: true)]
    private ?array $data = null;
    public function getId(): ?int
    {
        return $this->id;
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
    public function getDay(): ?int
    {
        return $this->day;
    }
    public function setDay(?int $day): self
    {
        $this->day = $day;

        return $this;
    }
    public function getPoints(): ?int
    {
        return $this->points;
    }
    public function setPoints(?int $points): self
    {
        $this->points = $points;

        return $this;
    }
    public function getComment(): ?string
    {
        return $this->comment;
    }
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
    public function getCod(): ?CauseOfDeath
    {
        return $this->cod;
    }
    public function setCod(?CauseOfDeath $cod): self
    {
        $this->cod = $cod;

        return $this;
    }
    public function getBegin(): ?\DateTimeInterface
    {
        return $this->begin;
    }
    public function setBegin(\DateTimeInterface $begin): self
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
    public function getTown(): ?TownRankingProxy
    {
        return $this->town;
    }
    public function setTown(?TownRankingProxy $town): self
    {
        $this->town = $town;

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
    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }
    public function setCitizen(?Citizen $citizen): self
    {
        $this->citizen = $citizen;

        // set (or unset) the owning side of the relation if necessary
        $newRankingEntry = null === $citizen ? null : $this;
        if ($citizen !== null && $citizen->getRankingEntry() !== $newRankingEntry) {
            $citizen->setRankingEntry($newRankingEntry);
        }

        return $this;
    }
    public static function fromCitizen(Citizen $citizen, bool $update = false): CitizenRankingProxy {
        if (!$update && $citizen->getRankingEntry()) return $citizen->getRankingEntry();
        $obj = (($update && $citizen->getRankingEntry()) ? $citizen->getRankingEntry() : new CitizenRankingProxy())
            ->setBaseID( $citizen->getId() )
            ->setUser( $citizen->getUser() )
            ->setDay( $citizen->getSurvivedDays() )
            ->setDayOfDeath($citizen->getDayOfDeath())
            ->setTown( $citizen->getTown()->getRankingEntry() )
            ->setCitizen( $citizen )
            ->setComment( $citizen->getComment() )
            ->setLastWords( $citizen->getLastWords() )
            ->setAlias($citizen->getAlias())
        ;

        if ($obj->getEnd() === null)
            $obj->setPoints( $citizen->getSurvivedDays() * ( $citizen->getSurvivedDays() + 1 ) / 2 );

        if ($obj->getBegin() === null) $obj->setBegin( new \DateTime('now') );
        if (!$citizen->getAlive() && $obj->getEnd() === null) $obj->setEnd( new \DateTime('now') );
        if (!$citizen->getAlive() && $obj->getCod() === null) $obj->setCod( $citizen->getCauseOfDeath() );
        if (!$citizen->getAlive() && $citizen->getDisposed()) {
            $type = null;
            switch($citizen->getDisposed()){
                case Citizen::Thrown:
                    $type = "garbage";
                    break;
                case Citizen::Watered:
                    $type = "water";
                    break;
                case Citizen::Cooked:
                    $type = "cook";
                    break;
                case Citizen::Ghoul:
                    $type = "ghoul";
                    break;
            }
            $obj->setCleanupType($type);

            if ($citizen->getDisposedBy()->count() > 0)
                $obj->setCleanupUsername($citizen->getDisposedBy()[0]->getUser()->getName());
        }

        return $obj;
    }
    public function getLastWords(): ?string
    {
        return $this->lastWords;
    }
    public function setLastWords(?string $lastWords): self
    {
        $this->lastWords = $lastWords;

        return $this;
    }
    public function getConfirmed(): ?bool
    {
        return $this->confirmed;
    }
    public function setConfirmed(bool $confirmed): self
    {
        $this->confirmed = $confirmed;

        return $this;
    }
    public function getImportID(): ?int
    {
        return $this->importID;
    }
    public function setImportID(int $importID): self
    {
        $this->importID = $importID;

        return $this;
    }
    public function getImportLang(): ?string
    {
        return $this->importLang;
    }
    public function setImportLang(?string $importLang): self
    {
        $this->importLang = $importLang;

        return $this;
    }
    public function getDayOfDeath(): ?int
    {
        return $this->dayOfDeath;
    }
    public function setDayOfDeath(int $dayOfDeath): self
    {
        $this->dayOfDeath = $dayOfDeath;

        return $this;
    }
    public function getCommentLocked(): ?bool
    {
        return $this->commentLocked;
    }
    public function setCommentLocked(?bool $commentLocked): self
    {
        $this->commentLocked = $commentLocked;

        return $this;
    }
    public function getAlias(): ?string
    {
        return $this->alias;
    }
    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }
    public function getName(): string
    {
        return $this->getAlias() ?? $this->getUser()->getName();
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
    public function getLimitedImport(): ?bool
    {
        return $this->limitedImport;
    }
    public function setLimitedImport(bool $limitedImport): self
    {
        $this->limitedImport = $limitedImport;

        return $this;
    }
    public function getCleanupType(): ?string
    {
        return $this->cleanup_type;
    }
    public function setCleanupType(?string $cleanup_type): self
    {
        $this->cleanup_type = $cleanup_type;

        return $this;
    }
    public function getCleanupUsername(): ?string
    {
        return $this->cleanup_username;
    }
    public function setCleanupUsername(?string $cleanup_username): self
    {
        $this->cleanup_username = $cleanup_username;

        return $this;
    }
    public function getResetMarker(): ?SoulResetMarker
    {
        return $this->resetMarker;
    }
    public function setResetMarker(?SoulResetMarker $resetMarker): self
    {
        // set the owning side of the relation if necessary
        if ($resetMarker?->getRanking() !== $this) {
            $resetMarker?->setRanking($this);
        }

        $this->resetMarker = $resetMarker;

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

    public function getGenerosityBonus(): int
    {
        return $this->generosityBonus ?? 0;
    }

    public function setGenerosityBonus(int $generosityBonus): self
    {
        $this->generosityBonus = max(0, $generosityBonus);

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

    public function getProperty(CitizenPersistentCache|string $cache): int {
        return Arr::get( $this->getData() ?? [], is_string($cache) ? $cache : $cache->value, 0 );
    }

    public function registerProperty(CitizenPersistentCache|string $cache, int $value = 1): static {
        $data = $this->getData() ?? [];
        $accumulate = is_string( $cache ) ? str_ends_with( $cache, '_count' ) : $cache->isAccumulative();
        if ($accumulate)
            Arr::set( $data, is_string($cache) ? $cache : $cache->value, $this->getProperty( $cache ) + $value );
        else Arr::set( $data, is_string($cache) ? $cache : $cache->value, $value );
        $this->setData( $data );
        return $this;
    }
}
