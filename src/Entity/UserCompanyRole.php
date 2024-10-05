<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Repository\UserCompanyRoleRepository;
use App\Controller\AddUserToCompanyController;
use App\Dto\AddUserToCompanyInput;
use ApiPlatform\Metadata\Link;
use App\Enum\Role;

#[ORM\UniqueConstraint(name: 'user_company_unique', columns: ['user_id', 'company_id'])]
#[ORM\Entity(repositoryClass: UserCompanyRoleRepository::class)]
#[ApiResource(

    operations: [

        // Permet l'ajout d'un utilisateur dans une entreprise
        new Post(
            read: false,
            uriTemplate: '/companies/{companyId}/users/{userId}/add_user',
            controller: AddUserToCompanyController::class,
            input: AddUserToCompanyInput::class, 
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class,
                    fromProperty: 'userCompanyRoles', 
                ),
                'userId' => new Link(
                    fromClass: User::class,
                    fromProperty: 'userCompanyRoles', 
                ),
            ],

        ),
    ]
)]
class UserCompanyRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userCompanyRoles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'userCompanyRoles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(type: 'string', enumType: Role::class, length: 255)]
    private Role $role;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(Role $role): static
    {
        $this->role = $role;
        return $this;
    }
}
