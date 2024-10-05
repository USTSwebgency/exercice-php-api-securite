<?php

namespace App\Dto;
// Dto pour recevoir le role lors de l'association d'un utilisateur à une entreprise

use Symfony\Component\Validator\Constraints as Assert;


class AddUserToCompanyInput
{
    #[Assert\NotBlank(message: "Le rôle est obligatoire")]
    #[Assert\Choice(
        choices: ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_CONSULTANT'],
        message: "Le rôle choisi n'est pas valide."
    )]
    public string $role;
}


