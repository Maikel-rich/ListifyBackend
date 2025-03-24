<?php

namespace App\Service;

use App\Entity\Supermarket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SupermarketService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getSupermarketsByUser(User $user): array
    {
        return $this->entityManager->getRepository(Supermarket::class)->findBy([
            'user' => $user
        ]);
    }

    public function createSupermarket(string $name, User $user): Supermarket
    {
        $supermarket = new Supermarket();
        $supermarket->setName($name);
        $supermarket->setUser($user);

        $this->entityManager->persist($supermarket);
        $this->entityManager->flush();

        return $supermarket;
    }

    public function editSupermarket(Supermarket $supermarket, string $newName): Supermarket
    {
        $supermarket->setName($newName);

        $this->entityManager->flush();

        return $supermarket;
    }

    public function deleteSupermarket(Supermarket $supermarket): Supermarket
    {
        $this->entityManager->remove($supermarket);

        $this->entityManager->flush();

        return $supermarket;
    }
}
