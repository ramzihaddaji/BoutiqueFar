<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\Article\CreateArticleDTO;
use App\DTO\Article\UpdateArticleDTO;
use App\Entity\Article;
use App\Exception\ArticleBusinessException;
use App\Form\Article\ArticleType;
use App\Repository\ArticleRepository;
use App\Service\Article\ArticleService;
use App\Service\Image\ImageStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/articles', name: 'admin_article_')]
#[IsGranted('ROLE_ADMIN')]
class ArticleController extends AbstractController
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('admin/article/index.html.twig', [
            'articles' => $articleRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ArticleService $articleService,
        ImageStorage $imageStorage
    ): Response {
        $dto = new CreateArticleDTO();
        $form = $this->createForm(ArticleType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFilename = null;
            /** @var UploadedFile|null $imageFile */
            $imageFile = $dto->imageFile;

            if ($imageFile instanceof UploadedFile) {
                $uploadedFilename = $this->handleImageUpload($imageFile, $imageStorage);
            }

            try {
                $article = $articleService->create($dto, $uploadedFilename);
                $this->addFlash('success', sprintf('L\'article "%s" a été créé avec succès.', $article->getName()));

                return $this->redirectToRoute('admin_article_index', [], Response::HTTP_SEE_OTHER);
            } catch (ArticleBusinessException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('admin/article/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('admin/article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Article $article,
        ArticleService $articleService,
        ImageStorage $imageStorage
    ): Response {
        $dto = UpdateArticleDTO::fromEntity($article);
        $form = $this->createForm(ArticleType::class, $dto, [
            'data_class' => UpdateArticleDTO::class,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFilename = $dto->existingImage;
            /** @var UploadedFile|null $imageFile */
            $imageFile = $dto->imageFile;

            if ($imageFile instanceof UploadedFile) {
                // En cas d'échec, on conserve l'image existante
                $uploadedFilename = $this->handleImageUpload($imageFile, $imageStorage) ?? $dto->existingImage;
            }

            try {
                $articleService->update($article, $dto, $uploadedFilename);
                $this->addFlash('success', sprintf('L\'article "%s" a été mis à jour.', $article->getName()));

                return $this->redirectToRoute('admin_article_index', [], Response::HTTP_SEE_OTHER);
            } catch (ArticleBusinessException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('admin/article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, ArticleService $articleService): Response
    {
        $submittedToken = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_article_' . $article->getId(), $submittedToken)) {
            try {
                $articleName = $article->getName();
                $articleService->delete($article);
                $this->addFlash('success', sprintf('L\'article "%s" a été supprimé.', $articleName));
            } catch (ArticleBusinessException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_article_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleImageUpload(UploadedFile $file, ImageStorage $storage): ?string
    {
        // 1. Vérification stricte du type MIME réel
        $mimeType = $file->getMimeType();
        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            $this->addFlash('danger', 'Type de fichier non autorisé. Formats acceptés : JPEG, PNG, WEBP.');

            return null;
        }

        // 2. Extension forcée d'après le type MIME validé + nom aléatoire
        $newFilename = sprintf('%s.%s', bin2hex(random_bytes(16)), self::ALLOWED_MIME_TYPES[$mimeType]);

        // 3. Stockage : Supabase Storage en production, public/uploads en local
        try {
            return $storage->store($file, $newFilename);
        } catch (\Throwable) {
            $this->addFlash('warning', 'Échec du téléversement de l\'image.');

            return null;
        }
    }
}
