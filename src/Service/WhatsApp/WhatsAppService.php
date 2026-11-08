<?php

declare(strict_types=1);

namespace App\Service\WhatsApp;

use App\Entity\Demande;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class WhatsAppService
{
    public function __construct(
        #[Autowire('%app.whatsapp_admin_phone%')]
        private readonly string $adminPhoneNumber,
    ) {
    }

    /**
     * Nettoie le numéro de téléphone pour conserver uniquement les chiffres (format international sans '+').
     */
    public function sanitizePhoneNumber(string $phoneNumber): string
    {
        return preg_replace('/[^0-9]/', '', $phoneNumber) ?? '';
    }

    /**
     * Construit le texte prérempli de la demande de disponibilité.
     */
    public function buildMessage(Demande $demande): string
    {
        $article = $demande->getArticle();

        return sprintf(
            "Bonjour, nouvelle demande !\n\n" .
            "Client : %s\n" .
            "Téléphone : %s\n" .
            "Ville : %s\n" .
            "Article : %s\n" .
            "Prix : %s €\n" .
            "Référence : #%d\n\n" .
            "Est-ce que cet article est disponible ?",
            $demande->getClientName(),
            $demande->getPhone(),
            $demande->getCity(),
            $article->getName(),
            $article->getPrice(),
            $article->getId() ?? 0
        );
    }

    /**
     * Génère l'URL officielle wa.me redirigeant vers WhatsApp avec le message prérempli encodé.
     */
    public function generateUrl(Demande $demande): string
    {
        $cleanPhone = $this->sanitizePhoneNumber($this->adminPhoneNumber);
        $message = $this->buildMessage($demande);

        return sprintf(
            'https://wa.me/%s?text=%s',
            $cleanPhone,
            urlencode($message)
        );
    }
}

