<?php

namespace App\Story;

use App\Factory\ProjectFactory;
use App\Factory\CompanyFactory;
use App\Story\DefaultUsersCompaniesStory;


use Zenstruck\Foundry\Story;


final class DefaultProjectsStory extends Story
{
    public function build(): void
    {
        $companies = CompanyFactory::createMany(10);

        foreach ($companies as $company) {
            ProjectFactory::createMany(2, [
                'company' => $company
            ]);
        }
    }
}
