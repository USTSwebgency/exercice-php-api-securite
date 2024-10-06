<?php


namespace App\Service;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UserTokenService
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getConnectedUser(): UserInterface
    {

        $token = $this->tokenStorage->getToken();

        if (null === $token || !$token->getUser() instanceof UserInterface) {
            throw new UnauthorizedHttpException('Bearer', 'Utilisateur non connecté ou token invalide');
        }

        return $token->getUser();
    }

}
