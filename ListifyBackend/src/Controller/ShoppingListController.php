<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ShoppingListService;
use Psr\Log\LoggerInterface;
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
    private LoggerInterface $logger;

    public function __construct(ShoppingListService $shoppingListService, LoggerInterface $logger)
    {
        $this->shoppingListService = $shoppingListService;
        $this->logger = $logger;
    }

    private function getAuthenticatedUser(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }

    #[Route('/create', name: 'shopping_list_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function createList(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['name'])) {
            return new JsonResponse(['error' => 'El nombre es obligatorio'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no válido'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $list = $this->shoppingListService->createList($data['name'], $user);
            return new JsonResponse([
                'id' => $list->getId(),
                'name' => $list->getName(),
                'status' => $list->getStatus()->value,
                'updatedAt' => $list->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'user' => $user->getUsername()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Error al crear la lista: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Error interno del servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/edit/{id}', name: 'shopping_list_edit', methods: ['PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function editShoppingList(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['name'])) {
            return new JsonResponse(['error' => 'El nombre es obligatorio'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no válido'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $list = $this->shoppingListService->updateList($id, $data['name'], $user);
            return new JsonResponse([
                'id' => $list->getId(),
                'newName' => $list->getName(),
                'updatedAt' => $list->getUpdatedAt()->format('Y-m-d H:i:s')
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Error al editar la lista: ' . $e->getMessage());
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/updateStatus/{id}', name: 'shopping_list_status', methods: ['PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateStatus(int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no válido'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $updatedList = $this->shoppingListService->updateListStatus($id, $user);
            return new JsonResponse([
                'id' => $updatedList->getId(),
                'status' => $updatedList->getStatus()->value,
                'updatedAt' => $updatedList->getUpdatedAt()?->format('Y-m-d H:i:s')
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Error al actualizar estado: ' . $e->getMessage());
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/getListsByUser', name: 'shopping_list_by_user', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getListsByUser(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no válido'], Response::HTTP_UNAUTHORIZED);
        }

        $lists = $this->shoppingListService->getListsByUser($user);
        return new JsonResponse(array_map(fn($list) => [
            'id' => $list->getId(),
            'name' => $list->getName(),
            'status' => $list->getStatus()->value,
            'updatedAt' => $list->getUpdatedAt()?->format('Y-m-d H:i:s')
        ], $lists), Response::HTTP_OK);
    }
}
