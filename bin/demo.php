<?php

declare(strict_types=1);

use App\Database\DatabaseConnection;
use App\Exceptions\DuplicateProductException;
use App\Exceptions\DuplicateSupplierException;
use App\Exceptions\InsufficientStockException;
use App\Repositories\Pdo\PdoProductRepository;
use App\Repositories\Pdo\PdoProductSupplierRepository;
use App\Repositories\Pdo\PdoStockMovementRepository;
use App\Repositories\Pdo\PdoSupplierRepository;
use App\Services\CatalogService;
use App\Services\InventoryService;

// Load Composer's PSR-4 autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

// Load the database configuration.
$config = require __DIR__ . '/../config/database.php';

try {
    // -----------------------------------------------------
    // 1. Create the PDO database connection.
    // -----------------------------------------------------
    $pdo = DatabaseConnection::create($config);

    // -----------------------------------------------------
    // 2. Create repository implementations.
    //
    // SQL and PDO remain isolated inside repositories.
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
    // 3. Create application services.
    // -----------------------------------------------------

    // CatalogService handles products, suppliers
    // and product-supplier relationships.
    $catalogService = new CatalogService(
        $productRepository,
        $supplierRepository,
        $productSupplierRepository
    );

    // InventoryService handles stock IN and OUT operations.
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
    // A timestamp keeps the demo product unique
    // when the script is executed multiple times.
    // -----------------------------------------------------
    $productName =
        'Cement 50kg Demo ' . date('YmdHis');

    $product =
        $catalogService->createProduct(
            $productName
        );

    echo "Product created:" . PHP_EOL;
    echo "ID: {$product->getId()}" . PHP_EOL;
    echo "Name: {$product->getName()}" . PHP_EOL;
    echo PHP_EOL;

    // -----------------------------------------------------
    // 5. Test duplicate product prevention.
    //
    // Extra spaces are added intentionally.
    // Product normalization should remove them.
    // -----------------------------------------------------
    try {
        $catalogService->createProduct(
            "   {$productName}   "
        );

        echo "ERROR: Duplicate product was allowed."
            . PHP_EOL;
    } catch (DuplicateProductException $exception) {
        echo "Duplicate product rejected correctly."
            . PHP_EOL;
    }

    echo PHP_EOL;

    // -----------------------------------------------------
    // 6. Generate a unique demo phone number.
    //
    // The number is intentionally formatted with dashes
    // so Supplier can normalize it before storing it.
    // -----------------------------------------------------
    $phoneDigits =
        '09' . substr((string) time(), -8);

    $formattedPhone =
        substr($phoneDigits, 0, 3)
        . '-'
        . substr($phoneDigits, 3, 3)
        . '-'
        . substr($phoneDigits, 6);

    $supplier =
        $catalogService->createSupplier(
            'Al Noor Trading',
            $formattedPhone
        );

    echo "Supplier created:" . PHP_EOL;
    echo "ID: {$supplier->getId()}" . PHP_EOL;
    echo "Name: {$supplier->getName()}" . PHP_EOL;
    echo "Stored phone: {$supplier->getPhone()}" . PHP_EOL;
    echo PHP_EOL;

    // -----------------------------------------------------
    // 7. Test supplier phone normalization.
    //
    // This is the same phone number but written
    // using spaces instead of dashes.
    //
    // Both formats should normalize to the same value.
    // -----------------------------------------------------
    $samePhoneDifferentFormat =
        substr($phoneDigits, 0, 3)
        . ' '
        . substr($phoneDigits, 3, 3)
        . ' '
        . substr($phoneDigits, 6);

    try {
        $catalogService->createSupplier(
            'Al Noor Trading Duplicate',
            $samePhoneDifferentFormat
        );

        echo "ERROR: Duplicate supplier was allowed."
            . PHP_EOL;
    } catch (DuplicateSupplierException $exception) {
        echo "Duplicate supplier rejected correctly."
            . PHP_EOL;
    }

    echo PHP_EOL;

    // -----------------------------------------------------
    // 8. Link the product with the supplier.
    //
    // Purchase price belongs to this relationship.
    // -----------------------------------------------------
    $productSupplier =
        $catalogService->linkProductToSupplier(
            $product->getId(),
            $supplier->getId(),
            '5.50'
        );

    echo "Product linked to supplier." . PHP_EOL;
    echo "Purchase price: "
        . $productSupplier->getPurchasePrice()
        . PHP_EOL;

    echo PHP_EOL;

    // -----------------------------------------------------
    // 9. Receive 100 units.
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
    // 10. Sell 30 units.
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
    // 11. Try to sell more than available stock.
    //
    // Current stock should be 70.
    // Selling 100 must be rejected.
    // -----------------------------------------------------
    echo "Trying to sell 100 units..." . PHP_EOL;

    try {
        $inventoryService->sellStock(
            $product->getId(),
            '100'
        );

        echo "ERROR: Overselling was allowed."
            . PHP_EOL;
    } catch (InsufficientStockException $exception) {
        echo "Sale rejected correctly." . PHP_EOL;
        echo $exception->getMessage() . PHP_EOL;
    }

    echo PHP_EOL;

    // -----------------------------------------------------
    // 12. Display final stock.
    // -----------------------------------------------------
    $finalStock =
        $inventoryService->getCurrentStock(
            $product->getId()
        );

    echo "Final stock: {$finalStock}" . PHP_EOL;
    echo PHP_EOL;

    // -----------------------------------------------------
    // 13. Display movement history.
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