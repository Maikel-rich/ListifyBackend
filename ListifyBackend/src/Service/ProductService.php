<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Supermarket;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\SupermarketRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class ProductService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    private function validateCategoryAndSupermarket(
        $data,
        CategoryRepository $categoryRepository,
        SupermarketRepository $supermarketRepository,
        User $user
    ): array {
        $category = isset($data['category']) ? $categoryRepository->find($data['category']) : null;
        $supermarket = isset($data['supermarket']) ? $supermarketRepository->find($data['supermarket']) : null;

        if ($category && $category->getUser() !== $user) {
            throw new InvalidArgumentException('La categoría no pertenece al usuario.');
        }

        if ($supermarket && $supermarket->getUser() !== $user) {
            throw new InvalidArgumentException('El supermercado no pertenece al usuario.');
        }

        return [$category, $supermarket];
    }

    public function createProduct(
        string $name,
        string $description,
        float $price,
        Category $category,
        Supermarket $supermarket,
        User $user
    ): Product {
        if (empty($name)) {
            throw new InvalidArgumentException('El nombre del producto no puede estar vacío.');
        }

        if ($price < 0) {
            throw new InvalidArgumentException('El precio no puede ser negativo.');
        }

        if ($category->getUser() !== $user) {
            throw new InvalidArgumentException('No puedes asignar una categoría que no te pertenece.');
        }

        if ($supermarket->getUser() !== $user) {
            throw new InvalidArgumentException('No puedes asignar un supermercado que no te pertenece.');
        }

        $product = new Product();
        $product->setName($name);
        $product->setDescription($description);
        $product->setPrice($price);
        $product->setCategory($category);
        $product->setSupermarket($supermarket);
        $product->setUser($user);

        try {
            $this->entityManager->persist($product);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException('Error al guardar el producto: ' . $e->getMessage());
        }

        return $product;
    }

    public function editProduct(
        Product $product,
        ?string $name,
        ?string $description,
        ?float $price,
        ?Category $category,
        ?Supermarket $supermarket,
        User $user
    ): Product {
        if ($product->getUser() !== $user) {
            throw new \RuntimeException('No tienes permiso para editar este producto.');
        }

        if ($category && $category->getUser() !== $user) {
            throw new InvalidArgumentException('No puedes asignar una categoría que no te pertenece.');
        }

        if ($supermarket && $supermarket->getUser() !== $user) {
            throw new InvalidArgumentException('No puedes asignar un supermercado que no te pertenece.');
        }

        if (!empty($name)) {
            $product->setName($name);
        }

        if (!empty($description)) {
            $product->setDescription($description);
        }

        if (!is_null($price) && $price >= 0) {
            $product->setPrice($price);
        }

        if ($category) {
            $product->setCategory($category);
        }

        if ($supermarket) {
            $product->setSupermarket($supermarket);
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException('Error al actualizar el producto: ' . $e->getMessage());
        }

        return $product;
    }

    public function deleteProduct(Product $product, User $user): void
    {
        if ($product->getUser() !== $user) {
            throw new \RuntimeException('No tienes permiso para eliminar este producto.');
        }

        try {
            $this->entityManager->remove($product);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException('Error al eliminar el producto: ' . $e->getMessage());
        }
    }

    public function getAllByUser(User $user): array
    {
        return $this->entityManager->getRepository(Product::class)->findBy(['user' => $user]);
    }
}