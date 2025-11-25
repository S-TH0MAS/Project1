<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ApiResource] // Expose cette entité dans l'API
class Client extends User
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Relation OneToMany : Un Client possède plusieurs ClientItems
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: ClientItem::class, orphanRemoval: true)]
    private Collection $clientItems;

    public function __construct()
    {
        // Important : Si User a un constructeur, on l'appelle
        // parent::__construct();

        $this->clientItems = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, ClientItem>
     */
    public function getClientItems(): Collection
    {
        return $this->clientItems;
    }

    public function addClientItem(ClientItem $clientItem): static
    {
        if (!$this->clientItems->contains($clientItem)) {
            $this->clientItems->add($clientItem);
            $clientItem->setClient($this);
        }

        return $this;
    }

    public function removeClientItem(ClientItem $clientItem): static
    {
        if ($this->clientItems->removeElement($clientItem)) {
            // set the owning side to null (unless already changed)
            if ($clientItem->getClient() === $this) {
                $clientItem->setClient(null);
            }
        }

        return $this;
    }
}