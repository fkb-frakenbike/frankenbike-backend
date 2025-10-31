<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\Table(name: "profiles")]
class Profile
{
    #[Groups(['profile:read', 'user:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['profile:read'])]
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'profile')]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", unique: true, nullable: false, onDelete: "CASCADE")]
    private ?User $user= null;

    #[Groups(['profile:read', 'user:read'])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nickname = null;

    #[Groups(['profile:read', 'user:read'])]
    #[ORM\Column(name: "first_name", length: 255, nullable: true)]
    private ?string $firstName = null;

    #[Groups(['profile:read', 'user:read'])]
    #[ORM\Column(name: "last_name", length: 255, nullable: true)]
    private ?string $lastName = null;

    #[Groups(['profile:read', 'user:read'])]
    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTime $birthdate = null;

    #[Groups(['profile:read', 'user:read'])]
    #[ORM\Column(name: "photo_url", length: 255, nullable: true)]
    private ?string $photoUrl = null;

    #[Groups(['profile:read', 'user:read'])]
    #[ORM\Column(name: "created_at", type: "datetime_immutable", options: ['default' => "CURRENT_TIMESTAMP"])]
    private \DateTimeImmutable $createdAt;

    #[Groups(['profile:read', 'user:read'])]
    #[ORM\Column(name: "updated_at", type: "datetime", options: ['default' => "CURRENT_TIMESTAMP"])]
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
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

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getBirthdate(): ?\DateTime
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTime $birthdate): static
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->photoUrl = $photoUrl;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}