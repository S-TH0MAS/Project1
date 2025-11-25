<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ClientItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientItemRepository::class)]
#[ApiResource]
class ClientItem extends Item
{
    // Note : Pas d'ID, pas de Name, pas de Category ici.
    // Ils sont hérités de la classe parente "Item".

    #[ORM\ManyToOne(inversedBy: 'clientItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }
}