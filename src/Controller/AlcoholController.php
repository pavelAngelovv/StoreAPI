<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\AlcoholRepository;
use Symfony\Component\Serializer\SerializerInterface;

class AlcoholController extends AbstractController
{
    #[Route('/alcohols', methods: ['GET'])]
    public function listAlcohols(AlcoholRepository $alcoholRepository, SerializerInterface $serializer): JsonResponse
    {
        $alcohols = $alcoholRepository->findAll();

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
}
