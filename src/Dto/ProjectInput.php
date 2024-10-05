<?php

// DTO pour recevoir les données lors de la création ou de la modification d'un projet

namespace App\Dto;


use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Project;

class ProjectInput
{

    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    public string $title;


    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    public string $description;

    public function __construct(string $title, string $description)
    {
        $this->title = $title;
        $this->description = $description;
    }
}
