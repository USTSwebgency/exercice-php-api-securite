<?php

namespace App\Security\Voter;

/* Voter pour gérer les permissions concernant l'ajout ou l'association d'un user dans une société 
et la visualiation des détails d'une société spécifique */

use App\Entity\Company;
use App\Entity\User;
use App\Enum\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class CompanyVoter extends Voter
{
    public const VIEW = 'view_company'; 
    public const ADD_USER = 'add_user_to_company';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::ADD_USER]) && $subject instanceof Company;
    }

  
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // Récupération de l'utilisateur à partir du token
        $user = $token->getUser();

        // Vérification si l'utilisateur est bien une instance de UserInterface
        if (!$user instanceof UserInterface) {
            return false; 
        }

        switch ($attribute) {
            case self::VIEW:
                // Vérifie si l'utilisateur est membre de la société
                return $subject->isUserInCompany($user);

            case self::ADD_USER:
                return $this->canAddUser($user, $subject);
        }

        return false; 
    }

    // Vérifie si l'utilisateur est admin et peut effectuer l'ajout
    private function canAddUser(UserInterface $user, Company $company): bool
    {
        $roleInCompany = $user->getRoleForCompany($company);
        return $roleInCompany && $roleInCompany->value === Role::ADMIN->value;
    }
}
