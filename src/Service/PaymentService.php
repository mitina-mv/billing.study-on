<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Response;

class PaymentService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function payment(User $user, Course $course): Transaction
    {
        $this->em->getConnection()->beginTransaction();
        try {
            if ($user->getBalance() < $course->getPrice()) {
                throw new \Exception(
                    'На счету недостаточно средств',
                    Response::HTTP_NOT_ACCEPTABLE
                );
            }

            $user->setBalance($user->getBalance() - $course->getPrice());
            $this->em->persist($user);

            $transaction = new Transaction();
            $currentData = new \DateTimeImmutable('now');
            $courseType = $course->getTypeName();

            $transaction->setCourse($course);
            $transaction->setAmount($course->getPrice() ?? 0.0);
            $transaction->setClient($user);
            $transaction->setTypeName('payment');
            $transaction->setCreateAt($currentData);

            if ($courseType === "rent") {
                $transaction->setExpiresAt($currentData->add(new \DateInterval('P1W')));
            }

            $this->em->persist($transaction);
            $this->em->flush();
            
            $this->em->getConnection()->commit();

            return $transaction;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    public function deposit(User $user, float $amount): Transaction
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $transaction = new Transaction();
            $currentData = new \DateTimeImmutable('now');

            $transaction->setClient($user);
            $transaction->setTypeName('deposit');
            $transaction->setAmount($amount);
            $transaction->setCreateAt($currentData);

            $user->setBalance($user->getBalance() + $amount);

            $this->em->persist($user);
            $this->em->persist($transaction);
            $this->em->flush();

            $this->em->getConnection()->commit();

            return $transaction;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }
}
