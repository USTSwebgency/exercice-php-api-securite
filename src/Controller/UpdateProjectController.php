<?php


namespace App\Controller;

use App\Entity\Project;
use App\Dto\ProjectInput;
use App\Repository\CompanyRepository;
use App\DataTransformer\ProjectInputToProjectUpdateDataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Service\UserTokenService;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UpdateProjectController extends AbstractController
{
    private UserTokenService $userTokenService;
    private CompanyRepository $companyRepository;
    private EntityManagerInterface $entityManager;
    private ProjectInputToProjectUpdateDataTransformer $transformer;
    private AuthorizationCheckerInterface $authChecker;

    public function __construct(
        UserTokenService $userTokenService,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager,
        ProjectInputToProjectUpdateDataTransformer $transformer,
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
        $user = $this->userTokenService->getConnectedUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non connecté.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $companyId = $request->attributes->get('companyId');
        $projectId = $request->attributes->get('id');

        // Vérification des paramètres requis
        if (!$companyId) {
            return new JsonResponse(['error' => 'L\'identifiant de la société est manquant.'], JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!$projectId) {
            return new JsonResponse(['error' => 'L\'identifiant du projet est manquant.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $project = $this->entityManager->getRepository(Project::class)->find($projectId);
        if (!$project) {
            return new JsonResponse(['error' => 'Projet non trouvé.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $company = $this->companyRepository->find($companyId);
        if (!$company || $project->getCompany() !== $company) {
            return new JsonResponse(['error' => 'Ce projet ne fait pas partie de votre société.'], JsonResponse::HTTP_FORBIDDEN);
        }

        if (!$this->authChecker->isGranted('edit_project', $project)) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à modifier ce projet.');
        }

        // Récupérer les données du corps de la requête
        $input = $request->toArray();
        $projectInput = new ProjectInput(
            $input['title'] ?? null,
            $input['description'] ?? null
        );

        // Vérification des données
        if (empty($projectInput->title) || empty($projectInput->description)) {
            return new JsonResponse(['error' => 'Titre et description sont obligatoires.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Appliquer les modifications au projet
        $updatedProject = $this->transformer->transform($projectInput, $project);

        // Sauvegarder les modifications
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'id' => $updatedProject->getId(),
            'title' => $updatedProject->getTitle(),
            'description' => $updatedProject->getDescription(),
            'message' => 'Projet mis à jour avec succès.'
        ], JsonResponse::HTTP_OK);
    }
}
