<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\CauseOfDeathRepository')]
#[UniqueEntity('ref')]
#[Table]
#[UniqueConstraint(name: 'ref_unique', columns: ['ref'])]
class CauseOfDeath
{
    /*
        FROM BBH
        1 Déshydratation terminale
        2 Strangulation
        3 Suicide par ingestion de Cyanure
        4 Justice populaire (mort par Pendaison) !
        5 Disparu(e) dans l'Outre-Monde pendant la nuit !
        6 Lacéré(e)... Dévoré(e)... pendant l'attaque de la nuit !
        7 Pénurie de drogue
        8 Infection généralisée
        9 Balle dans la tête
        10 Raison inconnue
        11 Meurtre par empoisonnement !
        12 Dévoré(e) par une Goule !
        13 Goule abattue au cours d'une agression !
        14 Goule affamée
        15 Cage à viande
        16 Crucifixion
        17 Pulvérisé un peu partout
        18 Possédé par une âme torturée
    */
    const Dehydration      = 1;
    // Old : 4;
    const Strangulation    = 2;
    // Old : 14;
    const Cyanide          = 3;
    // Old : 8;
    const Hanging          = 4;
    // Old : 12;
    const Vanished         = 5;
    // Old : 3;
    const NightlyAttack    = 6;
    // Old : 2;
    const Addiction        = 7;
    // Old : 6;
    const Infection        = 8;
    // Old : 7;
    const Headshot         = 9;
    // Old : 15;
    const Unknown          = 10;
    // Old : 1;
    const Poison           = 11;
    // Old : 9;
    const GhulEaten        = 12;
    // Old : 10;
    const GhulBeaten       = 13;
    // Old : 11;
    const GhulStarved      = 14;
    // Old : 5;
    const FleshCage        = 15;
    // Old : 13;
    const ChocolateCross   = 16;
    // Old : 19;
    const ExplosiveDoormat = 17;
    // Old : 18;
    const Haunted          = 18;
    // Old : 17;
    const Radiations       = 19;
    // Old : 16;
    const Apocalypse       = 20;
    // Old : --
    const LiverEaten       = 21;

    const RabidDog = 22;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 32)]
    private $icon;
    #[ORM\Column(type: 'string', length: 64)]
    private $label;
    #[ORM\Column(type: 'text')]
    private $description;
    #[ORM\Column(type: 'integer')]
    private $ref;
    #[ORM\ManyToMany(targetEntity: PictoPrototype::class)]
    private $pictos;
    public function __construct()
    {
        $this->pictos = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getIcon(): ?string
    {
        return $this->icon;
    }
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
    public function getLabel(): ?string
    {
        return $this->label;
    }
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
    public function getRef(): ?int
    {
        return $this->ref;
    }
    public function setRef(int $ref): self
    {
        $this->ref = $ref;

        return $this;
    }
    /**
     * @return Collection|PictoPrototype[]
     */
    public function getPictos(): Collection
    {
        return $this->pictos;
    }
    public function addPicto(PictoPrototype $picto): self
    {
        if (!$this->pictos->contains($picto)) {
            $this->pictos[] = $picto;
        }

        return $this;
    }
    public function removePicto(PictoPrototype $picto): self
    {
        $this->pictos->removeElement($picto);

        return $this;
    }
}
