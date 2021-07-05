<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    protected $serializer;
    protected $validator;
    protected $productRepository;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, ProductRepository $productRepository)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
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
}
