<?php

namespace Shared\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Application\Transaction\TransactionManager;
use Throwable;

final readonly class DoctrineTransactionManager implements TransactionManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function transactional(callable $callback, int $attempts = 1): mixed
    {
        $attempts = max(1, $attempts);
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $connection = $this->entityManager->getConnection();
            $connection->beginTransaction();

            try {
                $result = $callback();
                $this->entityManager->flush();
                $connection->commit();

                return $result;
            } catch (Throwable $exception) {
                $lastException = $exception;

                if ($connection->isTransactionActive()) {
                    $connection->rollBack();
                }

                $this->entityManager->clear();

                if ($attempt === $attempts) {
                    throw $exception;
                }
            }
        }

        throw $lastException;
    }
}
