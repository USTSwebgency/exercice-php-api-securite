<?php

/* Dto qui permet de limiter les infos renvoyées pour 
la liste des entreprises d'un user */

namespace App\Dto;

use App\Entity\Company;

class CompanyListOutput
{
    public int $id;
    public string $name;


    public static function createFromEntity(Company $company): self
    {
        $output = new self();
        $output->id = $company->getId();
        $output->name = $company->getName();

        return $output; 
    }
}

