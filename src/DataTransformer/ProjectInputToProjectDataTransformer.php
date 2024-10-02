<?php

namespace App\DataTransformer;

use App\Dto\ProjectInput;
use App\Entity\Project;
use App\Entity\Company;

/* Transformateur de données pour créer un projet à partir du Dto d'entrée ProjectInput */

class ProjectInputToProjectDataTransformer
{
    /* Transforme le Dto d'entrée en entité Project */
    
    public function transform(ProjectInput $input, Company $company): Project
    {
        $project = new Project($company);
        $project->setTitle($input->title);
        $project->setDescription($input->description);
    
        return $project;
    }
}
