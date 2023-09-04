<?php

namespace App\Controller;

use App\Repository\AlcoholRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AlcoholController extends AbstractController
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/alcohols', methods: ['GET'])]
    public function listAlcohols(Request $request, AlcoholRepository $alcoholRepository): JsonResponse
    {
        $page = $request->query->get('page');
        $perPage = $request->query->get('perPage');
        $nameFilter = $request->query->get('name');
        $typeFilter = $request->query->get('type');

        if (!$page || !$perPage) {
                throw new BadRequestHttpException("Query parameters 'page' and 'perPage' are required.");
        }

        $alcohols = $alcoholRepository->findByCriteria(
            $nameFilter,
            $typeFilter,
            $perPage,
            ($page - 1) * $perPage);

        $context = [
            'groups' => ['alcohol', 'producer', 'image'],
        ];

        $serializedAlcohols = $this->serializer->serialize($alcohols, 'json', $context);

        return $this->json([
            'total' => count($alcohols),
            'items' => json_decode($serializedAlcohols, true),
            ],
             200,
             [],
             ['groups' => $context]
        );
    }

    #[Route('/alcohols/{id}', methods: ['GET'])]
    public function getAlcohol(int $id, AlcoholRepository $alcoholRepository): JsonResponse
    {
        $alcohol = $alcoholRepository->find($id);

        if (!$alcohol) {
            throw new NotFoundHttpException("Not found.");
        }

        $context = [
            'groups' => ['alcohol', 'producer', 'image'],
        ];

        $serializedAlcohol = $this->serializer->serialize($alcohol, 'json', $context);

        return $this->json(
            json_decode($serializedAlcohol, true),
             200,
             [],
             ['groups' => $context]
        );
    }
}
