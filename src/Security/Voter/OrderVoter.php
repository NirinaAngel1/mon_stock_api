<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Order;

final class OrderVoter extends Voter
{
    public const VIEW = 'ORDER_VIEW';
    public const EDIT = 'ORDER_EDIT';
    public const VALIDATE = 'ORDER_VALIDATE';
    public const CANCEL = 'ORDER_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::EDIT,
            self::VIEW,
            self::VALIDATE,
            self::CANCEL,
        ])
            && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if(in_array('ROLE_ADMIN', $user->getRoles())){
            return true;
        }

        $order = $subject;

        if($order->getUserId() === $user){
            return true;
        }

        return false;
    }
}
