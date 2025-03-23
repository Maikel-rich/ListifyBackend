<?php

namespace App\Entity;

use App\Enum\ListRoleEnum;
use App\Repository\SharedListRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedListRepository::class)]
class SharedList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "sharedLists")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: ShoppingList::class, inversedBy: "sharedLists")]
    #[ORM\JoinColumn(name: "shopping_list_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?ShoppingList $shoppingList = null;

    #[ORM\Column]
    private ?int $role = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRole(): ?ListRoleEnum
    {
        return $this->role !== null ? ListRoleEnum::tryFrom($this->role) : null;
    }

    public function setRole(ListRoleEnum $role): self
    {
        $this->role = $role->value;
        return $this;
    }

    public function getRoles(): array
    {
        return [$this->role !== null ? ListRoleEnum::tryFrom($this->role)->name : 'ROLE_LIST_USER'];
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getShoppingList(): ?ShoppingList
    {
        return $this->shoppingList;
    }

    public function setShoppingList(ShoppingList $shoppingList): static
    {
        $this->shoppingList = $shoppingList;
        return $this;
    }
}
