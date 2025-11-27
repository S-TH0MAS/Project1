<?php

namespace App\DTO\Api\Recipe;

use Symfony\Component\Validator\Constraints as Assert;

class SaveRecipeDto
{
    #[Assert\NotBlank(message: 'cache_key is required')]
    #[Assert\Type(type: 'string', message: 'cache_key must be a string')]
    private ?string $cache_key = null;

    public function getCacheKey(): ?string
    {
        return $this->cache_key;
    }

    public function setCacheKey(?string $cache_key): self
    {
        $this->cache_key = $cache_key ? trim($cache_key) : null;
        return $this;
    }
}

