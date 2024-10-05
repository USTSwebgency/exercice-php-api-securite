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

    /**
     * Connecte un utilisateur et retourne le JWT token
     */
    private function login(User $user): string
    {
        $response = $this->client->request('POST', '/api/auth', [
            'json' => [
                'email' => $user->getEmail(),
                'password' => 'my_password',
            ],
        ]);

        // Vérification de la réponse HTTP 200
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Récupération du contenu JSON
        $data = json_decode($response->getContent(), true);

        // Vérifie que le token est bien présent
        $this->assertArrayHasKey('token', $data, "JWT token non présent dans la réponse.");

        return $data['token'];
    }


    public function testGetCompanyDetails()
    {
        // Création d'un utilisateur et d'une entreprise
        $user = UserFactory::createOne();
        $company = CompanyFactory::createOne();
        $unauthorizedUser = UserFactory::createOne();
        UserCompanyRoleFactory::createOne(['user' => $user, 'company' => $company, 'role' => Role::CONSULTANT]);

        // Connexion de l'utilisateur
        $jwtToken = $this->login($user); 

        $this->client->request('GET', '/api/users/user/companies/' . $company->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
        ]);

     
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);

        // Requête pour une entreprise qui n'existe pas
        $this->client->request('GET', '/api/users/user/companies/999', [
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

        // Vérifie que l'accès est refusé avec une réponse 404 (non trouvé)
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
