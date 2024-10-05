<?php

namespace App\Tests\Security;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\Role;
use App\Security\Voter\CompanyVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CompanyVoterTest extends TestCase
{
    private $voter;
    private $token;
    private $user;
    private $company;

    protected function setUp(): void
    {
        $this->voter = new CompanyVoter();
        $this->token = $this->createMock(TokenInterface::class);
        $this->user = $this->createMock(User::class);
        $this->company = $this->createMock(Company::class);

        // Associer l'utilisateur au token
        $this->token->method('getUser')->willReturn($this->user);
    }

    public function testViewCompanyGrantedIfUserIsInCompany(): void
    {
        // Simuler que l'utilisateur fait partie de la société
        $this->company->method('isUserInCompany')->with($this->user)->willReturn(true);

        $result = $this->voter->vote($this->token, $this->company, [CompanyVoter::VIEW]);

        $this->assertEquals(CompanyVoter::ACCESS_GRANTED, $result, "L'utilisateur doit avoir accès à la société.");
    }

    public function testViewCompanyDeniedIfUserIsNotInCompany(): void
    {
        // Simuler que l'utilisateur ne fait pas partie de la société
        $this->company->method('isUserInCompany')->with($this->user)->willReturn(false);

        $result = $this->voter->vote($this->token, $this->company, [CompanyVoter::VIEW]);

        $this->assertEquals(CompanyVoter::ACCESS_DENIED, $result, "L'utilisateur ne doit pas avoir accès à une société à laquelle il n'appartient pas.");
    }

    public function testAddUserToCompanyGrantedIfUserIsAdmin(): void
    {
        // Simuler que l'utilisateur a le rôle ADMIN dans la société
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::ADMIN);

        $result = $this->voter->vote($this->token, $this->company, [CompanyVoter::ADD_USER]);

        $this->assertEquals(CompanyVoter::ACCESS_GRANTED, $result, "Un admin doit avoir le droit d'ajouter des utilisateurs à la société.");
    }

    public function testAddUserToCompanyDeniedIfUserIsNotAdmin(): void
    {
        // Simuler que l'utilisateur a un rôle autre que ADMIN (par exemple, MANAGER)
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::MANAGER);

        $result = $this->voter->vote($this->token, $this->company, [CompanyVoter::ADD_USER]);

        $this->assertEquals(CompanyVoter::ACCESS_DENIED, $result, "Seul un admin doit pouvoir ajouter des utilisateurs à la société.");
    }

    public function testAddUserToCompanyDeniedIfUserIsNotInCompany(): void
    {
        // Simuler que l'utilisateur ne fait pas partie de la société
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(null);

        $result = $this->voter->vote($this->token, $this->company, [CompanyVoter::ADD_USER]);

        $this->assertEquals(CompanyVoter::ACCESS_DENIED, $result, "Un utilisateur qui n'est pas dans la société ne doit pas pouvoir ajouter des utilisateurs.");
    }
}
