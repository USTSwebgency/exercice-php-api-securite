<?php

namespace App\Controller;

use App\Dto\AddUserToCompanyInput;
use App\Entity\Company;
use App\Entity\User;
use App\DataTransformer\AddUserToCompanyDataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\UserTokenService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    public function __invoke(int $companyId, int $userId, Request $request, ValidatorInterface $validator): Response
    {
        // Récupérer l'utilisateur connecté
        $token = $this->tokenStorage->getToken();
        $userConnected = $token ? $token->getUser() : null;
    
        if (!$userConnected instanceof User) {
            throw new AccessDeniedHttpException("Utilisateur non connecté.");
        }
    
        // Récupérer la société
        $company = $this->entityManager->getRepository(Company::class)->find($companyId);
        if (!$company) {
            throw new NotFoundHttpException('Société non trouvée.');
        }
    
        // Récupérer l'utilisateur qu'on veut ajouter par son id
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé.');
        }
    
        // Vérifier si l'utilisateur fait partie de la société
        if (!$company->isUserInCompany($userConnected)) {
            throw new AccessDeniedHttpException("Vous n'êtes pas membre de cette société.");
        }
    
        // Vérifier si l'utilisateur connecté a le droit d'ajouter un utilisateur à la société
        if (!$this->isGranted('add_user_to_company', $company)) {
            throw new AccessDeniedException("Vous n'avez pas l'autorisation d'ajouter des utilisateurs à cette société.");
        }   
        
        // Vérifier si l'utilisateur existe déjà dans la société
        if ($company->isUserInCompany($user)) {
            throw new BadRequestHttpException('Cet utilisateur est déjà membre de cette société.');
        }
    
        // Désérialiser l'input pour obtenir le rôle
        $input = $this->serializer->deserialize($request->getContent(), AddUserToCompanyInput::class, 'json');

        // Valider le DTO
        $errors = $validator->validate($input);

        // Si des erreurs sont détectées, les renvoyer dans la réponse
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new BadRequestHttpException(implode(', ', $errorMessages));
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
