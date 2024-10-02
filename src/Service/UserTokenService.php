<?php

namespace App\Service;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserTokenService
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getConnectedUser(): ?UserInterface
    {
        // Récupère le token JWT
        $token = $this->tokenStorage->getToken();

        // Vérifie si le token existe et si l'utilisateur est connecté
        if (null === $token || !$token->getUser() instanceof UserInterface) {
            return null; 
        }

        // Récupère l'utilisateur à partir du token
        return $token->getUser();
    }
}
