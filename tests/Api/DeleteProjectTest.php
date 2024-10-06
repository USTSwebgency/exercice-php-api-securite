<?php

namespace App\Tests\Api;

use App\Entity\Project;
use App\Entity\Company;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Factory\CompanyFactory;
use App\Factory\ProjectFactory;
use App\Factory\UserCompanyRoleFactory;
use App\Enum\Role;
use Symfony\Component\HttpFoundation\Response;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
class DeleteProjectTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    private $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    private function login(User $user): string
    {
        $response = $this->client->request('POST', '/api/auth', [
            'json' => [
                'email' => $user->getEmail(),
                'password' => 'my_password',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($response->getContent(), true);

        return $data['token'];
    }
    public function testDeleteProject()
    {
        $company = CompanyFactory::createOne();
        $project = ProjectFactory::createOne(['company' => $company]);
        
        $admin = UserFactory::createOne();
        $manager = UserFactory::createOne();
        $consultant = UserFactory::createOne();
        $externalUser = UserFactory::createOne();
    
        UserCompanyRoleFactory::createOne(['user' => $admin, 'company' => $company, 'role' => Role::ADMIN]);
        UserCompanyRoleFactory::createOne(['user' => $manager, 'company' => $company, 'role' => Role::MANAGER]);
        UserCompanyRoleFactory::createOne(['user' => $consultant, 'company' => $company, 'role' => Role::CONSULTANT]);
    
        // L'admin peut supprimer le projet
        $jwtToken = $this->login($admin);
        $this->client->request('DELETE', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    
        // Vérifier que le projet a bien été supprimé
        $this->client->request('GET', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    
        // Recréer le projet pour le test de suppression par un manager
        $project = ProjectFactory::createOne(['company' => $company]);
        $jwtToken = $this->login($manager);
        $this->client->request('DELETE', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    
        // Recréer le projet pour le test de suppression par un consultant
        $project = ProjectFactory::createOne(['company' => $company]);
        $jwtToken = $this->login($consultant);
        $this->client->request('DELETE', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);  
        
        // Un utilisateur externe ne peut pas supprimer le projet
        $jwtToken = $this->login($externalUser);
        $this->client->request('DELETE', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        
    }
    
}
