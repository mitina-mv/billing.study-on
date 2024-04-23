<?php

namespace App\Controller;

use App\DTO\UserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
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

    #[Route('/register', name: 'api_auth', methods: ['POST'])]
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
                'code' => 401,
                'errors' => $jsonErrors
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = User::convertDtoToUser($dto);
        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'token' => $jwtManager->create($user),
            'roles' => $user->getRoles(),
        ], Response::HTTP_CREATED);
    }
}
