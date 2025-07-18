<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[ORM\Table(name: "images")]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column(name: "created_at", type: "datetime_immutable", options: ['default' => "CURRENT_TIMESTAMP"])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: "datetime")]
    private ?\DateTime $uploaded_at = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->uploaded_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }
}
