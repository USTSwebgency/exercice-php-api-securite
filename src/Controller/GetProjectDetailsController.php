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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;


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
        $user = $this->userTokenService->getConnectedUser();

        $company = $this->companyRepository->find($companyId);
        if (!$company) {
            throw new NotFoundHttpException("Société non trouvée.");
        }

        if (!$company->isUserInCompany($user)) {
            throw new AccessDeniedHttpException("Vous n'êtes pas membre de cette société.");
        }

        // Vérification des droits
        if (!$this->isGranted('view_project', $company)) {
            throw new AccessDeniedException("Vous n'avez pas les droits pour voir les projets de cette société.");
        }

        // Récupérer le projet spécifique
        $project = $company->getProjects()->filter(fn(Project $p) => $p->getId() === (int)$id)->first();
        if (!$project) {
            throw new NotFoundHttpException("Projet non trouvé.");
        }

        if (!$this->isGranted('view_project', $project)) {
            throw new AccessDeniedException("Vous n'avez pas les droits pour voir les détails de ce projet.");
        }

        $output = ProjectDetailsOutput::createFromEntity($project);
        return new JsonResponse($output);
    }
}
