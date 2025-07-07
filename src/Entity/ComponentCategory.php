<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ComponentCategoryRepository;

#[ORM\Entity(repositoryClass: ComponentCategoryRepository::class)]
class ComponentCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

}