<?php

// DTO pour recevoir les données lors de la création ou de la modification d'un projet

namespace App\Dto;

use App\Entity\Project;

class ProjectInput
{
    public string $title;
    public string $description;

    public function __construct(string $title, string $description)
    {
        $this->title = $title;
        $this->description = $description;
    }
}
