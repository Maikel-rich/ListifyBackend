<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CategoryService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getCategoriesByUser(User $user): array
    {
        return $this->entityManager->getRepository(Category::class)->findBy([
            'user' => $user
        ]);
    }

    public function createCategory(string $name, User $user): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setUser($user);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    public function editCategory(Category $category, string $newName): Category
    {
        $category->setName($newName);

        $this->entityManager->flush();

        return $category;
    }

    public function deleteCategory(Category $category): Category
    {
        $this->entityManager->remove($category);

        $this->entityManager->flush();

        return $category;
    }
}