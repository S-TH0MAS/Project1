<?php

namespace App\DTO\Api\Recipe;

use Symfony\Component\Validator\Constraints as Assert;

class GenerateRecipeDto
{
    #[Assert\NotBlank(message: 'prompt is required')]
    #[Assert\Type(type: 'string', message: 'prompt must be a string')]
    #[Assert\Length(
        min: 1,
        max: 1000,
        minMessage: 'prompt cannot be empty',
        maxMessage: 'prompt cannot exceed 1000 characters'
    )]
    private ?string $prompt = null;

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): self
    {
        $this->prompt = $prompt ? trim($prompt) : null;
        return $this;
    }
}

