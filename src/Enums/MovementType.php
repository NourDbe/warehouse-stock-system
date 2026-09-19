<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Represents the allowed stock movement types.
 *
 * IN  means stock entering the warehouse.
 * OUT means stock leaving the warehouse.
 */
enum MovementType: string
{
    case IN = 'IN';
    case OUT = 'OUT';
}