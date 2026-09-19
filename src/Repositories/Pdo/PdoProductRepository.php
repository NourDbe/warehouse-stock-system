<?php

declare(strict_types=1);

namespace App\Repositories\Pdo;

use App\Entities\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use DateTimeImmutable;
use PDO;
use RuntimeException;

/**
 * PDO implementation of the product repository.
 *
 * All SQL related to products is isolated inside this class.
 */
final class PdoProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Store a new product in the database.
     */
    public function create(Product $product): Product
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO products (name)
             VALUES (:name)'
        );

        $statement->execute([
            'name' => $product->getName(),
        ]);

        // Retrieve the product again so that the returned
        // entity contains its generated ID and created_at value.
        $createdProduct = $this->findById(
            (int) $this->pdo->lastInsertId()
        );

        if ($createdProduct === null) {
            throw new RuntimeException(
                'Unable to retrieve the created product.'
            );
        }

        return $createdProduct;
    }

    /**
     * Find a product by its primary key.
     */
    public function findById(int $id): ?Product
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, created_at
             FROM products
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
     * Find a product by its unique name.
     */
    public function findByName(string $name): ?Product
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, created_at
             FROM products
             WHERE name = :name'
        );

        $statement->execute([
            'name' => $name,
        ]);

        $data = $statement->fetch();

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    /**
     * Return all products ordered by their ID.
     *
     * @return Product[]
     */
    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, created_at
             FROM products
             ORDER BY id'
        );

        $products = [];

        while ($data = $statement->fetch()) {
            $products[] = $this->mapToEntity($data);
        }

        return $products;
    }

    /**
     * Convert a database row into a Product entity.
     *
     * @param array<string, mixed> $data
     */
    private function mapToEntity(array $data): Product
    {
        return new Product(
            $data['name'],
            (int) $data['id'],
            new DateTimeImmutable($data['created_at'])
        );
    }
}
