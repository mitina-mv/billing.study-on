<?php

namespace App\Controller;

use App\DTO\UserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1')]
class AuthController extends AbstractController
{
    private ValidatorInterface $validator;

    public function __construct(
        ValidatorInterface $validator,
    ) {
        $this->validator = $validator;
    }

    #[Route('/auth', name: 'api_auth', methods: ['POST'])]
    public function auth() : void
    {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
    ) : JsonResponse {
        $serializer = SerializerBuilder::create()->build();
        $dto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            $jsonErrors = [];
            foreach ($errors as $error) {
                $jsonErrors[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse([
                'code' => 400,
                'errors' => $jsonErrors
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)
            ->findOneBy(['email' => $dto->username]);

        if ($user !== null) {
            return new JsonResponse([
                'code' => 400,
                'errors' => [
                    "unique" => 'Email должен быть уникальным.'
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = User::convertDtoToUser($dto);
        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'token' => $jwtManager->create($user),
            'roles' => $user->getRoles(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/users/current', name: 'api_current', methods: ['GET'])]
    public function getCurrentUser(): JsonResponse
    {
        // TODO ошибка 401 возвращается преищумественное от JWT токена
        // возможно, проверка лишняя
        if ($this->getUser() === null) {
            return new JsonResponse([
                'code' => 401,
                "message" => 'Пользователь неавторизован.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'code' => 200,
            'username' => $this->getUser()->getEmail(),
            'roles' => $this->getUser()->getRoles(),
            'balance' => $this->getUser()->getBalance(),
        ], Response::HTTP_OK);
    }
}
