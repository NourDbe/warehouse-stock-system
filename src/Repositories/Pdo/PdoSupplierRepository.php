<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Entities\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use DateTimeImmutable;
use PDO;
use RuntimeException;

/**
 * PDO implementation of the supplier repository.
 *
 * Supplier-related SQL is kept isolated from
 * the rest of the application.
 */
final class PdoSupplierRepository implements SupplierRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Store a new supplier.
     */
    public function create(Supplier $supplier): Supplier
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO suppliers (name, phone)
             VALUES (:name, :phone)'
        );

        $statement->execute([
            'name' => $supplier->getName(),
            'phone' => $supplier->getPhone(),
        ]);

        $createdSupplier = $this->findById(
            (int) $this->pdo->lastInsertId()
        );

        if ($createdSupplier === null) {
            throw new RuntimeException(
                'Unable to retrieve the created supplier.'
            );
        }

        return $createdSupplier;
    }

    /**
     * Find a supplier by ID.
     */
    public function findById(int $id): ?Supplier
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, phone, created_at
             FROM suppliers
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $data = $statement->fetch();

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    /**
     * Find a supplier by phone number.
     */
    public function findByPhone(string $phone): ?Supplier
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, phone, created_at
             FROM suppliers
             WHERE phone = :phone'
        );

        $statement->execute([
            'phone' => $phone,
        ]);

        $data = $statement->fetch();

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    /**
     * Return all suppliers.
     *
     * @return Supplier[]
     */
    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, phone, created_at
             FROM suppliers
             ORDER BY id'
        );

        $suppliers = [];

        while ($data = $statement->fetch()) {
            $suppliers[] = $this->mapToEntity($data);
        }

        return $suppliers;
    }

    /**
     * Convert a database row into a Supplier entity.
     *
     * @param array<string, mixed> $data
     */
    private function mapToEntity(array $data): Supplier
    {
        return new Supplier(
            $data['name'],
            $data['phone'],
            (int) $data['id'],
            new DateTimeImmutable($data['created_at'])
        );
    }
}
