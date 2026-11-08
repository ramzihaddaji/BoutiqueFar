<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Demande\CreateDemandeDTO;
use App\Entity\Article;
use App\Exception\DemandeBusinessException;
use App\Form\Demande\DemandeType;
use App\Repository\ArticleRepository;
use App\Service\Demande\DemandeService;
use App\Service\WhatsApp\WhatsAppService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/demande', name: 'public_demande_')]
class DemandeController extends AbstractController
{
    /**
     * Affiche et traite le formulaire de demande de disponibilité pour un article donné.
     */
    #[Route('/article/{id}', name: 'create', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function create(
        int $id,
        Request $request,
        ArticleRepository $articleRepository,
        DemandeService $demandeService,
        WhatsAppService $whatsAppService
    ): Response {
        /** @var Article|null $article */
        $article = $articleRepository->findAvailableById($id);

        if ($article === null) {
            throw new NotFoundHttpException('Cet article est introuvable ou indisponible.');
        }

        // Protection basique contre le spam/inondation (1 demande par minute et par session/article)
        $session = $request->getSession();
        $lastRequestTime = $session->get('last_demande_time_' . $article->getId());
        $now = time();

        if (is_int($lastRequestTime) && ($now - $lastRequestTime) < 30) {
            $this->addFlash('warning', 'Veuillez patienter quelques instants avant de renouveler votre demande.');

            return $this->redirectToRoute('public_article_show', ['id' => $article->getId()]);
        }

        $dto = new CreateDemandeDTO();
        // L'article est assigné côté serveur en toute sécurité
        $dto->article = $article;

        $form = $this->createForm(DemandeType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Enregistrement de la demande en base
                $demande = $demandeService->create($dto);

                // Enregistrement du timestamp de dernière soumission pour la session
                $session->set('last_demande_time_' . $article->getId(), $now);

                // Génération du lien WhatsApp
                $whatsAppUrl = $whatsAppService->generateUrl($demande);

                // Redirection vers une page de confirmation avec bouton explicite vers WhatsApp
                return $this->render('public/demande/confirmation.html.twig', [
                    'demande' => $demande,
                    'article' => $article,
                    'whatsapp_url' => $whatsAppUrl,
                ]);
            } catch (DemandeBusinessException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('public/demande/form.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }
}

