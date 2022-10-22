<?php

namespace App\Entity;

use App\Enum\ArrayMergeDirective;
use App\Repository\NamedItemGroupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NamedItemGroupRepository::class)]
class NamedItemGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(type: 'string', length: 64)]
    private string $name = '';
    #[ORM\ManyToOne(targetEntity: ItemGroup::class, fetch: 'EAGER', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $itemGroup;
    #[ORM\Column(type: 'integer', enumType: ArrayMergeDirective::class)]
    private ArrayMergeDirective $operator = ArrayMergeDirective::Overwrite;
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
    public function getItemGroup(): ?ItemGroup
    {
        return $this->itemGroup;
    }
    public function setItemGroup(?ItemGroup $itemGroup): self
    {
        $this->itemGroup = $itemGroup;

        return $this;
    }
    public function getOperator(): ?ArrayMergeDirective
    {
        return $this->operator;
    }
    public function setOperator(ArrayMergeDirective $operator): self
    {
        $this->operator = $operator;

        return $this;
    }
}
