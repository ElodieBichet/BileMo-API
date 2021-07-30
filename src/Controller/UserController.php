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
     *     response=JsonResponse::HTTP_OK,
     *     description="Returns the list of your users"
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
     *     description="Number of items by page (0 to get all items)",
     *     @OA\Schema(type="int", default = 5)
     * )
     * @OA\Parameter(
     *     name="orderby",
     *     in="query",
     *     description="Name of the property used to sort items: id, username, first_name, last_name, email",
     *     @OA\Schema(type="string", default = "lastName")
     * )
     * @OA\Parameter(
     *     name="inverse",
     *     in="query",
     *     description="Set to true (1) to sort with descending order, and to false (0) to sort with ascending order",
     *     @OA\Schema(type="boolean", default = false)
     * )
     * @OA\Tag(name="Users")
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
     * @Route("/api/users/{id<\d+>}", name="api_user_details", methods={"GET"})
     * @OA\Response(
     *     response=JsonResponse::HTTP_OK,
     *     description="Returns a user"
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_UNAUTHORIZED,
     *     description="Unauthorized request"
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_NOT_FOUND,
     *     description="User not found"
     * )
     * @OA\Tag(name="Users")
     */
    public function details(User $user = null)
    {
        // check if user exists
        $this->checkUser($user);

        // if user can't be seen by the current user
        if (!$this->isGranted("USER_SEE", $user)) {
            throw new JsonException("You do not have the required rights to make this request", JsonResponse::HTTP_UNAUTHORIZED);
        }

        $context = SerializationContext::create()->setGroups(array("user:details"));
        $data = $this->serializer->serialize($user, 'json', $context);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/users", name="api_user_add", methods={"POST"})
     * @OA\RequestBody(
     *     description="The new user to create",
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="application/Json",
     *         @OA\Schema(
     *             type="object",
     *             @OA\Property(
     *                 property="username",
     *                 description="Username for user identification",
     *                 type="string"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 description="User's choosen password",
     *                 type="string"
     *             ),
     *             @OA\Property(
     *                 property="first_name",
     *                 description="User's first name",
     *                 type="string"
     *             ),
     *             @OA\Property(
     *                 property="last_name",
     *                 description="User's last name",
     *                 type="string"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 description="User's email address",
     *                 type="string"
     *             )
     *         )
     *     )
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_CREATED,
     *     description="Create a user and returns it"
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_BAD_REQUEST,
     *     description="Bad Json syntax or incorrect data"
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_UNAUTHORIZED,
     *     description="Unauthorized request"
     * )
     * @OA\Tag(name="Users")
     */
    public function add(Request $request, EntityManagerInterface $entityManager)
    {
        // if current user can't add a new user
        if (!$this->isGranted("USER_ADD", $this->getUser())) {
            throw new JsonException("You do not have the required rights to make this request", JsonResponse::HTTP_UNAUTHORIZED);
        }

        try {
            /** @var User */
            $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
            $user->setPassword($this->hasher->hashPassword($user, $user->getPassword()));
            $user->setCustomer($this->getUser()->getCustomer());

            $errors = $this->validateUser($user);
            if ($errors) {
                return $errors;
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $context = SerializationContext::create()->setGroups(array("user:details"));
            $data = $this->serializer->serialize($user, 'json', $context);

            return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            if ($th instanceof UniqueConstraintViolationException) {
                $message = "Uniqueness constraint violation: this user already exists";
            }
            throw new JsonException($message, JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/users/{id<\d+>}", name="api_user_update", methods={"PUT"})
     * @OA\Response(
     *     response=JsonResponse::HTTP_OK,
     *     description="Update a user and returns it"
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_BAD_REQUEST,
     *     description="Bad Json syntax or incorrect data"
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_UNAUTHORIZED,
     *     description="Unauthorized request"
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_NOT_FOUND,
     *     description="User not found"
     * )
     * @OA\RequestBody(
     *     description="The user data you want to update. Use empty values for unchanged data (e.g. ""password"": """").",
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="application/Json",
     *         @OA\Schema(
     *             type="object",
     *             @OA\Property(
     *                 property="password",
     *                 description="User's choosen password",
     *                 type="string"
     *             ),
     *             @OA\Property(
     *                 property="first_name",
     *                 description="User's first name",
     *                 type="string"
     *             ),
     *             @OA\Property(
     *                 property="last_name",
     *                 description="User's last name",
     *                 type="string"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 description="User's email address",
     *                 type="string"
     *             )
     *         )
     *     )
     * )
     * @OA\Tag(name="Users")
     */
    public function update(User $user = null, Request $request, EntityManagerInterface $entityManager)
    {
        // check if user exists
        $this->checkUser($user);

        // if current user can't add a new user
        if (!$this->isGranted("USER_EDIT", $user)) {
            throw new JsonException("You do not have the required rights to make this request", JsonResponse::HTTP_UNAUTHORIZED);
        }

        try {
            $updatedUser = $this->serializer->deserialize($request->getContent(), User::class, 'json');
            if ($updatedUser->getFirstName()) $user->setFirstName($updatedUser->getFirstName());
            if ($updatedUser->getLastName()) $user->setLastName($updatedUser->getLastName());
            if ($updatedUser->getEmail()) $user->setEmail($updatedUser->getEmail());
            if ($updatedUser->getPassword()) $user->setPassword($this->hasher->hashPassword($user, $updatedUser->getPassword()));

            // validate User
            $errors = $this->validateUser($user);
            if ($errors) {
                return $errors;
            }

            $entityManager->flush();

            $context = SerializationContext::create()->setGroups(array("user:details"));
            $data = $this->serializer->serialize($user, 'json', $context);

            return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            throw new JsonException($message, JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/users/{id<\d+>}", name="api_user_delete", methods={"DELETE"})
     * @OA\Response(
     *     response=JsonResponse::HTTP_NO_CONTENT,
     *     description="Delete a user"
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_UNAUTHORIZED,
     *     description="Unauthorized request"
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_NOT_FOUND,
     *     description="User not found"
     * )
     * @OA\Tag(name="Users")
     */
    public function remove(User $user = null, EntityManagerInterface $entityManager)
    {
        // check if user exists
        $this->checkUser($user);

        // if user can't be deleted by the current user
        if (!$this->isGranted("USER_DELETE", $user)) {
            throw new JsonException("You do not have the required rights to make this request", JsonResponse::HTTP_UNAUTHORIZED);
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
            throw new JsonException("Incorrect identifier or no user found with this identifier", JsonResponse::HTTP_NOT_FOUND);
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
