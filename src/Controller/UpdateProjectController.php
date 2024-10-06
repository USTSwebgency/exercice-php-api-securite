<?php


namespace App\Controller;

use App\Entity\Project;
use App\Dto\ProjectInput;
use App\Repository\CompanyRepository;
use App\DataTransformer\ProjectInputToProjectUpdateDataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Service\UserTokenService;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;



class UpdateProjectController extends AbstractController
{
    private UserTokenService $userTokenService;
    private CompanyRepository $companyRepository;
    private EntityManagerInterface $entityManager;
    private ProjectInputToProjectUpdateDataTransformer $transformer;
    private AuthorizationCheckerInterface $authChecker;

    public function __construct(
        UserTokenService $userTokenService,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager,
        ProjectInputToProjectUpdateDataTransformer $transformer,
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
        $projectId = $request->attributes->get('id');

        // Vérification des paramètres requis
        if (!$companyId) {
            throw new BadRequestHttpException("L'identifiant de la société est manquant.");
        }
        if (!$projectId) {
            throw new BadRequestHttpException("L'identifiant du projet est manquant.");
        }

        $company = $this->companyRepository->find($companyId);
        if (!$company->isUserInCompany($user)) {
            throw new AccessDeniedHttpException("Vous n'êtes pas membre de cette société.");
        }


        $project = $this->entityManager->getRepository(Project::class)->find($projectId);
        if (!$project) {
            throw new NotFoundHttpException("Projet non trouvé.");
        }


        if (!$this->authChecker->isGranted('edit_project', $project)) {
            throw new AccessDeniedException("Vous n'êtes pas autorisé à modifier ce projet.");
        }

        
        $input = $request->toArray();
        $projectInput = new ProjectInput(
            $input['title'] ?? null,
            $input['description'] ?? null
        ); 
        
        $errors = $validator->validate($projectInput);

        // Si des erreurs sont détectées, les renvoyer dans la réponse
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new BadRequestHttpException(implode(', ', $errorMessages));
        }

        // Vérifier si un projet avec le même titre existe déjà pour cette société
        $existingProject = $this->entityManager->getRepository(Project::class)
            ->findOneBy(['title' => $input['title'], 'company' => $company]);
    
        if ($existingProject) {
            throw new BadRequestHttpException("Un projet avec ce titre existe déjà pour votre société.");
        }

   
      $updatedProject = $this->transformer->transform($projectInput, $project);

      $this->entityManager->flush();

      return new JsonResponse([
          'id' => $updatedProject->getId(),
          'title' => $updatedProject->getTitle(),
          'description' => $updatedProject->getDescription(),
          'message' => 'Projet mis à jour avec succès.'
      ], JsonResponse::HTTP_OK);
  }

}