<?php

namespace App\Controller;

use App\Entity\ShoppingList;
use App\Entity\User;
use App\Repository\ShoppingListRepository;
use App\Service\ShoppingListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/list')]
class ShoppingListController extends AbstractController
{
    private ShoppingListService $shoppingListService;

    public function __construct(ShoppingListService $shoppingListService)
    {
        $this->shoppingListService = $shoppingListService;
    }

    #[Route('/create', name: 'shopping_list_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function createList(Request $request, ShoppingListService $shoppingListService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return new JsonResponse(['error' => 'El nombre es obligatorio'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Usuario no válido'], Response::HTTP_UNAUTHORIZED);
        }

        $list = $shoppingListService->createList($data['name'], $user);

        return new JsonResponse([
            'id' => $list->getId(),
            'name' => $list->getName(),
            'status' => $list->getStatus()->value,
            'updatedAt' => $list->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'user' => $user->getUsername()
        ], Response::HTTP_CREATED);
    }

    #[Route('/edit', name: 'shopping_list_edit', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function editShoppingList(
        Request $request,
        ShoppingListService $shoppingListService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['id']) || empty($data['name'])) {
            return new JsonResponse(['error' => 'ID y nombre son obligatorios'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Usuario no válido'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $list = $shoppingListService->updateList($data['id'], $data['name'], $user);

            return new JsonResponse([
                'id' => $list->getId(),
                'new name' => $list->getName(),
                'updatedAt' => $list->getUpdatedAt()->format('Y-m-d H:i:s')
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/updateStatus', name: 'shopping_list_status', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateStatus(
        Request $request,
        ShoppingListService $shoppingListService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['id'])) {
            return new JsonResponse(['error' => 'ID es obligatorio'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Usuario no válido'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $updatedList = $shoppingListService->updateListStatus($data['id'], $user);

            return new JsonResponse([
                'id' => $updatedList->getId(),
                'status' => $updatedList->getStatus()->value,
                'updatedAt' => $updatedList->getUpdatedAt()?->format('Y-m-d H:i:s')
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/getList', name: 'shopping_list_by_user', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getListsByUser(ShoppingListService $shoppingListService): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Usuario no válido'], Response::HTTP_UNAUTHORIZED);
        }

        $lists = $shoppingListService->getListsByUser($user);

        return new JsonResponse(array_map(fn($list) => [
            'id' => $list->getId(),
            'name' => $list->getName(),
            'status' => $list->getStatus()->value,
            'updatedAt' => $list->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ], $lists), Response::HTTP_OK);
    }

}
