<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an outgoing stock operation requests
 * more quantity than is currently available.
 */
final class InsufficientStockException extends RuntimeException
{
}
