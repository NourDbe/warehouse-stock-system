<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a supplier is not associated
 * with the requested product.
 */
final class ProductSupplierNotFoundException extends RuntimeException
{
}
