<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when trying to create a product
 * that already exists.
 */
final class DuplicateProductException extends RuntimeException
{
}