<?php

namespace App\Controller;

use App\Dto\AddUserToCompanyInput;
use App\Entity\Company;
use App\Entity\User;
use App\Enum\Role;
use App\Security\Voter\CompanyVoter;
use App\DataTransformer\AddUserToCompanyDataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AddUserToCompanyController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private AddUserToCompanyDataTransformer $dataTransformer;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        AddUserToCompanyDataTransformer $dataTransformer
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->dataTransformer = $dataTransformer;
    }

    public function __invoke(int $companyId, int $userId, Request $request): Response
    {
        // Récupérer l'utilisateur connecté
        $token = $this->tokenStorage->getToken();

        if (!$token || !$token->getUser() instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non connecté.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Récupérer la société
        $company = $this->entityManager->getRepository(Company::class)->find($companyId);
        if (!$company) {
            return new Response('Société non trouvée.', Response::HTTP_NOT_FOUND);
        }

        // Récupérer l'utilisateur qu'on veut ajouter par son id
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return new Response('Utilisateur non trouvé.', Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur fait partie de la société
        if (!$company->isUserInCompany($token->getUser())) {
            return new JsonResponse(['error' => 'Vous n\'êtes pas membre de cette société.'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Vérifier si l'utilisateur connecté a le droit d'ajouter un utilisateur à la société
        if (!$this->isGranted('add_user_to_company', $company)) {
            throw new AccessDeniedException('Vous n\'avez pas les droits pour ajouter un utilisateur à cette société.');
        }   
        
        // Vérifier si l'utilisateur existe déjà dans la société
        if ($company->isUserInCompany($user)) {
            return new JsonResponse(['error' => 'Cet utilisateur est déjà associé à cette société.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Désérialiser l'input pour obtenir le rôle
        try {
            $input = $this->serializer->deserialize($request->getContent(), AddUserToCompanyInput::class, 'json');    
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la désérialisation : ' . $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        // Vérifier si le rôle est valide
        if (!Role::isValidRole($input->role)) {
            return new JsonResponse(['error' => 'Rôle invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Utiliser le DataTransformer pour transformer l'input en UserCompanyRole
        $userCompanyRole = $this->dataTransformer->transform($input, $user, $company);

        // Ajouter l'utilisateur à l'entreprise en utilisant la méthode addUserCompanyRole
        $company->addUserCompanyRole($userCompanyRole);

        // Persist et flush
        $this->entityManager->persist($userCompanyRole);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur ajouté à la société avec succès.'], Response::HTTP_CREATED);
    }
}
