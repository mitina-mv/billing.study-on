<?php

namespace App\Controller;

use App\Exception\PaymentException;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use App\Service\PaymentService;
use App\Service\TransactionService;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1')]
class CourseController extends AbstractController
{
    private $removeFieldsToArray = ['id', 'transactions'];
    
    public function __construct(
        private CourseRepository $courseRepository,
    ) {
    }

    #[Route('/courses', name: 'api_courses', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $courses = $this->courseRepository->findAll();
        $result = [];

        foreach ($courses as $course) {
            $item = $course->toArray();
            $item['type'] = $course->getTypeName();

            foreach ($this->removeFieldsToArray as $code) {
                unset($item[$code]);
            }

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
                'errors' => [
                    'course' => 'Курс не найден'
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $course->toArray();
        $result['type'] = $course->getTypeName();

        foreach ($this->removeFieldsToArray as $code) {
            unset($result[$code]);
        }
        
        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[Route('/courses/{code}/pay', name: 'api_course_pay', methods: ['POST'])]
    public function pay(
        Request $request,
        PaymentService $paymentService,
        TransactionService $transactionService,
    ): JsonResponse {
        if ($this->getUser() === null) {
            return new JsonResponse([
                'code' => 401,
                "errors" => [
                    'unauthorized'=>'Пользователь неавторизован.'
                ]
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $course = $this->courseRepository->findOneBy(['code' => $request->get('code')]);
        $user = $this->getUser();

        if (empty($course)) {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'errors' => [
                    'course' => 'Курс не найден'
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        // проверяю что пользователь не совершал актуальных действия покупки с этим курсом
        $transactions = $transactionService->filter([
            'client' => $user->getId(),
            'course_code' => $request->get('code'),
            'type' => 'payment',
            'skip_expired' => true
        ]);

        if (count($transactions) !== 0) {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'errors' => [
                    'course' => 'Доступ к курсу актуален. Оплата не требуется.'
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $paymentService->payment($user, $course);
        } catch (PaymentException $e) { // ошибка оплаты
            $error = [
                'mes' => $e->getMessage(),
                'code' => Response::HTTP_NOT_ACCEPTABLE
            ];
        } catch (\Exception $e) {
            $error = [
                'mes' => 'Произошла непредвиденная ошибка. Повторите запрос позже.',
                'code' => Response::HTTP_BAD_REQUEST
            ];
        } finally {
            if (isset($error)) {
                return new JsonResponse([
                    'code' => $error['code'],
                    'errors' => [
                        'payment' => $error['mes']
                    ]
                ], $error['code']);
            }
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }
}
