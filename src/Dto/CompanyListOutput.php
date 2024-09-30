<?php

/* Création d'un Dto pour représenter la liste des sociétés d'un user à notre façon
 */

namespace App\Dto;

use App\Entity\Company;

class CompanyListOutput
{
    // Propriétés publiques pour stocker les informations de l'entreprise
    public int $id;
    public string $name;

    /* Méthode statique nous permettant de créer une instance du Dto à partir de notre class
    company
    */

    public static function createFromEntity(Company $company): self
    {
        $output = new self();
        $output->id = $company->getId();
        $output->name = $company->getName();

        return $output; // Retourne l'instance de CompanyListOutput.
    }
}

