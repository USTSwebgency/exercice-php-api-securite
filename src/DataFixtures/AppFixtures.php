<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Company;
use App\Entity\UserCompanyRole;
use App\Enum\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Factory\UserFactory;

class AppFixtures extends Fixture
{
    public function __construct(private UserFactory $userFactory) {}

    public function load(ObjectManager $manager): void
    {
        // Création des utilisateurs avec UserFactory
        UserFactory::createOne(['email' => 'user1@local.host']);
        UserFactory::createOne(['email' => 'user2@local.host']);
        UserFactory::createMany(10); // Crée 10 utilisateurs supplémentaires

        // Création de 5 entreprises
        $companies = [
            ['name' => 'Company Alpha', 'siret' => '12345678912345', 'address' => '12 rue de la Liberté, Paris'],
            ['name' => 'Company Beta', 'siret' => '12345678954321', 'address' => '34 avenue de la Paix, Lyon'],
            ['name' => 'Company Gamma', 'siret' => '12345678967890', 'address' => '56 boulevard de la Victoire, Lille'],
            ['name' => 'Company Delta', 'siret' => '12345678998765', 'address' => '78 impasse de la Résistance, Toulouse'],
            ['name' => 'Company Epsilon', 'siret' => '12345678943210', 'address' => '90 allée des Héros, Bordeaux'],
        ];

        // Récupérer tous les utilisateurs persistés
        $users = $manager->getRepository(User::class)->findAll(); // Récupère tous les utilisateurs

        foreach ($companies as $index => $companyData) {
            // Création de l'entité Company
            $company = new Company();
            $company->setName($companyData['name']);
            $company->setSiret($companyData['siret']);
            $company->setAddress($companyData['address']);

            $manager->persist($company);

            // Vérifie qu'il y a suffisamment d'utilisateurs pour attribuer les rôles
            if (count($users) > $index) {
                // Utiliser un utilisateur de la liste
                $userCompanyRole = new UserCompanyRole();
                $userCompanyRole->setUser($users[$index]); // Prendre l'utilisateur par index
                $userCompanyRole->setRole(Role::ADMIN); 
    

                // Associer le rôle à l'entreprise
                $company->addUserCompanyRole($userCompanyRole);
            }
        }

        // Persist all entities
        $manager->flush();
    }
}
