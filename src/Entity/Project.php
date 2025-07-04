<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Config\Definition\Exception\Exception;


#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: "projects")]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?User $user;

    #[ORM\Column(length: 100)]
    private ?string $title = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    // Add the created_at column from the database
    #[ORM\Column(name: "created_at", type: "datetime", options: ['default' => "CURRENT_TIMESTAMP"])]
    private \DateTime $createdAt;

    #[ORM\Column(name: "updated_at", type: "datetime", options: ['default' => "CURRENT_TIMESTAMP"])]
    private \DateTime $updatedAt;

    #[ORM\Column(length: 255)]
    private string $imageUrl;

    /**
     * A project can have multiple comments
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: "project", cascade: ["remove"])]
    private Collection $comments;

    private Collec

    /**
     * A project can have multiple likes (some might be user-likes referencing this project)
     */
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: "project", cascade: ["remove"])]
    private Collection $likes;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->components = new ArrayCollection();
        $this->likes = new ArrayCollection();
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }
    

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Like>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Like $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setProject($this);
        }

        return $this;
    }

    public function removeLike(Like $like): static
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getProject() === $this) {
                $like->setProject(null);
            }
        }

        return $this;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function setComments(Collection $comments): void
    {
        $this->comments = $comments;
    }

    public function addComment(Comment $comment): void
    {
        $this->comments->add($comment);
    }

    public function removeComment(Comment $comment): void
    {
        if (!$this->comments->contains($comment)) {
            throw new Exception("No comment to delete");
        } else {
            $this->comments->removeElement($comment);
        }
    }
}
