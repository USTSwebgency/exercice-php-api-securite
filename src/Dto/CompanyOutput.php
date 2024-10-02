<?php

/* Dto qui nous permet de structurer les données renvoyées concernant
une société spécifique d'un utilisateur */

namespace App\Dto;

use App\Entity\Company;

class CompanyOutput
{
    public int $id;
    public string $name;
    public string $address;

    public static function createFromEntity(Company $company): self
    {
        $output = new self();
        $output->id = $company->getId();
        $output->name = $company->getName();
        $output->address = $company->getAddress();

        return $output;
    }
}
