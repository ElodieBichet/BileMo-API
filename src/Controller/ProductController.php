<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    /**
     * @Route("/api/products", name="api_product_list", methods={"GET"})
     */
    public function list(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(array("product:list"));
        $data = $serializer->serialize($productRepository->findAll(), 'json', $context);
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }
}
