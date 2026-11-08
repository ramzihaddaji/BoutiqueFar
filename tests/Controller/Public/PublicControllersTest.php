<?php

declare(strict_types=1);

namespace App\Tests\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicControllersTest extends WebTestCase
{
    public function testPublicHomeIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        // On vérifie que la route est accessible sans authentification (statut 200)
        $this->assertResponseIsSuccessful();
    }

    public function testPublicCategoriesIndexIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/categories');

        $this->assertResponseIsSuccessful();
    }

    public function testPublicArticlesIndexIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/articles');

        $this->assertResponseIsSuccessful();
    }

    public function testInexistentArticleReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/articles/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testInexistentCategoryReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/categories/999999');

        $this->assertResponseStatusCodeSame(404);
    }
}

