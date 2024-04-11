<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
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

    #[Route('/user', name: 'user_put', methods: 'PUT')]
    public function update(Request $request): JsonResponse
    {

        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }
        $user = $dataMiddellware;

        dd($user);
        $phone = "0668000000";
        if(preg_match("/^[0-9]{10}$/", $phone)) {
            $old = $user->getTel();
            $user->setTel($phone);
            $this->entityManager->flush();
            return $this->json([
                "New_tel" => $user->getTel(),
                "Old_tel" => $old,
                "user" => $user->serializer(),
            ]);
        }
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