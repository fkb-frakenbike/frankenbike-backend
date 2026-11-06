<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

// 1. Import these interfaces from Symfony Security
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    #[Groups(['user:read', 'project:read'])]
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $id = null;

    #[Groups(['user:read', 'profile:read', 'project:read'])]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    private ?string $plainPassword = null;

    // Map to the "role" column in your DB
    #[Groups(['user:read', 'profile:read'])]
    #[ORM\Column(name: "role", type: "string", length: 5, options: ["default" => "user"])]
    private ?string $role;

    // Add the created_at column from the database
    #[Groups(['user:read'])]
    #[ORM\Column(name: "created_at", type: "datetime_immutable", options: ['default' => "CURRENT_TIMESTAMP"])]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'user')]
    private Collection $projects;

    /**
     * @var Collection<int, Like>
     */
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'user')]
    private Collection $likes;

    #[Groups(['user:read', 'project:read'])]
    #[ORM\OneToOne(targetEntity: Profile::class, mappedBy: 'user')]
    private ?Profile $profile = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user')]
    private Collection $comments;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->projects = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role ?? 'user';
    }
    
    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function setProfile(?Profile $profile): self {
        $this->profile = $profile;
        if ($profile && $profile->getUser() !== $this) {
            $profile->setUser($this);
        }
        return $this;
    }
    public function getProfile(): ?Profile {
        return $this->profile;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setUser($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getUser() === $this) {
                $project->setUser(null);
            }
        }

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
            $like->setUser($this);
        }

        return $this;
    }

    public function removeLike(Like $like): static
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getUser() === $this) {
                $like->setUser(null);
            }
        }

        return $this;
    }

    // Required by UserInterface
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /** *
     * required by Symfony’s UserInterface
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->getRole() === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }
    public function eraseCredentials(): void
    {
        $this->plainPassword = null; //a safe thing to do
    }

    /**
     * The session stores the user: keep only what identifies it, never the relations, and
     * only a checksum of the password hash.
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password' => null === $this->password ? null : hash('crc32c', $this->password),
            'role' => $this->role,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->role = $data['role'];
        $this->createdAt = new \DateTimeImmutable();
        $this->projects = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    /**
     * Called on every request with the user freshly loaded from the database. A changed role or
     * password ends the session (401): stale rights never survive, the next login picks up the new ones.
     */
    public function isEqualTo(UserInterface $user): bool
    {
        return $user instanceof self
            && $this->id?->equals($user->id)
            && $this->role === $user->role
            && ($this->password === $user->password || $this->password === hash('crc32c', (string) $user->password));
    }
}
