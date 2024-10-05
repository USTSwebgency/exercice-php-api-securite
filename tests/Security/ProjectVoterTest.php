<?php

namespace App\Tests\Security;

use App\Entity\Company;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\Role;
use App\Security\Voter\ProjectVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProjectVoterTest extends TestCase
{
    private ProjectVoter $voter;
    private TokenInterface $token;
    private User $user;
    private Company $company;
    private Project $project;

    protected function setUp(): void
    {
        $this->voter = new ProjectVoter();
        $this->token = $this->createMock(TokenInterface::class);
        $this->user = $this->createMock(User::class);
        $this->company = $this->createMock(Company::class);
        $this->project = $this->createMock(Project::class);

        // Associer l'utilisateur au token
        $this->token->method('getUser')->willReturn($this->user);
        // Associer le projet à la société
        $this->project->method('getCompany')->willReturn($this->company);
    }

    public function testViewProjectGrantedIfUserIsInCompany(): void
    {
        $this->company->method('isUserInCompany')->with($this->user)->willReturn(true);

        $result = $this->voter->vote($this->token, $this->project, [ProjectVoter::VIEW]);
        $this->assertEquals(ProjectVoter::ACCESS_GRANTED, $result, "Tous les utilisateurs membres de la société doivent pouvoir voir les projets.");
    }

    public function testCreateProjectGrantedIfUserIsAdminOrManager(): void
    {
        $this->company->method('isUserInCompany')->willReturn(true); // Assurer que l'utilisateur est dans la société
    
        // Test pour ADMIN
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::ADMIN);
        $result = $this->voter->vote($this->token, $this->project, [ProjectVoter::CREATE]);
        $this->assertEquals(ProjectVoter::ACCESS_GRANTED, $result, "Un admin doit avoir le droit de créer un projet.");
    
        // Test pour MANAGER
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::MANAGER);
        $result = $this->voter->vote($this->token, $this->project, [ProjectVoter::CREATE]);
        $this->assertEquals(ProjectVoter::ACCESS_GRANTED, $result, "Un manager doit avoir le droit de créer un projet.");
    }

    public function testCreateProjectDeniedIfUserIsConsultant(): void
    {
        $this->company->method('isUserInCompany')->willReturn(true); // Assurer que l'utilisateur est dans la société
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::CONSULTANT);

        $result = $this->voter->vote($this->token, $this->project, [ProjectVoter::CREATE]);
        $this->assertEquals(ProjectVoter::ACCESS_DENIED, $result, "Un consultant ne doit pas avoir le droit de créer un projet.");
    }

    public function testEditProjectGrantedIfUserIsAdminOrManager(): void
    {
        $this->company->method('isUserInCompany')->willReturn(true); // Assurer que l'utilisateur est dans la société
    
        // Test pour ADMIN
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::ADMIN);
        $result = $this->voter->vote($this->token, $this->project, [ProjectVoter::EDIT]);
        $this->assertEquals(ProjectVoter::ACCESS_GRANTED, $result, "Un admin doit avoir le droit de modifier un projet.");
    
        // Test pour MANAGER
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::MANAGER);
        $result = $this->voter->vote($this->token, $this->project, [ProjectVoter::EDIT]);
        $this->assertEquals(ProjectVoter::ACCESS_GRANTED, $result, "Un manager doit avoir le droit de modifier un projet.");
    }

    public function testEditProjectDeniedIfUserIsConsultant(): void
    {
        $this->company->method('isUserInCompany')->willReturn(true); // Assurer que l'utilisateur est dans la société
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::CONSULTANT);

        $result = $this->voter->vote($this->token, $this->project, [ProjectVoter::EDIT]);
        $this->assertEquals(ProjectVoter::ACCESS_DENIED, $result, "Un consultant ne doit pas avoir le droit de modifier un projet.");
    }

    public function testDeleteProjectGrantedIfUserIsAdmin(): void
    {
        $this->company->method('isUserInCompany')->willReturn(true); // Assurer que l'utilisateur est dans la société
    
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::ADMIN);
        $result = $this->voter->vote($this->token, $this->project, [ProjectVoter::DELETE]);
        $this->assertEquals(ProjectVoter::ACCESS_GRANTED, $result, "Un admin doit avoir le droit de supprimer un projet.");
    }
    
    public function testDeleteProjectDeniedIfUserIsNotAdmin(): void
    {
        $this->company->method('isUserInCompany')->willReturn(true); // Assurer que l'utilisateur est dans la société
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::MANAGER);

        $result = $this->voter->vote($this->token, $this->project, [ProjectVoter::DELETE]);
        $this->assertEquals(ProjectVoter::ACCESS_DENIED, $result, "Seul un admin doit avoir le droit de supprimer un projet.");
    }
}
