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

    public function payment(User $user, Course $course): array
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

            $result = [
                'success' => true,
                'course_type' => $courseType,
            ];

            if ($courseType === 'rent') {
                $result['expires_at'] = date_format($transaction->getExpiresAt(), "Y-m-dTH:i:s");
            }

            return $result;
        } catch (ORMException | \Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }
}
