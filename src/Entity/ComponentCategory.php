<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ComponentCategoryRepository;

#[ORM\Entity(repositoryClass: ComponentCategoryRepository::class)]
class ComponentCategory
{

}