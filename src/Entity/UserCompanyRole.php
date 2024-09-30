<?php

namespace App\Entity;

use App\Repository\UserCompanyRoleRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\Role;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use App\Controller\AddUserToCompanyController;
use App\Entity\Company;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Dto\AddUserToCompanyInput;
use App\Security\AppVoter;


#[ORM\Entity(repositoryClass: UserCompanyRoleRepository::class)]
#[ApiResource(
    operations: [
       /* new GetCollection(
            uriTemplate: '/company/{companyId}/user_roles',
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    toProperty: 'company'      
                ),
            ],
            security: 'is_granted("view", object.getCompany())',
        ),
        new Post(
            uriTemplate: '/company/{companyId}/add_user',
            controller: AddUserToCompanyController::class,
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    toProperty: 'company'      
                ),
            ],
            security: 'is_granted("add_user", object)', 
            denormalizationContext: ['groups' => ['add_user']],
            input: AddUserToCompanyInput::class, 
        ), */     
    ]
)]

class UserCompanyRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userCompanyRoles', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;


    #[ORM\ManyToOne(inversedBy: 'userCompanyRoles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $role;

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
        return Role::tryFrom($this->role); // Utilise l'énumération pour récupérer le rôle
    }
    
    public function setRole(Role $role): static
    {
        $this->role = $role->value; // Stocke la valeur de l'énumération sous forme de chaîne
        return $this;
    }
    
    
}
