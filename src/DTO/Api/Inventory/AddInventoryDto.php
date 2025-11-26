<?php

namespace App\DTO\Api\Inventory;

use Symfony\Component\Validator\Constraints as Assert;

class AddInventoryDto
{
    #[Assert\NotNull(message: 'itemId is required')]
    #[Assert\Type(type: 'integer', message: 'itemId must be an integer')]
    #[Assert\Positive(message: 'itemId must be a positive integer')]
    private ?int $itemId = null;

    #[Assert\NotNull(message: 'quantity is required')]
    #[Assert\Type(type: 'integer', message: 'quantity must be an integer')]
    #[Assert\Positive(message: 'quantity must be greater than 0')]
    private ?int $quantity = null;

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function setItemId(?int $itemId): self
    {
        $this->itemId = $itemId;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }
}

