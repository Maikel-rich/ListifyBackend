<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SupermarketRepository;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/product')]
class ProductController extends AbstractController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    private function validateCategoryAndSupermarket($data, CategoryRepository $categoryRepository, SupermarketRepository $supermarketRepository): array
    {
        $category = isset($data['category']) ? $categoryRepository->find($data['category']) : null;
        $supermarket = isset($data['supermarket']) ? $supermarketRepository->find($data['supermarket']) : null;

        return [$category, $supermarket];
    }

    #[Route('/create', name: 'product_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function createProduct(
        Request $request,
        CategoryRepository $categoryRepository,
        SupermarketRepository $supermarketRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Usuario no válido'], Response::HTTP_UNAUTHORIZED);
        }

        if (empty($data['name']) || !isset($data['price']) || !is_numeric($data['price'])) {
            return new JsonResponse(['error' => 'Datos inválidos'], Response::HTTP_BAD_REQUEST);
        }

        [$category, $supermarket] = $this->validateCategoryAndSupermarket($data, $categoryRepository, $supermarketRepository);

        if (!$category || !$supermarket) {
            return new JsonResponse(['error' => 'Categoría o supermercado no encontrado'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $product = $this->productService->createProduct(
                $data['name'],
                $data['description'] ?? null,
                (float)$data['price'],
                $category,
                $supermarket,
                $user
            );

            return new JsonResponse(['message' => 'producto creado correctamente',
                'producto' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'price' => $product->getPrice(),
                    'category' => $category->getName(),
                    'supermarket' => $supermarket->getName(),
                ]], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/edit/{id}', name: 'product_edit', methods: ['PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function editProduct(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        SupermarketRepository $supermarketRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $product = $productRepository->find($id);
        if (!$product) {
            return new JsonResponse(['error' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('PRODUCT_EDIT', $product);

        [$category, $supermarket] = $this->validateCategoryAndSupermarket($data, $categoryRepository, $supermarketRepository);

        try {
            $updatedProduct = $this->productService->editProduct(
                $product,
                $data['name'] ?? null,
                $data['description'] ?? null,
                isset($data['price']) && is_numeric($data['price']) ? (float)$data['price'] : null,
                $category,
                $supermarket,
                $user
            );

            return new JsonResponse(['message' => 'Producto editado correctamente',
                'producto editado' => [
                    'id' => $updatedProduct->getId(),
                    'name' => $updatedProduct->getName(),
                    'description' => $updatedProduct->getDescription(),
                    'price' => $updatedProduct->getPrice(),
                    'category' => $updatedProduct->getCategory()->getName(),
                    'supermarket' => $updatedProduct->getSupermarket()->getName()
                ]], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/delete/{id}', name: 'product_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteProduct(int $id, ProductRepository $productRepository): JsonResponse
    {
        $user = $this->getUser();
        $product = $productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('PRODUCT_DELETE', $product);

        try {
            $this->productService->deleteProduct($product, $user);
            return new JsonResponse(['message' => 'Producto eliminado correctamente'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/user', name: 'product_user_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getAllByUser(): JsonResponse
    {
        $user = $this->getUser();
        $products = $this->productService->getAllByUser($user);

        $response = array_map(fn(Product $product) => [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'category' => $product->getCategory()->getName(),
            'supermarket' => $product->getSupermarket()->getName()
        ], $products);

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
