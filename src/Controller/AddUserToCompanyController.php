<?php
namespace App\Controller;

use App\Dto\AddUserToCompanyInput;
use App\Entity\Company;
use App\Entity\User;
use App\Enum\Role;
use App\DataTransformer\AddUserToCompanyDataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\UserTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddUserToCompanyController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private AddUserToCompanyDataTransformer $dataTransformer;
    private UserTokenService $userTokenService;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        AddUserToCompanyDataTransformer $dataTransformer,
        UserTokenService $userTokenService 
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->dataTransformer = $dataTransformer;
        $this->userTokenService = $userTokenService; 
    }

    public function __invoke(int $companyId, int $userId, Request $request, ValidatorInterface $validator): Response
    {
      
        $userConnected = $this->userTokenService->getConnectedUser();

        $company = $this->entityManager->getRepository(Company::class)->find($companyId);
        if (!$company) {
            throw new NotFoundHttpException('Société non trouvée.');
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

       
        if (!$company->isUserInCompany($userConnected)) {
            throw new AccessDeniedHttpException("Vous n'êtes pas membre de cette société.");
        }

     
        if (!$this->isGranted('add_user_to_company', $company)) {
            throw new AccessDeniedException("Vous n'avez pas l'autorisation d'ajouter des utilisateurs à cette société.");
        }


        if ($company->isUserInCompany($user)) {
            throw new BadRequestHttpException('Cet utilisateur est déjà membre de cette société.');
        }

        // Désérialiser l'input pour obtenir le rôle
        $input = $this->serializer->deserialize($request->getContent(), AddUserToCompanyInput::class, 'json');

        $errors = $validator->validate($input);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new BadRequestHttpException(implode(', ', $errorMessages));
        }

        // Utiliser le DataTransformer pour transformer l'input en UserCompanyRole
        $userCompanyRole = $this->dataTransformer->transform($input, $user, $company);

     
        $company->addUserCompanyRole($userCompanyRole);

  
        $this->entityManager->persist($userCompanyRole);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur ajouté à la société avec succès.'], Response::HTTP_CREATED);
    }
}
