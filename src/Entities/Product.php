<?php

declare(strict_types=1);

namespace App\Entities;

use App\Traits\HasCreatedAt;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Represents a warehouse product.
 */
final class Product
{
    use HasCreatedAt;

    /**
     * Create a product entity.
     *
     * The ID can be null before the product is stored
     * in the database.
     */
    public function __construct(
        private string $name,
        private ?int $id = null,
        ?DateTimeImmutable $createdAt = null
    ) {
        // Remove unnecessary spaces from the product name.
        $this->name = trim($this->name);

        // A product cannot exist without a valid name.
        if ($this->name === '') {
            throw new InvalidArgumentException(
                'Product name cannot be empty.'
            );
        }

        $this->initializeCreatedAt($createdAt);
    }

    /**
     * Return the product ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Return the product name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}