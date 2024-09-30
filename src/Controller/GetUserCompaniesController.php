<?php 
namespace App\Controller;

use App\Dto\CompanyListOutput;

use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\GetUserCompaniesController;
use App\Controller\GetUserCompanyDetailsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;



class GetUserCompaniesController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private CompanyRepository $companyRepository;

    public function __construct(TokenStorageInterface $tokenStorage, CompanyRepository $companyRepository)
    {
        $this->tokenStorage = $tokenStorage;
        $this->companyRepository = $companyRepository;
    }

    public function __invoke(): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->tokenStorage->getToken()->getUser();

        // Récupérer les sociétés de l'utilisateur via la relation UserCompanyRole
        $companies = $this->companyRepository->findCompaniesByUser($user);

        // Transformer chaque société en DTO pour le retour
        $output = array_map(fn($company) => CompanyListOutput::createFromEntity($company), $companies);

        return $this->json($output);
    }
}