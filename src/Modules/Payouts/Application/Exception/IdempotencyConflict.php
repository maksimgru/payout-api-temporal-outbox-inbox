<?php

namespace Modules\Payouts\Application\Exception;

use RuntimeException;

final class IdempotencyConflict extends RuntimeException
{
}
