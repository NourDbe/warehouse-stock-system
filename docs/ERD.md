# Warehouse Stock System - ERD

mermaid
erDiagram

    PRODUCTS ||--o{ PRODUCT_SUPPLIERS : has
    SUPPLIERS ||--o{ PRODUCT_SUPPLIERS : provides
    PRODUCTS ||--o{ STOCK_MOVEMENTS : has
    PRODUCT_SUPPLIERS o|--o{ STOCK_MOVEMENTS : supplies

    PRODUCTS {
        INT id PK
        VARCHAR name UK
        TIMESTAMP created_at
    }

    SUPPLIERS {
        INT id PK
        VARCHAR name
        VARCHAR phone UK
        TIMESTAMP created_at
    }

    PRODUCT_SUPPLIERS {
        INT id PK
        INT product_id FK
        INT supplier_id FK
        DECIMAL purchase_price
    }

    STOCK_MOVEMENTS {
        INT id PK
        INT product_id FK
        INT supplier_id FK "Nullable for OUT"
        VARCHAR type "IN or OUT"
        DECIMAL quantity
        TIMESTAMP created_at
    }


## Relationships

- One Product can have many ProductSupplier records.
- One Supplier can have many ProductSupplier records.
- Therefore, Products and Suppliers have a Many-to-Many relationship.
- One Product can have many StockMovements.
- An IN StockMovement must reference a valid Product-Supplier relationship.
- An OUT StockMovement does not have a supplier.

## Main Relationships

text
Product 1 -------- N ProductSupplier
Supplier 1 ------- N ProductSupplier

Product N -------- N Supplier

Product 1 -------- N StockMovement

ProductSupplier 1 -------- N StockMovement (for IN movements)
