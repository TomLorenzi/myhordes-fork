<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: 'App\Repository\AwardRepository')]
class Award
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'awards')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AwardPrototype')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private $prototype;
    #[ORM\Column(type: 'string', length: 190, nullable: true)]
    private $customTitle;
    #[ORM\Column(type: 'blob', nullable: true)]
    private $customIcon;
    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private $customIconName;
    #[ORM\Column(type: 'string', length: 9, nullable: true)]
    private $customIconFormat;
    public function getUser(): ?User {
        return  $this->user;
    }
    public function getId(): ?int {
        return $this->id;
    }
    public function getPrototype(): ?AwardPrototype {
        return $this->prototype;
    }
    public function setPrototype(AwardPrototype $value): self {
        $this->prototype = $value;
        return $this;
    }
    public function setUser(?User $value): self {
        $this->user = $value;
        return $this;
    }
    public function getCustomTitle(): ?string
    {
        return $this->customTitle;
    }
    public function setCustomTitle(?string $customTitle): self
    {
        $this->customTitle = $customTitle;

        return $this;
    }
    public function getCustomIcon()
    {
        return $this->customIcon;
    }
    public function setCustomIcon($customIcon): self
    {
        $this->customIcon = $customIcon;

        return $this;
    }
    public function getCustomIconName(): ?string
    {
        return $this->customIconName;
    }
    public function setCustomIconName(?string $customIconName): self
    {
        $this->customIconName = $customIconName;

        return $this;
    }
    public function getCustomIconFormat(): ?string
    {
        return $this->customIconFormat;
    }
    public function setCustomIconFormat(?string $customIconFormat): self
    {
        $this->customIconFormat = $customIconFormat;

        return $this;
    }
}