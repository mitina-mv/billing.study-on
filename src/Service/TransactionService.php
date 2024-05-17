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
            $queryBuilder->innerJoin('t.course', 'c', 'WITH', 'c.id = t.course')
                ->andWhere('c.code = :code')
                ->setParameter('code', $filters['course_code']);
        }

        // оплаты аренд, которые уже истекли
        if (!empty($filters['skip_expired'])) {
            $currentData = new \DateTimeImmutable('now');

            $queryBuilder->andWhere('t.expires_at > :currentDate OR t.expires_at is null')
                 ->setParameter('currentDate', $currentData);
        }

        if (!empty($filters['client'])) {
            $queryBuilder->andWhere('t.client = :client')
                ->setParameter('client', $filters['client']);
        }

        // dd($queryBuilder);

        return $queryBuilder
            ->orderBy('t.create_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
