<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1')]
class CourseController extends AbstractController
{
    private $serializer;
    private $courseRepository;
    
    public function __construct(
        SerializerInterface $serializer,
        CourseRepository $courseRepository,
    ) {
        $this->courseRepository = $courseRepository;
        $this->serializer = $serializer;
    }

    #[Route('/courses', name: 'api_courses', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $courses = $this->courseRepository->findAll();
        $result = [];

        foreach ($courses as $course) {
            $item = $this->serializer->serialize($course, 'null');
            $item['type'] = $course->getTypeName();

            $result[] = $item;
        }
        
        return new JsonResponse(
            $result,
            Response::HTTP_OK
        );
    }

    #[Route('/courses/{code}', name: 'api_course', methods: ['GET'])]
    public function show(Request $request): JsonResponse
    {
        $course = $this->courseRepository->findOneBy(['code' => $request->get('code')]);

        if (empty($course)) {
            return new JsonResponse([
                'code' => 401,
                'message' => 'Курс не найден'
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->serializer->normalize($course, null);
        $result['type'] = $course->getTypeName();
        
        return new JsonResponse($result, Response::HTTP_OK);
    }
}
