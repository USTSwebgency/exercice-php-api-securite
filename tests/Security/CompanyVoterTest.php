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
    private CompanyVoter $voter;
    private TokenInterface $token;
    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        $this->voter = new CompanyVoter();
        $this->token = $this->createMock(TokenInterface::class);
        $this->user = $this->createMock(User::class);
        $this->company = $this->createMock(Company::class);

        // Associer l'utilisateur au token
        $this->token->method('getUser')->willReturn($this->user);
    }

    public function testViewAccessGrantedIfUserIsInCompany(): void
    {
        // Simuler que l'utilisateur fait partie de la société
        $this->company->method('isUserInCompany')->with($this->user)->willReturn(true);

        $result = $this->voter->vote($this->token, $this->company, [CompanyVoter::VIEW]);

        $this->assertEquals(CompanyVoter::ACCESS_GRANTED, $result, "L'utilisateur doit avoir accès à la société s'il en fait partie.");
    }

    public function testViewAccessDeniedIfUserIsNotInCompany(): void
    {
        // Simuler que l'utilisateur ne fait pas partie de la société
        $this->company->method('isUserInCompany')->with($this->user)->willReturn(false);

        $result = $this->voter->vote($this->token, $this->company, [CompanyVoter::VIEW]);

        $this->assertEquals(CompanyVoter::ACCESS_DENIED, $result, "L'utilisateur ne doit pas avoir accès à une société à laquelle il n'appartient pas.");
    }

    public function testAddUserAccessGrantedIfUserIsAdmin(): void
    {
        // Simuler que l'utilisateur a le rôle ADMIN dans la société
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::ADMIN);

        $result = $this->voter->vote($this->token, $this->company, [CompanyVoter::ADD_USER]);

        $this->assertEquals(CompanyVoter::ACCESS_GRANTED, $result, "Un admin doit avoir le droit d'ajouter des utilisateurs à la société.");
    }

    public function testAddUserAccessDeniedIfUserIsManager(): void
    {
        // Simuler que l'utilisateur a un rôle autre que ADMIN
        $this->user->method('getRoleForCompany')->with($this->company)->willReturn(Role::MANAGER);

        $result = $this->voter->vote($this->token, $this->company, [CompanyVoter::ADD_USER]);

        $this->assertEquals(CompanyVoter::ACCESS_DENIED, $result, "Un manager ne doit pas avoir le droit d'ajouter des utilisateurs à la société.");
    }

    public function testAddUserAccessDeniedIfUserIsNotInCompany(): void
    {
        // Simuler que l'utilisateur ne fait pas partie de la société
        $this->company->method('isUserInCompany')->with($this->user)->willReturn(false);

        $result = $this->voter->vote($this->token, $this->company, [CompanyVoter::ADD_USER]);

        $this->assertEquals(CompanyVoter::ACCESS_DENIED, $result, "Un utilisateur qui ne fait pas partie de la société ne doit pas pouvoir ajouter des utilisateurs.");
    }
}
