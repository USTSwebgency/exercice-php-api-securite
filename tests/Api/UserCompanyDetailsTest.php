<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Entity\Company;
use App\Factory\UserFactory;
use App\Factory\CompanyFactory;
use App\Factory\UserCompanyRoleFactory;
use App\Enum\Role;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserCompanyDetailsTest extends ApiTestCase
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


    public function testGetCompanyDetails()
    {
       
        $user = UserFactory::createOne();
        $company = CompanyFactory::createOne();
        $unauthorizedUser = UserFactory::createOne();
        UserCompanyRoleFactory::createOne(['user' => $user, 'company' => $company, 'role' => Role::CONSULTANT]);


        $jwtToken = $this->login($user); 
        $this->client->request('GET', '/api/users/user/companies/' . $company->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);

        // Company not found requete
        $this->client->request('GET', '/api/users/user/companies/100', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        // Requête sans authentification
        $this->client->request('GET', '/api/users/user/companies/' . $company->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);


        $jwtToken = $this->login($unauthorizedUser);
        $this->client->request('GET', '/api/users/user/companies/' . $company->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
