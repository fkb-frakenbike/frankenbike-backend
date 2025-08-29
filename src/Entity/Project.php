<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: "projects")]
class Project
{
    #[Groups(['project:read', 'user:read', 'like:read','comment:read', 'component:read','timeline:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['project:read'])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'projects')]
    private ?User $user=null;

    #[Groups(['project:read','timeline:read'])]
    #[ORM\Column(length: 255,  nullable: false)]
    private ?string $title = null;

    #[Groups(['project:read'])]
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    // Add the created_at column from the database
    #[Groups(['project:read'])]
    #[ORM\Column(name: "created_at", type: "datetime_immutable", options: ['default' => "CURRENT_TIMESTAMP"])]
    private \DateTimeImmutable $createdAt;

    #[Groups(['project:read'])]
    #[ORM\Column(name: "updated_at", type: "datetime", options: ['default' => "CURRENT_TIMESTAMP"])]
    private \DateTime $updatedAt;

    #[Groups(['project:read'])]
    #[ORM\Column(name: "image_url",length: 255,  nullable: false)]
    private string $imageUrl= "";

    /**
     * A project can have multiple comments
     */
    #[Groups(['project:read'])]
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: "project", cascade: ["remove"], orphanRemoval: true)]
    private Collection $comments;

    #[Groups(['project:read'])]
    #[ORM\OneToMany(targetEntity: Component::class, mappedBy: "project", cascade: ["remove"], orphanRemoval: true)]
    private Collection $components;



    /**
     * A project can have multiple likes (some might be user-likes referencing this project)
     */
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: "project", cascade: ["remove"], orphanRemoval: true)]
    private Collection $likes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCreatedAt(): ?\DateTimeImmutable
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

    public function setImageUrl(string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setProject($this); // set the owning side!
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if($comment->getProject()===$this){
                $comment->setProject(null);
            }
        }
        return $this;
    }

    public function getComponents(): Collection
    {
        return $this->components;
    }

    public function addComponent(Component $component): static
    {
        if(!$this->components->contains($component)) {
            $this->components->add($component);
            $component->setProject($this);
        }
        return $this;
    }

    public function removeComponent(Component $component): static
    {
        if ($this->components->removeElement($component)) {
            if ($component->getProject() === $this) {
                $component->setProject(null);
            }
        }
        return $this;
    }
}
