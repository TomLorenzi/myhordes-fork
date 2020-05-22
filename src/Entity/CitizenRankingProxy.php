<?php

namespace App\Entity;

use App\Repository\CitizenRankingProxyRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=CitizenRankingProxyRepository::class)
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="citizen_ranking_proxy_id_unique",columns={"base_id"})
 * })
 */
class CitizenRankingProxy
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="pastLifes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $day;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $points;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity=CauseOfDeath::class)
     */
    private $cod;

    /**
     * @ORM\Column(type="datetime")
     */
    private $begin;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $end;

    /**
     * @ORM\ManyToOne(targetEntity=TownRankingProxy::class, inversedBy="citizens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $town;

    /**
     * @ORM\Column(type="integer")
     */
    private $baseID;

    /**
     * @ORM\OneToOne(targetEntity=Citizen::class, mappedBy="rankingEntry", cascade={"persist"})
     */
    private $citizen;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $lastWords;

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
            ->setTown( $citizen->getTown()->getRankingEntry() )
            ->setCitizen( $citizen )
            ->setComment( $citizen->getComment() )
            ->setLastWords( $citizen->getLastWords() )
            ->setPoints( $citizen->getSurvivedDays() * ( $citizen->getSurvivedDays() + 1 ) / 2 );

        if ($obj->getBegin() === null) $obj->setBegin( new \DateTime('now') );
        if (!$citizen->getAlive() && $obj->getEnd() === null) $obj->setEnd( new \DateTime('now') );
        if (!$citizen->getAlive() && $obj->getCod() === null) $obj->setCod( $citizen->getCauseOfDeath() );

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
}
