<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Entities\StockMovement;

/**
 * Defines persistence operations for stock movements.
 */
interface StockMovementRepositoryInterface
{
    /**
     * Store a new stock movement.
     */
    public function create(
        StockMovement $movement
    ): StockMovement;

    /**
     * Return all movements for a specific product.
     *
     * @return StockMovement[]
     */
    public function findByProductId(int $productId): array;

    /**
     * Calculate the current stock quantity of a product
     * from all recorded IN and OUT movements.
     */
    public function getCurrentStock(int $productId): string;
}
