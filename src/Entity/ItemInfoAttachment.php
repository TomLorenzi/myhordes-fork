<?php

namespace App\Entity;

use App\Repository\ItemInfoAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemInfoAttachmentRepository::class)]
class ItemInfoAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\OneToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $item;
    #[ORM\Column(type: 'json')]
    private $data = [];
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getItem(): ?Item
    {
        return $this->item;
    }
    public function setItem(Item $item): self
    {
        $this->item = $item;

        return $this;
    }
    public function getData(): ?array
    {
        return $this->data;
    }
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
    public function get(string $info, $default = null) {
        return $this->data[$info] ?? $default;
    }
    public function set(string $info, $value) {
        $this->data[$info] = $value;
        $this->setData($this->data);
    }
}
