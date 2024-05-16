<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1')]
class CourseController extends AbstractController
{
    #[Route('/courses', name: 'api_courses', methods: ['GET'])]
    public function index(CourseRepository $courseRepository): JsonResponse
    {
        return new JsonResponse(
            $courseRepository->findAll(),
            Response::HTTP_OK
        );
    }

    #[Route('/courses/{code}', name: 'api_course', methods: ['GET'])]
    public function show(Request $request, CourseRepository $courseRepository): JsonResponse
    {
        $course = $courseRepository->findOneBy(['code' => $request->get('code')]);

        if (empty($course)) {
            return new JsonResponse([
                'code' => 401,
                'message' => 'Курс не найден'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        return new JsonResponse($course, Response::HTTP_OK);
    }
}
