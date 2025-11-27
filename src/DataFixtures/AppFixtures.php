<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Client;
use App\Entity\ClientItem;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\Recipe; // N'oubliez pas d'importer l'entité Recipe
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
        // =========================================================================
        // 1. Données de base (Catégorie => Liste d'ingrédients)
        // =========================================================================
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

        $allCategories = [];
        $allGenericItems = [];

        // =========================================================================
        // 2. Création des Catégories et des Items génériques
        // =========================================================================
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

        // =========================================================================
        // 3. Création des Utilisateurs (Clients)
        // =========================================================================

        // 3.a Client USER "test@test.mail"
        $client = new Client();
        $client->setEmail('test@test.mail');
        $client->setName('Utilisateur Test');
        $client->setRoles(['ROLE_USER']);
        $client->setPassword($this->hasher->hashPassword($client, 'azer1234'));
        $manager->persist($client);

        // 3.b Client ADMIN "admin@admin.mail"
        $admin = new Client();
        $admin->setEmail('admin@admin.mail');
        $admin->setName('admin');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'azer1234'));
        $manager->persist($admin);

        // =========================================================================
        // 4. Création d'Items Personnalisés (ClientItem)
        // =========================================================================
        $customItemsData = [
            ['name' => 'Ma Confiture Maison de fraise', 'cat' => 'Sucres & Pâtisserie'],
            ['name' => 'Reste de Pizza Jambon fromage', 'cat' => 'Céréales & Féculents'],
            ['name' => 'Soupe de Grand-mère au herbes', 'cat' => 'Légumes'],
        ];

        foreach ($customItemsData as $data) {
            $clientItem = new ClientItem();
            $clientItem->setName($data['name']);
            $clientItem->setClient($client);

            if (isset($allCategories[$data['cat']])) {
                $clientItem->setCategory($allCategories[$data['cat']]);
            } else {
                $clientItem->setCategory(array_values($allCategories)[0]);
            }

            $manager->persist($clientItem);

            // Ajout direct à l'inventaire
            $inv = new Inventory();
            $inv->setClient($client);
            $inv->setItem($clientItem);
            $inv->setQuantity(1);
            $manager->persist($inv);
        }

        // =========================================================================
        // 5. Remplir l'inventaire du client
        // =========================================================================
        shuffle($allGenericItems);
        $itemsForInventory = array_slice($allGenericItems, 0, 15);

        foreach ($itemsForInventory as $genericItem) {
            $inventory = new Inventory();
            $inventory->setClient($client);
            $inventory->setItem($genericItem);
            $inventory->setQuantity(mt_rand(1, 10));

            $manager->persist($inventory);
        }

        // =========================================================================
        // 6. Création des Recettes (NOUVEAU)
        // =========================================================================

        $recipesData = [
            [
                'name' => 'Pâtes Carbonara Simplifiées',
                'desc' => 'Une version rapide pour les étudiants, prête en 15 minutes.',
                'time' => 15,
                'matching' => 95,
                'ingredients' => ['Pâtes spaghetti', 'Lardon', 'Crème fraîche', 'Œuf', 'Poivre noir'],
                'steps' => [
                    'Faire cuire les pâtes dans l\'eau bouillante.',
                    'Faire revenir les lardons à la poêle.',
                    'Mélanger la crème et l\'œuf dans un bol.',
                    'Tout mélanger et servir chaud.'
                ],
                'author' => $admin // L'admin crée les recettes "officielles"
            ],
            [
                'name' => 'Salade de Fruits Vitaminée',
                'desc' => 'Le plein d\'énergie pour le petit déjeuner.',
                'time' => 10,
                'matching' => 100,
                'ingredients' => ['Pomme', 'Banane', 'Kiwi', 'Orange', 'Citron'],
                'steps' => [
                    'Éplucher tous les fruits.',
                    'Couper les fruits en petits dés.',
                    'Arroser de jus de citron pour éviter l\'oxydation.',
                    'Mélanger et déguster frais.'
                ],
                'author' => $admin
            ],
            [
                'name' => 'Poulet Rôti du Dimanche',
                'desc' => 'Le classique indémodable avec ses pommes de terre.',
                'time' => 90,
                'matching' => 80,
                'ingredients' => ['Poulet entier', 'Pomme de terre', 'Huile d\'olive', 'Thym', 'Ail'],
                'steps' => [
                    'Préchauffer le four à 200°C.',
                    'Disposer le poulet et les pommes de terre dans un plat.',
                    'Arroser d\'huile et ajouter les herbes.',
                    'Cuire pendant 1h30 en arrosant régulièrement.'
                ],
                'author' => $client // Le client peut aussi créer ses propres recettes
            ],
            [
                'name' => 'Omelette aux Champignons (sans champignons)',
                'desc' => 'Une omelette simple car je n\'avais pas de champignons.',
                'time' => 5,
                'matching' => 60,
                'ingredients' => ['Œuf', 'Beurre doux', 'Sel fin', 'Poivre noir'],
                'steps' => [
                    'Battre les œufs.',
                    'Cuire à la poêle avec du beurre.',
                    'Assaisonner.'
                ],
                'author' => $client
            ]
        ];

        $createdRecipes = [];

        foreach ($recipesData as $rData) {
            $recipe = new Recipe();
            $recipe->setName($rData['name']);
            $recipe->setDescription($rData['desc']);
            $recipe->setPreparationTime($rData['time']);
            $recipe->setMatching($rData['matching']);
            $recipe->setDate(time()); // Timestamp actuel
            $recipe->setImage(null); // Image laissée à null comme demandé

            // Doctrine gère automatiquement la conversion array -> JSON grâce au type json/simple_array
            $recipe->setIngredients($rData['ingredients']);
            $recipe->setSteps($rData['steps']);

            $recipe->setAuthor($rData['author']);

            $manager->persist($recipe);
            $createdRecipes[] = $recipe;
        }

        // =========================================================================
        // 7. Gestion des Favoris (NOUVEAU)
        // =========================================================================

        // Le client "Test" met en favori les Carbonara et la Salade de fruits
        // Note: Assurez-vous que votre entité Client a bien une méthode addRecipe() ou addFavorite()
        // générée par la relation ManyToMany. Je suppose ici addRecipe() basé sur le nom standard.

        if (isset($createdRecipes[0])) {
            // Ajoute Carbonara aux favoris du client
            $client->addRecipe($createdRecipes[0]);
        }
        if (isset($createdRecipes[1])) {
            // Ajoute Salade de fruits aux favoris du client
            $client->addRecipe($createdRecipes[1]);
        }

        // L'admin met en favori sa propre recette de Salade
        if (isset($createdRecipes[1])) {
            $admin->addRecipe($createdRecipes[1]);
        }

        $manager->flush();
    }
}