<?php

declare(strict_types=1);

namespace App\Entities;

use App\Traits\HasCreatedAt;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Represents a supplier that provides products
 * to the warehouse.
 */
final class Supplier
{
    use HasCreatedAt;

    /**
     * Create a supplier entity.
     */
    public function __construct(
        private string $name,
        private string $phone,
        private ?int $id = null,
        ?DateTimeImmutable $createdAt = null
    ) {
        // Normalize basic text input.
        // Normalize the supplier name by removing unnecessary spaces.
        $this->name = preg_replace(
            '/\s+/',
            ' ',
            trim($this->name)
        ) ?? '';

        // Store phone numbers using digits only.
        //
        // Examples:
        // 099 111 1111  -> 0991111111
        // 099-111-1111  -> 0991111111
        // (099)1111111  -> 0991111111
        $this->phone = preg_replace(
            '/\D+/',
            '',
            $this->phone
        ) ?? '';

        if ($this->name === '') {
            throw new InvalidArgumentException(
                'Supplier name cannot be empty.'
            );
        }

        if ($this->phone === '') {
            throw new InvalidArgumentException(
                'Supplier phone cannot be empty.'
            );
        }

        $this->initializeCreatedAt($createdAt);
    }

    /**
     * Return the supplier ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Return the supplier name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return the supplier phone number.
     */
    public function getPhone(): string
    {
        return $this->phone;
    }
}