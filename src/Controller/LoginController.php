<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginController extends AbstractController
{

    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/register', name: 'login_post', methods: 'POST')]
    public function create(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        // Récupérer les données JSON du corps de la requête
        $data = json_decode($request->getContent(), true);
        
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
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'isNotGoodPassword' => ($passwordHash->isPasswordValid($user, 'Zoubida') ),
            'isGoodPassword' => ($passwordHash->isPasswordValid($user, $password) ),
            'user' => $user->serializer(),
            'path' => 'src/Controller/UserController.php',
        ]);
    }
        
    // use Symfony\Component\HttpFoundation\Request;
    #[Route('/login', name: 'app_login_post', methods: ['POST', 'PUT'])]
    public function login(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
    {

        $user = $this->repository->findOneBy(["email" => "mike.sylvestre@lyknowledge.io"]);

        $parameters = json_decode($request->getContent(), true);


        return $this->json([
            'token' => $JWTManager->create($user),
            'data' => $request->getContent(),
            'message' => 'Welcome to MikeLand',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }
}