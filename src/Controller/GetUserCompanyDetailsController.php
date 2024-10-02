<?php

namespace App\Controller;

use App\Dto\CompanyOutput;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Service\UserTokenService;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GetUserCompanyDetailsController extends AbstractController
{
    private UserTokenService $userTokenService;
    private CompanyRepository $companyRepository;

    public function __construct(UserTokenService $userTokenService, CompanyRepository $companyRepository)
    {
        $this->userTokenService = $userTokenService;
        $this->companyRepository = $companyRepository;
    }

    public function __invoke(int $id): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->userTokenService->getConnectedUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non connecté.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $company = $this->companyRepository->findOneByUserAndCompanyId($user, $id);

        if (!$company) {
            throw new NotFoundHttpException("Société non trouvée");
        }

        // Vérifier si l'utilisateur a le droit de voir les détails de la société
        if (!$this->isGranted('view_company', $company)) {
            throw new AccessDeniedException("Vous n'avez pas les droits pour voir les détails de cette société");
        }

        // Transformer l'entité en Dto et retourner la réponse
        $output = CompanyOutput::createFromEntity($company);

        return $this->json($output);
    }
}
