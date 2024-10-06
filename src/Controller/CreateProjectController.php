<?php

namespace App\Controller;

use App\Dto\ProjectInput;
use App\Entity\Company;
use App\Entity\Project;
use App\Service\UserTokenService;
use App\Repository\CompanyRepository;
use App\DataTransformer\ProjectInputToProjectDataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateProjectController extends AbstractController
{
    private UserTokenService $userTokenService;
    private CompanyRepository $companyRepository;
    private EntityManagerInterface $entityManager;
    private ProjectInputToProjectDataTransformer $transformer;
    private AuthorizationCheckerInterface $authChecker;

    public function __construct(
        UserTokenService $userTokenService,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager,
        ProjectInputToProjectDataTransformer $transformer,
        AuthorizationCheckerInterface $authChecker
    ) {
        $this->userTokenService = $userTokenService;
        $this->companyRepository = $companyRepository;
        $this->entityManager = $entityManager;
        $this->transformer = $transformer;
        $this->authChecker = $authChecker;
    }

    public function __invoke(Request $request, ValidatorInterface $validator): JsonResponse
    {

        $user = $this->userTokenService->getConnectedUser();
        
      
        $companyId = $request->attributes->get('companyId');
        if (!$companyId) {
            throw new BadRequestHttpException("L'identifiant de la société est manquant.");
        }
    

        $company = $this->companyRepository->find($companyId);
        if (!$company) {
            throw new NotFoundHttpException('Société non trouvée.');
        }

        if (!$company->isUserInCompany($user)) {
            throw new AccessDeniedHttpException("Vous n'êtes pas membre de cette société.");
        }

        // Vérification des droits de création du projet
        if (!$this->authChecker->isGranted('create_project', $company)) {
            throw new AccessDeniedException("Vous n'êtes pas autorisé à créer un projet pour cette société.");
        }
        
        $data = json_decode($request->getContent(), true);
        $projectInput = new ProjectInput($data['title'], $data['description']);
        $errors = $validator->validate($projectInput);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new BadRequestHttpException(implode(', ', $errorMessages));
        }
        
        $existingProject = $this->entityManager->getRepository(Project::class)
            ->findOneBy(['title' => $data['title'], 'company' => $company]);
    
        if ($existingProject) {
            throw new BadRequestHttpException("Un projet avec ce titre existe déjà pour votre société.");
        }

        // Transformer les données d'entrée en entité Project
        $project = $this->transformer->transform($projectInput, $company);


        $company->addProject($project);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
    
        return new JsonResponse([
            'id' => $project->getId(),
            'title' => $project->getTitle(),
            'description' => $project->getDescription(),
            'company' => $company->getId(),
            'message' => 'Projet créé avec succès.'
        ], JsonResponse::HTTP_CREATED);
    }
    
}
