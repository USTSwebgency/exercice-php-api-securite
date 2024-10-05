<?php

namespace App\Story;

use App\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class DefaultUsersStory extends Story
{
    public function build(): void
    { 
        UserFactory::createOne(['email' => 'user1@local.host']);
        UserFactory::createOne(['email' => 'user2@local.host']);
        UserFactory::createMany(18); 
    }
}
