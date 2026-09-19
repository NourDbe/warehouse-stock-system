<?php

declare(strict_types=1);

use App\Database\DatabaseConnection;
use App\Entities\Product;
use App\Entities\ProductSupplier;
use App\Entities\Supplier;
use App\Exceptions\InsufficientStockException;
use App\Repositories\Pdo\PdoProductRepository;
use App\Repositories\Pdo\PdoProductSupplierRepository;
use App\Repositories\Pdo\PdoStockMovementRepository;
use App\Repositories\Pdo\PdoSupplierRepository;
use App\Services\InventoryService;

// Load Composer's PSR-4 autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

// Load database configuration.
$config = require __DIR__ . '/../config/database.php';

try {
    // -----------------------------------------------------
    // 1. Create the PDO connection.
    // -----------------------------------------------------
    $pdo = DatabaseConnection::create($config);

    // -----------------------------------------------------
    // 2. Create repository objects.
    //
    // All SQL and PDO operations remain inside repositories.
    // -----------------------------------------------------
    $productRepository =
        new PdoProductRepository($pdo);

    $supplierRepository =
        new PdoSupplierRepository($pdo);

    $productSupplierRepository =
        new PdoProductSupplierRepository($pdo);

    $stockMovementRepository =
        new PdoStockMovementRepository($pdo);

    // -----------------------------------------------------
    // 3. Create the inventory service.
    //
    // The service contains business logic and depends
    // on repository interfaces instead of PDO directly.
    // -----------------------------------------------------
    $inventoryService = new InventoryService(
        $productRepository,
        $productSupplierRepository,
        $stockMovementRepository
    );

    echo "=== Warehouse Stock System Demo ===" . PHP_EOL;
    echo PHP_EOL;

    // -----------------------------------------------------
    // 4. Create a demo product.
    //
    // A timestamp is added to the name so the demo can
    // be executed more than once without violating the
    // UNIQUE constraint on product names.
    // -----------------------------------------------------
    $productName =
        'Cement 50kg Demo ' . date('YmdHis');

    $product = $productRepository->create(
        new Product($productName)
    );

    echo "Product created:" . PHP_EOL;
    echo "ID: {$product->getId()}" . PHP_EOL;
    echo "Name: {$product->getName()}" . PHP_EOL;
    echo PHP_EOL;

    // -----------------------------------------------------
    // 5. Find or create the demo supplier.
    //
    // Phone is unique in the suppliers table, so we first
    // check whether this supplier already exists.
    // -----------------------------------------------------
    $supplierPhone = '0991111111';

    $supplier = $supplierRepository
        ->findByPhone($supplierPhone);

    if ($supplier === null) {
        $supplier = $supplierRepository->create(
            new Supplier(
                'Al Noor Trading',
                $supplierPhone
            )
        );

        echo "Supplier created." . PHP_EOL;
    } else {
        echo "Existing supplier reused." . PHP_EOL;
    }

    echo "Supplier ID: {$supplier->getId()}" . PHP_EOL;
    echo "Supplier: {$supplier->getName()}" . PHP_EOL;
    echo PHP_EOL;

    // -----------------------------------------------------
    // 6. Link the product with the supplier.
    //
    // The purchase price belongs to the relationship
    // between the product and supplier.
    // -----------------------------------------------------
    $productSupplier =
        new ProductSupplier(
            $product->getId(),
            $supplier->getId(),
            '5.50'
        );

    $productSupplierRepository->create(
        $productSupplier
    );

    echo "Product linked to supplier." . PHP_EOL;
    echo "Purchase price: 5.50" . PHP_EOL;
    echo PHP_EOL;

    // -----------------------------------------------------
    // 7. Receive 100 units into the warehouse.
    //
    // This creates an IN stock movement.
    // -----------------------------------------------------
    $inventoryService->receiveStock(
        $product->getId(),
        $supplier->getId(),
        '100'
    );

    echo "Received stock: +100" . PHP_EOL;

    $currentStock =
        $inventoryService->getCurrentStock(
            $product->getId()
        );

    echo "Current stock: {$currentStock}" . PHP_EOL;
    echo PHP_EOL;

    // -----------------------------------------------------
    // 8. Sell 30 units.
    //
    // This creates an OUT movement.
    // -----------------------------------------------------
    $inventoryService->sellStock(
        $product->getId(),
        '30'
    );

    echo "Sold stock: -30" . PHP_EOL;

    $currentStock =
        $inventoryService->getCurrentStock(
            $product->getId()
        );

    echo "Current stock: {$currentStock}" . PHP_EOL;
    echo PHP_EOL;

    // -----------------------------------------------------
    // 9. Try to sell more than the available stock.
    //
    // Current stock should now be 70.
    // Trying to sell 100 must fail.
    // -----------------------------------------------------
    echo "Trying to sell 100 units..." . PHP_EOL;

    try {
        $inventoryService->sellStock(
            $product->getId(),
            '100'
        );

        // This line should never execute.
        echo "ERROR: Overselling was allowed." . PHP_EOL;
    } catch (InsufficientStockException $exception) {
        echo "Sale rejected correctly." . PHP_EOL;
        echo $exception->getMessage() . PHP_EOL;
    }

    echo PHP_EOL;

    // -----------------------------------------------------
    // 10. Display final stock.
    // -----------------------------------------------------
    $finalStock =
        $inventoryService->getCurrentStock(
            $product->getId()
        );

    echo "Final stock: {$finalStock}" . PHP_EOL;
    echo PHP_EOL;

    // -----------------------------------------------------
    // 11. Display movement history.
    // -----------------------------------------------------
    echo "Movement history:" . PHP_EOL;

    $movements =
        $inventoryService->getMovementHistory(
            $product->getId()
        );

    foreach ($movements as $movement) {
        echo sprintf(
            "- %s %s units%s",
            $movement->getType()->value,
            $movement->getQuantity(),
            PHP_EOL
        );
    }

    echo PHP_EOL;
    echo "Demo completed successfully." . PHP_EOL;
} catch (Throwable $exception) {
    // Catch unexpected application or database errors.
    fwrite(
        STDERR,
        'Demo failed: '
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}