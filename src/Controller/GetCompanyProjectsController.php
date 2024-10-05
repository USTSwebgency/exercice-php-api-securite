<?php

namespace App\Controller;

use App\Entity\Company;
use App\Dto\ProjectListOutput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CompanyRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use App\Service\UserTokenService;


class GetCompanyProjectsController extends AbstractController
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

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->userTokenService->getConnectedUser();

        $companyId = $request->attributes->get('companyId');
        if (!$companyId) {
            throw new BadRequestHttpException("L'identifiant de la société est manquant.");
        }

        // Récupérer la société
        $company = $this->companyRepository->find($companyId);
        if (!$company) {
            throw new NotFoundHttpException("Société non trouvée.");
        }

        // Vérification des droits
        if (!$this->isGranted('view_project', $company)) {
            throw new AccessDeniedException("Vous n'avez pas les droits pour voir les projets de cette société.");
        }

        // Récupérer les projets de la société
        $projects = $company->getProjects()->toArray();

        if (empty($projects)) {
            return new JsonResponse(['message' => 'Aucun projet créé pour l\'instant.'], JsonResponse::HTTP_OK);
        }

        $output = array_map(fn($project) => ProjectListOutput::createFromEntity($project), $projects);

        return new JsonResponse($output);
    }
}
