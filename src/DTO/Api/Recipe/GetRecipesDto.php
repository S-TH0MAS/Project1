<?php

namespace App\DTO\Api\Recipe;

use Symfony\Component\Validator\Constraints as Assert;

class GetRecipesDto
{
    #[Assert\NotBlank(message: 'quantity is required')]
    #[Assert\Type(type: 'integer', message: 'quantity must be an integer')]
    #[Assert\Positive(message: 'quantity must be a positive integer')]
    private ?int $quantity = null;

    #[Assert\NotBlank(message: 'offset is required')]
    #[Assert\Type(type: 'integer', message: 'offset must be an integer')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'offset must be greater than or equal to 0')]
    private ?int $offset = null;

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function setOffset(?int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
}

