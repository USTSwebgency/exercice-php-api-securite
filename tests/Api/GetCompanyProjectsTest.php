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

class GetCompanyProjectsTest extends ApiTestCase
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

    public function testGetCompanyProjects()
    {
        // Créer des utilisateurs
        $admin = UserFactory::createOne();
        $manager = UserFactory::createOne();
        $consultant = UserFactory::createOne();
        $externalUser = UserFactory::createOne();

        // Créer une société et des projets
        $company = CompanyFactory::createOne();
        $project1 = ProjectFactory::createOne(['company' => $company]);
        $project2 = ProjectFactory::createOne(['company' => $company]);

        // Assigner les rôles
        UserCompanyRoleFactory::createOne(['user' => $admin, 'company' => $company, 'role' => Role::ADMIN]);
        UserCompanyRoleFactory::createOne(['user' => $manager, 'company' => $company, 'role' => Role::MANAGER]);
        UserCompanyRoleFactory::createOne(['user' => $consultant, 'company' => $company, 'role' => Role::CONSULTANT]);

        // L'admin peut voir la liste des projets de la société
        $jwtToken = $this->login($admin);
        $this->client->request('GET', '/api/companies/' . $company->getId() . '/projects', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $responseData);

        // Un consultant peut également voir la liste des projets
        $jwtToken = $this->login($consultant);
        $this->client->request('GET', '/api/companies/' . $company->getId() . '/projects', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Un consultant peut également voir la liste des projets
        $jwtToken = $this->login($manager);
        $this->client->request('GET', '/api/companies/' . $company->getId() . '/projects', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);   
        
        // Un utilisateur qui ne fait pas partie de la société ne peut pas voir les projets (403 Forbidden)
        $jwtToken = $this->login($externalUser);
        $this->client->request('GET', '/api/companies/' . $company->getId() . '/projects', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
