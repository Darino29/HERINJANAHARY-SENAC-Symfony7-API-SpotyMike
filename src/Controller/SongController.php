<?php

namespace App\Controller;

use App\Entity\Song;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SongController extends AbstractController
{
    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Song::class);
    }


    #[Route('/song/all', name: 'song_get_all', methods: 'GET')]
    public function readAll(): JsonResponse
    {
        $result = [];

        try {
            if (count($songs = $this->repository->findAll()) > 0)
                foreach ($songs as $song) {
                    array_push($result, $song->serializer());
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