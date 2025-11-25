<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Client;
use App\Entity\ClientItem;
use App\Entity\Inventory;
use App\Entity\Item;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Données cohérentes (Catégorie => Liste d'ingrédients)
        $foodData = [
            'Légumes' => ['Carotte', 'Oignon', 'Ail', 'Pomme de terre', 'Courgette', 'Aubergine', 'Poivron', 'Tomate', 'Épinard', 'Brocoli'],
            'Fruits' => ['Pomme', 'Banane', 'Orange', 'Citron', 'Fraise', 'Framboise', 'Poire', 'Pêche', 'Ananas', 'Kiwi'],
            'Viandes' => ['Poulet entier', 'Escalope de dinde', 'Steak haché', 'Filet de bœuf', 'Lardon', 'Saucisse', 'Jambon blanc'],
            'Poissons & Fruits de mer' => ['Pavé de saumon', 'Thon en conserve', 'Cabillaud', 'Crevette', 'Moule', 'Sardine'],
            'Produits Laitiers & Œufs' => ['Lait demi-écrémé', 'Beurre doux', 'Crème fraîche', 'Œuf', 'Yaourt nature', 'Mozzarella', 'Parmesan'],
            'Céréales & Féculents' => ['Riz basmati', 'Pâtes spaghetti', 'Farine de blé', 'Semoule', 'Pain de mie', 'Lentille', 'Pois chiche'],
            'Épices & Herbes' => ['Sel fin', 'Poivre noir', 'Paprika', 'Cumin', 'Curry', 'Basilic séché', 'Origan', 'Thym'],
            'Huiles & Condiments' => ['Huile d\'olive', 'Huile de tournesol', 'Vinaigre balsamique', 'Moutarde de Dijon', 'Ketchup', 'Mayonnaise', 'Sauce soja'],
            'Sucres & Pâtisserie' => ['Sucre en poudre', 'Levure chimique', 'Chocolat noir', 'Miel', 'Sirop d\'érable'],
            'Fruits à coque' => ['Amande', 'Noix', 'Noisette', 'Pistache']
        ];

        // On garde en mémoire les objets pour les lier plus tard
        $allCategories = [];
        $allGenericItems = [];

        // 2. Création des Catégories et des Items génériques
        foreach ($foodData as $categoryName => $ingredients) {
            $category = new Category();
            $category->setName($categoryName);
            $manager->persist($category);

            $allCategories[$categoryName] = $category;

            foreach ($ingredients as $ingredientName) {
                $item = new Item();
                $item->setName($ingredientName);
                $item->setCategory($category);

                $manager->persist($item);
                $allGenericItems[] = $item;
            }
        }

        // 3. Création du User (Client) "test@test.mail"
        $client = new Client();
        $client->setEmail('test@test.mail');
        $client->setName('Utilisateur Test');
        $client->setRoles(['ROLE_USER']);

        // Hashage du mot de passe "azer1234"
        $password = $this->hasher->hashPassword($client, 'azer1234');
        $client->setPassword($password);

        $manager->persist($client);

        // 4. Création d'Items Personnalisés (ClientItem)
        // Note : Un ClientItem hérite de Item, il lui faut donc aussi une catégorie.
        $customItemsData = [
            ['name' => 'Ma Confiture Maison de fraise', 'cat' => 'Sucres & Pâtisserie'],
            ['name' => 'Reste de Pizza Jambon fromage', 'cat' => 'Céréales & Féculents'],
            ['name' => 'Soupe de Grand-mère au herbes', 'cat' => 'Légumes'],
        ];

        foreach ($customItemsData as $data) {
            $clientItem = new ClientItem();
            $clientItem->setName($data['name']);
            $clientItem->setClient($client); // Liaison au client (créateur)

            // On assigne la catégorie correspondante (obligatoire via l'héritage Item)
            if (isset($allCategories[$data['cat']])) {
                $clientItem->setCategory($allCategories[$data['cat']]);
            } else {
                // Fallback si la catégorie n'est pas trouvée (ne devrait pas arriver ici)
                $clientItem->setCategory(array_values($allCategories)[0]);
            }

            $manager->persist($clientItem);

            // OPTIONNEL : Ajouter directement ce Custom Item à l'inventaire du client
            $inv = new Inventory();
            $inv->setClient($client);
            $inv->setItem($clientItem);
            $inv->setQuantity(1); // Il en a 1
            $manager->persist($inv);
        }

        // 5. Remplir l'inventaire du client avec des items génériques
        // On prend 15 items au hasard dans la liste globale
        shuffle($allGenericItems);
        $itemsForInventory = array_slice($allGenericItems, 0, 15);

        foreach ($itemsForInventory as $genericItem) {
            $inventory = new Inventory();
            $inventory->setClient($client);
            $inventory->setItem($genericItem);

            // Quantité aléatoire entre 1 et 10
            $inventory->setQuantity(mt_rand(1, 10));

            $manager->persist($inventory);
        }

        $manager->flush();
    }
}