<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Entities\Supplier;

/**
 * Defines persistence operations for suppliers.
 */
interface SupplierRepositoryInterface
{
    /**
     * Store a new supplier.
     */
    public function create(Supplier $supplier): Supplier;

    /**
     * Find a supplier by ID.
     */
    public function findById(int $id): ?Supplier;

    /**
     * Find a supplier by phone number.
     *
     * Phone lookup helps detect duplicate supplier records.
     */
    public function findByPhone(string $phone): ?Supplier;

    /**
     * Return all suppliers.
     *
     * @return Supplier[]
     */
    public function findAll(): array;
}
