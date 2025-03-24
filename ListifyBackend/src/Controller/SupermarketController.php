<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SupermarketRepository;
use App\Service\SupermarketService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/supermarket')]
class SupermarketController extends AbstractController
{
    private SupermarketService $supermarketService;

    public function __construct(SupermarketService $supermarketService)
    {
        $this->supermarketService = $supermarketService;
    }

    #[Route('/getByUser', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getSupermarkets(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $supermarkets = $this->supermarketService->getSupermarketsByUser($user);

        $supermarketData = array_map(function ($supermarket) {
            return [
                'id' => $supermarket->getId(),
                'name' => $supermarket->getName(),
            ];
        }, $supermarkets);

        return new JsonResponse($supermarketData, 200);
    }

    #[Route('/create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function createSupermarket(Request $request, SupermarketRepository $supermarketRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['name'])) {
            return new JsonResponse(['error' => 'Name is required'], 400);
        }

        $existingSupermarket = $supermarketRepository->findOneBy(['name' => $data['name'], 'user' => $user]);
        if ($existingSupermarket) {
            return new JsonResponse(['error' => 'Supermarket name already exists'], 400);
        }

        $supermarket = $this->supermarketService->createSupermarket($data['name'], $user);

        return new JsonResponse([
            'id' => $supermarket->getId(),
            'name' => $supermarket->getName()
        ], 201);
    }

    #[Route('/edit/{id}', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function editSupermarket(int $id, Request $request, SupermarketRepository $supermarketRepository, SupermarketService $supermarketService): JsonResponse
    {
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        if (!isset($data['name'])) {
            return new JsonResponse(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        $supermarket = $supermarketRepository->find($id);
        if (!$supermarket) {
            return new JsonResponse(['error' => 'Supermarket not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('SUPERMARKET_EDIT', $supermarket);

        $existingSupermarket = $supermarketRepository->findOneBy(['name' => $data['name'], 'user' => $user]);
        if ($existingSupermarket && $existingSupermarket->getId() !== $supermarket->getId()) {
            return new JsonResponse(['error' => 'Supermarket name already exists'], Response::HTTP_BAD_REQUEST);
        }

        $updatedSupermarket = $supermarketService->editSupermarket($supermarket, $data['name']);

        return $this->json([
            'message' => 'Supermarket actualizado correctamente',
            'supermarket' => [
                'id' => $updatedSupermarket->getId(),
                'name' => $updatedSupermarket->getName()
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteSupermarket(int $id, SupermarketRepository $supermarketRepository, SupermarketService $supermarketService): JsonResponse
    {
        $supermarket = $supermarketRepository->find($id);

        if (!$supermarket) {
            return new JsonResponse(['error' => 'Supermarket not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('SUPERMARKET_DELETE', $supermarket);

        $user = $this->getUser();

        if ($supermarket->getUser() !== $user) {
            return new JsonResponse(['error' => 'Supermarket not allowed'], Response::HTTP_FORBIDDEN);
        }

        $supermarketService->deleteSupermarket($supermarket);

        return new JsonResponse([
            'message' => 'Supermarket eliminado correctamente'
        ], Response::HTTP_OK);
    }
}