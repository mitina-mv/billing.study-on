<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;

class TransactionService
{
    public function __construct(
        private TransactionRepository $transactionRepository
    ) {
    }
    
    public function filter(array $filters = [])
    {
        $queryBuilder = $this->transactionRepository->createQueryBuilder('t');

        // тип транзакции payment|deposit
        if (!empty($filters['type'])) {
            $type = Transaction::TYPE_NAMES[$filters['type']];

            $queryBuilder->andWhere('t.type = :type')
                ->setParameter('type', $type);
        }

        if (!empty($filters['course_code'])) {
            $queryBuilder->andWhere('t.code = :code')
                ->setParameter('code', $filters['course_code']);
        }

        // оплаты аренд, которые уже истекли
        if (!empty($filters['skip_expired'])) {
            $currentData = new \DateTimeImmutable('now');

            $queryBuilder->andWhere('t.expires_at > :currentDate')
                 ->setParameter('currentDate', $currentData);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
