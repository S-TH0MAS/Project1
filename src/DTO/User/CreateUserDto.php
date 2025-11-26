<?php

namespace App\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserDto
{
    #[Assert\NotBlank(message: 'email is required')]
    #[Assert\Email(message: 'email must be a valid email address')]
    #[Assert\Length(
        max: 180,
        maxMessage: 'email cannot exceed 180 characters'
    )]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'password is required')]
    #[Assert\Type(type: 'string', message: 'password must be a string')]
    #[Assert\Length(
        min: 6,
        max: 4096,
        minMessage: 'password must be at least 6 characters',
        maxMessage: 'password cannot exceed 4096 characters'
    )]
    private ?string $password = null;

    #[Assert\NotBlank(message: 'name is required')]
    #[Assert\Type(type: 'string', message: 'name must be a string')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'name cannot be empty',
        maxMessage: 'name cannot exceed 255 characters'
    )]
    private ?string $name = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email ? trim($email) : null;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name ? trim($name) : null;
        return $this;
    }
}

