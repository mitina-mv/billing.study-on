<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\DataFixtures;
use App\Tests\Helpers\GetTokenTrait;

class CourseTest extends AbstractTest
{
    use GetTokenTrait;
    
    protected function getFixtures(): array
    {
        return [AppFixtures::class, DataFixtures::class];
    }

    public function testGetCourses()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/v1/courses',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseCode(200);
        $this->assertTrue(is_array($content));
    }
}
