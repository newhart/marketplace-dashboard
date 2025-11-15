# Documentation API Orders

## Base URL
```
http://votre-domaine.com/api
```

**Note importante** : Tous les endpoints listés ci-dessous sont relatifs à `/api`. Par exemple, `GET /customer/orders` correspond à l'URL complète `http://votre-domaine.com/api/customer/orders`.

## Authentification
Toutes les routes nécessitent une authentification via Sanctum. Incluez le token d'authentification dans le header :
```
Authorization: Bearer {votre_token}
```

---

## 1. Créer une commande

### Endpoint
```
POST /orders
```

### Description
Permet à un client authentifié de créer une nouvelle commande.

### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

### Payload (Request Body)
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 3,
      "quantity": 1
    }
  ],
  "total_amount": 150.00
}
```

### Paramètres requis
- `items` (array, requis) : Liste des produits de la commande
  - `product_id` (integer, requis) : ID du produit (doit exister dans la table products)
  - `quantity` (integer, requis) : Quantité commandée (minimum: 1)
- `total_amount` (numeric, requis) : Montant total de la commande (minimum: 0)

### Réponse réussie (201)
```json
{
  "success": true,
  "message": "Commande créée avec succès",
  "data": {
    "order_id": 15,
    "status": "pending",
    "total_amount": 150.00
  }
}
```

### Erreurs possibles

#### 422 - Validation échouée
```json
{
  "errors": {
    "items": ["Le champ items est requis."],
    "items.0.product_id": ["Le produit sélectionné n'existe pas."],
    "items.0.quantity": ["La quantité doit être au moins 1."],
    "total_amount": ["Le champ total amount est requis."]
  }
}
```

#### 401 - Non authentifié
```json
{
  "error": "Utilisateur non authentifié"
}
```

#### 500 - Erreur serveur
```json
{
  "success": false,
  "message": "Erreur lors de la création de la commande",
  "error": "Message d'erreur détaillé"
}
```

---

## 2. Obtenir les commandes du client

### Endpoint
```
GET /customer/orders
```

### Description
Récupère toutes les commandes du client authentifié avec les détails des produits.

### Headers
```
Authorization: Bearer {token}
```

### Payload
Aucun payload requis.

### Réponse réussie (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "user_id": 5,
      "total_amount": 150.00,
      "status": "pending",
      "created_at": "2025-11-15T10:30:00.000000Z",
      "updated_at": "2025-11-15T10:30:00.000000Z",
      "items": [
        {
          "id": 25,
          "order_id": 15,
          "product_id": 1,
          "quantity": 2,
          "price": 50.00,
          "created_at": "2025-11-15T10:30:00.000000Z",
          "updated_at": "2025-11-15T10:30:00.000000Z",
          "product": {
            "id": 1,
            "name": "Produit exemple",
            "description": "Description du produit",
            "price": 50.00,
            "stock": 100
          }
        },
        {
          "id": 26,
          "order_id": 15,
          "product_id": 3,
          "quantity": 1,
          "price": 50.00,
          "created_at": "2025-11-15T10:30:00.000000Z",
          "updated_at": "2025-11-15T10:30:00.000000Z",
          "product": {
            "id": 3,
            "name": "Autre produit",
            "description": "Description",
            "price": 50.00,
            "stock": 50
          }
        }
      ]
    }
  ]
}
```

### Erreurs possibles

#### 401 - Non authentifié
```json
{
  "error": "Utilisateur non authentifié"
}
```

#### 500 - Erreur serveur
```json
{
  "success": false,
  "message": "Erreur lors de la récupération des commandes",
  "error": "Message d'erreur détaillé"
}
```

---

## 3. Obtenir les détails d'une commande (Client)

### Endpoint
```
GET /customer/orders/{orderId}
```

### Description
Récupère les détails complets d'une commande spécifique pour le client authentifié.

### Headers
```
Authorization: Bearer {token}
```

### Paramètres URL
- `orderId` (integer, requis) : ID de la commande

### Exemple d'URL
```
GET /customer/orders/15
```

### Payload
Aucun payload requis.

### Réponse réussie (200)
```json
{
  "success": true,
  "data": {
    "id": 15,
    "user_id": 5,
    "total_amount": 150.00,
    "status": "pending",
    "created_at": "2025-11-15T10:30:00.000000Z",
    "updated_at": "2025-11-15T10:30:00.000000Z",
    "user": {
      "id": 5,
      "name": "Client Nom",
      "email": "client@example.com"
    },
    "items": [
      {
        "id": 25,
        "order_id": 15,
        "product_id": 1,
        "quantity": 2,
        "price": 50.00,
        "created_at": "2025-11-15T10:30:00.000000Z",
        "updated_at": "2025-11-15T10:30:00.000000Z",
        "product": {
          "id": 1,
          "name": "Produit exemple",
          "description": "Description du produit",
          "price": 50.00,
          "stock": 100
        }
      }
    ]
  }
}
```

### Erreurs possibles

#### 401 - Non authentifié
```json
{
  "error": "Utilisateur non authentifié"
}
```

#### 404 - Commande non trouvée
```json
{
  "success": false,
  "message": "Erreur lors de la récupération des détails de la commande",
  "error": "No query results for model [App\\Models\\Order] {id}"
}
```

#### 500 - Erreur serveur
```json
{
  "success": false,
  "message": "Erreur lors de la récupération des détails de la commande",
  "error": "Message d'erreur détaillé"
}
```

---

## 4. Obtenir les commandes du marchand

### Endpoint
```
GET /merchant/orders
```

### Description
Récupère toutes les commandes contenant des produits du marchand authentifié.

### Headers
```
Authorization: Bearer {token}
```

### Payload
Aucun payload requis.

### Note
L'utilisateur doit avoir le rôle "merchant" pour accéder à cette route.

### Réponse réussie (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "user_id": 5,
      "total_amount": 150.00,
      "status": "pending",
      "created_at": "2025-11-15T10:30:00.000000Z",
      "updated_at": "2025-11-15T10:30:00.000000Z",
      "items": [
        {
          "id": 25,
          "order_id": 15,
          "product_id": 1,
          "quantity": 2,
          "price": 50.00,
          "created_at": "2025-11-15T10:30:00.000000Z",
          "updated_at": "2025-11-15T10:30:00.000000Z",
          "product": {
            "id": 1,
            "name": "Mon Produit",
            "description": "Description",
            "price": 50.00,
            "merchant_id": 10
          }
        }
      ]
    }
  ]
}
```

### Erreurs possibles

#### 403 - Accès refusé
```json
{
  "error": "Accès non autorisé"
}
```

#### 500 - Erreur serveur
```json
{
  "success": false,
  "message": "Erreur lors de la récupération des commandes",
  "error": "Message d'erreur détaillé"
}
```

---

## 5. Obtenir les détails d'une commande (Marchand)

### Endpoint
```
GET /merchant/orders/{orderId}
```

### Description
Récupère les détails d'une commande avec uniquement les items liés aux produits du marchand authentifié.

### Headers
```
Authorization: Bearer {token}
```

### Paramètres URL
- `orderId` (integer, requis) : ID de la commande

### Exemple d'URL
```
GET /merchant/orders/15
```

### Payload
Aucun payload requis.

### Note
L'utilisateur doit avoir le rôle "merchant" et la commande doit contenir au moins un de ses produits.

### Réponse réussie (200)
```json
{
  "success": true,
  "data": {
    "id": 15,
    "user_id": 5,
    "total_amount": 150.00,
    "status": "pending",
    "created_at": "2025-11-15T10:30:00.000000Z",
    "updated_at": "2025-11-15T10:30:00.000000Z",
    "items": [
      {
        "id": 25,
        "order_id": 15,
        "product_id": 1,
        "quantity": 2,
        "price": 50.00,
        "created_at": "2025-11-15T10:30:00.000000Z",
        "updated_at": "2025-11-15T10:30:00.000000Z",
        "product": {
          "id": 1,
          "name": "Mon Produit",
          "description": "Description",
          "price": 50.00,
          "merchant_id": 10
        }
      }
    ]
  }
}
```

### Erreurs possibles

#### 403 - Accès refusé
```json
{
  "error": "Accès non autorisé"
}
```

#### 404 - Commande non trouvée ou sans produits du marchand
```json
{
  "error": "Commande non trouvée ou ne contient pas vos produits"
}
```

#### 500 - Erreur serveur
```json
{
  "success": false,
  "message": "Erreur lors de la récupération des détails de la commande",
  "error": "Message d'erreur détaillé"
}
```

---

## 6. Annuler une commande (Marchand uniquement)

### Endpoint
```
DELETE /merchant/orders/{orderId}
```

### Description
Permet à un marchand d'annuler une commande contenant ses produits.

### Headers
```
Authorization: Bearer {token}
```

### Paramètres URL
- `orderId` (integer, requis) : ID de la commande à annuler

### Exemple d'URL
```
DELETE /merchant/orders/15
```

### Payload
Aucun payload requis.

### Note
L'utilisateur doit avoir le rôle "merchant" et la commande doit contenir au moins un de ses produits.

### Réponse réussie (200)
```json
{
  "success": true,
  "message": "Commande annulée avec succès"
}
```

### Erreurs possibles

#### 403 - Accès refusé
```json
{
  "error": "Accès non autorisé"
}
```

#### 404 - Commande non trouvée ou sans produits du marchand
```json
{
  "error": "Commande non trouvée ou ne contient pas vos produits"
}
```

#### 500 - Erreur serveur
```json
{
  "success": false,
  "message": "Erreur lors de l'annulation de la commande",
  "error": "Message d'erreur détaillé"
}
```

---

## 7. Générer une facture

### Endpoint
```
GET /orders/{orderId}/invoice
```

### Description
Génère une facture PDF pour une commande spécifique. Accessible aux clients pour leurs commandes et aux marchands pour les commandes contenant leurs produits.

### Headers
```
Authorization: Bearer {token}
```

### Paramètres URL
- `orderId` (integer, requis) : ID de la commande

### Exemple d'URL
```
GET /orders/15/invoice
```

### Payload
Aucun payload requis.

### Note
- Les clients peuvent générer des factures pour leurs propres commandes
- Les marchands peuvent générer des factures pour les commandes contenant leurs produits

### Réponse réussie (200)
```json
{
  "success": true,
  "message": "Facture générée avec succès",
  "data": {
    "invoice_url": "http://votre-domaine.com/storage/invoices/invoice_15_1731667800.pdf"
  }
}
```

### Erreurs possibles

#### 401 - Non authentifié
```json
{
  "error": "Utilisateur non authentifié"
}
```

#### 403 - Accès refusé
```json
{
  "error": "Accès non autorisé à cette commande"
}
```

#### 500 - Erreur serveur
```json
{
  "success": false,
  "message": "Erreur lors de la génération de la facture",
  "error": "Message d'erreur détaillé"
}
```

---

## Exemples d'utilisation avec cURL

### Créer une commande
```bash
curl -X POST http://votre-domaine.com/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer votre_token" \
  -d '{
    "items": [
      {
        "product_id": 1,
        "quantity": 2
      },
      {
        "product_id": 3,
        "quantity": 1
      }
    ],
    "total_amount": 150.00
  }'
```

### Obtenir les commandes du client
```bash
curl -X GET http://votre-domaine.com/api/customer/orders \
  -H "Authorization: Bearer votre_token"
```

### Obtenir les détails d'une commande (Client)
```bash
curl -X GET http://votre-domaine.com/api/customer/orders/15 \
  -H "Authorization: Bearer votre_token"
```

### Obtenir les commandes du marchand
```bash
curl -X GET http://votre-domaine.com/api/merchant/orders \
  -H "Authorization: Bearer votre_token"
```

### Annuler une commande (Marchand)
```bash
curl -X DELETE http://votre-domaine.com/api/merchant/orders/15 \
  -H "Authorization: Bearer votre_token"
```

### Générer une facture
```bash
curl -X GET http://votre-domaine.com/api/orders/15/invoice \
  -H "Authorization: Bearer votre_token"
```

---

## Statuts de commande possibles

- `pending` : En attente
- `processing` : En traitement
- `completed` : Complétée
- `cancelled` : Annulée
- `refunded` : Remboursée

---

## Notes importantes

1. **Authentification** : Tous les endpoints nécessitent un token Sanctum valide
2. **Rôles** : Certains endpoints nécessitent des rôles spécifiques (merchant)
3. **Validation** : Tous les payloads sont validés côté serveur
4. **Sécurité** : Les clients ne peuvent accéder qu'à leurs propres commandes
5. **Marchands** : Les marchands ne voient que les items de commande contenant leurs produits
6. **Structure OrderItems** :
   - Le champ `price` dans `order_items` représente le **prix unitaire** du produit au moment de la commande
   - Le prix est automatiquement récupéré depuis la table `products` lors de la création de la commande
   - Pour calculer le montant total d'un item : `price × quantity`
   - Le champ `total_amount` de la commande doit correspondre à la somme de tous les items

