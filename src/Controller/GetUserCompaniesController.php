<?php 
namespace App\Controller;

use App\Dto\CompanyListOutput;

use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\GetUserCompaniesController;
use App\Controller\GetUserCompanyDetailsController;
use App\Service\UserTokenService;



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

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non connecté.'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        
        // Récupérer les sociétés de l'utilisateur
        $companies = $this->companyRepository->findCompaniesByUser($user);

        if (empty($companies)) {
            return new JsonResponse(['message' => 'Pour le moment vous n\'etes affilié à aucune société'], JsonResponse::HTTP_OK);
        }

        // Transformer chaque société en Dto pour le retour
        $output = array_map(fn($company) => CompanyListOutput::createFromEntity($company), $companies);

        return $this->json($output);
    }
}