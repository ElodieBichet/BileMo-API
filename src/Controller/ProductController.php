<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    protected $serializer;
    protected $productRepository;

    public function __construct(SerializerInterface $serializer, ProductRepository $productRepository)
    {
        $this->serializer = $serializer;
        $this->productRepository = $productRepository;
    }

    /**
     * @Route("/api/products", name="api_product_list", methods={"GET"})
     */
    public function list(): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(array("product:list"));
        $data = $this->serializer->serialize($this->productRepository->findAll(), 'json', $context);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/products/{id}", name="api_product_details", methods={"GET"})
     */
    public function details(Product $product = null): JsonResponse
    {
        if (!$product) {
            return $this->json([
                'status' => JsonResponse::HTTP_NOT_FOUND,
                'message' => "Aucun produit trouvé avec cet identifiant"
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $context = SerializationContext::create()->setGroups(array("product:details"));
        $data = $this->serializer->serialize($product, 'json', $context);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }
}
