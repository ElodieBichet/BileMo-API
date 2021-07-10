<?php

namespace App\Security\Voter;

use App\Entity\User;
use PhpParser\Node\Stmt\Return_;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['USER_SEE', 'USER_DELETE', 'USER_EDIT', 'USER_ADD'])
            && $subject instanceof \App\Entity\User;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User */
        $user = $token->getUser();

        // if the user is anonymous or if related customer is not allowed, do not grant access
        if (!$user instanceof UserInterface || !$user->getCustomer()->isAllowed()) {
            return false;
        }

        // ROLE_ADMIN can do anything!
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Users can see, add or delete users from their own customer
        if (in_array($attribute, ['USER_SEE', 'USER_DELETE', 'USER_ADD'])) {
            return $user->getCustomer() === $subject->getCustomer();
        }

        if ($attribute === 'USER_EDIT') {
            return $user === $subject;
        }

        return false;
    }
}
