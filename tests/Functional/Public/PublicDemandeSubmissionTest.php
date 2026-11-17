<?php

declare(strict_types=1);

namespace App\Tests\Functional\Public;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Demande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicDemandeSubmissionTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testDemandeCreationAndWhatsAppRedirectPage(): void
    {
        // 1. Préparer un article disponible dans une catégorie active
        $category = new Category();
        $category->setName('Horlogerie Publique');
        $category->setActive(true);

        $article = new Article();
        $article->setName('Chronomètre Sport');
        $article->setPrice('149.00');
        $article->setAvailable(true);
        $article->setCategory($category);

        $this->em->persist($category);
        $this->em->persist($article);
        $this->em->flush();

        // 2. Accéder à la page de demande
        $crawler = $this->client->request('GET', '/demande/article/' . $article->getId());
        $this->assertResponseIsSuccessful();

        // 3. Remplir et soumettre le formulaire
        $form = $crawler->selectButton('Valider et continuer sur WhatsApp')->form([
            'demande[clientName]' => 'Julien Mercier',
            'demande[phone]' => '+33611223344',
            'demande[city]' => 'Nantes',
        ]);
        $this->client->submit($form);

        // 4. Vérifier que la réponse affiche la page de confirmation (avec le lien wa.me)
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Votre demande est enregistrée !');
        $this->assertSelectorTextContains('.btn-whatsapp', 'Ouvrir WhatsApp et envoyer le message');

        // 5. Vérifier que la demande est enregistrée dans la base de données
        /** @var Demande $demandeInDb */
        $demandeInDb = $this->em->getRepository(Demande::class)->findOneBy(['clientName' => 'Julien Mercier'], ['id' => 'DESC']);
        $this->assertNotNull($demandeInDb);
        $this->assertSame('+33611223344', $demandeInDb->getPhone());
        $this->assertSame('Nantes', $demandeInDb->getCity());
        $this->assertSame($article->getId(), $demandeInDb->getArticle()->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $demandeInDb->getCreatedAt());
    }

    public function testDemandeRefusedIfArticleUnavailable(): void
    {
        $category = new Category();
        $category->setName('Bagagerie');
        $category->setActive(true);

        $article = new Article();
        $article->setName('Valise Cabine Epuisée');
        $article->setPrice('89.00');
        $article->setAvailable(false); // Article indisponible !
        $article->setCategory($category);

        $this->em->persist($category);
        $this->em->persist($article);
        $this->em->flush();

        // Doit renvoyer une 404
        $this->client->request('GET', '/demande/article/' . $article->getId());
        $this->assertResponseStatusCodeSame(404);
    }
}

