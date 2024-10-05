<?php

namespace App\Entity;

use App\Controller\CreateProjectController;
use App\Controller\UpdateProjectController;
use App\Controller\GetProjectDetailsController;
use App\Controller\GetCompanyProjectsController;
use App\Controller\DeleteProjectController;
use App\Enum\Role; 
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProjectRepository;
use App\Dto\ProjectListOutput;
use App\Dto\ProjectDetailsOutput; 
use App\Dto\ProjectInput;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ApiResource(

    operations: [

        // Renvoie la liste des projets d'une entreprise
        new GetCollection(
            name: 'company_projects',
            uriTemplate: '/companies/{companyId}/projects',
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    fromProperty: 'projects'     
                ),
            ],
            controller: GetCompanyProjectsController::class,
            output: ProjectListOutput::class,
        ),

        // Renvoi les détails d'un projet d'une entreprise
        new Get(
            read: false,
            name: 'get_company_project',
            uriTemplate: '/companies/{companyId}/projects/{id}',
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    fromProperty: 'projects'     
                ),
                'id' => new link(
                    fromClass: Project::class,
                    fromProperty: 'company'
                )
            ],
            controller: GetProjectDetailsController::class,
            output: ProjectDetailsOutput::class
        ),

        // Crée un nouveau projet pour une entreprise
        new Post(
            read: false,
            uriTemplate: '/companies/{companyId}/projects',
            input: ProjectInput::class,
            controller: CreateProjectController::class,
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class,
                    fromProperty: 'projects' 
                ),
            ],
        ),
        
        // Met à jour un projet existant dans une entreprise
        new Put(
            read: false,
            uriTemplate: '/companies/{companyId}/projects/{id}',
            input: ProjectInput::class,
            controller: UpdateProjectController::class,
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    fromProperty: 'projects'      
                ),
                'id' => new link(
                    fromClass: Project::class,
                    fromProperty: 'company'
                )
            ],
        ),

        // Supprime un projet d'une entreprise
        new Delete(
            read: false,
            controller: DeleteProjectController::class,
            uriTemplate: '/companies/{companyId}/projects/{id}',
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    fromProperty: 'projects'    
                ),
                'id' => new link(
                    fromClass: Project::class,
                    fromProperty: 'company'
                )
            ],
        )

    ]
)]

class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(nullable: false)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    /* Un projet doit etre obligatoirement lié à la société du user qui crée le projet 
    Plus une date de création */
    public function __construct(Company $company)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->company = $company; 
    }
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }
    
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    // La société ne peut pas être modifiée après la création du projet
    public function setCompany(?Company $company): static
    {
        if (null === $this->company) {
            $this->company = $company;
        }

        return $this;
    }
}