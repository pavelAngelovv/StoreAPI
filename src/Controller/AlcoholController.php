<?php

namespace App\Controller;

use App\Entity\Alcohol;
use App\Repository\AlcoholRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AlcoholController extends AbstractController
{
    public function __construct(
        private AlcoholRepository $alcoholRepository,
        private ValidatorInterface $validator,
        private ManagerRegistry $doctrine
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

    #[Route('/alcohols', name: 'app_alcohol_post_item', methods: ['POST'])]
    public function createAlcohol(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $entityManager = $this->doctrine->getManager();
        $alcohol = $serializer->deserialize($request->getContent(), Alcohol::class, 'json');
        $errors = $this->validator->validate($alcohol);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 400);
        }
            $entityManager->persist($alcohol);
            $entityManager->flush();

        return $this->json($alcohol, 201);
    }

    #[Route('/alcohols/{id}', name: 'app_alcohol_update_item', methods: ['PUT'])]
public function updateAlcohol(int $id, Request $request, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
{
    $entityManager = $this->doctrine->getManager();
    $alcohol = $entityManager->getRepository(Alcohol::class)->find($id);

    if (!$alcohol) {
        throw new NotFoundHttpException("Alcohol not found.");
    }

    $updatedAlcohol = $serializer->deserialize($request->getContent(), Alcohol::class, 'json');

    $errors = $validator->validate($updatedAlcohol);

    if (count($errors) > 0) {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }

        return $this->json(['errors' => $errorMessages], 400);
    }

    $alcohol->setName($updatedAlcohol->getName());
    $alcohol->setType($updatedAlcohol->getType());
    $alcohol->setDescription($updatedAlcohol->getDescription());
    $alcohol->setProducer($updatedAlcohol->getProducer());
    $alcohol->setAbv($updatedAlcohol->getAbv());
    $alcohol->setImage($updatedAlcohol->getImage());

    $entityManager->flush();

    return $this->json($alcohol);
}

    #[Route('/alcohols/{id}', name: 'app_alcohol_delete_item', methods: ['DELETE'])]
    public function deleteAlcohol(int $id): JsonResponse
    {
        $entityManager = $this->doctrine->getManager();
        $alcohol = $entityManager->getRepository(Alcohol::class)->find($id);

        if (!$alcohol) {
            throw new NotFoundHttpException("Alcohol not found.");
        }

        $entityManager->remove($alcohol);
        $entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
