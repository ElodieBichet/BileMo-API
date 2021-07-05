<?php

namespace App\Security\Voter;

use PhpParser\Node\Stmt\Return_;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['USER_SEE', 'USER_DELETE', 'USER_EDIT'])
            && $subject instanceof \App\Entity\User;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ROLE_ADMIN can do anything!
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'USER_SEE':
                // logic to determine if the user can SEE a user
                // return true if connected user has the same customer than the user to display
                return $user->getCustomer() === $subject->getCustomer();
                break;
            case 'USER_DELETE':
                // logic to determine if the user can DELETE a user
                // return true if user is the user to delete
                return $user->getCustomer() === $subject->getCustomer();
                break;
            case 'USER_EDIT':
                // logic to determine if the user can EDIT a user
                // return true if user is the user to edit
                return $user === $subject;
                break;
        }

        return false;
    }
}
