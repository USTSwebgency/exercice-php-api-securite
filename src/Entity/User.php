<?php

namespace App\Entity;


use App\Repository\UserRepository;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
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
use App\Security\AppVoter;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]

#[ApiResource(
    operations: [
        new GetCollection(
            name: 'get_users', // Nom de l'opération pour récupérer tous les utilisateurs
            // security: "is_granted('')" /
        ),
        new GetCollection(
            name: 'get_user_companies',
            uriTemplate: '/users/user/companies',
            controller: GetUserCompaniesController::class,
            output: CompanyListOutput::class, 
            // security: 'is_granted("view", object)',
        ),
        new Get(
            name: 'get_user_company_details',
            uriTemplate: '/users/user/companies/{id}',
            controller: GetUserCompanyDetailsController::class,
            output: CompanyOutput::class,
            // security: 'is_granted("view", object)',
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
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: false)]
    private ?string $password = null;

    /**
     * @var Collection<int, UserCompanyRole>
     */
    #[ORM\OneToMany(targetEntity: UserCompanyRole::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist'])]
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
     * A visual identifier that represents this user.
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

    /**
     * @return Collection<int, UserCompanyRole>
     */
    public function getUserCompanyRoles(): Collection
    {
        return $this->userCompanyRoles;
    }
    
    // Lie un user à une company par l'attribution d'un role dans la company
    public function addUserCompanyRole(UserCompanyRole $userCompanyRole): static
    {
        if (!$this->userCompanyRoles->contains($userCompanyRole)) {
            $this->userCompanyRoles->add($userCompanyRole);
            $userCompanyRole->setUser($this);
        }

        return $this;
    }

    // Retirer une association et aussi permettre de retirer un user quand on le voudra
    public function removeUserCompanyRole(UserCompanyRole $userCompanyRole): static
    {
        if ($this->userCompanyRoles->removeElement($userCompanyRole)) {
            // set the owning side to null (unless already changed)
            if ($userCompanyRole->getUser() === $this) {
                $userCompanyRole->setUser(null);
            }
        }

        return $this;
    }

    // Méthode pour récuperer le role d'un user dans une société
    public function getRoleForCompany(Company $company): ?Role
    {
        foreach ($this->userCompanyRoles as $userCompanyRole) {
            if ($userCompanyRole->getCompany() === $company) {
                return $userCompanyRole->getRole(); // Retourne l'énumération Role
            }
        }
    
        return null;
    }
    

    // Méthode pour récuperer la liste des sociétés auxquelles appartient un user
    public function getCompanies(): Collection
    {
        $companies = new ArrayCollection();
        foreach ($this->userCompanyRoles as $userCompanyRole) {
            $companies->add($userCompanyRole->getCompany());
        }
        return $companies;
    }
}