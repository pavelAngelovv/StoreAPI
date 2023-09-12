<?php

namespace App\Controller;

use App\Entity\Alcohol;
use App\Entity\Image;
use App\Entity\Producer;
use App\Repository\AlcoholRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
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
    public function createAlcohol(Request $request): JsonResponse
    {
        $entityManager = $this->entityManager;
        $uploadedFiles = $request->files->all();

        if (!isset($uploadedFiles['image'])) {
            return $this->json(['errors' => ['image' => 'Image file is required.']], 400);
        }

        $imageFile = $uploadedFiles['image'];

        if (!$imageFile instanceof UploadedFile) {
            return $this->json(['errors' => ['image' => 'Invalid file uploaded.']], 400);
        }

        $fileName = md5(uniqid()) . '.' . $imageFile->getClientOriginalExtension();

        try {
            $imageFile->move(
                $this->getParameter('image_directory'),
                $fileName
            );
        } catch (FileException $e) {
            return $this->json(['errors' => ['image' => 'Failed to upload image.']], 400);
        }

        $image = new Image();
        $image->setName($fileName);

        $alcoholData = $request->request->all();

        if (isset($alcoholData['abv'])) {
            $alcoholData['abv'] = (float) $alcoholData['abv'];
        }

        $alcohol = $this->serializer->deserialize(json_encode($alcoholData), Alcohol::class, 'json');

        $producerId = $alcoholData['producerId'] ?? null;
        if ($producerId) {
            $existingProducer = $entityManager->getRepository(Producer::class)->find($producerId);
            if (!$existingProducer) {
                return $this->json(['errors' => ['producer' => 'Producer not found.']], 400);
            }
            $alcohol->setProducer($existingProducer);
        } else {
            return $this->json(['errors' => ['producer' => 'Producer ID is required.']], 400);
        }

        $alcohol->setImage($image);

        $errors = $this->validator->validate($alcohol);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 400);
        }

        $entityManager->persist($image);
        $entityManager->persist($alcohol);
        $entityManager->flush();

        return $this->json(
            $alcohol,
            JsonResponse::HTTP_CREATED,
            [],
            ['groups' => 'alcohol']
        );
    }

    #[Route('/alcohols/{id}', name: 'app_alcohol_update_item', methods: ['PUT'])]
    public function updateAlcohol(int $id, Request $request): JsonResponse
    {
        $entityManager = $this->entityManager;
        $alcohol = $entityManager->getRepository(Alcohol::class)->find($id);

        if (!$alcohol) {
            throw new NotFoundHttpException("Alcohol not found.");
        }

        $updatedData = json_decode($request->getContent(), true);

        $producerId = $updatedData['producerId'] ?? null;

        if ($producerId) {
            $existingProducer = $entityManager->getRepository(Producer::class)->find($producerId);

            if (!$existingProducer) {
                return $this->json(['errors' => ['producer' => 'Producer not found.']], 400);
            }

            $alcohol->setProducer($existingProducer);
        }

        if ($alcohol->getImage()) {
            $alcohol->getImage()->setName($updatedData['image']['name'] ?? $alcohol->getImage()->getName());
        }
        $alcohol->setName($updatedData['name'] ?? $alcohol->getName());
        $alcohol->setType($updatedData['type'] ?? $alcohol->getType());
        $alcohol->setDescription($updatedData['description'] ?? $alcohol->getDescription());
        $alcohol->setAbv($updatedData['abv'] ?? $alcohol->getAbv());

        $errors = $this->validator->validate($alcohol);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 400);
        }

        $entityManager->flush();

        return $this->json(
            $alcohol,
            200,
            [],
            ['groups' => 'alcohol']
        );
    }

    #[Route('/alcohols/{id}', name: 'app_alcohol_delete_item', methods: ['DELETE'])]
    public function deleteAlcohol(int $id): JsonResponse
    {
        $entityManager = $this->entityManager;
        $alcohol = $entityManager->getRepository(Alcohol::class)->find($id);

        if (!$alcohol) {
            throw new NotFoundHttpException("Alcohol not found.");
        }

        $entityManager->remove($alcohol);
        $entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
