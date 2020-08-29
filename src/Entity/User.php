<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity("email")
 * @UniqueEntity("name")
 * @Table(
 *     name="`user`",
 *     uniqueConstraints={
 *         @UniqueConstraint(name="email_unique",columns={"email"}),
 *         @UniqueConstraint(name="user_name_unique",columns={"name"}),
 *         @UniqueConstraint(name="user_twinoid_unique",columns={"twinoid_id"})
 *     }
 * )
 */
class User implements UserInterface, EquatableInterface
{

    const ROLE_USER      =  0;
    const ROLE_ORACLE    =  2;
    const ROLE_CROW      =  3;
    const ROLE_ADMIN     =  4;
    const ROLE_SUPER     =  5;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private $email;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $pass;

    /**
     * @ORM\Column(type="boolean")
     */
    private $validated;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\UserPendingValidation", mappedBy="user", cascade={"persist", "remove"})
     */
    private $pendingValidation;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Citizen", mappedBy="user")
     */
    private $citizens;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AdminBan", mappedBy="user")
     */
    private $bannings;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\FoundRolePlayText", mappedBy="user", cascade={"persist", "remove"})
     */
    private $foundTexts;

    /**
     * @ORM\Column(type="integer")
     */
    private $soulPoints = 0;
    
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Picto", mappedBy="user", cascade={"persist", "remove"})
     */
    private $pictos;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Award", mappedBy="user")
     */
    private $awards;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $externalId = '';

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Avatar", cascade={"persist", "remove"})
     */
    private $avatar;

    /**
     * @ORM\Column(type="boolean")
     */
    private $preferSmallAvatars = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $postAsDefault;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $forumTitle;

    /**
     * This field matches to the filename of the picto
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $forumIcon;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $language = null;

    /**
     * @ORM\Column(type="smallint")
     */
    private $rightsElevation = 0;

    /**
     * @ORM\OneToMany(targetEntity=CitizenRankingProxy::class, mappedBy="user", orphanRemoval=true)
     */
    private $pastLifes;

    /**
     * @ORM\Column(type="integer")
     */
    private $heroDaysSpent = 0;

    /**
     * @ORM\OneToOne(targetEntity=TwinoidImportPreview::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $twinoidImportPreview;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $twinoidID;

    /**
     * @ORM\OneToMany(targetEntity=TwinoidImport::class, mappedBy="user", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $twinoidImports;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $importedSoulPoints;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $importedHeroDaysSpent;

    /**
     * @ORM\ManyToOne(targetEntity=Changelog::class)
     */
    private $latestChangelog;

    /**
     * @ORM\OneToMany(targetEntity=ConnectionIdentifier::class, mappedBy="user", orphanRemoval=true)
     */
    private $connectionIdentifiers;

    /**
     * @ORM\OneToOne(targetEntity=ShadowBan::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $shadowBan;

    public function __construct()
    {
        $this->citizens = new ArrayCollection();
        $this->foundTexts = new ArrayCollection();
        $this->pictos = new ArrayCollection();
        $this->bannings = new ArrayCollection();
        $this->pastLifes = new ArrayCollection();
        $this->twinoidImports = new ArrayCollection();
        $this->connectionIdentifiers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getForumIcon(): ?string {
        return $this->forumIcon;
    }

    public function setForumIcon(string $value): self {
        $this->forumIcon = $value;

        return $this;
    }

    public function getForumTitle(): ?string {
        return $this->forumTitle;
    }

    public function setForumTitle(string $value): self {
        $this->forumTitle = $value;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSalt( ): string {
        return 'user_salt_myhordes_ffee45';
    }

    /**
     * @return Collection|AdminBan[]
     */
    public function getBannings(): Collection
    {
        return $this->bannings;
    }

    public function getValidated(): ?bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): self
    {
        $this->validated = $validated;

        return $this;
    }

    public function getPendingValidation(): ?UserPendingValidation
    {
        return $this->pendingValidation;
    }

    public function setPendingValidation(?UserPendingValidation $pendingValidation): self
    {
        $this->pendingValidation = $pendingValidation;

        // set (or unset) the owning side of the relation if necessary
        $newUser = null === $pendingValidation ? null : $this;
        if ($pendingValidation && $pendingValidation->getUser() !== $newUser) {
            $pendingValidation->setUser($newUser);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        $roles = [];
        if ($this->pass === null) return $roles;

        if     ($this->rightsElevation >= self::ROLE_SUPER)  $roles[] = 'ROLE_SUPER';
        elseif ($this->rightsElevation >= self::ROLE_ADMIN)  $roles[] = 'ROLE_ADMIN';
        elseif ($this->rightsElevation >= self::ROLE_CROW)   $roles[] = 'ROLE_CROW';
        elseif ($this->rightsElevation >= self::ROLE_ORACLE) $roles[] = 'ROLE_ORACLE';

        if (strstr($this->email, "@localhost") === "@localhost") $roles[] = 'ROLE_DUMMY';
        if ($this->email === 'crow') $roles[] = 'ROLE_CROW';

        if ($this->validated) $roles[] = 'ROLE_USER';
        else $roles[] = 'ROLE_REGISTERED';

        if ($this->getIsBanned()) $roles[] = 'ROLE_FORUM_BANNED';

        if ($this->getShadowBan()) $roles[] = 'ROLE_SHADOW_BANNED';

        return array_unique($roles);
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return $this->pass;
    }

    public function setPassword(?string $pass): self {
        $this->pass = $pass;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials() {}

    /**
     * @inheritDoc
     */
    public function isEqualTo(UserInterface $user) {
        $b1 = $this->getPassword() !== null && $user->getPassword() !== null &&
            $this->getUsername() === $user->getUsername() &&
            $this->getPassword() === $user->getPassword() &&
            $this->getRoles() === $user->getRoles();
        if ($user instanceof User) {
            return $b1 &&
                $this->getRightsElevation() === $user->getRightsElevation();
        } else return $b1;
    }

    public function getIsBanned(): bool {
        foreach ($this->getBannings() as $b)
            if ($b->getActive())
                return true;
        return false;
    }

    public function getLongestActiveBan(): ?AdminBan {
        $ban = null;
        foreach ($this->getBannings() as $b){
            if ($b->getActive()) {
                if (!isset($ban)) {
                    $ban = $b;                       
                }
                else if ($b->getBanEnd() > $ban->getBanEnd())
                    $ban = $b;     
            }
        }            
        if (isset($ban))
            return $ban;
        return null;
    }

    /**
     * @return Collection|AdminBan[]
     */
    public function getActiveBans(): Collection {
        $bans = $this->getBannings();
        foreach ($bans as $ban){
                if (!($ban->getActive()))
                    $bans->remove($ban->getId());  
            }            
        return $bans;
    }

    public function getActiveCitizen(): ?Citizen {
        foreach ($this->getCitizens() as $c)
            if ($c->getActive())
                return $c;
        return null;
    }

    public function getAliveCitizen(): ?Citizen {
        foreach ($this->getCitizens() as $c)
            if ($c->getAlive())
                return $c;
        return null;
    }

    /**
     * @return Collection|Citizen[]
     */
    public function getCitizens(): Collection
    {
        return $this->citizens;
    }

    public function addCitizen(Citizen $citizen): self
    {
        if (!$this->citizens->contains($citizen)) {
            $this->citizens[] = $citizen;
            $citizen->setUser($this);
        }

        return $this;
    }

    public function removeCitizen(Citizen $citizen): self
    {
        if ($this->citizens->contains($citizen)) {
            $this->citizens->removeElement($citizen);
            // set the owning side to null (unless already changed)
            if ($citizen->getUser() === $this) {
                $citizen->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|FoundRolePlayText[]
     */
    public function getFoundTexts(): Collection
    {
        return $this->foundTexts;
    }

    public function addFoundText(FoundRolePlayText $foundText): self
    {
        if (!$this->foundTexts->contains($foundText)) {
            $this->foundTexts[] = $foundText;
        }

        return $this;
    }

    public function removeFoundText(FoundRolePlayText $foundText): self
    {
        if ($this->foundTexts->contains($foundText)) {
            $this->foundTexts->removeElement($foundText);
        }

        return $this;
    }

    public function getAllSoulPoints(): int {
        return ($this->getSoulPoints() ?? 0) + ($this->getImportedSoulPoints() ?? 0);
    }

    public function getSoulPoints(): ?int
    {
        return $this->soulPoints;
    }

    public function setSoulPoints(int $soulPoints): self
    {
        $this->soulPoints = $soulPoints;

        return $this;
    }

    public function addSoulPoints(int $soulPoints): self
    {
        $this->soulPoints += $soulPoints;

        return $this;
    }


    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return Collection|Awards[]
     */
    public function getAwards(): Collection {
        return $this->awards;
    }

    public function addAward(Award $award): self {
        if(!$this->awards->contains($award)) {
            $this->awards[] = $award;
            $award->setUser($this);
        }

        return $this;
    }

    public function removeAward(Award $award): self {
        if ($this->awards->contains($award)) {
            $this->awards->removeElement($award);
            // set the owning side to null (unless already changed)
            if ($award->getUser() === $this) {
                $award->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Pictos[]
     */
    public function getPictos(): Collection
    {
        return $this->pictos;
    }

    public function addPicto(Picto $picto): self
    {
        if (!$this->pictos->contains($picto)) {
            $this->pictos[] = $picto;
            $picto->setUser($this);
        }

        return $this;
    }

    public function removePicto(Picto $picto): self
    {
        if ($this->pictos->contains($picto)) {
            $this->pictos->removeElement($picto);
            // set the owning side to null (unless already changed)
            if ($picto->getUser() === $this) {
                $picto->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @param int $persisted
     * @param PictoPrototype $prototype
     * @param Town|TownLogEntry $town
     * @return Picto|null
     */
    public function findPicto( int $persisted, PictoPrototype $prototype, $town ): ?Picto {
        foreach ($this->getPictos() as $picto) {
            /** @var Picto $picto */
            if (
                $picto->getPersisted() === $persisted &&
                $picto->getPrototype() === $prototype &&
                (($town instanceof Town) ? ($picto->getTown() === $town) : ($picto->getTownEntry() === $town)))
                return $picto;
        }
        return null;
    }

    public function getAvatar(): ?Avatar
    {
        return $this->avatar;
    }

    public function setAvatar(?Avatar $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getPreferSmallAvatars(): ?bool
    {
        return $this->preferSmallAvatars;
    }

    public function setPreferSmallAvatars(bool $preferSmallAvatars): self
    {
        $this->preferSmallAvatars = $preferSmallAvatars;

        return $this;
    }

    public function getPostAsDefault(): ?string
    {
        return $this->postAsDefault;
    }

    public function setPostAsDefault(?string $postAsDefault): self
    {
        $this->postAsDefault = $postAsDefault;

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

    public function getRightsElevation(): ?int
    {
        return $this->rightsElevation;
    }

    public function setRightsElevation(int $rightsElevation): self
    {
        $this->rightsElevation = $rightsElevation;

        return $this;
    }

    /**
     * @return Collection|CitizenRankingProxy[]
     */
    public function getPastLifes(): Collection
    {
        return $this->pastLifes;
    }

    public function addPastLife(CitizenRankingProxy $pastLife): self
    {
        if (!$this->pastLifes->contains($pastLife)) {
            $this->pastLifes[] = $pastLife;
            $pastLife->setUser($this);
        }

        return $this;
    }

    public function removePastLife(CitizenRankingProxy $pastLife): self
    {
        if ($this->pastLifes->contains($pastLife)) {
            $this->pastLifes->removeElement($pastLife);
            // set the owning side to null (unless already changed)
            if ($pastLife->getUser() === $this) {
                $pastLife->setUser(null);
            }
        }

        return $this;
    }

    public function getAllHeroDaysSpent(): int
    {
        return ($this->getHeroDaysSpent() ?? 0) + ($this->getImportedHeroDaysSpent() ?? 0);
    }

    public function getHeroDaysSpent(): ?int
    {
        return $this->heroDaysSpent;
    }

    public function setHeroDaysSpent(int $heroDaysSpent): self
    {
        $this->heroDaysSpent = $heroDaysSpent;

        return $this;
    }

    public function getTwinoidImportPreview(): ?TwinoidImportPreview
    {
        return $this->twinoidImportPreview;
    }

    public function setTwinoidImportPreview(?TwinoidImportPreview $twinoidImportPreview): self
    {
        $this->twinoidImportPreview = $twinoidImportPreview;

        // set the owning side of the relation if necessary
        if ($twinoidImportPreview !== null && $twinoidImportPreview->getUser() !== $this) {
            $twinoidImportPreview->setUser($this);
        }

        return $this;
    }

    public function getTwinoidID(): ?int
    {
        return $this->twinoidID;
    }

    public function setTwinoidID(?int $twinoidID): self
    {
        $this->twinoidID = $twinoidID;

        return $this;
    }

    /**
     * @return Collection|TwinoidImport[]
     */
    public function getTwinoidImports(): Collection
    {
        return $this->twinoidImports;
    }

    public function addTwinoidImport(TwinoidImport $twinoidImport): self
    {
        if (!$this->twinoidImports->contains($twinoidImport)) {
            $this->twinoidImports[] = $twinoidImport;
            $twinoidImport->setUser($this);
        }

        return $this;
    }

    public function removeTwinoidImport(TwinoidImport $twinoidImport): self
    {
        if ($this->twinoidImports->contains($twinoidImport)) {
            $this->twinoidImports->removeElement($twinoidImport);
            // set the owning side to null (unless already changed)
            if ($twinoidImport->getUser() === $this) {
                $twinoidImport->setUser(null);
            }
        }

        return $this;
    }

    public function getImportedSoulPoints(): ?int
    {
        return $this->importedSoulPoints;
    }

    public function setImportedSoulPoints(?int $importedSoulPoints): self
    {
        $this->importedSoulPoints = $importedSoulPoints;

        return $this;
    }

    public function getImportedHeroDaysSpent(): ?int
    {
        return $this->importedHeroDaysSpent;
    }

    public function setImportedHeroDaysSpent(?int $importedHeroDaysSpent): self
    {
        $this->importedHeroDaysSpent = $importedHeroDaysSpent;

        return $this;
    }

    public function getLatestChangelog(): ?Changelog
    {
        return $this->latestChangelog;
    }

    public function setLatestChangelog(?Changelog $latestChangelog): self
    {
        $this->latestChangelog = $latestChangelog;

        return $this;
    }

    /**
     * @return Collection|ConnectionIdentifier[]
     */
    public function getConnectionIdentifiers(): Collection
    {
        return $this->connectionIdentifiers;
    }

    public function addConnectionIdentifier(ConnectionIdentifier $connectionIdentifier): self
    {
        if (!$this->connectionIdentifiers->contains($connectionIdentifier)) {
            $this->connectionIdentifiers[] = $connectionIdentifier;
            $connectionIdentifier->setUser($this);
        }

        return $this;
    }

    public function removeConnectionIdentifier(ConnectionIdentifier $connectionIdentifier): self
    {
        if ($this->connectionIdentifiers->contains($connectionIdentifier)) {
            $this->connectionIdentifiers->removeElement($connectionIdentifier);
            // set the owning side to null (unless already changed)
            if ($connectionIdentifier->getUser() === $this) {
                $connectionIdentifier->setUser(null);
            }
        }

        return $this;
    }

    public function getShadowBan(): ?ShadowBan
    {
        return $this->shadowBan;
    }

    public function setShadowBan(?ShadowBan $shadowBan): self
    {
        $this->shadowBan = $shadowBan;

        // set the owning side of the relation if necessary
        if ($shadowBan && $shadowBan->getUser() !== $this) {
            $shadowBan->setUser($this);
        }

        return $this;
    }
}
