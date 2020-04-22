<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RolePlayTextPageRepository")
 * @UniqueEntity("id")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="page_unique",columns={"page_number", "role_play_text_id"})
 * })
 */
class RolePlayTextPage
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $page_number;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RolePlayText", inversedBy="pages")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rolePlayText;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPageNumber(): ?string
    {
        return $this->page_number;
    }

    public function setPageNumber(?string $page_number): self
    {
        $this->page_number = $page_number;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getRolePlayText(): ?RolePlayText
    {
        return $this->rolePlayText;
    }

    public function setRolePlayText(RolePlayText $rolePlayText): self
    {
        $this->rolePlayText = $rolePlayText;

        return $this;
    }
}
