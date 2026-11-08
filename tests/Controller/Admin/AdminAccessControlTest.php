<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Admin;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminAccessControlTest extends WebTestCase
{
    public function testUnauthenticatedUserCannotAccessAdmin(): void
    {
        $client = static::createClient();

        // Une requête sans authentification vers /admin doit rediriger (ou refuser l'accès 401/403/302)
        $client->request('GET', '/admin');
        $this->assertTrue($client->getResponse()->isRedirection() || $client->getResponse()->getStatusCode() === 401 || $client->getResponse()->getStatusCode() === 403);

        $client->request('GET', '/admin/categories');
        $this->assertTrue($client->getResponse()->isRedirection() || $client->getResponse()->getStatusCode() === 401 || $client->getResponse()->getStatusCode() === 403);

        $client->request('GET', '/admin/articles');
        $this->assertTrue($client->getResponse()->isRedirection() || $client->getResponse()->getStatusCode() === 401 || $client->getResponse()->getStatusCode() === 403);
    }

    public function testAuthenticatedAdminCanAccessDashboard(): void
    {
        $client = static::createClient();

        $admin = new Admin();
        $admin->setEmail('admin@boutique.test');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword('password');

        $client->loginUser($admin);

        // Crée une réponse de test (même si le template dashboard sera finalisé ensuite)
        // Vérifie qu'aucune erreur d'accès n'est levée (statut non 401/403)
        $client->request('GET', '/admin');
        $this->assertNotSame(401, $client->getResponse()->getStatusCode());
        $this->assertNotSame(403, $client->getResponse()->getStatusCode());
    }
}

