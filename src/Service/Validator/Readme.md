# 🛠️ RequestValidator Service

Ce service permet de **simplifier** et **centraliser** la validation des données entrantes (généralement issues du **body JSON** d'une requête API) en utilisant le composant **Validator** natif de Symfony.

Il remplace les multiples `if (empty($data['field']))` par une **définition déclarative**, claire et robuste.

---

## ⚙️ Installation / Configuration

Le service est **autoconfiguré par Symfony**. Il suffit de l'injecter dans vos contrôleurs ou autres services.

**Namespace :** `App\Service\Validator\RequestValidator`

---

## 🚀 Utilisation de base

### 1. Injection de dépendance

Dans votre contrôleur :

```php
use App\Service\Validator\RequestValidator;
use Symfony\Component\Validator\Constraints as Assert;

public function maMethode(RequestValidator $validator)
{
    // ...
}
```

### 2. Validation des données

La méthode `check()` prend deux arguments :

* Les **données à valider** (tableau associatif)
* Le **schéma de validation** (tableau de contraintes)

Si la validation échoue, une **Exception est levée** avec un message d'erreur formaté.

```php
// Récupération des données
$data = json_decode($request->getContent(), true) ?? [];

// Définition du schéma
$constraints = [
    'email' => [
        new Assert\NotBlank(['message' => 'Email requis']),
        new Assert\Email(['message' => 'Format email invalide'])
    ],
    'age' => [
        new Assert\NotBlank(),
        new Assert\Type(['type' => 'integer']),
        new Assert\GreaterThan(['value' => 18])
    ]
];

try {
    // Validation
    $validator->check($data, $constraints);

    // Si on arrive ici, $data est valide !
    $email = $data['email'];

} catch (\Exception $e) {
    return new JsonResponse([
        'error' => 'Erreur de validation',
        'message' => $e->getMessage() // ex: "email: Format email invalide, age: Cette valeur doit être supérieure à 18."
    ], Response::HTTP_BAD_REQUEST);
}
```

---

## 📚 Exemples de Contraintes Utiles

| Type      | Contrainte                                     | Description                              |
| --------- | ---------------------------------------------- | ---------------------------------------- |
| Requis    | `new Assert\NotBlank()`                        | Champ obligatoire, non vide              |
| Type      | `new Assert\Type(['type' => 'integer'])`       | Vérifie le type attendu                  |
| Nombre    | `new Assert\Positive()`                        | Doit être strictement supérieur à 0      |
| Nombre    | `new Assert\Range(['min' => 1, 'max' => 5])`   | Doit être compris entre `min` et `max`   |
| Texte     | `new Assert\Length(['min' => 3])`              | Longueur minimale                        |
| Choix     | `new Assert\Choice(['choices' => ['A', 'B']])` | Valeur autorisée dans une liste          |
| Format    | `new Assert\Email()`                           | Email valide                             |
| Optionnel | `new Assert\Optional([...])`                   | Valide seulement si le champ est présent |

---

## 💡 Astuces

### ✔️ Champs optionnels vs Champs ignorés

* **Champs ignorés** : Par défaut, le service accepte les champs *supplémentaires* non définis dans le schéma (`allowExtraFields: true`).
* **Champs optionnels** : Pour valider un champ seulement s'il est présent :

```php
'telephone' => new Assert\Optional([
    new Assert\Type(['type' => 'string']),
    new Assert\Length(['min' => 10])
]),
```

### ✔️ Validation d'IDs (Foreign Keys)

Pour valider qu'un ID est bien un entier positif avant même une recherche en base :

```php
'itemId' => [
    new Assert\NotBlank(),
    new Assert\Type(['type' => 'integer']),
    new Assert\Positive()
],
```

---

## 🎉 Conclusion

Avec le **RequestValidator**, vos contrôleurs deviennent plus propres, plus sûrs et plus lisibles. Une seule ligne pour valider une structure complexe : simple et efficace !
