<?php

namespace Shared\Application\Transaction;

interface TransactionManager
{
    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function transactional(callable $callback, int $attempts = 1): mixed;
}
