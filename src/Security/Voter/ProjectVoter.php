<?php


namespace App\Security\Voter;

/* Votant pour gérer les permissions concernant les opérations sur les projets */

use App\Entity\Company;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class ProjectVoter extends Voter
{
    public const EDIT = 'edit_project';
    public const VIEW = 'view_project';
    public const CREATE = 'create_project';
    public const DELETE = 'delete_project';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::CREATE, self::DELETE])
            && ($subject instanceof Project || $subject instanceof Company); 
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Vérification si l'utilisateur est bien un User
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Déterminer la société liée au projet ou directement la société
        if ($subject instanceof Project) {
            $company = $subject->getCompany();
        } elseif ($subject instanceof Company) {
            $company = $subject;
        } else {
            return false;
        }

        // Vérification si l'utilisateur fait partie de la société
        if (!$company->isUserInCompany($user)) {
            return false;
        }

        // Vérification des permissions spécifiques à l'action demandée
        switch ($attribute) {
            case self::VIEW:
                return $this->canView($user);
            case self::CREATE:
                return $this->canCreate($user, $company);
            case self::EDIT:
                return $this->canEdit($user, $company);
            case self::DELETE:
                return $this->canDelete($user, $company);
        }

        return false;
    }

    // Autorisation de visualisation (VIEW) : tous les utilisateurs dans la société peuvent voir les projets
    private function canView(UserInterface $user): bool
    {
        return true;
    }

    // Autorisation de création (CREATE) : seuls les admins et managers peuvent créer des projets
    private function canCreate(UserInterface $user, Company $company): bool
    {
        $roleInCompany = $user->getRoleForCompany($company);
        return in_array($roleInCompany->value, [Role::ADMIN->value, Role::MANAGER->value], true);
    }

    // Autorisation d'édition (EDIT) : seuls les admins et managers peuvent éditer des projets
    private function canEdit(UserInterface $user, Company $company): bool
    {
        $roleInCompany = $user->getRoleForCompany($company);
        return in_array($roleInCompany->value, [Role::ADMIN->value, Role::MANAGER->value], true);
    }

    // Autorisation de suppression (DELETE) : seuls les admins peuvent supprimer des projets
    private function canDelete(UserInterface $user, Company $company): bool
    {
        $roleInCompany = $user->getRoleForCompany($company);
        return $roleInCompany->value === Role::ADMIN->value;
    }
}
