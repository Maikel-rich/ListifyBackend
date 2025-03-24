<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Service\CategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/category')]
class CategoryController extends AbstractController
{
    private CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    #[Route('/getByUser', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getCategories(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $categories = $this->categoryService->getCategoriesByUser($user);

        $categoryData = array_map(fn($category) => [
            'id' => $category->getId(),
            'name' => $category->getName(),
        ], $categories);

        return new JsonResponse($categoryData, 200);
    }

    #[Route('/create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function createCategory(Request $request, CategoryRepository $categoryRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['name'])) {
            return new JsonResponse(['error' => 'Missing category name'], 400);
        }

        $existingCategory = $categoryRepository->findOneBy(['name' => $data['name'], 'user' => $user]);
        if ($existingCategory) {
            return new JsonResponse(['error' => 'Category name already exists'], 400);
        }

        $category = $this->categoryService->createCategory($data['name'], $user);

        return new JsonResponse([
            'id' => $category->getId(),
            'name' => $category->getName()
        ], 201);
    }

    #[Route('/edit/{id}', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function editCategory(int $id, Request $request, CategoryRepository $categoryRepository, CategoryService $categoryService): JsonResponse
    {
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        if (!isset($data['name'])) {
            return new JsonResponse(['error' => 'Data missing'], Response::HTTP_BAD_REQUEST);
        }

        $category = $categoryRepository->find($id);
        if (!$category) {
            return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('CATEGORY_EDIT', $category);

        $existingCategory = $categoryRepository->findOneBy(['name' => $data['name'], 'user' => $user]);
        if ($existingCategory && $existingCategory->getId() !== $category->getId()) {
            return new JsonResponse(['error' => 'Category name already exists'], Response::HTTP_BAD_REQUEST);
        }

        $updatedCategory = $categoryService->editCategory($category, $data['name']);

        return $this->json([
            'message' => 'Categoría actualizada con éxito',
            'category' => [
                'id' => $updatedCategory->getId(),
                'name' => $updatedCategory->getName(),
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteCategory(int $id, CategoryRepository $categoryRepository, CategoryService $categoryService): JsonResponse
    {
        $category = $categoryRepository->find($id);

        if (!$category) {
            return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('CATEGORY_DELETE', $category);

        $user = $this->getUser();
        if ($category->getUser() !== $user) {
            return new JsonResponse(['error' => 'Category not allowed'], Response::HTTP_FORBIDDEN);
        }

        $categoryService->deleteCategory($category);

        return new JsonResponse([
            'message' => 'Categoría eliminada con éxito'
        ], Response::HTTP_OK);
    }
}
