<?php

// Dto pour recevoir le role lors de l'association d'un utilisateur à une entreprise

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

class AddUserToCompanyInput
{
    public string $role;
}


