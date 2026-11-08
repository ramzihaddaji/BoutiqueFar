<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityHardeningTest extends WebTestCase
{
    public function testLoginPageRendersWithCsrf(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('input[name="_csrf_token"]'));
        $this->assertCount(1, $crawler->filter('input[name="_email"]'));
        $this->assertCount(1, $crawler->filter('input[name="_password"]'));
    }

    public function testLoginWithInvalidCredentialsFails(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $client->request('POST', '/login', [
            '_email' => 'fake_admin@test.com',
            '_password' => 'wrong_password',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testAdminProtectedRoutesRedirectToLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin');
        $this->assertResponseRedirects('/login');

        $client->request('GET', '/admin/categories/new');
        $this->assertResponseRedirects('/login');

        $client->request('GET', '/admin/articles/new');
        $this->assertResponseRedirects('/login');
    }

    public function testPublicCannotPostToAdminRoutesDirectly(): void
    {
        $client = static::createClient();

        // Tentative de modification sans authentification
        $client->request('POST', '/admin/categories/new', [
            'category' => ['name' => 'Hacked Category'],
        ]);
        $this->assertResponseRedirects('/login');

        $client->request('POST', '/admin/articles/new', [
            'article' => ['name' => 'Hacked Article'],
        ]);
        $this->assertResponseRedirects('/login');
    }

    public function testAuthenticatedAdminCanLogout(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Crée ou récupère un admin persisté avec identifiant Doctrine
        $admin = $em->getRepository(Admin::class)->findOneBy(['email' => 'admin_test@test.com']);
        if ($admin === null) {
            $admin = new Admin();
            $admin->setEmail('admin_test@test.com');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword('hashed_pass');
            $em->persist($admin);
            $em->flush();
        }

        $client->loginUser($admin);

        $client->request('GET', '/logout');
        $this->assertResponseRedirects('/login');
    }
}
