<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $repository;
    private $tokenVerifier;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, TokenVerifierService $tokenVerifier){
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenVerifier;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/user', name: 'user_post', methods: 'POST')]
    public function create(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
    // Récupérer les données JSON du corps de la requête
    $data = json_decode($request->getContent(), true);

    // Créer un nouvel utilisateur avec les données fournies
    $user = new User();
    $user->setName($data['name'] ?? ''); 
    $user->setEmail($data['email'] ?? ''); 
    $user->setIdUser($data['idUser'] ?? ''); 
    $user->setTel($data['tel'] ?? ''); 
    $user->setSexe($data['sexe'] ?? ''); 

    // Convertir la date de naissance en objet DateTime
    $dateOfBirthString = $data['date_birth'] ?? ''; 
    $dateOfBirth = new DateTime($dateOfBirthString);
    $user->setDateBirth($dateOfBirth); 

    $user->setCreateAt(new DateTimeImmutable());
    $user->setUpdateAt(new DateTimeImmutable());
    
    
    // Générer le hachage du mot de passe
    $password = $data['password'] ?? ''; 
    $hash = $passwordHash->hashPassword($user, $password);
    $user->setPassword($hash);

    // Enregistrer l'utilisateur dans la base de données
    $this->entityManager->persist($user);
    $this->entityManager->flush();

    return $this->json([
        'user' => $user->serializer(),
        'path' => 'src/Controller/UserController.php',
    ]);
}

    #[Route('/user/{id}', name: 'user_post', methods: ['POST'])]
    public function update(Request $request, UserPasswordHasherInterface $passwordHash, int $id): JsonResponse
    {
    // Récupérer les données JSON du corps de la requête
    $data = json_decode($request->getContent(), true);

    // Récupérer l'utilisateur à mettre à jour depuis la base de données
    $user = $this->entityManager->getRepository(User::class)->find($id);

    // Vérifier si l'utilisateur existe
    if (!$user) {
        throw $this->createNotFoundException('Utilisateur non trouvé');
    }

    // Mettre à jour les propriétés de l'utilisateur avec les nouvelles données
    $user->setName($data['name'] ?? $user->getName()); 
    $user->setEmail($data['email'] ?? $user->getEmail()); 
    $user->setTel($data['tel'] ?? $user->getTel()); 
    $user->setSexe($data['sexe'] ?? $user->getSexe()); 

    // Convertir la date de naissance en objet DateTime
    $dateOfBirthString = $data['date_birth'] ?? ''; 
    if (!empty($dateOfBirthString)) {
        $dateOfBirth = new DateTime($dateOfBirthString);
        $user->setDateBirth($dateOfBirth);
    }

    // Mettre à jour la date de mise à jour
    $user->setUpdateAt(new DateTimeImmutable());
    
    // Si un nouveau mot de passe est fourni, le mettre à jour
    $password = $data['password'] ?? '';
    if (!empty($password)) {
        $hash = $passwordHash->hashPassword($user, $password);
        $user->setPassword($hash);
    }

    // Enregistrer les modifications dans la base de données
    $this->entityManager->flush();

    return $this->json([
        'user' => $user->serializer(),
        'path' => 'src/Controller/UserController.php',
    ]);
}



    #[Route('/user', name: 'user_delete', methods: 'DELETE')]
    public function delete(): JsonResponse
    {
        $this->entityManager->remove($this->repository->findOneBy(["id"=>1]));
        $this->entityManager->flush();
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/user', name: 'user_get', methods: 'GET')]
    public function read(): JsonResponse
    {


        $serializer = new Serializer([new ObjectNormalizer()]);
        // $jsonContent = $serializer->serialize($person, 'json');
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/user/all', name: 'user_get_all', methods: 'GET')]
    public function readAll(): JsonResponse
    {
        $result = [];

        try {
            if (count($users = $this->repository->findAll()) > 0)
                foreach ($users as $user) {
                    array_push($result, $user->serializer());
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