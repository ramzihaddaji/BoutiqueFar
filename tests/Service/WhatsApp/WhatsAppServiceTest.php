<?php

declare(strict_types=1);

namespace App\Tests\Service\WhatsApp;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Demande;
use App\Service\WhatsApp\WhatsAppService;
use PHPUnit\Framework\TestCase;

class WhatsAppServiceTest extends TestCase
{
    private WhatsAppService $service;

    protected function setUp(): void
    {
        $this->service = new WhatsAppService('+33 6 12 34 56 78');
    }

    public function testSanitizePhoneNumber(): void
    {
        $this->assertSame('33612345678', $this->service->sanitizePhoneNumber('+33 6 12 34 56 78'));
        $this->assertSame('33612345678', $this->service->sanitizePhoneNumber('+33-6-12-34-56-78'));
    }

    public function testBuildMessageContainsExpectedFormat(): void
    {
        $category = (new Category())->setName('Accessoires')->setActive(true);
        $article = (new Article())
            ->setName('Sac à dos cuir')
            ->setPrice('79.99')
            ->setCategory($category);

        $demande = (new Demande())
            ->setClientName('Thomas Dubois')
            ->setPhone('+33698765432')
            ->setCity('Bordeaux')
            ->setArticle($article);

        $message = $this->service->buildMessage($demande);

        $this->assertStringContainsString('Bonjour, nouvelle demande !', $message);
        $this->assertStringContainsString('Client : Thomas Dubois', $message);
        $this->assertStringContainsString('Téléphone : +33698765432', $message);
        $this->assertStringContainsString('Ville : Bordeaux', $message);
        $this->assertStringContainsString('Article : Sac à dos cuir', $message);
        $this->assertStringContainsString('Prix : 79.99 €', $message);
        $this->assertStringContainsString('Est-ce que cet article est disponible ?', $message);
    }

    public function testGenerateUrl(): void
    {
        $category = (new Category())->setName('Mode')->setActive(true);
        $article = (new Article())
            ->setName('Veste')
            ->setPrice('120.00')
            ->setCategory($category);

        $demande = (new Demande())
            ->setClientName('Alice')
            ->setPhone('0601020304')
            ->setCity('Paris')
            ->setArticle($article);

        $url = $this->service->generateUrl($demande);

        $this->assertStringStartsWith('https://wa.me/33612345678?text=', $url);
        // Doit être correctement encodé en URL
        $this->assertStringNotContainsString("\n", $url);
        $this->assertStringContainsString(urlencode('Bonjour, nouvelle demande !'), $url);
    }
}
