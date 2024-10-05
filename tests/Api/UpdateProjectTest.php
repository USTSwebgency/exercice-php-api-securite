<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Company;
use App\Factory\UserFactory;
use App\Factory\CompanyFactory;
use App\Factory\ProjectFactory;
use App\Factory\UserCompanyRoleFactory;
use App\Enum\Role;
use Symfony\Component\HttpFoundation\Response;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UpdateProjectTest extends ApiTestCase
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

    public function testUpdateProject()
    {
        // Créer des utilisateurs
        $admin = UserFactory::createOne();
        $manager = UserFactory::createOne();
        $consultant = UserFactory::createOne();
        $externalUser = UserFactory::createOne();

        // Créer une société et un projet
        $company = CompanyFactory::createOne();
        $project = ProjectFactory::createOne(['company' => $company]);

        // Assigner les rôles
        UserCompanyRoleFactory::createOne(['user' => $admin, 'company' => $company, 'role' => Role::ADMIN]);
        UserCompanyRoleFactory::createOne(['user' => $manager, 'company' => $company, 'role' => Role::MANAGER]);
        UserCompanyRoleFactory::createOne(['user' => $consultant, 'company' => $company, 'role' => Role::CONSULTANT]);

        // L'admin peut modifier le projet
        $jwtToken = $this->login($admin);
        $this->client->request('PUT', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
            'json' => [
                'title' => 'Nouveau Titre',
                'description' => 'Nouvelle Description',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Nouveau Titre', $responseData['title']);
        $this->assertEquals('Nouvelle Description', $responseData['description']);   
        
        // Le manager peut aussi modifier le projet
        $jwtToken = $this->login($manager);
        $this->client->request('PUT', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
            'json' => [
                'title' => 'Titre Modifié par le Manager',
                'description' => 'Description Modifiée par le Manager',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Titre Modifié par le Manager', $responseData['title']);
        $this->assertEquals('Description Modifiée par le Manager', $responseData['description']);

        

        // Un consultant ne peut pas modifier le projet (403 Forbidden)
        $jwtToken = $this->login($consultant);
        $this->client->request('PUT', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
            'json' => [
                'title' => 'Consultant Titre',
                'description' => 'Consultant Description',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);  
        
        
        // Un utilisateur externe ne peut pas modifier le projet
        $jwtToken = $this->login($externalUser);
        $this->client->request('PUT', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
            'json' => [
                'title' => 'Consultant Titre',
                'description' => 'Consultant Description',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        
    }
}
