<?php

namespace App\Story;

use App\Factory\UserCompanyRoleFactory;
use App\Story\DefaultUsersCompaniesStory;
use App\Story\DefaultUsersStory;
use Zenstruck\Foundry\Story;
use App\Enum\Role;


final class DefaultUserCompanyRolesStory extends Story
{
    public function build(): void
    {
        UserCompanyRoleFactory::createMany(12);
    }
}
