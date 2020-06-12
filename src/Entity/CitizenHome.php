<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CitizenHomeRepository")
 */
class CitizenHome
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Inventory", inversedBy="home", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $chest;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Citizen", mappedBy="home", cascade={"persist", "remove"})
     */
    private $citizen;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CitizenHomeUpgrade", mappedBy="home", orphanRemoval=true)
     */
    private $citizenHomeUpgrades;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CitizenHomePrototype")
     * @ORM\JoinColumn(nullable=false)
     */
    private $prototype;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="integer")
     */
    private $additionalDefense = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $additionalStorage = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $holdsBody = false;

    /**
     * @ORM\Column(type="integer")
     */
    private $recycling = 0;

    public function __construct()
    {
        $this->citizenHomeUpgrades = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChest(): ?Inventory
    {
        return $this->chest;
    }

    public function setChest(Inventory $chest): self
    {
        $this->chest = $chest;

        return $this;
    }

    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }

    public function setCitizen(Citizen $citizen): self
    {
        $this->citizen = $citizen;

        // set the owning side of the relation if necessary
        if ($citizen->getHome() !== $this) {
            $citizen->setHome($this);
        }

        return $this;
    }

    /**
     * @return Collection|CitizenHomeUpgrade[]
     */
    public function getCitizenHomeUpgrades(): Collection
    {
        return $this->citizenHomeUpgrades;
    }

    public function addCitizenHomeUpgrade(CitizenHomeUpgrade $citizenHomeUpgrade): self
    {
        if (!$this->citizenHomeUpgrades->contains($citizenHomeUpgrade)) {
            $this->citizenHomeUpgrades[] = $citizenHomeUpgrade;
            $citizenHomeUpgrade->setHome($this);
        }

        return $this;
    }

    public function removeCitizenHomeUpgrade(CitizenHomeUpgrade $citizenHomeUpgrade): self
    {
        if ($this->citizenHomeUpgrades->contains($citizenHomeUpgrade)) {
            $this->citizenHomeUpgrades->removeElement($citizenHomeUpgrade);
            // set the owning side to null (unless already changed)
            if ($citizenHomeUpgrade->getHome() === $this) {
                $citizenHomeUpgrade->setHome(null);
            }
        }

        return $this;
    }

    public function getPrototype(): ?CitizenHomePrototype
    {
        return $this->prototype;
    }

    public function setPrototype(?CitizenHomePrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAdditionalDefense(): ?int
    {
        return $this->additionalDefense;
    }

    public function setAdditionalDefense(int $additionalDefense): self
    {
        $this->additionalDefense = $additionalDefense;

        return $this;
    }

    public function getAdditionalStorage(): ?int
    {
        return $this->additionalStorage;
    }

    public function setAdditionalStorage(int $additionalStorage): self
    {
        $this->additionalStorage = $additionalStorage;

        return $this;
    }

    public function getHoldsBody(): ?bool
    {
        return $this->holdsBody;
    }

    public function setHoldsBody(bool $holdsBody): self
    {
        $this->holdsBody = $holdsBody;

        return $this;
    }

    public function getRecycling(): ?int
    {
        return $this->recycling;
    }

    public function setRecycling(?int $recycling): self
    {
        $this->recycling = $recycling;

        return $this;
    }
}
