<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AlcoholRepository;

class AlcoholController extends AbstractController
{
    #[Route('/alcohols', methods: ['GET'])]
    public function listAlcohols(Request $request, AlcoholRepository $alcoholRepository): JsonResponse
    {
        $page = $request->query->get('page');
        $perPage = $request->query->get('perPage');
        $nameFilter = $request->query->get('name');
        $typeFilter = $request->query->get('type');

        if (!$page || !$perPage) {
            return new JsonResponse(['message' => "Query parameters 'page' and 'perPage' are required."], JsonResponse::HTTP_BAD_REQUEST);
        }

        $alcohols = $alcoholRepository->findByCriteria($nameFilter, $typeFilter, $perPage, ($page - 1) * $perPage);

        $serializedAlcohols = [];
        foreach ($alcohols as $alcohol) {
            $serializedAlcohol = [
                'id' => $alcohol->getId(),
                'name' => $alcohol->getName(),
                'type' => $alcohol->getType(),
                'description' => $alcohol->getDescription(),
                'producer' => [
                    'id' => $alcohol->getProducer()->getId(),
                    'name' => $alcohol->getProducer()->getName(),
                    'country' => $alcohol->getProducer()->getCountry(),
                ],
                'abv' => $alcohol->getAbv(),
                'image' => [
                    'id' => $alcohol->getImage()->getId(),
                    'name' => $alcohol->getImage()->getName(),
                    'url' => $alcohol->getImage()->getUrl(),
                ],
            ];
            $serializedAlcohols[] = $serializedAlcohol;
        }

        $response = [
            'total' => count($alcohols),
            'items' => $serializedAlcohols,
        ];

        return new JsonResponse($response);
    }

    #[Route('/alcohols/{id}', methods: ['GET'])]
    public function getAlcohol(int $id, AlcoholRepository $alcoholRepository): JsonResponse
    {
        $alcohol = $alcoholRepository->find($id);

        if (!$alcohol) {
            return new JsonResponse(['message' => 'Not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $serializedAlcohol = [
            'id' => $alcohol->getId(),
            'name' => $alcohol->getName(),
            'type' => $alcohol->getType(),
            'description' => $alcohol->getDescription(),
            'producer' => [
                'id' => $alcohol->getProducer()->getId(),
                'name' => $alcohol->getProducer()->getName(),
                'country' => $alcohol->getProducer()->getCountry(),
            ],
            'abv' => $alcohol->getAbv(),
            'image' => [
                'id' => $alcohol->getImage()->getId(),
                'name' => $alcohol->getImage()->getName(),
                'url' => $alcohol->getImage()->getUrl(),
            ],
        ];

        return new JsonResponse($serializedAlcohol);
    }
}
