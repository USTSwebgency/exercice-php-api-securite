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

class GetCompanyProjectDetailsTest extends ApiTestCase
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

    public function testGetCompanyProjectDetails()
    {
      
        $admin = UserFactory::createOne();
        $consultant = UserFactory::createOne();
        $manager = UserFactory::createOne();
        $externalUser = UserFactory::createOne();
    
        $company = CompanyFactory::createOne();
        $project = ProjectFactory::createOne(['company' => $company]);

        // Assigner les rôles
        UserCompanyRoleFactory::createOne(['user' => $admin, 'company' => $company, 'role' => Role::ADMIN]);
        UserCompanyRoleFactory::createOne(['user' => $manager, 'company' => $company, 'role' => Role::MANAGER]);
        UserCompanyRoleFactory::createOne(['user' => $consultant, 'company' => $company, 'role' => Role::CONSULTANT]);

        // L'admin peut voir les détails du projet
        $jwtToken = $this->login($admin);
        $this->client->request('GET', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($project->getTitle(), $responseData['title']);
        $this->assertEquals($project->getDescription(), $responseData['description']);

        // Manager peut également voir
        $jwtToken = $this->login($manager);
        $this->client->request('GET', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        
        // Consultant aussi
        $jwtToken = $this->login($consultant);
        $this->client->request('GET', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);


        $jwtToken = $this->login($externalUser);
        $this->client->request('GET', '/api/companies/' . $company->getId() . '/projects/' . $project->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
