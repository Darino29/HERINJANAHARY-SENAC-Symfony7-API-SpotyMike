<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginController extends AbstractController
{

    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/login', name: 'app_login', methods: 'GET')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to MikeLand',
            'path' => 'src/Controller/LoginController.php',
            ]);
        }
        
    // use Symfony\Component\HttpFoundation\Request;
    #[Route('/login', name: 'app_login_post', methods: ['POST', 'PUT'])]
    public function login(Request $request): JsonResponse
    {

        $user = $this->repository->findOneBy(["email" => "mike.sylvestre@lyknowledge.io"]);

        $parameters = json_decode($request->getContent(), true);
        return $this->json([
            'user' => json_encode($user->serializer()),
            'data' => $request->getContent(),
            'message' => 'Welcome to MikeLand',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }
}