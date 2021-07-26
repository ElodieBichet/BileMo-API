<?php

namespace App\Listener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionListener
{
    public function JsonifyException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        $status = (method_exists($exception, 'getStatusCode')) ? $exception->getStatusCode() : $exception->getCode();
        if ($status === 0) $status = 500;

        $message = $exception->getMessage();

        $response = new JsonResponse([
            'status' => $status,
            'message' => $message
        ]);
        $response->setStatusCode($status);

        $event->setResponse($response);
    }
}
