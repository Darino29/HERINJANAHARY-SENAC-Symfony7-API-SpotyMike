<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\User;
use DateTimeImmutable;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\BadRequestException;
use Symfony\Component\Security\Core\Exception\UnauthorizedException;
use Symfony\Component\Security\Core\Exception\ForbiddenException;
use Symfony\Component\Security\Core\Exception\ConflictException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ArtistController extends AbstractController
{
    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Artist::class);
    }

    #[Route('/artist', name: 'artist_create', methods: ['POST'])]
    public function create(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        // Récupérer les données JSON du corps de la requête
        $data = json_decode($request->getContent(), true);

        // Vérifier que les données nécessaires sont présentes
        if (!isset($data['fullname']) || !isset($data['label'])) {
            throw new BadRequestException('Fullname and label are required.');
        }

        // Créer un nouvel artiste avec les données fournies
        $artist = new Artist();
        $artist->setFullname($data['fullname']);
        $artist->setLabel($data['label']);
        $artist->setDescription($data['description'] ?? '');

        // Enregistrer l'artiste dans la base de données
        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        // Récupérer l'ID de l'artiste créé
        $artistId = $artist->getId();

        return $this->json([
            'error' => false,
            'message' => 'Artist created successfully',
            'artist_id' => $artistId,
        ], Response::HTTP_CREATED);
    }


    #[Route('/artist/{id}', name: 'artist_delete', methods: 'DELETE')]
    public function delete(Artist $artist): JsonResponse
    {
        $this->entityManager->remove($artist);
        $this->entityManager->flush();
        return $this->json([
            'message' => 'Artist deleted successfully',
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }

    #[Route('/artist/{id}', name: 'artist_update', methods: 'PUT')]
    public function update(Request $request, Artist $artist): JsonResponse
    {
        // Récupérer les données JSON du corps de la requête
        $data = json_decode($request->getContent(), true);

        // Mettre à jour les informations de l'artiste
        if (isset($data['fullname'])) {
            $artist->setFullname($data['fullname']);
        }
        if (isset($data['label'])) {
            $artist->setLabel($data['label']);
        }
        if (isset($data['description'])) {
            $artist->setDescription($data['description']);
        }

        // Enregistrer les modifications dans la base de données
        $this->entityManager->flush();

        return $this->json([
            'artist' => $artist->serializer(),
            'message' => 'Artist updated successfully',
        ]);
    }

    #[Route('/artist/{id}', name: 'artist_get', methods: 'GET')]
    public function read(Artist $artist): JsonResponse
    {
        return $this->json([
            'artist' => $artist->serializer(),
            'message' => 'Artist retrieved successfully',
        ]);
    }

    #[Route('/artist', name: 'artist_get_all', methods: 'GET')]
    public function readAll(): JsonResponse
    {
        $artists = $this->repository->findAll();
        $data = array_map(function($artist) {
            return $artist->serializer();
        }, $artists);

        return $this->json([
            'artist' => $data,
            'message' => 'All artists retrieved successfully',
        ]);
    }

    #[Route('/artist/{fullname}', name: 'artist_get_by_fullname', methods: 'GET')]
    public function readByFullname(string $fullname, Request $request): JsonResponse
    {
        // Vérifier l'authentification
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('JWT token not found.');
        }

        // Récupérer l'artiste par son nom complet
        $artist = $this->repository->findOneBy(['fullname' => $fullname]);

        // Vérifier si l'artiste existe
        if (!$artist) {
            return $this->json([
                'message' => 'Artist not found',
            ], 404);
        }

        return $this->json([
            'artist' => $artist->serializer(),
            'message' => 'Artist retrieved successfully',
        ]);
    }
}
