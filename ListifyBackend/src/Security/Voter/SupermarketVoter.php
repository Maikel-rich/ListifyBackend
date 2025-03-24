<?php

namespace App\Security\Voter;

use App\Entity\Supermarket;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SupermarketVoter extends Voter
{
    public const EDIT = 'SUPERMARKET_EDIT';
    public const DELETE = 'SUPERMARKET_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Supermarket;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Supermarket $supermarket */
        $supermarket = $subject;

        return $supermarket->getUser() === $user;
    }
}
