<?php

namespace App\Entity;

use App\Repository\BankAntiAbuseRepository;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BankAntiAbuseRepository::class)
 */
class BankAntiAbuse
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Citizen", inversedBy="bankAntiAbuse")
     * @ORM\JoinColumn(nullable=false)
     */
    private $citizen;

    /**
     * @ORM\Column(type="integer")
     */
    private $nbItemTaken = 0;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNbItemTaken(): ?int
    {
        return $this->nbItemTaken;
    }

    public function setNbItemTaken(int $nbItemTaken): self
    {
        $this->nbItemTaken = $nbItemTaken;

        return $this;
    }

    public function increaseNbItemTaken(): self
    {
        $this->setNbItemTaken( $this->getNbItemTaken() + 1 );
        return $this;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }

    public function setCitizen(?Citizen $citizen): self
    {
        $this->citizen = $citizen;

        return $this;
    }
}
