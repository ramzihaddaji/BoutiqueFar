<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Entity\Admin;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminCategoryWorkflowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $admin = $this->em->getRepository(Admin::class)->findOneBy(['email' => 'admin_category_test@test.com']);
        if ($admin === null) {
            $admin = new Admin();
            $admin->setEmail('admin_category_test@test.com');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword('password123');
            $this->em->persist($admin);
            $this->em->flush();
        }

        $this->client->loginUser($admin);
    }

    public function testCreateRootCategoryAndRecursiveChild(): void
    {
        // 1. Accès au formulaire de création
        $crawler = $this->client->request('GET', '/admin/categories/new');
        $this->assertResponseIsSuccessful();

        // 2. Soumission de la catégorie racine "Mode"
        $form = $crawler->selectButton('Enregistrer la catégorie')->form();
        $form['category[name]'] = 'Mode Homme Functional';
        $form['category[description]'] = 'Rayon masculin';
        $form['category[active]'] = '1';
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/categories');
        $this->client->followRedirect();

        /** @var Category|null $rootCategory */
        $rootCategory = $this->em->getRepository(Category::class)->findOneBy(['name' => 'Mode Homme Functional']);
        $this->assertNotNull($rootCategory);
        $this->assertNull($rootCategory->getParent());

        // 3. Création d'une sous-catégorie rattachée (Niveau 2)
        $crawler2 = $this->client->request('GET', '/admin/categories/new');
        $form2 = $crawler2->selectButton('Enregistrer la catégorie')->form();
        $form2['category[name]'] = 'Pantalons Functional';
        $form2['category[parent]'] = (string) $rootCategory->getId();
        $form2['category[active]'] = '1';
        $this->client->submit($form2);

        $this->assertResponseRedirects('/admin/categories');
        $this->client->followRedirect();

        /** @var Category|null $childCategory */
        $childCategory = $this->em->getRepository(Category::class)->findOneBy(['name' => 'Pantalons Functional']);
        $this->assertNotNull($childCategory);
        $this->assertSame($rootCategory->getId(), $childCategory->getParent()->getId());

        // 4. Création d'un sous-niveau 3 (Jeans)
        $crawler3 = $this->client->request('GET', '/admin/categories/new');
        $form3 = $crawler3->selectButton('Enregistrer la catégorie')->form();
        $form3['category[name]'] = 'Jeans Slim Functional';
        $form3['category[parent]'] = (string) $childCategory->getId();
        $form3['category[active]'] = '1';
        $this->client->submit($form3);

        $this->assertResponseRedirects('/admin/categories');
        $this->client->followRedirect();

        /** @var Category|null $subChildCategory */
        $subChildCategory = $this->em->getRepository(Category::class)->findOneBy(['name' => 'Jeans Slim Functional']);
        $this->assertNotNull($subChildCategory);
        $this->assertSame($childCategory->getId(), $subChildCategory->getParent()->getId());
        $this->assertSame($rootCategory->getId(), $subChildCategory->getParent()->getParent()->getId());
    }

    public function testDeleteCategoryWithoutCsrfFails(): void
    {
        $category = new Category();
        $category->setName('Catégorie Suppression Test');
        $this->em->persist($category);
        $this->em->flush();

        // Envoi POST sans jeton CSRF valide
        $this->client->request('POST', '/admin/categories/' . $category->getId(), [
            '_token' => 'invalid_csrf_token',
        ]);

        $this->assertResponseRedirects('/admin/categories');
        $this->client->followRedirect();

        // La catégorie doit toujours exister
        $this->assertNotNull($this->em->getRepository(Category::class)->find($category->getId()));
    }
}
