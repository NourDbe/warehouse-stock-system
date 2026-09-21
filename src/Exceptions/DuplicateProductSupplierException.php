<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a product is already associated
 * with the selected supplier.
 */
final class DuplicateProductSupplierException extends RuntimeException
{
}