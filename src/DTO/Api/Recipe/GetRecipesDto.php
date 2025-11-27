<?php

namespace App\DTO\Api\Recipe;

use Symfony\Component\Validator\Constraints as Assert;

class GetRecipesDto
{
    #[Assert\NotBlank(message: 'quantity is required')]
    #[Assert\Type(type: 'integer', message: 'quantity must be an integer')]
    #[Assert\Positive(message: 'quantity must be a positive integer')]
    private ?int $quantity = null;

    #[Assert\Type(type: 'integer', message: 'offset must be an integer')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'offset must be greater than or equal to 0')]
    private ?int $offset = null;

    #[Assert\Type(type: 'string', message: 'mode must be a string')]
    #[Assert\Choice(choices: ['all', 'favorite', 'author'], message: 'mode must be one of: all, favorite, author')]
    private ?string $mode = null;

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
        return $this->offset ?? 0;
    }

    public function setOffset(?int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function getMode(): string
    {
        return $this->mode ?? 'all';
    }

    public function setMode(?string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }
}

