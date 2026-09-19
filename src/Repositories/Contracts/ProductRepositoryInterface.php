<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Entities\Product;

/**
 * Defines the operations available for product persistence.
 *
 * The application depends on this interface instead of
 * depending directly on PDO or SQL.
 */
interface ProductRepositoryInterface
{
    /**
     * Store a new product and return the persisted entity.
     */
    public function create(Product $product): Product;

    /**
     * Find a product by its ID.
     *
     * Returns null when the product does not exist.
     */
    public function findById(int $id): ?Product;

    /**
     * Find a product by its name.
     *
     * Returns null when no matching product exists.
     */
    public function findByName(string $name): ?Product;

    /**
     * Return all products.
     *
     * @return Product[]
     */
    public function findAll(): array;
}
