<?php

namespace App\Entity;

use App\Enum\ComponentCategory;
use App\Enum\ComponentOrigin;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name:"components")]
class Component
{
    #[Groups(['component:read', 'project:read'])]
    #[ORM\Id]
    #[ORM\Column(type:"integer")]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Groups(['component:read', 'project:read'])]
    #[ORM\Column(type:"string", length: 255)]
    private ?string $name;

    #[Groups(['component:read', 'project:read'])]
    #[ORM\Column(type:'text', nullable:true)]
    private ?string $description = null;

    #[Groups(['component:read', 'project:read'])]
    #[ORM\Column(type: "string", length: 50, enumType: ComponentOrigin::class)]
    private ComponentOrigin $origin; //enum

    #[Groups(['component:read'])]
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: "components")]
    #[ORM\JoinColumn(name: "project_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Project $project = null;

    #[Groups(['component:read', 'project:read'])]
    #[ORM\Column(type: "string", length: 50, enumType: ComponentCategory::class)]
    private ComponentCategory $category;

    #[Groups(['component:read', 'project:read'])]
    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable  $createdAt;

    #[Groups(['component:read', 'project:read'])]
    #[ORM\Column(name: "photo_s3_key", type: "string", length: 512, options: ['default' => ''])]
    private string $photoS3Key = '';

    #[Groups(['component:read', 'project:read'])]
    #[ORM\Column(name: "photo_mime_type", type: "string", length: 128, options: ['default' => 'image/jpeg'])]
    private string $photoMimeType = 'image/jpeg';

    #[Groups(['component:read', 'project:read'])]
    #[ORM\Column(name: "photo_size", type: "bigint", options: ['default' => 0])]
    private int $photoSize = 0;

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

    public function getPhotoS3Key(): string
    {
        return $this->photoS3Key;
    }

    public function setPhotoS3Key(string $key): self
    {
        $this->photoS3Key = $key;
        return $this;
    }

    public function getPhotoMimeType(): string
    {
        return $this->photoMimeType;
    }

    public function setPhotoMimeType(string $mime): self
    {
        $this->photoMimeType = $mime;
        return $this;
    }

    public function getPhotoSize(): int
    {
        return $this->photoSize;
    }

    public function setPhotoSize(int $size): self
    {
        $this->photoSize = $size;
        return $this;
    }
}