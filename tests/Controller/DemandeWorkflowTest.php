<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemandeWorkflowTest extends WebTestCase
{
    public function testInexistentArticleReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/demande/article/999999');

        $this->assertResponseStatusCodeSame(404);
    }
}

