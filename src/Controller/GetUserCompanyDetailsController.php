<?php

namespace App\Controller;

use App\Dto\CompanyOutput;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class GetUserCompanyDetailsController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private CompanyRepository $companyRepository;

    public function __construct(TokenStorageInterface $tokenStorage, CompanyRepository $companyRepository)
    {
        $this->tokenStorage = $tokenStorage;
        $this->companyRepository = $companyRepository;
    }

    public function __invoke(int $id): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->tokenStorage->getToken()->getUser();

        // Utiliser la méthode du repository pour récupérer la société si l'utilisateur y est associé
        $company = $this->companyRepository->findOneByUserAndCompanyId($user, $id);

        // Si la société n'existe pas ou si l'utilisateur n'est pas associer lever une exception 404
        if (!$company) {
            throw new NotFoundHttpException("Société non trouvée ou l'utilisateur n'est pas dans cette société.");
        }

        // Transformer l'entité en DTO et retourner la réponse
        $output = CompanyOutput::createFromEntity($company);

        return $this->json($output);
    }
}
