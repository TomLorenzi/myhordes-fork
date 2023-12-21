<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: 'App\Repository\ActionCounterRepository')]
#[Table]
#[UniqueConstraint(name: 'action_counter_assoc_unique', columns: ['citizen_id', 'type', 'reference_id'])]
class ActionCounter
{
    const ActionTypeWell        		= 1;
    const ActionTypeHomeKitchen 		= 2;
    const ActionTypeHomeLab     		= 3;
    const ActionTypeTrash       		= 4;
    const ActionTypeComplaint   		= 5;
    const ActionTypeRemoveLog   		= 6;
    const ActionTypeSendPMItem  		= 7;
    const ActionTypeSandballHit 		= 8;
    const ActionTypeClothes     		= 9;
    const ActionTypeHomeCleanup 		= 10;
    const ActionTypeShower      		= 11;
    const ActionTypeReceiveHeroic 		= 12;
	const ActionTypePool      			= 13;
    const ActionTypeSpecialDigScavenger = 14;
	const ActionTypeDumpInsertion 		= 15;
    const ActionTypeSpecialActionTech	= 16;
    const ActionTypeSpecialActionSurv	= 17;
    const ActionTypeSpecialActionHunter	= 18;
    const ActionTypeSpecialActionAPLoan	= 19;
    const PerGameActionTypes = [
        self::ActionTypeRemoveLog,
		self::ActionTypePool
    ];
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer')]
    private $type;
    #[ORM\Column(type: 'integer')]
    private $count = 0;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Citizen', inversedBy: 'actionCounters')]
    #[ORM\JoinColumn(nullable: false)]
    private $citizen;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $last;
    #[ORM\Column(type: 'integer')]
    private $referenceID = 0;

    #[ORM\Column(nullable: true)]
    private ?array $additionalData = [];
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getType(): ?int
    {
        return $this->type;
    }
    public function setType(int $type): self
    {
        $this->type = $type;
        return $this;
    }
    public function getCount(): ?int
    {
        return $this->count;
    }
    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this->setLast($count === 0 ? null :new \DateTime());
    }
    public function increment(int $by = 1): self {
        $this->count += max(0,$by);
        return $this->setLast(new \DateTime());
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
    public function getLast(): ?\DateTimeInterface
    {
        return $this->last;
    }
    public function setLast(?\DateTimeInterface $last): self
    {
        $this->last = $last;

        return $this;
    }
    public function getDaily(): ?bool
    {
        return !in_array($this->type, self::PerGameActionTypes);
    }
    public function getReferenceID(): ?int
    {
        return $this->referenceID;
    }
    public function setReferenceID(int $referenceID): self
    {
        $this->referenceID = $referenceID;

        return $this;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData ?? [];
    }

    public function setAdditionalData(?array $additionalData): self
    {
        $this->additionalData = $additionalData;

        return $this;
    }

    public function addRecord( array $data ): self {
        $this->setAdditionalData( array_merge( $this->getAdditionalData() ?? [], [$data] ) );
        return $this;
    }

    public function setRecord( int $key, mixed $data, ?string $recordKey = null ): self {
        $json = $this->getAdditionalData();
        if ($recordKey !== null) {
            $entry = $json[$key] ?? [];
            $entry[$recordKey] = $data;
            $json[$key] = $entry;
        } else $json[$key] = $data;
        $this->setAdditionalData( $json );
        return $this;
    }
}
