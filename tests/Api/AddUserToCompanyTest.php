<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Entity\Company;
use App\Factory\UserFactory;
use App\Factory\CompanyFactory;
use App\Factory\UserCompanyRoleFactory;
use App\Enum\Role;
use Symfony\Component\HttpFoundation\Response;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AddUserToCompanyTest extends ApiTestCase
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

    public function testAddUserToCompany()
    {
        // Créer une société et des utilisateurs
        $company = CompanyFactory::createOne();
        $existingUser = UserFactory::createOne();
        $newUser = UserFactory::createOne();
        $admin = UserFactory::createOne();
        $externalUser = UserFactory::createOne();
        $manager = UserFactory::createOne();
        $consultant = UserFactory::createOne();

        // Assigner des rôles
        UserCompanyRoleFactory::createOne(['user' => $admin, 'company' => $company, 'role' => Role::ADMIN]);
        UserCompanyRoleFactory::createOne(['user' => $existingUser, 'company' => $company, 'role' => Role::MANAGER]);
        UserCompanyRoleFactory::createOne(['user' => $manager, 'company' => $company, 'role' => Role::MANAGER]);
        UserCompanyRoleFactory::createOne(['user' => $consultant, 'company' => $company, 'role' => Role::CONSULTANT]);

        // L'admin peut ajouter un utilisateur à la société
        $jwtToken = $this->login($admin);
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/users/' . $newUser->getId() . '/add_user', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
            'json' => ['role' => Role::CONSULTANT] // Exemple de rôle
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        // Vérifier que le nouvel utilisateur a été ajouté
        $this->assertTrue($company->isUserInCompany($newUser));

        // Un utilisateur existant dans la société ne peut pas être ajouté à nouveau
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/users/' . $existingUser->getId() . '/add_user', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
            'json' => ['role' => Role::CONSULTANT]
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Simuler un utilisateur non connecté
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/users/' . $newUser->getId() . '/add_user', [
            'json' => ['role' => Role::CONSULTANT]
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // Simuler un utilisateur externe non autorisé
        $jwtToken = $this->login($externalUser);
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/users/' . $newUser->getId() . '/add_user', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
            'json' => ['role' => Role::CONSULTANT]
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Vérifier qu'un manager ne peut pas ajouter un utilisateur
        $jwtToken = $this->login($manager);
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/users/' . $newUser->getId() . '/add_user', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
            'json' => ['role' => Role::CONSULTANT]
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Vérifier qu'un consultant ne peut pas ajouter un utilisateur
        $jwtToken = $this->login($consultant);
        $this->client->request('POST', '/api/companies/' . $company->getId() . '/users/' . $newUser->getId() . '/add_user', [
            'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
            'json' => ['role' => Role::CONSULTANT]
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
