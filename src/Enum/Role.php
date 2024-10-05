<?php

namespace App\Enum;


 // L'énumération Role définit les différents rôles d'utilisateur disponibles dans l'application.
 
enum Role: string
{
    case ADMIN = 'ROLE_ADMIN';
    case MANAGER = 'ROLE_MANAGER';
    case CONSULTANT = 'ROLE_CONSULTANT';

    // Retourne la liste de tous les rôles disponibles sous forme de tableau.
    public static function getAllRoles(): array
    {
        return [
            self::ADMIN,
            self::MANAGER,
            self::CONSULTANT,
        ];
    }
    // Vérifie si un rôle est valide
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::getAllRoles(), true);
    }

    // Convertit un rôle sous forme de chaîne en instance de Role.
    public static function fromString(string $role): ?Role
    {
        return self::tryFrom($role); 
    }

    /**
     * Retourne le rôle sous forme de chaîne pour l'API de Symfony.
     *
     * @return string
     */
    public function getSymfonyRole(): string
    {
        return $this->value;
    }
}
