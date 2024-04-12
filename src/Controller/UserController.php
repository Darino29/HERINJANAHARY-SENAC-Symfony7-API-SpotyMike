<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use DateTime;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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


    #[Route('/user', name: 'update_user_profile', methods: ['POST'])]
public function updateUserProfile(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, JWTTokenManagerInterface $JWTManager): JsonResponse
{
    // Récupérer les données JSON du corps de la requête
    $data = json_decode($request->getContent(), true);
    
    // Récupérer le token JWT depuis les en-têtes de la requête
    $token = $request->headers->get('Authorization');

    // Vérifier si le token est présent et valide
    if (!$token) {
        return $this->json(['error' => true, 'message' => 'Non authentifié'], 401);
    }

    // Extraire le JWT du format 'Bearer xxxx'
    $jwt = str_replace('Bearer ', '', $token);

    // Vérifier si le token est valide et récupérer l'utilisateur associé
    try {
        $user = $JWTManager->parse($jwt)->getUser();
    } catch (JWTDecodeFailureException $e) {
        return $this->json(['error' => true, 'message' => 'Token invalide'], 401);
    }

    // Vérifier si l'utilisateur existe
    if (!$user) {
        return $this->json(['error' => true, 'message' => 'Utilisateur introuvable'], 404);
    }

    // Mettre à jour les informations de l'utilisateur
    if (isset($data['name'])) {
        $user->setName($data['name']);
    }
    if (isset($data['tel'])) {
        // Valider le format du numéro de téléphone
        if (!preg_match('/^\d{10}$/', $data['tel'])) {
            return $this->json(['error' => true, 'message' => 'Format de numéro de téléphone invalide'], 400);
        }
        // Vérifier si le numéro de téléphone est déjà utilisé par un autre utilisateur
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['tel' => $data['tel']]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return $this->json(['error' => true, 'message' => 'Numéro de téléphone déjà utilisé par un autre utilisateur'], 409);
        }
        $user->setTel($data['tel']);
    }
    if (isset($data['sexe'])) {
        // Vérifier si la valeur de sexe est valide
        if (!in_array($data['sexe'], ['0', '1'])) {
            return $this->json(['error' => true, 'message' => 'Valeur de sexe invalide'], 400);
        }
        $user->setSexe($data['sexe']);
    }
    // Ajoutez d'autres mises à jour pour les autres champs de l'utilisateur

    // Valider les modifications avec le Validator
    $errors = $validator->validate($user);

    // Si des erreurs de validation sont présentes
    if (count($errors) > 0) {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }
        return $this->json([
            'error' => true,
            'message' => 'Erreur de validation de donnée',
            'details' => $errorMessages,
        ], 400);
    }

    // Enregistrer les modifications dans la base de données
    $entityManager->flush();

    // Réponse de succès avec le profil mis à jour
    return $this->json([
        'error' => false,
        'message' => 'Profil mis à jour avec succès',
        'user' => $user->serializer(), // Serializer le nouvel objet utilisateur
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