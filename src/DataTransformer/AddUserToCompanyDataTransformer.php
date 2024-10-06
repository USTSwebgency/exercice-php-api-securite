<?php

namespace App\DataTransformer;

use App\Entity\UserCompanyRole;
use App\Entity\User;
use App\Entity\Company;
use App\Dto\AddUserToCompanyInput;
use App\Enum\Role;


/* Transformateur de données pour ajouter un utilisateur à une entreprise à partir
du Dto d'entrée AddUserToCompanyInput */

class AddUserToCompanyDataTransformer
{
    
    /* Transforme le Dto en entité UserCompanyRole */

    public function transform(AddUserToCompanyInput $input, User $user, Company $company): UserCompanyRole
    {

        $userCompanyRole = new UserCompanyRole();    
        $userCompanyRole->setUser($user);
        $userCompanyRole->setCompany($company);
        $role = Role::fromString($input->role);
        if ($role === null) {
            throw new \InvalidArgumentException("Le rôle spécifié est invalide.");
        }
        $userCompanyRole->setRole($role);
        return $userCompanyRole; 
    }
}
