<?php

namespace App\Controller;

use App\Repository\AlcoholRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AlcoholController extends AbstractController
{
    public function __construct(
        private AlcoholRepository $alcoholRepository
    ) {
    }

    #[Route('/alcohols', name: 'app_alcohol_get_collection', methods: ['GET'])]
    public function listAlcohols(Request $request): JsonResponse
    {
        $page = $request->query->get('page');
        $perPage = $request->query->get('perPage');
        $nameFilter = $request->query->get('name');
        $typeFilter = $request->query->get('type');

        if (!$page || !$perPage) {
            throw new BadRequestHttpException("Query parameters 'page' and 'perPage' are required.");
        }

        $alcohols = $this->alcoholRepository->findByCriteria(
            $nameFilter,
            $typeFilter,
            $perPage,
            ($page - 1) * $perPage
        );
        
        return $this->json(
            [
                'total' => count($alcohols),
                'alcohols' => $alcohols,
            ],
            200,
            [],
            ['groups' => 'alcohol']
        );
    }

    #[Route('/alcohols/{id}', name: 'app_alcohol_get_item', methods: ['GET'])]
    public function getAlcohol(int $id): JsonResponse
    {
        $alcohol = $this->alcoholRepository->find($id);

        if (!$alcohol) {
            throw new NotFoundHttpException("Not found.");
        }

        return $this->json(
            $alcohol,
            200,
            [],
            ['groups' => 'alcohol']
        );
    }
}
