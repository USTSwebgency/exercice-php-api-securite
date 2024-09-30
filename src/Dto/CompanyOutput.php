<?php


/* Création d'un Dto pour représenter les données spécifique d'une entreprise à notre façon 

*/

namespace App\Dto;

use App\Entity\Company;

class CompanyOutput
{
    // Propriétés publiques pour stocker les informations de l'entreprise
    public int $id;
    public string $name;
    public string $address;

    /* Méthode statique nous permettant de créer une instance du Dto à partir de notre class
    company
    */
    public static function createFromEntity(Company $company): self
    {
        $output = new self();
        $output->id = $company->getId();
        $output->name = $company->getName();
        $output->address = $company->getAddress();

        return $output; // Retourne l'instance de CompanyOutput.
    }
}
