<?php

namespace App\Controller;

use App\Entity\Product;
use Hateoas\HateoasBuilder;
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
     * @Route("/api/products/{id}", name="api_product_details", methods={"GET"})
     */
    public function details(Product $product = null): JsonResponse
    {
        if (!$product) {
            return new JsonResponse([
                'status' => JsonResponse::HTTP_NOT_FOUND,
                'message' => "Aucun produit trouvé avec cet identifiant"
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $context = SerializationContext::create()->setGroups(array("product:details"));
        $data = $this->serializer->serialize($product, 'json', $context);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }
}
