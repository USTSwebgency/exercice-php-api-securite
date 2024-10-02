<?php

namespace App\DataTransformer;

use App\Dto\ProjectInput;
use App\Entity\Project;

/* Transformateur de données pour mettre à jour un projet existant à partir de ProjectInput */

class ProjectInputToProjectUpdateDataTransformer
{

    // Transforme le Dto en entité project

    public function transform(ProjectInput $input, Project $project): Project
    {
        // Met à jour les propriétés existantes de l'entité Project

        $project->setTitle($input->title);
        $project->setDescription($input->description);
    
        return $project;
    }
}
