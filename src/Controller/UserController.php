<?php

namespace App\Controller;

use App\Repository\UserRepository;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    protected $serializer;
    protected $validator;
    protected $userRepository;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, UserRepository $userRepository)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/api/users", name="api_user_list", methods={"GET"})
     */
    public function list(): JsonResponse
    {
        // GET only users related to the same customer as the current authenticated user
        $users = $this->userRepository->findBy(["customer" => $this->getUser()->getCustomer()]);
        $context = SerializationContext::create()->setGroups(array("user:list"));

        $data = $this->serializer->serialize($users, 'json', $context);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }
}
