<?php
namespace App\Security\Voter;

/* Voter pour gérer les permissions concernant l'ajout ou l'association d'un user dans une société 
et la visualisation des détails d'une société spécifique */

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
        // Vérifie si l'attribut est bien pris en charge et que le sujet est une instance de Company
        return in_array($attribute, [self::VIEW, self::ADD_USER]) && $subject instanceof Company;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // Récupération de l'utilisateur à partir du token
        $user = $token->getUser();

        // Vérification si l'utilisateur est bien une instance de User (pas seulement UserInterface)
        if (!$user instanceof User) {
            return false; 
        }

        switch ($attribute) {
            case self::VIEW:
                // Vérifie si l'utilisateur est membre de la société
                return $subject->isUserInCompany($user);

            case self::ADD_USER:
                // Vérifie si l'utilisateur a les droits d'ajouter un autre utilisateur à la société
                return $this->canAddUser($user, $subject);
        }

        return false; 
    }

    // Vérifie si l'utilisateur est admin et peut effectuer l'ajout
    private function canAddUser(User $user, Company $company): bool
    {
        $roleInCompany = $user->getRoleForCompany($company);
        return $roleInCompany === Role::ADMIN;
    }
}
