<?php

namespace App\Service;

use App\Entity\ShoppingList;
use App\Entity\User;
use App\Enum\ListStatusEnum;
use App\Repository\ShoppingListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ShoppingListService
{
    private EntityManagerInterface $entityManager;
    private ShoppingListRepository $listRepository;

    public function __construct(EntityManagerInterface $entityManager, ShoppingListRepository $listRepository)
    {
        $this->entityManager = $entityManager;
        $this->listRepository = $listRepository;
    }

    public function createList(string $name, User $user): ShoppingList
    {
        $list = new ShoppingList();
        $list->setName($name);
        $list->setStatus(ListStatusEnum::IN_PROCESS);
        $list->setUser($user);
        $list->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($list);
        $this->entityManager->flush();

        return $list;
    }

    /**
     * @throws \Exception
     */
    public function updateList(int $id, string $name, User $user): ShoppingList
    {
        $list = $this->listRepository->find($id);

        if (!$list) {
            throw new \Exception('Lista no encontrada');
        }

        if ($list->getUser() !== $user) {
            throw new \Exception('No tienes permisos para editar esta lista');
        }

        $list->setName($name);
        $list->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $list;
    }

    /**
     * @throws \Exception
     */
    public function updateListStatus(int $id, User $user): ShoppingList
    {
        $list = $this->listRepository->find($id);

        if (!$list) {
            throw new \Exception('Lista no encontrada');
        }

        if ($list->getUser() !== $user) {
            throw new \Exception('No tienes permisos para modificar esta lista');
        }

        $newStatus = ($list->getStatus() === ListStatusEnum::IN_PROCESS)
            ? ListStatusEnum::DONE
            : ListStatusEnum::IN_PROCESS;

        $list->setStatus($newStatus);
        $list->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $list;
    }

    public function getListsByUser(User $user): array
    {
        return $this->listRepository->findBy(['user' => $user]);
    }
}
