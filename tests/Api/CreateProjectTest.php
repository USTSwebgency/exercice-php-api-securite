<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Entity\Company;
use App\Entity\Project;
use App\Enum\Role;
use App\Repository\UserRepository;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\UserFactory;
use App\Factory\UserCompanyRoleFactory;
use App\Factory\CompanyFactory;
use App\Factory\ProjectFactory;
use Symfony\Component\HttpFoundation\Response;

class CreateProjectTest extends ApiTestCase
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

    public function testCreateProject()
    {
        
        $admin = UserFactory::createOne();
        $manager = UserFactory::createOne();
        $consultant = UserFactory::createOne();
        $company = CompanyFactory::createOne();
        $project = ProjectFactory::createOne(['company' => $company ]);

        UserCompanyRoleFactory::createOne(['user' => $admin, 'company' => $company, 'role' => Role::ADMIN]);
        UserCompanyRoleFactory::createOne(['user' => $manager, 'company' => $company, 'role' => Role::MANAGER]);
        UserCompanyRoleFactory::createOne(['user' => $consultant, 'company' => $company, 'role' => Role::CONSULTANT]);

        $projectData1 = [
            'title' => 'Admin Project',
            'description' => 'Ceci est un nouveau projet.'
        ];

        $projectData2 = [
            'title' => 'Manager Project',
            'description' => 'Ceci est un nouveau projet.'
        ];

        $projectData3 = [
            'title' => 'Consultant Project',
            'description' => 'Ceci est un nouveau projet.'
        ];



        $jwtToken = $this->login($admin);
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/projects', [
            'json' => $projectData1,
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);


        $jwtToken = $this->login($manager);
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/projects', [
            'json' => $projectData2,
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);


        $jwtToken = $this->login($consultant);
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/projects', [
            'json' => $projectData3,
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);


        $projectDataWithExistingTitle = [
            'title' => 'Manager Project',
            'description' => 'Ceci est un nouveau projet.'
        ];

        $jwtToken = $this->login($manager);
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/projects', [
            'json' => $projectDataWithExistingTitle,
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $projectDataWithoutTitle = [
            'title' => 'Project',
            'description' => ''
        ];

        $projectDataWithoutDescription = [
            'title' => '',
            'description' => 'Ceci est un nouveau projet.'
        ];

        $jwtToken = $this->login($admin);
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/projects', [
            'json' => $projectDataWithExistingTitle,
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $this->client->request('POST', '/api/companies/' . $company->getId() . '/projects', [
            'json' => $projectDataWithoutDescription,
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

    }

}

