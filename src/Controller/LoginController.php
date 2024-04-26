<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/register', name: 'login_post', methods: 'POST')]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        // Récupérer les données JSON du corps de la requête
        $data = json_decode($request->getContent(), true);

        $requiredAttributes = ['name', 'email', 'date_birth', 'password'];
        foreach ($requiredAttributes as $attribute) {
            if (!isset($data[$attribute]) || empty($data[$attribute])) {
                return $this->json([
                    'error' => true,
                    'message' => "Des champs obligatoires sont manquants.",
                ], 400);
            }
        }

        // Créer un nouvel utilisateur avec les données fournies
        $user = new User();
        $user->setName($data['name']); 
        $user->setEmail($data['email']); 
        $user->setIdUser($data['idUser'] ?? uniqid()); 

        // Vérifier si le sexe est fourni
        if (isset($data['sexe'])) {
            $user->setSexe($data['sexe']);
        }

        // Vérifier si le numéro de téléphone est fourni
        if (isset($data['tel'])) {
            $user->setTel($data['tel']);
        }

        // Convertir la date de naissance en objet DateTime
        $dateOfBirthString = $data['date_birth']; 
        $dateOfBirth = new DateTime($dateOfBirthString);
        $user->setDateBirth($dateOfBirth);  

        $user->setCreateAt(new DateTimeImmutable());
        $user->setUpdateAt(new DateTimeImmutable());
        
        // Valider l'utilisateur avec le Validator
        $errors = $validator->validate($user);

        // Si des erreurs de validation sont présentes
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'error' => true,
                'message' => 'Format des données invalides',
                'details' => $errorMessages,
            ], 400);
        }

        // Vérifier si l'utilisateur a moins de 12 ans
        $age = date_diff(new DateTime(), $dateOfBirth)->y;
        if ($age < 12) {
            return $this->json([
                'error' => true,
                'message' => 'Âge minimum non respecté (moins de 12 ans)',
            ], 400);
        }

        // Vérifier si le champ email est vide
        if (empty($data['email'])) {
            return $this->json([
                'error' => true,
                'message' => "Le champ 'email' ne peut pas être vide",
            ], 400);
        }

        // Vérifier si l'email est déjà utilisé
        $existingUser = $this->repository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json([
                'error' => true,
                'message' => 'Email déjà utilisé',
            ], 400);
        }
        
        // Générer le hachage du mot de passe
        $password = $data['password'] ?? ''; 
        $hash = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hash);
        
        // Enregistrer l'utilisateur dans la base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Réponse de succès
        return $this->json([
            'error' => false,
            'message' => 'Enregistrement réussi',
            'user' => $user->serializer(),
        ], 201);
    }
    
    #[Route('/login', name: 'app_login_post', methods: ['POST'])]
    public function login(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        // Récupérer les données JSON du corps de la requête
        $data = json_decode($request->getContent(), true);
    
        // Récupérer l'email et le mot de passe depuis les données
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
    
        // Rechercher l'utilisateur par son email dans la base de données
        $user = $this->repository->findOneBy(["email" => $email]);
    
        // Vérifier si un utilisateur correspondant a été trouvé
        if (!$user) {
            return $this->json(['message' => 'Identifiants invalides'], 401);
        }

        // Vérifier le mot de passe
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Identifiants invalides'], 401);
        }
        
        
        // Générer le token JWT pour l'utilisateur
        $token = $JWTManager->create($user);
    
        // Retourner le token JWT et toute autre information pertinente
        return $this->json([
            'token' => $token,
            'user' => $user->serializer(),
            'message' => 'Connexion réussie',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }
}
