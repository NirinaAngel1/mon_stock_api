<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();

        /** @var \APP\Entity\User $user */
        $user = $event->getUser();

        if(!$user){
            return;
        }

        $data['user'] = [
            'id'=> $user->getId(),
            'email'=> $user->getEmail(),
            'roles'=> $user->getRoles(),
            'lastLogin'=> (new \DateTimeImmutable())->format('Y-m-d H:i:s'),

        ];

        $event->setData($data);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccess',
        ];
    }
}