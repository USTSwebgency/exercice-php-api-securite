<?php

namespace App\Entity;

use App\Repository\UserRepository;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use App\Enum\Role;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Dto\CompanyListOutput;
use App\Dto\CompanyOutput;
use App\Controller\GetUserCompaniesController;
use App\Controller\GetUserCompanyDetailsController;
use App\Security\CompanyVoter;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(

    operations: [

        // Renvoie la liste de tous les users de la BDD
        new GetCollection(
            name: 'get_users_emails',
            uriTemplate: '/users',
            normalizationContext: ['groups' => ['email_only']],
        ),

        // Renvoie la liste de toutes les sociétés d'un user 
        new GetCollection(
            name: 'get_user_companies',
            uriTemplate: '/users/user/companies',
            controller: GetUserCompaniesController::class,
            output: CompanyListOutput::class,
        ),

        // Renvoie les détails d'une société spécifique dont fait partie l'utilisateur
        new Get(
            name: 'get_user_company_details',
            uriTemplate: '/users/user/companies/{id}',
            controller: GetUserCompanyDetailsController::class,
            output: CompanyOutput::class,
        ),
    ],

    paginationEnabled: false,
)]

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: false)]
    #[Groups(['email_only'])]
    private ?string $email = null;

    /**
     * @var list<string> Les rôles de l'utilisateur
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string Le mot de passe haché
     */
    #[ORM\Column(nullable: false)]
    private ?string $password = null;

    /**
     * @var Collection<int, UserCompanyRole>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserCompanyRole::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userCompanyRoles;

    public function __construct()
    {
        $this->userCompanyRoles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Un identifiant visuel représentant cet utilisateur.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @return list<string>
     * @see UserInterface
     *
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }


    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = array_unique($roles);
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /*
     * Retourne les rôles de l'utilisateur pour les entreprises.
     * @return Collection<int, UserCompanyRole>
     */
    public function getUserCompanyRoles(): Collection
    {
        return $this->userCompanyRoles;
    }

    // Lie un utilisateur à une entreprise en lui attribuant un role
    public function addUserCompanyRole(UserCompanyRole $userCompanyRole): static
    {
        if (!$this->userCompanyRoles->contains($userCompanyRole)) {
            $this->userCompanyRoles->add($userCompanyRole);
            $userCompanyRole->setUser($this);
        }
        return $this;
    }

    // Retire une association entre utilisateur et entreprise
    public function removeUserCompanyRole(UserCompanyRole $userCompanyRole): static
    {
        if ($this->userCompanyRoles->removeElement($userCompanyRole)) {
            if ($userCompanyRole->getUser() === $this) {
                $userCompanyRole->setUser(null);
            }
        }
        return $this;
    }

    // Retourne le rôle de l'utilisateur dans une entreprise spécifique
    public function getRoleForCompany(Company $company): ?Role
    {
        foreach ($this->userCompanyRoles as $userCompanyRole) {
            if ($userCompanyRole->getCompany() === $company) {
                return $userCompanyRole->getRole();
            }
        }
        return null; 
    }
    
    public function getCompanies(): Collection
    {
        $companies = new ArrayCollection();
        foreach ($this->userCompanyRoles as $userCompanyRole) {
            $companies->add($userCompanyRole->getCompany());
        }
        return $companies;
    }
}
