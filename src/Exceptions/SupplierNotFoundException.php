<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a requested supplier does not exist.
 */
final class SupplierNotFoundException extends RuntimeException
{
}