# 🛠️ RequestValidator Service (Mode DTO)

Ce service permet de transformer automatiquement le JSON d'une requête en Objet PHP (DTO) **et de le valider en une seule étape**, en combinant **Serializer** et **Validator** de Symfony.

---

## ⚙️ Pourquoi utiliser cette approche ?

* **Sécurité des types** : Le JSON est converti en objets typés (int, string, etc.).
* **Autocomplétion** : Votre IDE connaît les propriétés du DTO.
* **Propreté du code** : Règles dans le DTO, pas dans le contrôleur.
* **Erreurs détaillées** : Retour structurée pour le front.

---

## 🚀 Guide d'Utilisation

### 🔹 Étape 1 : Créer un DTO

```php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AddInventoryDto
{
    #[Assert\NotBlank(message: "L'ID est obligatoire")]
    #[Assert\Type('integer')]
    public int $itemId;

    #[Assert\NotBlank]
    #[Assert\Positive(message: "La quantité doit être positive")]
    public int $quantity;

    #[Assert\Length(min: 3)]
    public ?string $comment = null;
}
```

---

### 🔹 Étape 2 : Utiliser dans le Contrôleur

```php
use App\Service\Validator\RequestValidator;
use App\DTO\AddInventoryDto;
use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

public function add(Request $request, RequestValidator $validator): JsonResponse
{
    try {
        /** @var AddInventoryDto $dto */
        $dto = $validator->validate($request->getContent(), AddInventoryDto::class);

    } catch (ValidationException $e) {
        return new JsonResponse([
            'error' => 'Erreur de validation',
            'message' => $e->getMessage(),
            'details' => $e->getDetails()
        ], 400);

    } catch (\Exception $e) {
        return new JsonResponse([
            'error' => 'Bad Request',
            'message' => $e->getMessage()
        ], 400);
    }

    $newItem = new Item();
    $newItem->setId($dto->itemId);
    $newItem->setStock($dto->quantity);

    // ... suite de la logique ...
}
```

---

## 📡 Format de Réponse d'Erreur

```json
{
    "error": "Erreur de validation",
    "message": "Erreur de validation des données",
    "details": {
        "itemId": "L'ID doit être un entier.",
        "quantity": "La quantité doit être positive."
    }
}
```

---

## 📚 Traduction : Tableaux vs Attributs

| Type     | Ancienne syntaxe                         | Nouvelle syntaxe (Attribut DTO)       |
| -------- | ---------------------------------------- | ------------------------------------- |
| Requis   | new Assert\NotBlank()                    | #[Assert\NotBlank]                    |
| Type     | new Assert\Type(['type' => 'int'])       | #[Assert\Type('integer')]             |
| Email    | new Assert\Email()                       | #[Assert\Email]                       |
| Nombre   | new Assert\Positive()                    | #[Assert\Positive]                    |
| Longueur | new Assert\Length(['min' => 3])          | #[Assert\Length(min: 3)]              |
| Choix    | new Assert\Choice(['choices' => ['A']])  | #[Assert\Choice(choices: ['A', 'B'])] |
| Regex    | new Assert\Regex(['pattern' => '/.../']) | #[Assert\Regex('/.../')]              |
| Imbriqué | new Assert\Valid()                       | #[Assert\Valid]                       |

---

## 💡 Astuces & Fonctionnement interne

### ✔️ 1. Gestion des Types

Le Serializer convertit les valeurs avant même la validation. Si un champ typé `int` reçoit une string invalide → **erreur immédiate**.

### ✔️ 2. Objets Imbriqués

```php
class OrderDto {
    #[Assert\Valid]
    public AddressDto $address;
}
```