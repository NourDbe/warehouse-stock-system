<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Entities\StockMovement;
use App\Enums\MovementType;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use DateTimeImmutable;
use PDO;
use RuntimeException;

/**
 * PDO implementation for stock movements.
 *
 * This repository stores movement history and calculates
 * current stock from IN and OUT records.
 */
final class PdoStockMovementRepository implements
    StockMovementRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Store a new stock movement.
     */
    public function create(
        StockMovement $movement
    ): StockMovement {
        $statement = $this->pdo->prepare(
            'INSERT INTO stock_movements
                (product_id, supplier_id, type, quantity)
             VALUES
                (:product_id, :supplier_id, :type, :quantity)'
        );

        $statement->execute([
            'product_id' => $movement->getProductId(),
            'supplier_id' => $movement->getSupplierId(),
            'type' => $movement->getType()->value,
            'quantity' => $movement->getQuantity(),
        ]);

        $createdMovement = $this->findById(
            (int) $this->pdo->lastInsertId()
        );

        if ($createdMovement === null) {
            throw new RuntimeException(
                'Unable to retrieve the created stock movement.'
            );
        }

        return $createdMovement;
    }

    /**
     * Find one stock movement by its ID.
     *
     * This method is private because it is only needed
     * internally after inserting a new movement.
     */
    private function findById(int $id): ?StockMovement
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                product_id,
                supplier_id,
                type,
                quantity,
                created_at
             FROM stock_movements
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
     * Return the movement history for a product.
     *
     * @return StockMovement[]
     */
    public function findByProductId(int $productId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                product_id,
                supplier_id,
                type,
                quantity,
                created_at
             FROM stock_movements
             WHERE product_id = :product_id
             ORDER BY created_at ASC, id ASC'
        );

        $statement->execute([
            'product_id' => $productId,
        ]);

        $movements = [];

        while ($data = $statement->fetch()) {
            $movements[] = $this->mapToEntity($data);
        }

        return $movements;
    }

    /**
     * Calculate current stock from movement history.
     *
     * IN quantities are added.
     * OUT quantities are subtracted.
     */
    public function getCurrentStock(int $productId): string
    {
        $statement = $this->pdo->prepare(
            "SELECT COALESCE(
                SUM(
                    CASE
                        WHEN type = 'IN' THEN quantity
                        WHEN type = 'OUT' THEN -quantity
                        ELSE 0
                    END
                ),
                0
            ) AS current_stock
            FROM stock_movements
            WHERE product_id = :product_id"
        );

        $statement->execute([
            'product_id' => $productId,
        ]);

        $data = $statement->fetch();

        return (string) ($data['current_stock'] ?? '0');
    }

    /**
     * Convert a database row into a StockMovement entity.
     *
     * @param array<string, mixed> $data
     */
    private function mapToEntity(array $data): StockMovement
    {
        return new StockMovement(
            (int) $data['product_id'],
            MovementType::from($data['type']),
            (string) $data['quantity'],
            $data['supplier_id'] !== null
                ? (int) $data['supplier_id']
                : null,
            (int) $data['id'],
            new DateTimeImmutable($data['created_at'])
        );
    }
}
