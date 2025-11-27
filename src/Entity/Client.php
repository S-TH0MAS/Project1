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

    /**
     * @var Collection<int, Inventory>
     */
    #[ORM\OneToMany(targetEntity: Inventory::class, mappedBy: 'client', orphanRemoval: true)]
    private Collection $inventories;

    /**
     * @var Collection<int, Recipe>
     */
    #[ORM\OneToMany(targetEntity: Recipe::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $recipes;

    /**
     * @var Collection<int, Recipe>
     */
    #[ORM\ManyToMany(targetEntity: Recipe::class, inversedBy: 'clients')]
    private Collection $favorites;

    public function __construct()
    {
        // Important : Si User a un constructeur, on l'appelle
        // parent::__construct();

        $this->clientItems = new ArrayCollection();
        $this->inventories = new ArrayCollection();
        $this->recipes = new ArrayCollection();
        $this->favorites = new ArrayCollection();
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

    /**
     * @return Collection<int, Inventory>
     */
    public function getInventories(): Collection
    {
        return $this->inventories;
    }

    public function addInventory(Inventory $inventory): static
    {
        if (!$this->inventories->contains($inventory)) {
            $this->inventories->add($inventory);
            $inventory->setClient($this);
        }

        return $this;
    }

    public function removeInventory(Inventory $inventory): static
    {
        if ($this->inventories->removeElement($inventory)) {
            // set the owning side to null (unless already changed)
            if ($inventory->getClient() === $this) {
                $inventory->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Recipe>
     */
    public function getRecipes(): Collection
    {
        return $this->recipes;
    }

    public function addRecipe(Recipe $recipe): static
    {
        if (!$this->recipes->contains($recipe)) {
            $this->recipes->add($recipe);
            $recipe->setAuthor($this);
        }

        return $this;
    }

    public function removeRecipe(Recipe $recipe): static
    {
        if ($this->recipes->removeElement($recipe)) {
            // set the owning side to null (unless already changed)
            if ($recipe->getAuthor() === $this) {
                $recipe->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Recipe>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Recipe $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
        }

        return $this;
    }

    public function removeFavorite(Recipe $favorite): static
    {
        $this->favorites->removeElement($favorite);

        return $this;
    }
}