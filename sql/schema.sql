-- =========================================================
-- Warehouse Stock System
-- Database Schema
-- =========================================================

-- Create the database only if it does not already exist.
CREATE DATABASE IF NOT EXISTS warehouse_stock_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Select the database for the following tables.
USE warehouse_stock_system;


-- =========================================================
-- Products Table
-- Stores basic information about warehouse products.
-- Current stock quantity is NOT stored here.
-- It will be calculated from stock movements.
-- =========================================================
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Product name must be unique to reduce duplicate products.
    name VARCHAR(150) NOT NULL UNIQUE,

    -- Automatically stores the creation date and time.
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- =========================================================
-- Suppliers Table
-- Stores supplier information separately to avoid duplication.
-- =========================================================
CREATE TABLE suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Supplier name.
    name VARCHAR(150) NOT NULL,

    -- Supplier phone number.
    -- It is unique to help prevent duplicate supplier records.
    phone VARCHAR(20) NOT NULL UNIQUE,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- =========================================================
-- Product Suppliers Table
-- Represents the Many-to-Many relationship between
-- products and suppliers.
--
-- A product can have multiple suppliers.
-- A supplier can provide multiple products.
-- The purchase price belongs to this relationship.
-- =========================================================
CREATE TABLE product_suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- References the product provided by the supplier.
    product_id INT UNSIGNED NOT NULL,

    -- References the supplier that provides the product.
    supplier_id INT UNSIGNED NOT NULL,

    -- Purchase price for this product from this specific supplier.
    purchase_price DECIMAL(12, 2) NOT NULL,

    -- Prevent duplicate relationships between
    -- the same product and supplier.
    CONSTRAINT uq_product_supplier
        UNIQUE (product_id, supplier_id),

    -- Purchase price cannot be negative.
    CONSTRAINT chk_purchase_price
        CHECK (purchase_price >= 0),

    -- Product must exist in the products table.
    CONSTRAINT fk_product_suppliers_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE RESTRICT,

    -- Supplier must exist in the suppliers table.
    CONSTRAINT fk_product_suppliers_supplier
        FOREIGN KEY (supplier_id)
        REFERENCES suppliers(id)
        ON DELETE RESTRICT
);


-- =========================================================
-- Stock Movements Table
-- Stores every stock IN and OUT operation.
--
-- IN  = Stock received from a supplier.
-- OUT = Stock sold or removed from the warehouse.
--
-- Current stock is calculated from these records instead
-- of storing a quantity directly inside the products table.
-- =========================================================
CREATE TABLE stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Product affected by this movement.
    product_id INT UNSIGNED NOT NULL,

    -- Supplier is required for IN movements.
    -- It remains NULL for OUT movements.
    supplier_id INT UNSIGNED NULL,

    -- Movement type:
    -- IN  = incoming stock
    -- OUT = outgoing stock
    type VARCHAR(10) NOT NULL,

    -- Quantity involved in the movement.
    quantity DECIMAL(12, 3) NOT NULL,

    -- Date and time of the movement.
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Only IN and OUT movement types are allowed.
    CONSTRAINT chk_movement_type
        CHECK (type IN ('IN', 'OUT')),

    -- Quantity must always be greater than zero.
    CONSTRAINT chk_movement_quantity
        CHECK (quantity > 0),

    -- An IN movement must have a supplier.
    -- An OUT movement must not have a supplier.
    CONSTRAINT chk_movement_supplier
        CHECK (
            (type = 'IN' AND supplier_id IS NOT NULL)
            OR
            (type = 'OUT' AND supplier_id IS NULL)
        ),

    -- Product must exist before recording a movement.
    CONSTRAINT fk_stock_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE RESTRICT,

    -- For incoming stock, the product and supplier combination
    -- must already exist in product_suppliers.
    CONSTRAINT fk_stock_product_supplier
        FOREIGN KEY (product_id, supplier_id)
        REFERENCES product_suppliers(product_id, supplier_id)
        ON DELETE RESTRICT
);