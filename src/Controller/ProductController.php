<?php

namespace App\Controller;

use JsonException;
use App\Entity\Product;
use OpenApi\Annotations as OA;
use App\Service\PaginationService;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    protected $serializer;
    protected $productRepository;
    protected $pagination;

    public function __construct(SerializerInterface $serializer, ProductRepository $productRepository, PaginationService $pagination)
    {
        $this->serializer = $serializer;
        $this->productRepository = $productRepository;
        $this->pagination = $pagination;
    }

    /**
     * @Route("/api/products", name="api_product_list", methods={"GET"})
     * @OA\Get(summary="Get list of BileMo products")
     * @OA\Response(
     *     response=JsonResponse::HTTP_OK,
     *     description="Returns the list of products"
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
     *     @OA\Schema(type="int", default = 10)
     * )
     * @OA\Parameter(
     *     name="orderby",
     *     in="query",
     *     description="Name of the property used to sort items: id, name, created_at, description, price, color, available_quantity",
     *     @OA\Schema(type="string", default = "name")
     * )
     * @OA\Parameter(
     *     name="inverse",
     *     in="query",
     *     description="Set to true (1) to sort with descending order, and to false (0) to sort with ascending order",
     *     @OA\Schema(type="boolean", default = false)
     * )
     * @OA\Tag(name="Products")
     */
    public function list(Request $request): JsonResponse
    {
        $queryBuilder = $this->productRepository->createQueryBuilder('product');

        $data = $this->pagination->paginate($request, $queryBuilder);

        $context = SerializationContext::create()->setGroups(array("product:list"));
        $jsonData = $this->serializer->serialize($data, 'json', $context);

        return new JsonResponse($jsonData, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/products/{id<\d+>}", name="api_product_details", methods={"GET"})
     * @OA\Get(summary="Get details of a product")
     * @OA\Response(
     *     response=JsonResponse::HTTP_OK,
     *     description="Returns a product"
     * )
     * @OA\Response(
     *     response=JsonResponse::HTTP_NOT_FOUND,
     *     description="Product not found"
     * )
     * @OA\Tag(name="Products")
     */
    public function details(Product $product = null): JsonResponse
    {
        if (!$product || !($product instanceof Product)) {
            throw new JsonException("Incorrect identifier or no product found with this identifier", JsonResponse::HTTP_NOT_FOUND);
        }

        $context = SerializationContext::create()->setGroups(array("product:details"));
        $data = $this->serializer->serialize($product, 'json', $context);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }
}
