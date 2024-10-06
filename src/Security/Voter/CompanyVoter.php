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
        return in_array($attribute, [self::VIEW, self::ADD_USER]) && $subject instanceof Company;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false; 
        }

        switch ($attribute) {
            case self::VIEW:
                return $subject->isUserInCompany($user);

            case self::ADD_USER:
                return $this->canAddUser($user, $subject);
        }

        return false; 
    }

    private function canAddUser(User $user, Company $company): bool
    {
        $roleInCompany = $user->getRoleForCompany($company);
        return $roleInCompany === Role::ADMIN;
    }
}