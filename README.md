# Warehouse Stock System

A warehouse inventory management system built with **pure PHP**, **PDO**, and **MySQL**.

The project addresses common warehouse problems such as duplicated product and supplier data, inaccurate stock quantities, missing movement history, and selling more stock than is actually available.

The implementation focuses on:

- Normalized database design
- Clear separation of responsibilities
- Repository Pattern
- SOLID principles
- Exception-based error handling
- Composer and PSR-4 autoloading
- Stock tracking through movement history

---

## Features

- Manage warehouse products
- Manage suppliers
- Link products to multiple suppliers
- Store a different purchase price for each product-supplier relationship
- Record incoming stock as `IN`
- Record outgoing stock as `OUT`
- Calculate current stock from movement history
- Prevent selling more than the available stock
- Prevent duplicate product and supplier records
- Normalize product names and supplier phone numbers
- Keep PDO and SQL isolated from business logic
- Handle errors using Exceptions

---

## Tech Stack

- PHP 8.1+
- MySQL
- PDO
- Composer
- PSR-4 Autoloading

No framework and no ORM are used.

---

## Project Structure

```text
warehouse-stock-system/
├── bin/
│   ├── demo.php
│   └── test-connection.php
├── config/
│   └── database.php
├── docs/
│   └── ERD.md
├── sql/
│   └── schema.sql
├── src/
│   ├── Database/
│   │   └── DatabaseConnection.php
│   ├── Entities/
│   │   ├── Product.php
│   │   ├── ProductSupplier.php
│   │   ├── StockMovement.php
│   │   └── Supplier.php
│   ├── Enums/
│   │   └── MovementType.php
│   ├── Exceptions/
│   │   ├── DuplicateProductException.php
│   │   ├── DuplicateProductSupplierException.php
│   │   ├── DuplicateSupplierException.php
│   │   ├── InsufficientStockException.php
│   │   ├── ProductNotFoundException.php
│   │   ├── ProductSupplierNotFoundException.php
│   │   └── SupplierNotFoundException.php
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   ├── ProductRepositoryInterface.php
│   │   │   ├── ProductSupplierRepositoryInterface.php
│   │   │   ├── StockMovementRepositoryInterface.php
│   │   │   └── SupplierRepositoryInterface.php
│   │   └── Pdo/
│   │       ├── PdoProductRepository.php
│   │       ├── PdoProductSupplierRepository.php
│   │       ├── PdoStockMovementRepository.php
│   │       └── PdoSupplierRepository.php
│   ├── Services/
│   │   ├── CatalogService.php
│   │   └── InventoryService.php
│   └── Traits/
│       └── HasCreatedAt.php
├── .gitignore
├── composer.json
├── composer.lock
└── README.md
```

---

## Database Design

The database contains four main tables:

### `products`

Stores product information only.

```text
id
name
created_at
```

The current stock quantity is intentionally **not stored** in this table.

### `suppliers`

Stores supplier information separately.

```text
id
name
phone
created_at
```

This avoids repeating supplier information in multiple records.

### `product_suppliers`

Represents the many-to-many relationship between products and suppliers.

```text
id
product_id
supplier_id
purchase_price
```

A product can have multiple suppliers, and one supplier can provide multiple products.

The purchase price belongs to this relationship because the same product may have a different purchase price depending on the supplier.

The pair:

```text
(product_id, supplier_id)
```

is unique.

### `stock_movements`

Stores every stock operation.

```text
id
product_id
supplier_id
type
quantity
created_at
```

Supported movement types:

```text
IN
OUT
```

- `IN` means incoming stock and requires a supplier.
- `OUT` means outgoing stock and does not reference a supplier.

---

## ERD

The full ERD is available here:

```text
docs/ERD.md
```

Main relationships:

```text
Product 1 -------- N ProductSupplier
Supplier 1 ------- N ProductSupplier

Product N -------- N Supplier

Product 1 -------- N StockMovement
```

The `product_suppliers` table resolves the many-to-many relationship between products and suppliers.

---

## Normalization

The schema is designed to reduce duplication and keep each piece of information in the correct place.

### First Normal Form - 1NF

Each field stores one atomic value.

For example, suppliers are not stored as comma-separated values inside a product record.

Incorrect:

```text
Product: Cement
Suppliers: Supplier A, Supplier B
```

Correct:

```text
products
suppliers
product_suppliers
```

Each relationship is stored as its own record.

### Second Normal Form - 2NF

The purchase price depends on the combination of:

```text
product_id + supplier_id
```

Example:

```text
Cement + Supplier A = 5.00
Cement + Supplier B = 5.50
```

Therefore, `purchase_price` belongs in `product_suppliers`.

### Third Normal Form - 3NF

Supplier information is stored only in `suppliers`.

For example, `supplier_name` and `supplier_phone` are not duplicated inside:

```text
products
product_suppliers
stock_movements
```

The same principle is applied to product data.

This reduces update anomalies and keeps the data consistent.

---

## Stock Calculation

The system does not store a mutable `current_quantity` value.

Instead, every stock change is stored as a separate movement.

Example:

```text
IN   100
IN    50
OUT   20
OUT   10
```

Current stock is calculated as:

```text
100 + 50 - 20 - 10 = 120
```

Conceptually:

```text
Current Stock = Total IN - Total OUT
```

This preserves the full stock history and makes it possible to trace how the current quantity was reached.

---

## Preventing Overselling

Before an `OUT` movement is stored, `InventoryService` calculates the current stock.

Example:

```text
Current stock: 70
Requested sale: 100
```

The operation is rejected by throwing:

```text
InsufficientStockException
```

No outgoing movement is saved when the requested quantity is greater than the available stock.

---

## Architecture

The project separates responsibilities into clear layers:

```text
CLI / Application
        |
        v
Services
        |
        v
Repository Interfaces
        |
        v
PDO Repository Implementations
        |
        v
PDO
        |
        v
MySQL
```

### Entities

Entities represent domain data:

- `Product`
- `Supplier`
- `ProductSupplier`
- `StockMovement`

They do not contain SQL or PDO logic.

### Services

Services contain business rules.

#### `CatalogService`

Responsible for:

- Creating products
- Creating suppliers
- Preventing duplicates
- Linking products to suppliers

#### `InventoryService`

Responsible for:

- Receiving stock
- Selling stock
- Preventing overselling
- Calculating current stock
- Retrieving movement history

Services do not use PDO directly.

### Repositories

Repositories are responsible for data persistence.

Repository interfaces define the operations required by the application:

```text
ProductRepositoryInterface
SupplierRepositoryInterface
ProductSupplierRepositoryInterface
StockMovementRepositoryInterface
```

PDO implementations contain the actual SQL:

```text
PdoProductRepository
PdoSupplierRepository
PdoProductSupplierRepository
PdoStockMovementRepository
```

---

## Design Pattern

### Repository Pattern

The project uses the **Repository Pattern** because the main architectural need is to isolate database access from business logic.

Without a repository layer, services would directly contain PDO and SQL code such as:

```php
$pdo->prepare(...);
```

Instead, the structure is:

```text
InventoryService
        |
        v
StockMovementRepositoryInterface
        |
        v
PdoStockMovementRepository
        |
        v
PDO
```

The service depends on an abstraction instead of depending directly on PDO.

This keeps the code easier to maintain and reduces coupling between the business layer and the database layer.

### Why Factory Pattern Was Not Used

Factory Pattern is useful when object creation changes according to a runtime condition or when several object types must be created through one centralized creation process.

That is not the main problem in this project.

The main problem is isolating database access, so Repository Pattern is the more appropriate choice.

---

## SOLID Principles

The project applies SOLID principles without unnecessary over-engineering.

### S - Single Responsibility Principle

Each class has one focused responsibility.

Examples:

```text
Product                 -> represents product data
InventoryService        -> inventory business rules
CatalogService          -> catalog business rules
PdoProductRepository    -> product persistence
DatabaseConnection      -> PDO connection creation
```

### O - Open/Closed Principle

The service layer depends on repository interfaces.

A different repository implementation can be added without changing the service logic, as long as it implements the same contract.

### L - Liskov Substitution Principle

Any class implementing a repository interface can be used where that interface is expected, provided it respects the same contract.

### I - Interface Segregation Principle

The project uses small, focused repository interfaces instead of one large interface containing unrelated methods.

### D - Dependency Inversion Principle

High-level services depend on repository interfaces rather than concrete PDO repository classes.

Example:

```text
InventoryService
        |
        v
StockMovementRepositoryInterface
```

instead of:

```text
InventoryService
        |
        v
PDO
```

---

## Enum

`MovementType` defines the allowed movement types:

```php
enum MovementType: string
{
    case IN = 'IN';
    case OUT = 'OUT';
}
```

This prevents arbitrary movement-type values from being used in application code.

---

## Trait

`HasCreatedAt` contains shared timestamp behavior used by multiple entities.

This avoids duplicating the same timestamp property and getter logic in several classes.

---

## Interfaces

Repository interfaces define data-access contracts.

Example:

```php
interface ProductRepositoryInterface
{
    public function create(Product $product): Product;

    public function findById(int $id): ?Product;

    public function findByName(string $name): ?Product;

    public function findAll(): array;
}
```

Services depend on these interfaces instead of concrete PDO repositories.

---

## Exception Handling

The business and data-access layers use Exceptions instead of `echo` or `die`.

Examples:

```text
ProductNotFoundException
SupplierNotFoundException
ProductSupplierNotFoundException
DuplicateProductException
DuplicateSupplierException
DuplicateProductSupplierException
InsufficientStockException
```

The CLI entry points catch Exceptions and display the final message to the user.

---

## Input Normalization

Product names are trimmed and repeated spaces are collapsed.

Example:

```text
"  Cement   50kg  "
```

becomes:

```text
"Cement 50kg"
```

Supplier phone numbers are stored using digits only.

Example:

```text
099-111-1111
099 111 1111
(099)1111111
```

become:

```text
0991111111
```

This reduces duplicate supplier records caused by formatting differences.

---

## Composer and PSR-4

The project uses Composer for autoloading.

The PSR-4 mapping is:

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

After adding or changing classes, regenerate the autoloader:

```bash
composer dump-autoload
```

---

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd warehouse-stock-system
```

### 2. Install Composer dependencies

```bash
composer install
```

### 3. Create the database

Import:

```text
sql/schema.sql
```

using phpMyAdmin or the MySQL CLI.

The script creates:

```text
warehouse_stock_system
```

and all required tables.

---

## Database Configuration

Database configuration is located in:

```text
config/database.php
```

The project reads the following environment variables when available:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
```

Default local values are:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=warehouse_stock_system
DB_USER=root
DB_PASSWORD=
```

Real production credentials should not be committed to GitHub.

---

## Test the Database Connection

Run:

```bash
php bin/test-connection.php
```

Expected output:

```text
Database connection successful.
```

---

## Run the Demo

Run:

```bash
php bin/demo.php
```

The demo tests a complete workflow:

```text
Create Product
      |
      v
Create Supplier
      |
      v
Link Product + Supplier
      |
      v
Receive 100 units
      |
      v
Sell 30 units
      |
      v
Current Stock = 70
      |
      v
Try to sell 100
      |
      v
InsufficientStockException
```

It also checks duplicate product and supplier handling.

---

## Example Demo Result

```text
Product created.
Duplicate product rejected correctly.

Supplier created.
Duplicate supplier rejected correctly.

Product linked to supplier.

Received stock: +100
Current stock: 100.000

Sold stock: -30
Current stock: 70.000

Trying to sell 100 units...
Sale rejected correctly.

Final stock: 70.000

Movement history:
- IN 100.000 units
- OUT 30.000 units

Demo completed successfully.
```

---

## Git History

The repository was developed incrementally so the commit history reflects the evolution of the solution.

Development stages include:

```text
Initial project structure and Composer setup
Add database schema and PDO connection
Add core entities, movement enum and timestamp trait
Add repository contracts
Implement PDO repositories
Add inventory service and domain exceptions
Add inventory demo workflow
Add catalog validation and input normalization
Add project documentation and ERD
```

---

## Final Design Summary

The system solves the warehouse problem by:

- Separating products and suppliers into normalized tables
- Resolving the many-to-many relationship through `product_suppliers`
- Storing supplier-specific purchase prices in the relationship table
- Recording each stock operation as a separate movement
- Calculating current stock from movement history
- Preventing sales that exceed available stock
- Isolating SQL and PDO inside repositories
- Keeping business rules inside services
- Applying Repository Pattern
- Applying SOLID principles
- Using Enum, Trait, Interfaces, Exceptions, Composer, and PSR-4 autoloading

---

## Assignment Notes

This project was created as an educational warehouse inventory task using pure PHP and PDO.
