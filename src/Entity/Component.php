<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\ORM\Table(name:"components")]
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

    #[ORM\Column(length: 50)]
    private string $origin;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: "components")]
    #[ORM\JoinColumn(name: "project_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: ComponentCategory::class, inversedBy: "components")]
    #[ORM\JoinColumn(name: "category_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?ComponentCategory $category = null;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}