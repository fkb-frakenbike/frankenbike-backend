<?php

namespace App\Entity;

use App\Enum\ComponentCategory;
use App\Enum\ComponentOrigin;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name:"components")]
class Component
{
    #[ORM\Id]
    #[ORM\Column(type:"integer")]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type:"string", length: 255)]
    private ?string $name;

    #[ORM\Column(type:'text', nullable:true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 50, enumType: ComponentOrigin::class)]
    private ComponentOrigin $origin; //enum

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: "components")]
    #[ORM\JoinColumn(name: "project_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Project $project = null;

    #[ORM\Column(type: "string", length: 50, enumType: ComponentCategory::class)]
    private ComponentCategory $category;

    #[ORM\Column(type: "datetime")]
    private \DateTimeImmutable  $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName( string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ComponentCategory
    {
        return $this->category;
    }

    public function setCategory(ComponentCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getOrigin(): ComponentOrigin
    {
        return $this->origin;
    }

    public function setOrigin(ComponentOrigin $origin): static
    {
        $this->origin =$origin;
        return $this;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }
}