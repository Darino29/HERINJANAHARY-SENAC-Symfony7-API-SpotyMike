<?php

namespace App\Controller;

use App\Entity\Artist;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArtistController extends AbstractController
{
    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Artist::class);
    }


    #[Route('/artist/all', name: 'artist_get_all', methods: 'GET')]
    public function readAll(): JsonResponse
    {
        $result = [];

        try {
            if (count($artists = $this->repository->findAll()) > 0)
                foreach ($artists as $artist) {
                    array_push($result, $artist->serializer());
                }
            return new JsonResponse([
                'data' => $result,
                'message' => 'Successful'
            ], 400);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage()
            ], 404);
        }
    }
}