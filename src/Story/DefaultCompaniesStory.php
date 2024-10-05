<?php

namespace App\Story;

use App\Factory\CompanyFactory;
use Zenstruck\Foundry\Story;

final class DefaultCompaniesStory extends Story
{
    public function build(): void
    {   
        CompanyFactory::createMany(10);
    }
}
