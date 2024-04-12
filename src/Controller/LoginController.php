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

        
        $email = $data['email'];

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if ($userRepository->findOneByEmail($email) === null) {
                $user->setEmail($email);
            } 
            else{
                return $this->json([
                    'error' => true,
                    'message' => "Cet e-mail est déjà utilisé par un autre compte.",
                    ], 400);
                }
        } else { return $this->json([
            'error' => true,
            'message' => "Le format de l'email est invalide",
        ], 400);
        }


        
        $user->setIdUser($data['idUser'] ?? uniqid()); 

        // Vérifier si le sexe est fourni
        if (isset($data['sexe'])) {
            $sexe = $data['sexe'];

            if ($sexe === '0' || $sexe === '1') {
                $user->setSexe($sexe);
            } else {
                return $this->json([
                    'error' => true,
                    'message' => "La valeur du champ sexe est invalide. Les valeurs autorisées sont de 0 pour Femme, 1 pour Homme.",
                ], 400);
            }
        }
        

       
        if (isset($data['tel'])) {
            $tel = $data['tel'];
            // Vérification du format du numéro de téléphone (format français)
            if (preg_match('/^0[1-9]([-. ]?[0-9]{2}){4}$/', $tel)) {
                $user->setTel($tel);
            } else {
                return $this->json([
                    'error' => true,
                    'message' => "Le format du numéro de téléphone est invalide.",
                ], 400);
            }
        }
        
        $dateOfBirthString = $data['date_birth'];

if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateOfBirthString)) {
    $dateOfBirth = DateTime::createFromFormat('d/m/Y', $dateOfBirthString);
    $now = new DateTime();
    
    // Vérifier si la personne a moins de 12 ans
    $age = $now->diff($dateOfBirth)->y;
    if ($age < 12) {
        return $this->json([
            'error' => true,
            'message' => "L'utilisateur doit avoir au moins 12 ans",
        ], 400);
    }

    $user->setDateBirth($dateOfBirth);  
    $user->setCreateAt(new DateTimeImmutable());
    $user->setUpdateAt(new DateTimeImmutable());
} else {
    return $this->json([
        'error' => true,
        'message' => "Le format de la date de naissance est invalide. Le format attendu est JJ/MM/AAAA.",
    ], 400); 
}

        
        
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
        $password = $data['password'];
          if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{8,}$/', $password)) {
            $password = $data['password'] ?? ''; 
            $hash = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hash);
          }
          else {
            return $this->json([
                'error' => true,
                'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum.',
            ], 400);
          }
        // Générer le hachage du mot de passe
        
        
        // Enregistrer l'utilisateur dans la base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Réponse de succès
        return $this->json([
            'error' => false,
            'message' => 'L\'utilisateur a bien été créé avec succès',
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
