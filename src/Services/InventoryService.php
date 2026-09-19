<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\StockMovement;
use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\ProductSupplierNotFoundException;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductSupplierRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;

/**
 * Handles the main warehouse inventory business rules.
 *
 * This service does not contain SQL and does not use PDO directly.
 * Database operations are delegated to repository interfaces.
 */
final class InventoryService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductSupplierRepositoryInterface $productSupplierRepository,
        private StockMovementRepositoryInterface $stockMovementRepository
    ) {
    }

    /**
     * Receive stock from a supplier.
     *
     * The product must exist and the supplier must already
     * be associated with that product.
     */
    public function receiveStock(
        int $productId,
        int $supplierId,
        string $quantity
    ): StockMovement {
        // Make sure the product exists.
        $product = $this->productRepository->findById($productId);

        if ($product === null) {
            throw new ProductNotFoundException(
                "Product with ID {$productId} was not found."
            );
        }

        // Make sure this supplier is allowed to provide this product.
        $productSupplier =
            $this->productSupplierRepository
                ->findByProductAndSupplier(
                    $productId,
                    $supplierId
                );

        if ($productSupplier === null) {
            throw new ProductSupplierNotFoundException(
                'The selected supplier is not associated with this product.'
            );
        }

        // The StockMovement entity validates that the quantity
        // is positive and that IN movements have a supplier.
        $movement = new StockMovement(
            $productId,
            MovementType::IN,
            $quantity,
            $supplierId
        );

        return $this->stockMovementRepository->create($movement);
    }

    /**
     * Sell or remove stock from the warehouse.
     *
     * The requested quantity cannot exceed the current stock.
     */
    public function sellStock(
        int $productId,
        string $quantity
    ): StockMovement {
        // Make sure the product exists.
        $product = $this->productRepository->findById($productId);

        if ($product === null) {
            throw new ProductNotFoundException(
                "Product with ID {$productId} was not found."
            );
        }

        // Validate quantity before comparing it with current stock.
        if (!is_numeric($quantity) || (float) $quantity <= 0) {
            throw new \InvalidArgumentException(
                'Sale quantity must be greater than zero.'
            );
        }

        // Calculate stock from all previous IN and OUT movements.
        $currentStock =
            $this->stockMovementRepository
                ->getCurrentStock($productId);

        // Prevent selling more stock than is currently available.
        if ((float) $quantity > (float) $currentStock) {
            throw new InsufficientStockException(
                "Insufficient stock. Available: {$currentStock}, requested: {$quantity}."
            );
        }

        // OUT movements do not reference a supplier.
        $movement = new StockMovement(
            $productId,
            MovementType::OUT,
            $quantity
        );

        return $this->stockMovementRepository->create($movement);
    }

    /**
     * Return the current stock quantity for a product.
     */
    public function getCurrentStock(int $productId): string
    {
        // Prevent requesting stock for a product that does not exist.
        if ($this->productRepository->findById($productId) === null) {
            throw new ProductNotFoundException(
                "Product with ID {$productId} was not found."
            );
        }

        return $this->stockMovementRepository
            ->getCurrentStock($productId);
    }

    /**
     * Return all stock movements for a product.
     *
     * @return StockMovement[]
     */
    public function getMovementHistory(int $productId): array
    {
        if ($this->productRepository->findById($productId) === null) {
            throw new ProductNotFoundException(
                "Product with ID {$productId} was not found."
            );
        }

        return $this->stockMovementRepository
            ->findByProductId($productId);
    }
}
