<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Entities\ProductSupplier;
use App\Repositories\Contracts\ProductSupplierRepositoryInterface;
use PDO;
use RuntimeException;

/**
 * PDO repository for product-supplier relationships.
 */
final class PdoProductSupplierRepository implements
    ProductSupplierRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Store a relationship between a product and supplier.
     */
    public function create(
        ProductSupplier $productSupplier
    ): ProductSupplier {
        $statement = $this->pdo->prepare(
            'INSERT INTO product_suppliers
                (product_id, supplier_id, purchase_price)
             VALUES
                (:product_id, :supplier_id, :purchase_price)'
        );

        $statement->execute([
            'product_id' => $productSupplier->getProductId(),
            'supplier_id' => $productSupplier->getSupplierId(),
            'purchase_price' => $productSupplier->getPurchasePrice(),
        ]);

        $createdRelation = $this->findByProductAndSupplier(
            $productSupplier->getProductId(),
            $productSupplier->getSupplierId()
        );

        if ($createdRelation === null) {
            throw new RuntimeException(
                'Unable to retrieve the created product-supplier relationship.'
            );
        }

        return $createdRelation;
    }

    /**
     * Find a relationship using both product and supplier IDs.
     */
    public function findByProductAndSupplier(
        int $productId,
        int $supplierId
    ): ?ProductSupplier {
        $statement = $this->pdo->prepare(
            'SELECT id, product_id, supplier_id, purchase_price
             FROM product_suppliers
             WHERE product_id = :product_id
               AND supplier_id = :supplier_id'
        );

        $statement->execute([
            'product_id' => $productId,
            'supplier_id' => $supplierId,
        ]);

        $data = $statement->fetch();

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    /**
     * Return all supplier relationships for one product.
     *
     * @return ProductSupplier[]
     */
    public function findByProductId(int $productId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, product_id, supplier_id, purchase_price
             FROM product_suppliers
             WHERE product_id = :product_id
             ORDER BY id'
        );

        $statement->execute([
            'product_id' => $productId,
        ]);

        $relations = [];

        while ($data = $statement->fetch()) {
            $relations[] = $this->mapToEntity($data);
        }

        return $relations;
    }

    /**
     * Convert a database row into an entity.
     *
     * @param array<string, mixed> $data
     */
    private function mapToEntity(array $data): ProductSupplier
    {
        return new ProductSupplier(
            (int) $data['product_id'],
            (int) $data['supplier_id'],
            (string) $data['purchase_price'],
            (int) $data['id']
        );
    }
}
