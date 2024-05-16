<?php

namespace App\Controller;

use App\DTO\UserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;

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
    #[OA\Post(
        path: '/api/v1/auth',
        description: "Входные данные: email и пароль.
        \nВыходные данные:
        \n - JSON с JWT-токеном в случае успеха, 
        \n - JSON с ошибками в случае возникновения ошибок",
        summary: "Аутентификация пользователя"
    )]

    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'username', type: 'string'),
                new OA\Property(property: 'password', type: 'string')
            ],
            type: 'object'
        )
    )]

    #[OA\Response(
        response: 201,
        description: 'Успешная аутентификация',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string'),
            ],
            type: 'object'
        )
    )]

    #[OA\Response(
        response: 401,
        description: 'Невалидные данные',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 401),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Invalid credentials.'
                )
            ],
            type: 'object'
        )
    )]

    #[OA\Response(
        response: 500,
        description: 'Ошибка сервера',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string')
            ],
            type: 'object'
        )
    )]

    #[OA\Tag(
        name: "User"
    )]
    public function auth() : void
    {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/register',
        description: "Входные данные: email и пароль.
        \nВыходные данные:
        \n - JSON с JWT-токеном в случае успеха, 
        \n - JSON с ошибками в случае возникновения ошибок",
        summary: "Регистрация пользователя"
    )]

    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'username', type: 'string'),
                new OA\Property(property: 'password', type: 'string')
            ],
            type: 'object'
        )
    )]

    #[OA\Response(
        response: 201,
        description: 'Успешная регистрация',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string'),
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(
                    property: 'roles',
                    type: 'array',
                    items: new OA\Items(type: "string")
                ),
            ],
            type: 'object'
        )
    )]
    

    #[OA\Response(
        response: 400,
        description: 'Невалидные данные',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 400),
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(type: "string")
                )
            ],
            type: 'object'
        )
    )]

    #[OA\Response(
        response: 500,
        description: 'Ошибка сервера',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string')
            ],
            type: 'object'
        )
    )]
    #[OA\Tag(
        name: "User"
    )]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
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
                'code' => Response::HTTP_BAD_REQUEST,
                'errors' => $jsonErrors
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)
            ->findOneBy(['email' => $dto->username]);

        if ($user !== null) {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'errors' => [
                    "username" => 'Email должен быть уникальным.'
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = User::convertDtoToUser($dto);
        $em->persist($user);
        $em->flush();

        // добавляем refresh token
        $refreshToken = $refreshTokenGenerator->createForUserWithTtl(
            $user,
            (new \DateTime())->modify('+1 month')->getTimestamp()
        );
        $refreshTokenManager->save($refreshToken);

        return new JsonResponse([
            'token' => $jwtManager->create($user),
            'roles' => $user->getRoles(),
            'code' => Response::HTTP_CREATED,
            'refresh_token' => $refreshToken->getRefreshToken(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/users/current', name: 'api_current', methods: ['GET'])]

    #[OA\Get(
        path: '/api/v1/users/current',
        description: "Входные данные - JWT-токен.
        \nВыходные данные 
        \n - Объект пользователя в случае успеха, 
        \n - JSON с ошибками в случае возникновения ошибок",
        summary: "Получение текущего пользователя"
    )]

    #[OA\Response(
        response: 200,
        description: 'Успешное получение пользователя',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 200),
                new OA\Property(property: 'username', type: 'string'),
                new OA\Property(
                    property: 'roles',
                    type: 'array',
                    items: new OA\Items(type: "string")
                ),
                new OA\Property(property: 'balance', type: 'integer', example: 0)
            ],
            type: 'object'
        )
    )]

    #[OA\Response(
        response: 401,
        description: 'Невалидный JWT-токен',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 401),
                new OA\Property(property: 'errors', type: 'string', example: "Invalid JWT Token")
            ],
            type: 'object'
        )
    )]

    #[OA\Response(
        response: 500,
        description: 'Ошибка сервера',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string')
            ],
            type: 'object'
        )
    )]
    #[OA\Tag(
        name: "User"
    )]

    #[Security(name: "Bearer")]
    public function getCurrentUser(): JsonResponse
    {
        // TODO ошибка 401 возвращается преищумественное от JWT токена
        // возможно, проверка лишняя
        if ($this->getUser() === null) {
            return new JsonResponse([
                'code' => 401,
                "errors" => [
                    'unauthorized'=>'Пользователь неавторизован.'
                ]
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'code' => 200,
            'username' => $this->getUser()->getEmail(),
            'roles' => $this->getUser()->getRoles(),
            'balance' => $this->getUser()->getBalance(),
        ], Response::HTTP_OK);
    }

    #[Route('/token/refresh', name: 'api_refresh', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/token/refresh',
        summary: "Обновление JWT-токена"
    )]
    #[OA\Tag(
        name: "User"
    )]
    public function refresh(): void
    {
    }
}
