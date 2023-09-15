<?php

namespace App\Controller;

use App\Entity\Alcohol;
use App\Entity\Image;
use App\Entity\Producer;
use App\Repository\AlcoholRepository;
use App\Repository\ProducerRepository;
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
        private EntityManagerInterface $entityManager,
        private ProducerRepository $producerRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
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
            JsonResponse::HTTP_OK,
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
            JsonResponse::HTTP_OK,
            [],
            ['groups' => 'alcohol']
        );
    }

    #[Route('/alcohols', name: 'app_alcohol_create_item', methods: ['POST'])]
    public function createAlcohol(Request $request): JsonResponse
    {
        $uploadedFiles = $request->files->all();
    
        $alcoholData = $request->request->all();
    
        if (isset($alcoholData['abv'])) {
            $alcoholData['abv'] = (float) $alcoholData['abv'];
        }
    
        $alcohol = $this->serializer->deserialize(json_encode($alcoholData), Alcohol::class, 'json');
        $producerId = $alcoholData['producerId'] ?? null;

        if ($producerId) {
            $existingProducer = $this->entityManager->getRepository(Producer::class)->find($producerId);
            if (!$existingProducer) {
                throw new BadRequestHttpException("Producer not found");
            }
            $alcohol->setProducer($existingProducer);
        } else {
            throw new BadRequestHttpException("Producer ID is required");
        }

        if (!isset($uploadedFiles['image'])) {
            throw new BadRequestHttpException("Image file is required");
        }
    
        $imageFile = $uploadedFiles['image'];
        if (!$imageFile instanceof UploadedFile) {
            throw new BadRequestHttpException("Invalid file uploaded");
        }
    
        $fileName = md5(uniqid()) . '.' . $imageFile->getClientOriginalExtension();
    
        try {
            $imageFile->move(
                $this->getParameter('image_directory'),
                $fileName
            );
        } catch (FileException $e) {
            throw new BadRequestHttpException("Failed to upload image");
        }
        $image = new Image();
        $image->setName($imageFile->getClientOriginalName());
        $image->setFilename($fileName);
        $alcohol->setImage($image);
    
        $errors = $this->validator->validate($alcohol);
    
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
    
            throw new BadRequestHttpException(json_encode(['errors' => $errorMessages]));
        }
    
        $this->entityManager->persist($image);
        $this->entityManager->persist($alcohol);
        $this->entityManager->flush();
    
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
        $requestData = json_decode($request->getContent(), true);
    
        if (!isset($requestData['producerId'])) {
            throw new BadRequestHttpException("The 'producerId' field is required.");
        }
    
        $producerId = $requestData['producerId'];
        $alcohol = $this->alcoholRepository->find($id);
    
        if (!$alcohol) {
            throw new NotFoundHttpException("Alcohol not found.");
        }
    
        $this->serializer->deserialize(
            $request->getContent(),
            Alcohol::class,
            'json',
            ['object_to_populate' => $alcohol]
        );
    
        $existingProducer = $this->producerRepository->find($producerId);
    
        if (!$existingProducer) {
            throw new BadRequestHttpException("Producer not found.");
        }
    
        $alcohol->setProducer($existingProducer);
    
        $errors = $this->validator->validate($alcohol);
    
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
    
            throw new BadRequestHttpException(json_encode(['errors' => $errorMessages]));
        }

        $this->entityManager->persist($alcohol);    
        $this->entityManager->flush();
    
        return $this->json(
            $alcohol,
            JsonResponse::HTTP_OK,
            [],
            ['groups' => 'alcohol']
        );
    }

    #[Route('/alcohols/{id}', name: 'app_alcohol_delete_item', methods: ['DELETE'])]
    public function deleteAlcohol(int $id): JsonResponse
    {
        $alcohol = $this->alcoholRepository->find($id);

        if (!$alcohol) {
            throw new NotFoundHttpException("Alcohol not found.");
        }

        $this->entityManager->remove($alcohol);
        $this->entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
