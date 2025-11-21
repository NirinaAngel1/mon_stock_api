<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents():array
    {
        return [
            'kernel.exception'=>'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event):void
    {
        $exception = $event->getThrowable();

        // $statusCode = $exception instanceof HttpExceptionInterface
        // ? $exception->getStatusCode()
        // : 500;

        $statusCode = 500;
        $message = "Une erreur de serveur est survenue.";

        if($exception instanceof NotFoundHttpException){
            $statusCode = 404;

            $path = $event->getRequest()->getPathInfo();

            if(str_contains($path, '/api/categories')){
                $message = "Catégorie inexistante.";
            }else if(str_contains($path, '/api/products')){
                $message = "Produit non trouvé.";
            }else{
                $message = "Ressource introuvable.";
            }
        }

        $response = new JsonResponse([
            'status'=>'error',
            'code'=>$statusCode,
            'message'=>$exception->getMessage(),
        ], $statusCode);

        $event->setResponse($response);
    }
}