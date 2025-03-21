<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    #[Route(path: '/verify-user', name: 'app_auth_verify_user', methods: ['GET'])]
    public function user(): JsonResponse
    {
        $user = $this->getUser(); // Obtiene el usuario autenticado

        if (!$user instanceof User) {
            return new JsonResponse(['valid' => false, 'message' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'valid' => true,
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'rol' => $user->getRol()?->name,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): void
    {
        throw new \LogicException('Este método es manejado por el firewall de seguridad.');
    }

    /**
     * @throws \Exception
     */
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        // Decodificar JSON de la petición
        $data = json_decode($request->getContent(), true);

        // Validar datos requeridos
        if (!isset($data['username'], $data['email'], $data['password'], $data['name'], $data['surname'], $data['birthdate'], $data['dni'], $data['address'], $data['phone_number'])) {
            return new JsonResponse(['error' => 'Faltan datos'], Response::HTTP_BAD_REQUEST);
        }

        // Verificar si el usuario ya existe
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]) or $entityManager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'El usuario ya existe'], Response::HTTP_CONFLICT);
        }

        // Crear nuevo usuario
        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setRol(UserRoleEnum::USER);

        // Hashear la contraseña
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Crear cliente asociado
        $client = new Client();
        $client->setName($data['name']);
        $client->setSurname($data['surname']);
        try {
            $date = new \DateTime($data['birthdate']);
            $client->setBirthdate($date);
        } catch (\Exception $e) {
            throw new \Exception('Error al procesar la fecha: ' . $e->getMessage());
        }
        $client->setDni($data['dni']);
        $client->setAddress($data['address']);
        $client->setPhoneNumber($data['phone_number']);

        //Añadir cliente a ususario
        $user->setClient($client);

        // Guardar usuario y cliente en la base de datos
        $entityManager->persist($user);
        $entityManager->persist($client);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Usuario y cliente creados con éxito',
            'user_id' => $user->getId(),
            'cliente_id' => $client->getId()
        ], Response::HTTP_CREATED);
    }
}



