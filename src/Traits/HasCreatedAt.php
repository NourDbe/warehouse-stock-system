<?php

declare(strict_types=1);

namespace App\Traits;

use DateTimeImmutable;

/**
 * Provides created-at functionality for entities
 * that store a creation timestamp.
 */
trait HasCreatedAt
{
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Initialize the creation date of the entity.
     */
    protected function initializeCreatedAt(
        ?DateTimeImmutable $createdAt = null
    ): void {
        $this->createdAt = $createdAt;
    }

    /**
     * Return the creation date if it exists.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }
}