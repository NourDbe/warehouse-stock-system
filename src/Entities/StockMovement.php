<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\MovementType;
use App\Traits\HasCreatedAt;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Represents an incoming or outgoing stock movement.
 */
final class StockMovement
{
    use HasCreatedAt;

    /**
     * Create a stock movement.
     */
    public function __construct(
        private int $productId,
        private MovementType $type,
        private string $quantity,
        private ?int $supplierId = null,
        private ?int $id = null,
        ?DateTimeImmutable $createdAt = null
    ) {
        if ($this->productId <= 0) {
            throw new InvalidArgumentException(
                'Product ID must be greater than zero.'
            );
        }

        // Quantity must always be positive.
        if (
            !is_numeric($this->quantity)
            || (float) $this->quantity <= 0
        ) {
            throw new InvalidArgumentException(
                'Movement quantity must be greater than zero.'
            );
        }

        // Incoming stock must always have a supplier.
        if (
            $this->type === MovementType::IN
            && $this->supplierId === null
        ) {
            throw new InvalidArgumentException(
                'Incoming stock must have a supplier.'
            );
        }

        // Outgoing stock represents a sale/removal
        // and therefore should not reference a supplier.
        if (
            $this->type === MovementType::OUT
            && $this->supplierId !== null
        ) {
            throw new InvalidArgumentException(
                'Outgoing stock cannot have a supplier.'
            );
        }

        $this->initializeCreatedAt($createdAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getType(): MovementType
    {
        return $this->type;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getSupplierId(): ?int
    {
        return $this->supplierId;
    }
}