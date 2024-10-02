<?php

namespace App\Entity;

use App\Dto;
use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Enum\Role; 
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;


use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ApiResource(
    operations: [
        /* new Put(
            name: 'edit_company',
            uriTemplate: '/user/company/{id}',
            security: 'is_granted("edit", object)', // Appelle le CompanyVoter pour modifier une société
        ),
        new Post(
            security: 'is_granted("create", object)', // Appelle le CompanyVoter pour créer une société
        ),

        new Delete(
            security: 'is_granted("delete", object)', // Appelle le CompanyVoter pour supprimer une société
        ), */
    ]
)]


class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $siret = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Project::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $projects;

    /**
     * @var Collection<int, UserCompanyRole>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: UserCompanyRole::class)]
    private Collection $userCompanyRoles;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->userCompanyRoles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /*
     * Retourne la collection des projets liés à la société.
     * @return Collection<int, Project>
     */

    public function getProjects(): Collection
    {
        return $this->projects;
    }

     // Ajoute un projet à la société
    public function addProject(Project $project): static
    {
        if(!$this->projects->contains($project)){
            $this->projects->add($project);
            $project->setCompany($this);
        }

        return $this;
    }

    // Retire un projet de la société
    public function removeProject(Project $project): static
    {
        if($this->projects->removeElement($project)){
            if($project->getCompany() == $this)
            $project->setCompany(null);
        }
        return $this;
    }

    /*
     * Retourne les rôles des utilisateurs dans la société.
     * @return Collection<int, UserCompanyRole>
     */
    public function getUserCompanyRoles(): Collection
    {
        return $this->userCompanyRoles;
    }

    // Lie une société à un user par l'attribution d'un role
    public function addUserCompanyRole(UserCompanyRole $userCompanyRole): static
    {
        if (!$this->userCompanyRoles->contains($userCompanyRole)) {
            $this->userCompanyRoles->add($userCompanyRole);
            $userCompanyRole->setCompany($this);
        }

        return $this;
    }

    // Retire une association entre utilisateur et entreprise
    public function removeUserCompanyRole(UserCompanyRole $userCompanyRole): static
    {
        if ($this->userCompanyRoles->removeElement($userCompanyRole)) {
            // set the owning side to null (unless already changed)
            if ($userCompanyRole->getCompany() === $this) {
                $userCompanyRole->setCompany(null);
            }
        }

        return $this;
    }


    // Vérifie si l'utilisateur a un rôle dans la société
    public function isUserInCompany(User $user): bool
    {
        return $this->userCompanyRoles->exists(function($key, UserCompanyRole $userCompanyRole) use ($user) {
            return $userCompanyRole->getUser() === $user;
        });
    }
    
    // Vérifie si l'utilisateur a un rôle spécifique dans la company
    public function hasUser(User $user): bool
    {
        return $this->userCompanyRoles->exists(function($key, UserCompanyRole $userCompanyRole) use ($user) {
            return $userCompanyRole->getUser() === $user;
        });
    }
}
