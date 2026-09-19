<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Entities\ProductSupplier;

/**
 * Defines persistence operations for the relationship
 * between products and suppliers.
 */
interface ProductSupplierRepositoryInterface
{
    /**
     * Store a product-supplier relationship.
     */
    public function create(
        ProductSupplier $productSupplier
    ): ProductSupplier;

    /**
     * Find a specific product-supplier relationship.
     */
    public function findByProductAndSupplier(
        int $productId,
        int $supplierId
    ): ?ProductSupplier;

    /**
     * Return all suppliers associated with a product.
     *
     * @return ProductSupplier[]
     */
    public function findByProductId(int $productId): array;
}
