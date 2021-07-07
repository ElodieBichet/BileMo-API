<?php

namespace App\Controller;

use Exception;
use Throwable;
use App\Entity\User;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\TryCatch;
use App\Repository\UserRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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

        /* @var Customer */
        $customer = $this->getUser()->getCustomer();
        $context = SerializationContext::create()->setGroups(array("user:list"));

        $data = $this->serializer->serialize($customer->getUsers(), 'json', $context);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/users/{id}", name="api_user_details", methods={"GET"})
     */
    public function details(User $user = null)
    {
        // if user is not found
        if (!$user) {
            return $this->json([
                'status' => JsonResponse::HTTP_NOT_FOUND,
                'message' => "Aucun utilisateur trouvé avec cet identifiant"
            ], JsonResponse::HTTP_NOT_FOUND);
        }
        // if user can't be seen by the current user
        if (!$this->isGranted("USER_SEE", $user)) {
            return $this->json([
                'status' => JsonResponse::HTTP_UNAUTHORIZED,
                'message' => "Vous n'êtes pas autorisé à effectuer cette requête"
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $context = SerializationContext::create()->setGroups(array("user:details"));
        $data = $this->serializer->serialize($user, 'json', $context);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/users", name="api_user_add", methods={"POST"})
     */
    public function add(Request $request, EntityManagerInterface $entityManager)
    {
        // if current user can't add a new user
        if (!$this->isGranted("USER_ADD", $this->getUser())) {
            return $this->json([
                'status' => JsonResponse::HTTP_UNAUTHORIZED,
                'message' => "Vous n'êtes pas autorisé à effectuer cette requête"
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            /** @var User */
            $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
            $user->setCustomer($this->getUser()->getCustomer());

            $errors = $this->validator->validate($user);

            if (count($errors) > 0) {
                return new JsonResponse($this->serializer->serialize($errors, 'json'), 400, [], true);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $context = SerializationContext::create()->setGroups(array("user:details"));
            $data = $this->serializer->serialize($user, 'json', $context);

            return new JsonResponse($data, 201, [], true);
        } catch (Throwable $th) {
            if ($th instanceof UniqueConstraintViolationException) {
                return $this->json([
                    'status' => 400,
                    'message' => "Violation d'une contrainte d'unicité : cet utilisateur existe déjà"
                ], 400);
            }
            return $this->json([
                'status' => 400,
                'message' => $th->getMessage()
            ], 400);
        }
    }

    /**
     * @Route("/api/users/{id}", name="api_user_delete", methods={"DELETE"})
     */
    public function remove(User $user = null, EntityManagerInterface $entityManager)
    {
        // if user is not found
        if (!$user) {
            return $this->json([
                'status' => JsonResponse::HTTP_NOT_FOUND,
                'message' => "Aucun utilisateur trouvé avec cet identifiant"
            ], JsonResponse::HTTP_NOT_FOUND);
        }
        // if user can't be deleted by the current user
        if (!$this->isGranted("USER_DELETE", $user)) {
            return $this->json([
                'status' => JsonResponse::HTTP_UNAUTHORIZED,
                'message' => "Vous n'êtes pas autorisé à effectuer cette requête"
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
