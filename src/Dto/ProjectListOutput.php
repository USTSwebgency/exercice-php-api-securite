<?php

// Dto pour représenter la liste des projets d'une entreprise avec des infos limitées

namespace App\Dto;

use App\Entity\Project;

class ProjectListOutput
{
    public int $id;   
    public string $title;

    public static function createFromEntity(Project $project): self
    {
        $output = new self();
        $output->id = $project->getId();  
        $output->title = $project->getTitle();

        return $output;
    }
}
