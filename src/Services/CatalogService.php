<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Product;
use App\Entities\ProductSupplier;
use App\Entities\Supplier;
use App\Exceptions\DuplicateProductException;
use App\Exceptions\DuplicateProductSupplierException;
use App\Exceptions\DuplicateSupplierException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\SupplierNotFoundException;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductSupplierRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;

/**
 * Handles business rules related to products,
 * suppliers and their relationships.
 *
 * This service contains no SQL and does not use PDO directly.
 */
final class CatalogService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private SupplierRepositoryInterface $supplierRepository,
        private ProductSupplierRepositoryInterface $productSupplierRepository
    ) {
    }

    /**
     * Create a new product after checking for duplicates.
     */
    public function createProduct(string $name): Product
    {
        // Creating the entity also normalizes its name.
        $product = new Product($name);

        $existingProduct =
            $this->productRepository->findByName(
                $product->getName()
            );

        if ($existingProduct !== null) {
            throw new DuplicateProductException(
                'A product with this name already exists.'
            );
        }

        return $this->productRepository->create($product);
    }

    /**
     * Create a new supplier after normalizing
     * and checking its phone number.
     */
    public function createSupplier(
        string $name,
        string $phone
    ): Supplier {
        // Supplier normalizes both name and phone.
        $supplier = new Supplier(
            $name,
            $phone
        );

        $existingSupplier =
            $this->supplierRepository->findByPhone(
                $supplier->getPhone()
            );

        if ($existingSupplier !== null) {
            throw new DuplicateSupplierException(
                'A supplier with this phone number already exists.'
            );
        }

        return $this->supplierRepository->create($supplier);
    }

    /**
     * Associate a supplier with a product
     * and define the purchase price.
     */
    public function linkProductToSupplier(
        int $productId,
        int $supplierId,
        string $purchasePrice
    ): ProductSupplier {
        // Make sure the product exists.
        if ($this->productRepository->findById($productId) === null) {
            throw new ProductNotFoundException(
                "Product with ID {$productId} was not found."
            );
        }

        // Make sure the supplier exists.
        if ($this->supplierRepository->findById($supplierId) === null) {
            throw new SupplierNotFoundException(
                "Supplier with ID {$supplierId} was not found."
            );
        }

        // Prevent the same product-supplier relationship
        // from being created more than once.
        $existingRelation =
            $this->productSupplierRepository
                ->findByProductAndSupplier(
                    $productId,
                    $supplierId
                );

        if ($existingRelation !== null) {
            throw new DuplicateProductSupplierException(
                'This supplier is already associated with this product.'
            );
        }

        $productSupplier = new ProductSupplier(
            $productId,
            $supplierId,
            $purchasePrice
        );

        return $this->productSupplierRepository
            ->create($productSupplier);
    }
}