# Warehouse Stock System

A warehouse inventory management system built with **Pure PHP**, **PDO**, and **MySQL**.

The project addresses common warehouse problems such as duplicate product and supplier data, inaccurate stock quantities, missing stock movement history, and selling quantities greater than the available stock.

The implementation focuses on:

- Normalized database design
- Clear separation of responsibilities
- Repository Pattern
- SOLID principles
- Exception-based error handling
- Composer and PSR-4 autoloading
- Complete stock movement history

---

## Features

- Manage warehouse products
- Manage suppliers
- Link products to multiple suppliers
- Allow one supplier to provide multiple products
- Store a different purchase price for each product-supplier relationship
- Record incoming stock using `IN` movements
- Record outgoing stock using `OUT` movements
- Calculate current stock from movement history
- Prevent selling more than the available quantity
- Prevent unnecessary duplicate products and suppliers
- Normalize product names and supplier phone numbers
- Isolate PDO and SQL from business logic
- Handle application errors using Exceptions

---

## Tech Stack

- PHP 8.1+
- MySQL
- PDO
- Composer
- PSR-4 Autoloading

The project uses **no framework** and **no ORM**.

---

## Project Structure

```text
warehouse-stock-system/
├── bin/
│   ├── demo.php
│   └── test-connection.php
│
├── config/
│   └── database.php
│
├── docs/
│   └── ERD.md
│
├── sql/
│   └── schema.sql
│
├── src/
│   ├── Database/
│   │   └── DatabaseConnection.php
│   │
│   ├── Entities/
│   │   ├── Product.php
│   │   ├── ProductSupplier.php
│   │   ├── StockMovement.php
│   │   └── Supplier.php
│   │
│   ├── Enums/
│   │   └── MovementType.php
│   │
│   ├── Exceptions/
│   │   ├── DuplicateProductException.php
│   │   ├── DuplicateProductSupplierException.php
│   │   ├── DuplicateSupplierException.php
│   │   ├── InsufficientStockException.php
│   │   ├── ProductNotFoundException.php
│   │   ├── ProductSupplierNotFoundException.php
│   │   └── SupplierNotFoundException.php
│   │
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   ├── ProductRepositoryInterface.php
│   │   │   ├── ProductSupplierRepositoryInterface.php
│   │   │   ├── StockMovementRepositoryInterface.php
│   │   │   └── SupplierRepositoryInterface.php
│   │   │
│   │   └── Pdo/
│   │       ├── PdoProductRepository.php
│   │       ├── PdoProductSupplierRepository.php
│   │       ├── PdoStockMovementRepository.php
│   │       └── PdoSupplierRepository.php
│   │
│   ├── Services/
│   │   ├── CatalogService.php
│   │   └── InventoryService.php
│   │
│   └── Traits/
│       └── HasCreatedAt.php
│
├── .gitignore
├── composer.json
├── composer.lock
└── README.md
```

---

# Database Design

The database contains four main tables:

- `products`
- `suppliers`
- `product_suppliers`
- `stock_movements`

---

## Products Table

The `products` table stores product information only.

```text
products
----------------
id
name
created_at
```

The product name is unique to reduce duplicate product records.

The current stock quantity is intentionally **not stored** in this table.

Instead, the current quantity is calculated from stock movements.

---

## Suppliers Table

The `suppliers` table stores supplier information separately.

```text
suppliers
----------------
id
name
phone
created_at
```

Supplier data is stored only once instead of being repeated with every product or stock movement.

The phone number is stored in a normalized format and is unique in the current database design.

---

## Product Suppliers Table

The `product_suppliers` table represents the relationship between products and suppliers.

```text
product_suppliers
-------------------------
id
product_id
supplier_id
purchase_price
```

A product can be provided by multiple suppliers.

A supplier can provide multiple products.

Therefore, the relationship between Product and Supplier is:

```text
Many-to-Many
```

The `product_suppliers` table resolves this relationship.

The purchase price belongs to this relationship because the same product may have a different purchase price depending on the supplier.

Example:

```text
Cement + Supplier A = 5.00
Cement + Supplier B = 5.50
```

The pair:

```text
(product_id, supplier_id)
```

is unique, which prevents the same product-supplier relationship from being stored more than once.

---

## Stock Movements Table

The `stock_movements` table stores every operation that changes warehouse stock.

```text
stock_movements
-------------------------
id
product_id
supplier_id
type
quantity
created_at
```

The supported movement types are:

```text
IN
OUT
```

### IN Movement

`IN` represents stock entering the warehouse.

An incoming movement:

- Must reference a product
- Must reference a supplier
- Must use a valid product-supplier relationship

This means the combination:

```text
product_id + supplier_id
```

must already exist in:

```text
product_suppliers
```

### OUT Movement

`OUT` represents stock leaving the warehouse, such as a sale.

An outgoing movement:

- Must reference a product
- Does not require a supplier
- Stores `supplier_id` as `NULL`

---

# ERD

The complete ERD is available in:

```text
docs/ERD.md
```

The main relationships are:

```text
Product 1 -------- N ProductSupplier

Supplier 1 ------- N ProductSupplier

Product N -------- N Supplier

Product 1 -------- N StockMovement

ProductSupplier 1 -------- N StockMovement
                         (for IN movements)
```

Conceptually:

```text
Product
   |
   | 1
   |
   | N
ProductSupplier
   N
   |
   | 1
Supplier
```

This creates the following Many-to-Many relationship:

```text
Product N -------- N Supplier
```

A product can also have many stock movements:

```text
Product 1 -------- N StockMovement
```

For incoming stock, the product and supplier combination must already exist in `product_suppliers`.

Outgoing movements do not require a supplier.

---

# Normalization

The database schema is normalized to reduce unnecessary duplication and prevent inconsistent data.

The design follows the concepts of **1NF, 2NF, and 3NF**.

---

## First Normal Form - 1NF

Each database field stores one atomic value.

For example, supplier names are not stored as multiple values inside one product field.

Incorrect design:

```text
Product: Cement
Suppliers: Supplier A, Supplier B, Supplier C
```

Instead, suppliers are stored as separate records and linked through:

```text
product_suppliers
```

This keeps each value atomic and each relationship stored separately.

---

## Second Normal Form - 2NF

The purchase price depends on both:

```text
product_id
+
supplier_id
```

For example:

```text
Cement + Supplier A = 5.00

Cement + Supplier B = 5.50
```

The purchase price does not belong only to the product.

It also does not belong only to the supplier.

It belongs to the relationship between them.

Therefore:

```text
purchase_price
```

is stored inside:

```text
product_suppliers
```

---

## Third Normal Form - 3NF

Supplier information is stored only inside:

```text
suppliers
```

For example, the following fields are not duplicated inside stock movements:

```text
supplier_name
supplier_phone
```

Instead, the system stores a supplier reference.

The same principle is applied to products.

Product information is stored only in:

```text
products
```

and other tables reference the product using:

```text
product_id
```

This reduces:

- Data duplication
- Update anomalies
- Inconsistent supplier data
- Inconsistent product data

---

# Stock Management Strategy

The system does not store a mutable value such as:

```text
products.current_quantity
```

Instead, every operation is stored as a separate stock movement.

Example:

```text
IN   100
IN    50
OUT   20
OUT   10
```

The current stock is calculated as:

```text
100 + 50 - 20 - 10 = 120
```

Conceptually:

```text
Current Stock = Total IN - Total OUT
```

This approach provides a complete history of all warehouse operations.

It also makes it possible to determine exactly how the current stock quantity was reached.

---

# Preventing Overselling

Before creating an `OUT` movement, the system calculates the current available stock.

Example:

```text
Current stock = 70
Requested sale = 100
```

Because:

```text
100 > 70
```

the operation is rejected.

The system throws:

```text
InsufficientStockException
```

and no outgoing movement is stored.

This prevents the warehouse from selling more items than are actually available.

---

# Architecture

The application uses a layered architecture.

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

Each layer has a clear responsibility.

---

## Entities

Entities represent domain data.

The main entities are:

```text
Product
Supplier
ProductSupplier
StockMovement
```

Entities do not contain SQL queries.

They also do not directly communicate with PDO.

---

## Services

Services contain the business rules of the application.

There are two main services.

### CatalogService

Responsible for:

- Creating products
- Creating suppliers
- Preventing duplicate products
- Preventing duplicate suppliers
- Linking products to suppliers
- Preventing duplicate product-supplier relationships

### InventoryService

Responsible for:

- Receiving stock
- Selling stock
- Preventing overselling
- Calculating current stock
- Retrieving stock movement history

Services do not contain SQL.

Services also do not use PDO directly.

---

# Repository Pattern

The project uses the **Repository Pattern**.

The main architectural problem is the need to isolate SQL and PDO from the business logic.

Without repositories, a service might directly contain code such as:

```php
$pdo->prepare(...);
```

This would tightly couple business logic to the database implementation.

Instead, the project uses:

```text
Service
   |
   v
Repository Interface
   |
   v
PDO Repository
   |
   v
PDO
   |
   v
MySQL
```

Example:

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

The service depends on an abstraction instead of directly depending on PDO.

---

## Repository Interfaces

Repository interfaces define the operations required by the application.

```text
ProductRepositoryInterface
SupplierRepositoryInterface
ProductSupplierRepositoryInterface
StockMovementRepositoryInterface
```

They define **what operations are available**, but they do not contain SQL.

---

## PDO Repository Implementations

The actual database access code is stored inside:

```text
src/Repositories/Pdo/
```

The PDO repositories are:

```text
PdoProductRepository
PdoSupplierRepository
PdoProductSupplierRepository
PdoStockMovementRepository
```

All SQL queries are isolated inside this layer.

---

# Why Repository Pattern?

Repository Pattern was selected because the main design problem is separating database access from application logic.

The pattern provides:

- Separation of concerns
- Reduced coupling
- Easier maintenance
- Replaceable data-access implementations
- Cleaner business services

The application can depend on repository contracts instead of directly depending on PDO.

---

# Why Factory Pattern Was Not Used

Factory Pattern is useful when an application needs to create different object types depending on runtime conditions.

That is not the primary problem in this project.

The main problem is:

```text
How can SQL and PDO be isolated from business logic?
```

Repository Pattern directly solves this problem.

Therefore, Repository Pattern was selected instead of adding Factory Pattern unnecessarily.

---

# SOLID Principles

The project follows SOLID principles where they provide real value without introducing unnecessary complexity.

---

## S - Single Responsibility Principle

Each class has one clear responsibility.

Examples:

```text
Product
→ Represents product data

Supplier
→ Represents supplier data

CatalogService
→ Handles product and supplier business rules

InventoryService
→ Handles stock business rules

PdoProductRepository
→ Handles product database operations

DatabaseConnection
→ Creates the PDO connection
```

This prevents one class from handling multiple unrelated responsibilities.

---

## O - Open/Closed Principle

The service layer depends on repository interfaces.

A different repository implementation can be created without changing the service logic.

For example:

```text
ProductRepositoryInterface
           ^
           |
PdoProductRepository
```

A different implementation could later implement the same contract.

---

## L - Liskov Substitution Principle

Classes implementing the same repository interface can be used where that interface is expected as long as they respect the same contract.

For example:

```text
ProductRepositoryInterface
```

can be implemented by:

```text
PdoProductRepository
```

without changing the code that depends on the interface.

---

## I - Interface Segregation Principle

The application uses focused repository interfaces instead of one large interface containing unrelated methods.

For example:

```text
ProductRepositoryInterface
SupplierRepositoryInterface
ProductSupplierRepositoryInterface
StockMovementRepositoryInterface
```

Each interface focuses on a specific responsibility.

---

## D - Dependency Inversion Principle

High-level services depend on abstractions rather than concrete PDO implementations.

For example:

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
PdoStockMovementRepository
        |
        v
PDO
```

This keeps business logic independent from low-level database details.

---

# Enum

The application uses:

```text
MovementType
```

to define the allowed stock movement types.

```php
enum MovementType: string
{
    case IN = 'IN';
    case OUT = 'OUT';
}
```

This prevents arbitrary values such as:

```text
sale
purchase
input
output
```

from being used inside application logic.

Only:

```text
IN
OUT
```

are valid movement types.

---

# Trait

The application uses:

```text
HasCreatedAt
```

as a Trait.

It contains shared timestamp behavior used by multiple entities.

This avoids duplicating the same:

```text
createdAt
```

property and getter logic across different classes.

---

# Interfaces

Repository contracts are implemented using PHP Interfaces.

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

Services depend on these interfaces rather than concrete PDO classes.

---

# Exception Handling

The project uses Exceptions for error handling.

The business and data-access layers do not use:

```php
die();
```

or direct output as an error-handling strategy.

Custom Exceptions include:

```text
ProductNotFoundException
SupplierNotFoundException
ProductSupplierNotFoundException

DuplicateProductException
DuplicateSupplierException
DuplicateProductSupplierException

InsufficientStockException
```

CLI entry points catch the Exceptions and display the final message to the user.

---

# Input Normalization

Input normalization is used to reduce duplicate records caused by formatting differences.

---

## Product Names

Leading and trailing spaces are removed.

Repeated spaces are collapsed.

Example:

```text
"  Cement   50kg  "
```

becomes:

```text
"Cement 50kg"
```

---

## Supplier Phone Numbers

Supplier phone numbers are stored using digits only.

For example:

```text
099-111-1111

099 111 1111

(099)1111111
```

are normalized to:

```text
0991111111
```

This helps reduce duplicate supplier records caused by different phone-number formatting.

---

# PDO Connection

Database communication is handled using PDO only.

The connection logic is isolated inside:

```text
src/Database/DatabaseConnection.php
```

PDO is configured to throw Exceptions when database errors occur.

Prepared statements are used for queries containing user-provided values.

---

# Composer and PSR-4

Composer is used for dependency management and autoloading.

The project uses PSR-4.

The mapping is defined in:

```text
composer.json
```

Example:

```json
{
    "autoload": {
        "psr-4": {
            "App\\\\": "src/"
        }
    }
}
```

After adding or changing classes, regenerate Composer's autoloader:

```bash
composer dump-autoload
```

---

# Installation

## 1. Clone the Repository

```bash
git clone <repository-url>
```

Then enter the project directory:

```bash
cd warehouse-stock-system
```

---

## 2. Install Composer Dependencies

Run:

```bash
composer install
```

---

## 3. Create the Database

Import:

```text
sql/schema.sql
```

using phpMyAdmin or the MySQL CLI.

The script creates the database:

```text
warehouse_stock_system
```

and the required tables:

```text
products
suppliers
product_suppliers
stock_movements
```

---

# Database Configuration

Database configuration is located in:

```text
config/database.php
```

The project reads these environment variables when available:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
```

The default local configuration is:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=warehouse_stock_system
DB_USER=root
DB_PASSWORD=
```

Real production credentials should not be committed to GitHub.

---

# Test Database Connection

Run:

```bash
php bin/test-connection.php
```

Expected result:

```text
Database connection successful.
```

---

# Run the Demo

Run:

```bash
php bin/demo.php
```

The demo tests the main system workflow.

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
Receive 100 Units
      |
      v
Current Stock = 100
      |
      v
Sell 30 Units
      |
      v
Current Stock = 70
      |
      v
Try to Sell 100 Units
      |
      v
InsufficientStockException
```

The demo also tests:

- Duplicate product prevention
- Duplicate supplier prevention
- Supplier phone normalization
- Product-supplier relationships
- Stock calculation
- Overselling prevention
- Stock movement history

---

# Example Demo Result

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

Insufficient stock. Available: 70.000, requested: 100.

Final stock: 70.000

Movement history:

- IN 100.000 units
- OUT 30.000 units

Demo completed successfully.
```

---

# Git History

The project was developed incrementally so the Git history reflects the evolution of the solution.

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

Refine ERD documentation
```

---

# Final Design Summary

The system solves the warehouse inventory problem by:

- Separating products and suppliers into normalized tables
- Avoiding unnecessary duplicated data
- Resolving the Product-Supplier Many-to-Many relationship using `product_suppliers`
- Storing supplier-specific purchase prices in the relationship table
- Recording every stock operation independently
- Preserving complete stock movement history
- Calculating current stock from `IN` and `OUT`