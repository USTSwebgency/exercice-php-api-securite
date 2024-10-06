<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Factory\CompanyFactory;
use App\Factory\UserCompanyRoleFactory;
use App\Entity\Company;
use App\Enum\Role;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserCompanyTest extends ApiTestCase
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


    public function testGetCompanies()
    {
        $member = UserFactory::createOne();
        $company = CompanyFactory::createOne();
        UserCompanyRoleFactory::createOne(['user' => $member, 'company' => $company, 'role' => Role::ADMIN]);

      
        $jwtToken = $this->login($member);
        $this->client->request('GET', '/api/users/user/companies', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);

         // Requête anonyme sans authentification
        $this->client->request('GET', '/api/users/user/companies');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $userWithoutCompany = UserFactory::createOne();
        $jwtToken = $this->login($userWithoutCompany);
        $this->client->request('GET', '/api/users/user/companies', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertEmpty($this->client->getResponse()->getContent());
    }
}
