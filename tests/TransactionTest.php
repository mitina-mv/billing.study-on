<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\DataFixtures;
use App\Tests\Helpers\GetTokenTrait;

class TransactionTest extends AbstractTest
{
    use GetTokenTrait;
    
    protected function getFixtures(): array
    {
        return [AppFixtures::class, DataFixtures::class];
    }
}
