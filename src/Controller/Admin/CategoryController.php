<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\Category\CreateCategoryDTO;
use App\DTO\Category\UpdateCategoryDTO;
use App\Entity\Category;
use App\Exception\CategoryBusinessException;
use App\Form\Category\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\Category\CategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories', name: 'admin_category_')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
#[Route('', name: 'index', methods: ['GET'])]
public function index(CategoryRepository $categoryRepository): Response
{
    return $this->render('admin/category/index.html.twig', [
        'categories' => $categoryRepository->findCategoryTree(),
    ]);
}

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, CategoryService $categoryService): Response
    {
        $dto = new CreateCategoryDTO();
        $form = $this->createForm(CategoryType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $category = $categoryService->create($dto);
                $this->addFlash('success', sprintf('La catégorie "%s" a été créée avec succès.', $category->getName()));

                return $this->redirectToRoute('admin_category_index', [], Response::HTTP_SEE_OTHER);
            } catch (CategoryBusinessException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('admin/category/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('admin/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, CategoryService $categoryService): Response
    {
        $dto = UpdateCategoryDTO::fromEntity($category);
        $form = $this->createForm(CategoryType::class, $dto, [
            'data_class' => UpdateCategoryDTO::class,
            'current_category_id' => $category->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $categoryService->update($category, $dto);
                $this->addFlash('success', sprintf('La catégorie "%s" a été mise à jour.', $category->getName()));

                return $this->redirectToRoute('admin_category_index', [], Response::HTTP_SEE_OTHER);
            } catch (CategoryBusinessException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, CategoryService $categoryService): Response
    {
        $submittedToken = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_category_' . $category->getId(), $submittedToken)) {
            try {
                $categoryName = $category->getName();
                $categoryService->delete($category);
                $this->addFlash('success', sprintf('La catégorie "%s" a été supprimée.', $categoryName));
            } catch (CategoryBusinessException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_category_index', [], Response::HTTP_SEE_OTHER);
    }
}

