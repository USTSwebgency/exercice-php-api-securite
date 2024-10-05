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

        // Vérification de la réponse HTTP 200 (OK)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Récupération du contenu JSON
        $data = json_decode($response->getContent(), true);

        return $data['token'];
    }

    public function testGetCompanies()
    {
        $member = UserFactory::createOne();
        $company = CompanyFactory::createOne();
        UserCompanyRoleFactory::createOne(['user' => $member, 'company' => $company, 'role' => Role::ADMIN]);

        // Connexion de l'utilisateur
        $jwtToken = $this->login($member);

        // Requête pour obtenir la liste des entreprises de l'utilisateur
        $this->client->request('GET', '/api/users/user/companies', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);

        // Vérification que la réponse est un succès (200 OK)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérification que la liste des sociétés est retournée
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);

         // Requête anonyme sans authentification
        $this->client->request('GET', '/api/users/user/companies');

        // Vérification que l'accès est refusé (401 Unauthorized)
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $userWithoutCompany = UserFactory::createOne();

        // Connexion de l'utilisateur
        $jwtToken = $this->login($userWithoutCompany);

        // Requête pour obtenir la liste des entreprises
        $this->client->request('GET', '/api/users/user/companies', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);

        // Vérification que l'utilisateur n'a pas de sociétés affiliées (204 No Content)
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Vérification que la réponse ne contient pas de contenu
        $this->assertEmpty($this->client->getResponse()->getContent());
    }
}
