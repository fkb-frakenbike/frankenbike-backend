<?php

namespace App\Entity;

use App\Repository\LikeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LikeRepository::class)]
#[ORM\Table(name: "likes")]
class Like
{
    #[Groups(['like:read', 'user:read', 'project:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['like:read'])]
    #[ORM\ManyToOne(inversedBy: 'likes')]
    #[ORM\JoinColumn(name: "user_id", nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    #[Groups(['like:read'])]
    #[ORM\ManyToOne(inversedBy: 'likes')]
    #[ORM\JoinColumn(name: "project_id", nullable: true, onDelete: "CASCADE")]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: Comment::class, inversedBy: null)]
    #[ORM\JoinColumn(name: "comment_id", nullable: true, onDelete: "CASCADE")]
    private ?Comment $comment = null;

    #[ORM\Column(type: "datetime_immutable")]
    private ?\DateTimeImmutable $created_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self {
        $this->project = $project;
        if ($project !== null) $this->comment = null;
        return $this;
    }

    public function setComment(?Comment $comment): self {
        $this->comment = $comment;
        if ($comment !== null) $this->project = null;
        return $this;
    }

    public function getComment(): ?Comment {
        return $this->comment;
    }


    public function getCreated_at(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

}
