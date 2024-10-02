<?php

namespace App\Controller;

use App\Dto\ProjectInput;
use App\Entity\Company;
use App\Entity\Project;
use App\Service\UserTokenService;
use App\Repository\CompanyRepository;
use App\DataTransformer\ProjectInputToProjectDataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


class CreateProjectController extends AbstractController
{
    private UserTokenService $userTokenService;
    private CompanyRepository $companyRepository;
    private EntityManagerInterface $entityManager;
    private ProjectInputToProjectDataTransformer $transformer;
    private AuthorizationCheckerInterface $authChecker;

    public function __construct(
        UserTokenService $userTokenService,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager,
        ProjectInputToProjectDataTransformer $transformer,
        AuthorizationCheckerInterface $authChecker
    ) {
        $this->userTokenService = $userTokenService;
        $this->companyRepository = $companyRepository;
        $this->entityManager = $entityManager;
        $this->transformer = $transformer;
        $this->authChecker = $authChecker;
    }

    public function __invoke(Request $request): JsonResponse
    {
        // Récupérer l'utilisateur qui s'authentifie
        $user = $this->userTokenService->getConnectedUser();
    
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non connecté.'], JsonResponse::HTTP_UNAUTHORIZED);
        }
    
        // Récupérer l'id de la société depuis la requête
        $companyId = $request->attributes->get('companyId');

        if (!$companyId) {
            return new JsonResponse(['error' => 'L\'identifiant de la société est manquant.'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Récupérer la société depuis le repository
        $company = $this->companyRepository->find($companyId);


        if (!$company instanceof Company) {
            return new JsonResponse(['error' => 'Société non trouvée.'], JsonResponse::HTTP_NOT_FOUND);
        }
    
        // Verification des droits de création
        if (!$this->authChecker->isGranted('create_project', $company)) {
            return new JsonResponse(['error' => 'Vous n\'êtes pas autorisé à créer un projet pour cette société.'], JsonResponse::HTTP_FORBIDDEN);
        }
        
        // Récupérer et valider les données de la requête
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['title'], $data['description'])) {
            return new JsonResponse(['error' => 'Le titre et la description sont obligatoires.'], JsonResponse::HTTP_BAD_REQUEST);
        }    
        
        // Vérifier si un projet avec le même titre existe déjà pour cette société
        $existingProject = $this->entityManager->getRepository(Project::class)
            ->findOneBy(['title' => $data['title'], 'company' => $company]);
    
        if ($existingProject) {
            return new JsonResponse(['error' => 'Un projet avec ce titre existe déjà pour votre société.'], JsonResponse::HTTP_CONFLICT);
        }
        
        // Créer une instance de ProjectInput avec les données de la requête
        $projectInput = new ProjectInput($data['title'], $data['description']);
    
        // Transformer les données d'entrée en entité Project
        $project = $this->transformer->transform($projectInput, $company);
    
        // Ajouter le projet à la société et le persister
        $company->addProject($project);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
    
        return new JsonResponse([
            'id' => $project->getId(),
            'title' => $project->getTitle(),
            'description' => $project->getDescription(),
            'message' => 'Projet créé avec succès.'
        ], JsonResponse::HTTP_CREATED);
    }
    
}
