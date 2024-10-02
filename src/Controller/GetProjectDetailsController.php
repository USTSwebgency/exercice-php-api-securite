<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Project;
use App\Dto\ProjectDetailsOutput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CompanyRepository;
use App\Service\UserTokenService;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GetProjectDetailsController extends AbstractController
{
    private UserTokenService $userTokenService;
    private CompanyRepository $companyRepository;

    public function __construct(
        UserTokenService $userTokenService,
        CompanyRepository $companyRepository
    ) {
        $this->userTokenService = $userTokenService;
        $this->companyRepository = $companyRepository;
    }

    public function __invoke(Request $request, $companyId, $id): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->userTokenService->getConnectedUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non connecté.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Vérifier que l'id de la société est valide
        $company = $this->companyRepository->find($companyId);
        if (!$company) {
            return new JsonResponse(['error' => 'Société non trouvée.'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérification des permissions 
        if (!$this->isGranted('view_project', $company)) {
            throw new AccessDeniedException('Vous n\'avez pas les droits pour voir les projets de cette société.');
        }

        // Récupérer le projet spécifique dans la liste des projets de la société
        $project = $company->getProjects()->filter(fn(Project $p) => $p->getId() === (int)$id)->first();

        if (!$project) {
            return new JsonResponse(['error' => 'Projet non trouvé pour votre société.'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérification des permissions pour visualiser ce projet
        if (!$this->isGranted('view_project', $project)) {
            throw new AccessDeniedException('Vous n\'avez pas les droits pour voir les détails de ce projet.');
        }

        // Créer l'objet de sortie avec les détails du projet
        $output = ProjectDetailsOutput::createFromEntity($project);
        return new JsonResponse($output);
    }
}
