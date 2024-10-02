<?php
namespace App\Controller;

use App\Entity\Project;
use App\Security\Voter\ProjectVoter;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\UserTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DeleteProjectController extends AbstractController
{
    private UserTokenService $userTokenService;
    private CompanyRepository $companyRepository;
    private EntityManagerInterface $entityManager;
    private AuthorizationCheckerInterface $authChecker;

    public function __construct(
        UserTokenService $userTokenService,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authChecker
    ) {
        $this->userTokenService = $userTokenService;
        $this->companyRepository = $companyRepository;
        $this->entityManager = $entityManager;
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

        // Récupérer le projet et vérifier son existence
        $project = $this->entityManager->getRepository(Project::class)->find($projectId);
        if (!$project) {
            return new JsonResponse(['error' => 'Projet non trouvé.'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier si la société existe et si le projet lui appartient
        $company = $this->companyRepository->find($companyId);
        if (!$company || $project->getCompany() !== $company) {
            return new JsonResponse(['error' => 'Ce projet ne fait pas partie de votre société.'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Vérification des droits de suppression 
        if (!$this->authChecker->isGranted('delete_project', $project)) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce projet.');
        }

        // Supprimer le projet
        try {
            $this->entityManager->remove($project);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['message' => 'Projet supprimé avec succès.'], JsonResponse::HTTP_NO_CONTENT);
    }
}
