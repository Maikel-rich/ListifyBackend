<?php

namespace App\Controller;

use App\Service\ListProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/list-product')]
class ListProductController extends AbstractController
{
    private ListProductService $listProductService;

    public function __construct(ListProductService $listProductService)
    {
        $this->listProductService = $listProductService;
    }

    #[Route('/add', name: 'add_product_to_list', methods: ['POST'])]
    public function addProductToList(Request $request): JsonResponse
    {
        $user = $this->listProductService->getUserOrFail();
        $data = json_decode($request->getContent(), true);

        $shoppingList = $this->listProductService->getShoppingListOrFail($data['shoppingListId'], $user);
        $product = $this->listProductService->getProductOrFail($data['productId'], $user);
        $amount = $data['amount'] ?? 1;

        $listProduct = $this->listProductService->addProductToList($shoppingList, $product, $amount);

        return $this->json([
            'message' => 'Producto agregado a la lista',
            'productId' => $listProduct->getProduct()->getId(),
            'amount' => $listProduct->getAmount(),
        ]);
    }

    #[Route('/remove', name: 'remove_product_from_list', methods: ['DELETE'])]
    public function removeProductFromList(Request $request): JsonResponse
    {
        $user = $this->listProductService->getUserOrFail();
        $data = json_decode($request->getContent(), true);

        $shoppingList = $this->listProductService->getShoppingListOrFail($data['shoppingListId'], $user);
        $product = $this->listProductService->getProductOrFail($data['productId'], $user);

        $this->listProductService->removeProductFromList($shoppingList, $product);

        return $this->json(['message' => 'Producto eliminado de la lista']);
    }

    #[Route('/list/{id}', name: 'get_products_by_list', methods: ['GET'])]
    public function getProductsByList(int $id): JsonResponse
    {
        $user = $this->listProductService->getUserOrFail();
        $shoppingList = $this->listProductService->getShoppingListOrFail($id, $user);

        $products = $this->listProductService->getProductsByList($shoppingList);

        $response = array_map(fn($lp) => [
            'productId' => $lp->getProduct()->getId(),
            'name' => $lp->getProduct()->getName(),
            'amount' => $lp->getAmount()
        ], $products);

        return $this->json($response);
    }

    #[Route('/update-amount', name: 'update_product_amount', methods: ['PATCH'])]
    public function updateProductAmount(Request $request): JsonResponse
    {
        $user = $this->listProductService->getUserOrFail();
        $data = json_decode($request->getContent(), true);

        $shoppingList = $this->listProductService->getShoppingListOrFail($data['shoppingListId'], $user);
        $product = $this->listProductService->getProductOrFail($data['productId'], $user);
        $amount = $data['amount'] ?? 1;

        $this->listProductService->updateProductAmount($shoppingList, $product, $amount);

        return $this->json(['message' => 'Cantidad de producto actualizada']);
    }
}