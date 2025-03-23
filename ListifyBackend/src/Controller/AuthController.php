<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(UserInterface $user, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $token = $JWTManager->createFromPayload($user, [
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'username' => $user->getUserIdentifier(),
        ]);

        return new JsonResponse(['token' => $token]);
    }

    #[Route(path: '/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(TokenStorageInterface $tokenStorage): JsonResponse
    {
        // Obtener el token actual del almacenamiento de seguridad
        $token = $tokenStorage->getToken();

        // Verificar si hay un token válido
        if ($token) {
            $tokenStorage->setToken(null); // Invalida el token en la sesión del servidor
        }

        return new JsonResponse(['message' => 'Logout exitoso'], 200);
    }

    #[Route(path: '/verify-user', name: 'app_auth_verify_user', methods: ['GET'])]
    public function user(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['valid' => false, 'message' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'valid' => true,
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'rol' => $user->getRol()?->value, // Se asegura de devolver el valor correcto
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['username'], $data['email'], $data['password'], $data['name'], $data['surname'], $data['birthdate'], $data['dni'], $data['address'], $data['phone_number'])) {
            return new JsonResponse(['error' => 'Faltan datos'], Response::HTTP_BAD_REQUEST);
        }

        // Verificar si ya existe el usuario (email o username)
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']])
            ?? $entityManager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'El usuario ya existe'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setRol(UserRoleEnum::USER);

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $client = new Client();
        $client->setName($data['name']);
        $client->setSurname($data['surname']);

        try {
            $date = \DateTime::createFromFormat('Y-m-d', $data['birthdate']);
            if (!$date) {
                return new JsonResponse(['error' => 'Formato de fecha inválido (Y-m-d esperado)'], Response::HTTP_BAD_REQUEST);
            }
            $client->setBirthdate($date);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error al procesar la fecha'], Response::HTTP_BAD_REQUEST);
        }

        $client->setDni($data['dni']);
        $client->setAddress($data['address']);
        $client->setPhoneNumber($data['phone_number']);

        $user->setClient($client);

        $entityManager->beginTransaction();
        try {
            $entityManager->persist($user);
            $entityManager->persist($client);
            $entityManager->flush();
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
            return new JsonResponse(['error' => 'Error al registrar el usuario'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'message' => 'Usuario y cliente creados con éxito',
            'user_id' => $user->getId(),
            'cliente_id' => $client->getId()
        ], Response::HTTP_CREATED);
    }
}



