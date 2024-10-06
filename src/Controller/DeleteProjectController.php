<?php
namespace App\Controller;

use App\Entity\Project;
use App\Security\Voter\ProjectVoter;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\UserTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class DeleteProjectController extends AbstractController
{
    private UserTokenService $userTokenService;
    private CompanyRepository $companyRepository;
    private EntityManagerInterface $entityManager;
    private AuthorizationCheckerInterface $authChecker;

    public function __construct(
        UserTokenService $userTokenService,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authChecker
    ) {
        $this->userTokenService = $userTokenService;
        $this->companyRepository = $companyRepository;
        $this->entityManager = $entityManager;
        $this->authChecker = $authChecker;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->userTokenService->getConnectedUser();
        
        $companyId = $request->attributes->get('companyId');
        $projectId = $request->attributes->get('id');

        if (!$companyId || !$projectId) {
            throw new BadRequestHttpException("L'identifiant de la société ou du projet est manquant.");
        }

        $company = $this->companyRepository->find($companyId);
        if (!$company->isUserInCompany($user)) {
            throw new AccessDeniedHttpException("Vous n'êtes pas membre de cette société.");
        }

        $project = $this->entityManager->getRepository(Project::class)->find($projectId);
        if (!$project) {
            throw new NotFoundHttpException('Projet non trouvé.');
        }
        
        if (!$this->authChecker->isGranted('delete_project', $project)) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce projet.');
        }        
        
        $this->entityManager->remove($project);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Projet supprimé avec succès.'], JsonResponse::HTTP_NO_CONTENT);
    }
}
