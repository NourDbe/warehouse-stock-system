<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a requested product does not exist.
 */
final class ProductNotFoundException extends RuntimeException
{
}
