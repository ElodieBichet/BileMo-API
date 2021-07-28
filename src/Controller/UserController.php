<?php

namespace App\Controller;

use Throwable;
use JsonException;
use App\Entity\User;
use OpenApi\Annotations as OA;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    protected $serializer;
    protected $validator;
    protected $userRepository;
    protected $pagination;
    protected $hasher;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        PaginationService $pagination,
        UserPasswordHasherInterface $hasher
    ) {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->userRepository = $userRepository;
        $this->pagination = $pagination;
        $this->hasher = $hasher;
    }

    /**
     * @Route("/api/users", name="api_user_list", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns the list of users from the same customer"
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page number",
     *     @OA\Schema(type="int", default = "1")
     * )
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Number of items by page (0 for all items)",
     *     @OA\Schema(type="int", default = 5)
     * )
     * @OA\Parameter(
     *     name="orderby",
     *     in="query",
     *     description="Name of the property used to sort items",
     *     @OA\Schema(type="string", default = "lastName")
     * )
     * @OA\Parameter(
     *     name="inverse",
     *     in="query",
     *     description="Set to true (1) to sort with descending order, and to false (0) to sort with ascending order",
     *     @OA\Schema(type="boolean", default = false)
     * )
     */
    public function list(Request $request): JsonResponse
    {
        // GET only users related to the same customer as the current authenticated user
        /* @var Customer */
        $customer = $this->getUser()->getCustomer();
        $queryBuilder = $this->userRepository->createQueryBuilder('user')
            ->where("user.customer = " . $customer->getId());

        $data = $this->pagination->paginate($request, $queryBuilder);

        $context = SerializationContext::create()->setGroups(array("user:list"));
        $jsonData = $this->serializer->serialize($data, 'json', $context);

        return new JsonResponse($jsonData, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/users/{id}", name="api_user_details", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Returns a user"
     * )
     */
    public function details(User $user = null)
    {
        // check if user exists
        $this->checkUser($user);

        // if user can't be seen by the current user
        if (!$this->isGranted("USER_SEE", $user)) {
            throw new JsonException("Vous n'êtes pas autorisé à effectuer cette requête", JsonResponse::HTTP_UNAUTHORIZED);
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
            throw new JsonException("Vous n'êtes pas autorisé à effectuer cette requête", JsonResponse::HTTP_UNAUTHORIZED);
        }

        try {
            /** @var User */
            $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
            $user->setPassword($this->hasher->hashPassword($user, $user->getPassword()));
            $user->setCustomer($this->getUser()->getCustomer());

            $this->validateUser($user);

            $entityManager->persist($user);
            $entityManager->flush();

            $context = SerializationContext::create()->setGroups(array("user:details"));
            $data = $this->serializer->serialize($user, 'json', $context);

            return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            if ($th instanceof UniqueConstraintViolationException) {
                $message = "Violation d'une contrainte d'unicité : cet utilisateur existe déjà";
            }
            throw new JsonException($message, JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/users/{id}", name="api_user_update", methods={"PUT"})
     */
    public function update(User $user = null, Request $request, EntityManagerInterface $entityManager)
    {
        // check if user exists
        $this->checkUser($user);

        // if current user can't add a new user
        if (!$this->isGranted("USER_EDIT", $user)) {
            throw new JsonException("Vous n'êtes pas autorisé à effectuer cette requête", JsonResponse::HTTP_UNAUTHORIZED);
        }

        try {
            $updatedUser = $this->serializer->deserialize($request->getContent(), User::class, 'json');
            if ($updatedUser->getFirstName()) $user->setFirstName($updatedUser->getFirstName());
            if ($updatedUser->getLastName()) $user->setLastName($updatedUser->getLastName());
            if ($updatedUser->getEmail()) $user->setEmail($updatedUser->getEmail());
            if ($updatedUser->getPassword()) $user->setPassword($this->hasher->hashPassword($user, $updatedUser->getPassword()));

            // validate User
            $this->validateUser($user);

            $entityManager->flush();

            $context = SerializationContext::create()->setGroups(array("user:details"));
            $data = $this->serializer->serialize($user, 'json', $context);

            return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            if ($th instanceof UniqueConstraintViolationException) {
                $message = "Violation d'une contrainte d'unicité : cet utilisateur existe déjà";
            }
            throw new JsonException($message, JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/users/{id}", name="api_user_delete", methods={"DELETE"})
     */
    public function remove(User $user = null, EntityManagerInterface $entityManager)
    {
        // check if user exists
        $this->checkUser($user);

        // if user can't be deleted by the current user
        if (!$this->isGranted("USER_DELETE", $user)) {
            throw new JsonException("Vous n'êtes pas autorisé à effectuer cette requête", JsonResponse::HTTP_UNAUTHORIZED);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }

    /**
     * checkUser
     *
     * @param  mixed $user
     * @return void
     */
    protected function checkUser($user)
    {
        // if user is not found
        if (!$user || !($user instanceof User)) {
            throw new HttpException(JsonResponse::HTTP_NOT_FOUND, "Aucun utilisateur trouvé avec cet identifiant");
        }
    }

    /**
     * validateUser
     *
     * @param  mixed $user
     * @return mixed void|JsonResponse
     */
    protected function validateUser($user)
    {
        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {
            return new JsonResponse(
                $this->serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true
            );
        }
    }
}
