<?php

namespace App\Controller;

use App\Service\TransactionService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1')]
class TransactionController extends AbstractController
{
    #[Route('/transactions', name: 'api_transactions', methods: ['GET'])]
    public function index(
        Request $request,
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

        $filter = $request->get('filter', []);

        $transactions = $transactionService->filter(
            array_merge(
                $filter,
                ['client' => $this->getUser()->getId()]
            )
        );

        $result = [];

        foreach ($transactions as $transaction) {
            $item = [
                'id' => $transaction->getId(),
                'create_at' => date_format($transaction->getCreateAt(), "Y-m-dTH:i:s"),
                'type' => $transaction->getTypeName(),
                'amount' => $transaction->getAmount(),
                'expires_at' => $transaction->getExpiresAt()
                    ? date_format($transaction->getCreateAt(), "Y-m-dTH:i:s")
                    : null
            ];

            if ($transaction->getTypeName() == 'payment') {
                $item['course_code'] = $transaction->getCourse()->getCode();
            }

            $result[] = $item;
        }

        return new JsonResponse([
            $result
        ], Response::HTTP_OK);
    }
}
