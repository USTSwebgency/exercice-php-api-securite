<?php 
namespace App\Controller;

use App\Dto\CompanyListOutput;

use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\GetUserCompaniesController;
use App\Controller\GetUserCompanyDetailsController;
use App\Service\UserTokenService;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;


class GetUserCompaniesController extends AbstractController
{
    private UserTokenService $userTokenService;
    private CompanyRepository $companyRepository;

    public function __construct(UserTokenService $userTokenService, CompanyRepository $companyRepository)
    {
        $this->userTokenService = $userTokenService;
        $this->companyRepository = $companyRepository;
    }

    public function __invoke(): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->userTokenService->getConnectedUser();
        
        // Récupérer les sociétés de l'utilisateur
        $companies = $this->companyRepository->findCompaniesByUser($user);

        if (empty($companies)) {
            return new JsonResponse(['message' => 'Pour le moment vous n\'etes affilié à aucune société'], JsonResponse::HTTP_NO_CONTENT);
        }

        // Transformer chaque société en DTO pour le retour
        $output = array_map(fn($company) => CompanyListOutput::createFromEntity($company), $companies);

        return $this->json($output);
    }
} 