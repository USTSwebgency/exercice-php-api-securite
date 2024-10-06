<?php

namespace App\Controller;

use App\Dto\CompanyOutput;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\UserTokenService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

        $user = $this->userTokenService->getConnectedUser();

        $company = $this->companyRepository->findOneByUserAndCompanyId($user, $id);

        // Vérifier si l'utilisateur a le droit de voir les détails de la société
       /* if (!$this->isGranted('view_company', $company)) {
            throw new AccessDeniedHttpException("Vous n'avez pas les droits pour voir les détails de cette société.");
        }*/

        $output = CompanyOutput::createFromEntity($company);

        return $this->json($output);
    }
}
