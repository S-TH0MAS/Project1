<?php

namespace App\DTO\Api\Item;

use Symfony\Component\Validator\Constraints as Assert;

class AddItemDto
{
    #[Assert\NotBlank(message: 'name is required')]
    #[Assert\Type(type: 'string', message: 'name must be a string')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'name cannot be empty',
        maxMessage: 'name cannot exceed 255 characters'
    )]
    private ?string $name = null;

    #[Assert\NotBlank(message: 'category is required')]
    private string|int|null $category = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name ? trim($name) : null;
        return $this;
    }

    public function getCategory(): string|int|null
    {
        return $this->category;
    }

    public function setCategory(string|int|null $category): self
    {
        $this->category = $category;
        return $this;
    }
}

