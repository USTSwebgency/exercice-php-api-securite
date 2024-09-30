<?php

namespace App\Entity;

use App\Controller\CreateProjectController;
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
use App\Security\AppVoter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ApiResource(
    operations: [
        // Récupérer tous les projets d'une entreprise
        new GetCollection(
            name: 'company_projects',
            uriTemplate: '/companies/{companyId}/projects',
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    toProperty: 'company'      
                ),
            ],
            output: ProjectListOutput::class,
        //   security: 'is_granted("view", object)',
        ),

        // Récupérer un projet spécifique d'une entreprise
        new Get(
            name: 'get_company_project',
            uriTemplate: '/companies/{companyId}/projects/{id}',
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    toProperty: 'company'      
                ),
            ],
            output: ProjectDetailsOutput::class,
            security: 'is_granted("view", object)'
        
        ),

        // Créer un nouveau projet au sein d'une entreprise
        new Post(
            uriTemplate: '/companies/{companyId}/projects',
            input: ProjectInput::class,
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    toProperty: 'company'      
                ),
            ],
            //security: 'is_granted("create", object)', 
        ),

        // Mettre à jour un projet existant
        new Put(
            uriTemplate: '/companies/{companyId}/projects/{id}',
            input: ProjectInput::class,
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    toProperty: 'company'      
                ),
            ],
            //security: 'is_granted("edit", object)', 
        ),

        // Supprimer un projet
       /* new Delete(
            uriTemplate: '/companies/{companyId}/projects/{id}',
            uriVariables: [
                'companyId' => new Link(
                    fromClass: Company::class, 
                    toProperty: 'company'    
                ),
            ],
            security: 'is_granted("delete", object)', 
        ),*/

    ]
)]

class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $description = null;

    #[ORM\Column(nullable: false)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    /* Il est bon de définir une date de création non modififiable et de 
    permettre l'association du projet à son entreprise lors de son initialisation */

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    // Empêche la modification de la company
    public function setCompany(?Company $company): static
    {
        if (null === $this->company) {
            $this->company = $company;
        }

        return $this;
    }
}