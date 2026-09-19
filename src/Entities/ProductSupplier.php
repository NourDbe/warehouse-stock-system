<?php

declare(strict_types=1);

namespace App\Entities;

use InvalidArgumentException;

/**
 * Represents the relationship between a product
 * and one of its suppliers.
 *
 * The purchase price belongs to this relationship
 * because the same product can have a different
 * price for each supplier.
 */
final class ProductSupplier
{
    /**
     * Create a product-supplier relationship.
     */
    public function __construct(
        private int $productId,
        private int $supplierId,
        private string $purchasePrice,
        private ?int $id = null
    ) {
        // Product and supplier IDs must reference valid records.
        if ($this->productId <= 0) {
            throw new InvalidArgumentException(
                'Product ID must be greater than zero.'
            );
        }

        if ($this->supplierId <= 0) {
            throw new InvalidArgumentException(
                'Supplier ID must be greater than zero.'
            );
        }

        // DECIMAL values are kept as strings to avoid
        // floating-point precision problems.
        if (
            !is_numeric($this->purchasePrice)
            || (float) $this->purchasePrice < 0
        ) {
            throw new InvalidArgumentException(
                'Purchase price must be zero or greater.'
            );
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getPurchasePrice(): string
    {
        return $this->purchasePrice;
    }
}