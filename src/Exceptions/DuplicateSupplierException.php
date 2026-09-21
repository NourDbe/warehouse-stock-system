<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when trying to create a supplier
 * with a phone number that already exists.
 */
final class DuplicateSupplierException extends RuntimeException
{
}