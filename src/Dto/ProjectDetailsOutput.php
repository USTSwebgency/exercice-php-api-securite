<?php

// DTO pour renvoyer les détails d'un projet spécifique

namespace App\Dto;

use App\Entity\Project;

class ProjectDetailsOutput
{
    public int $id;           
    public string $title;
    public string $description;
    public \DateTimeImmutable $createdAt;
    public string $companyName;

    public static function createFromEntity(Project $project): self
    {
        $output = new self();
        $output->id = $project->getId();  
        $output->title = $project->getTitle();
        $output->description = $project->getDescription();
        $output->createdAt = $project->getCreatedAt();
        $output->companyName = $project->getCompany()->getName(); 

        return $output;
    }
}
