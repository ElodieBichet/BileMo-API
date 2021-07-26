<?php

namespace App\Listener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionListener
{
    public function JsonifyException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        $status = $exception->getCode();
        $message = $exception->getMessage();

        if ($exception instanceof HttpException) {
            $status = $exception->getStatusCode();
        }

        $response = new JsonResponse([
            'status' => $status,
            'message' => $message
        ]);
        $response->setStatusCode($status);

        $event->setResponse($response);
    }
}
