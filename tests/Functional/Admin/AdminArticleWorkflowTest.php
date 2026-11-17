<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Entity\Admin;
use App\Entity\Article;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminArticleWorkflowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $admin = $this->em->getRepository(Admin::class)->findOneBy(['email' => 'admin_article_test@test.com']);
        if ($admin === null) {
            $admin = new Admin();
            $admin->setEmail('admin_article_test@test.com');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword('password123');
            $this->em->persist($admin);
            $this->em->flush();
        }

        $this->client->loginUser($admin);
    }

    public function testCreateArticleInLeafCategory(): void
    {
        // 1. Préparer une catégorie feuille active
        $category = new Category();
        $category->setName('Sneakers Test');
        $category->setActive(true);
        $this->em->persist($category);
        $this->em->flush();

        // 2. Accès formulaire
        $crawler = $this->client->request('GET', '/admin/articles/new');
        $this->assertResponseIsSuccessful();

        // 3. Soumission
        $form = $crawler->selectButton('Enregistrer l\'article')->form([
            'article[name]' => 'Air Runner 2026',
            'article[description]' => 'Chaussures de running légères',
            'article[price]' => '129.99',
            'article[available]' => true,
            'article[category]' => (string) $category->getId(),
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/articles');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');

        /** @var Article $article */
        $article = $this->em->getRepository(Article::class)->findOneBy(['name' => 'Air Runner 2026'], ['id' => 'DESC']);
        $this->assertNotNull($article);
        $this->assertSame('129.99', $article->getPrice());
        $this->assertTrue($article->isAvailable());
        $this->assertSame($category->getId(), $article->getCategory()->getId());
    }

    public function testEditArticle(): void
    {
        $category = new Category();
        $category->setName('T-Shirts Test');
        $category->setActive(true);

        $article = new Article();
        $article->setName('T-Shirt Initial');
        $article->setPrice('25.00');
        $article->setCategory($category);
        $article->setAvailable(true);

        $this->em->persist($category);
        $this->em->persist($article);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/admin/articles/' . $article->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Mettre à jour l\'article')->form([
            'article[name]' => 'T-Shirt Modifié',
            'article[price]' => '29.90',
            'article[category]' => (string) $category->getId(),
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/articles');
        $this->client->followRedirect();

        $articleId = $article->getId();
        $this->em->clear();

        /** @var Article $updatedArticle */
        $updatedArticle = $this->em->getRepository(Article::class)->find($articleId);
        $this->assertSame('T-Shirt Modifié', $updatedArticle->getName());
        $this->assertSame('29.90', $updatedArticle->getPrice());
    }

    public function testDeleteArticleWithoutCsrfFails(): void
    {
        $category = new Category();
        $category->setName('Accessoires Test');
        $category->setActive(true);

        $article = new Article();
        $article->setName('Casquette Test');
        $article->setPrice('19.90');
        $article->setCategory($category);

        $this->em->persist($category);
        $this->em->persist($article);
        $this->em->flush();

        $this->client->request('POST', '/admin/articles/' . $article->getId(), [
            '_token' => 'invalid_csrf_token',
        ]);

        $this->assertResponseRedirects('/admin/articles');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');

        // L'article n'a pas été supprimé
        $this->assertNotNull($this->em->getRepository(Article::class)->find($article->getId()));
    }
}

