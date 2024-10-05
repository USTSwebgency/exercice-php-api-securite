<?php

namespace App\DataFixtures;

use App\Story\DefaultUsersStory;
use App\Story\DefaultCompaniesStory;
use App\Story\DefaultUserCompanyRolesStory;
use App\Enum\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;


class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        DefaultUsersStory::load();
        DefaultCompaniesStory::load();
        DefaultUserCompanyRolesStory::load();
    }
}
