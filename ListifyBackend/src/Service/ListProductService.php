<?php

namespace App\Service;

use App\Entity\ListProduct;
use App\Entity\Product;
use App\Entity\ShoppingList;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ListProductService
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function getUserOrFail(): \Symfony\Component\Security\Core\User\UserInterface
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedException('Usuario no autenticado.');
        }
        return $user;
    }

    public function getShoppingListOrFail(int $id, User $user): ShoppingList
    {
        $shoppingList = $this->entityManager->getRepository(ShoppingList::class)->find($id);
        if (!$shoppingList || $shoppingList->getUser() !== $user) {
            throw new AccessDeniedException('Lista no encontrada o no pertenece al usuario.');
        }
        return $shoppingList;
    }

    public function getProductOrFail(int $id, User $user): Product
    {
        $product = $this->entityManager->getRepository(Product::class)->find($id);
        if (!$product || $product->getUser() !== $user) {
            throw new AccessDeniedException('Producto no encontrado o no pertenece al usuario.');
        }
        return $product;
    }
    public function addProductToList(ShoppingList $shoppingList, Product $product, int $amount): ListProduct
    {
        $existingListProduct = $this->entityManager->getRepository(ListProduct::class)->findOneBy([
            'list' => $shoppingList,
            'product' => $product
        ]);

        if ($existingListProduct) {
            $existingListProduct->setAmount($existingListProduct->getAmount() + $amount);
        } else {
            $listProduct = new ListProduct();
            $listProduct->setList($shoppingList);
            $listProduct->setProduct($product);
            $listProduct->setAmount($amount);

            $this->entityManager->persist($listProduct);
            $existingListProduct = $listProduct;
        }

        $this->entityManager->flush();
        return $existingListProduct;
    }

    public function removeProductFromList(ShoppingList $shoppingList, Product $product): void
    {
        $listProduct = $this->entityManager->getRepository(ListProduct::class)->findOneBy([
            'list' => $shoppingList,
            'product' => $product
        ]);

        if (!$listProduct) {
            throw new NotFoundHttpException('El producto no está en la lista.');
        }

        $this->entityManager->remove($listProduct);
        $this->entityManager->flush();
    }

    public function getProductsByList(ShoppingList $shoppingList): array
    {
        return $this->entityManager->getRepository(ListProduct::class)->findBy([
            'list' => $shoppingList
        ]);
    }

    public function updateProductAmount(ShoppingList $shoppingList, Product $product, int $amount): void
    {
        $listProduct = $this->entityManager->getRepository(ListProduct::class)->findOneBy([
            'list' => $shoppingList,
            'product' => $product
        ]);

        if (!$listProduct) {
            throw new NotFoundHttpException('El producto no está en la lista.');
        }

        $listProduct->setAmount($amount);
        $this->entityManager->flush();
    }
}
