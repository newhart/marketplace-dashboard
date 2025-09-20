# Documentation API - Gestion des Commandes

Cette documentation décrit les endpoints API pour la gestion des commandes dans le système marketplace.

## Base URL
```
http://votre-domaine.com/api
```

## Authentification
Toutes les routes nécessitent une authentification via Sanctum. Incluez le token Bearer dans l'en-tête Authorization :
```
Authorization: Bearer {token}
```

---

## 📦 **Créer une Commande**

### `POST /orders`

Crée une nouvelle commande pour l'utilisateur authentifié.

#### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

#### Corps de la requête
```json
{
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        },
        {
            "product_id": 5,
            "quantity": 1
        }
    ],
    "total_amount": 150.75
}
```

#### Paramètres
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| items | array | Oui | Liste des articles de la commande |
| items.*.product_id | integer | Oui | ID du produit (doit exister) |
| items.*.quantity | integer | Oui | Quantité (minimum 1) |
| total_amount | number | Oui | Montant total de la commande |

#### Réponse de succès (201)
```json
{
    "success": true,
    "message": "Commande créée avec succès",
    "data": {
        "order_id": 123,
        "status": "pending",
        "total_amount": 150.75
    }
}
```

#### Réponses d'erreur
- **422 Unprocessable Entity** : Erreurs de validation
- **401 Unauthorized** : Utilisateur non authentifié
- **500 Internal Server Error** : Erreur serveur

---

## 👤 **API Client**

### `GET /customer/orders`

Récupère toutes les commandes du client authentifié.

#### Headers
```
Authorization: Bearer {token}
```

#### Réponse de succès (200)
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "status": "pending",
            "total_amount": 150.75,
            "created_at": "2025-09-20T10:30:00Z",
            "updated_at": "2025-09-20T10:30:00Z",
            "items": [
                {
                    "id": 1,
                    "quantity": 2,
                    "price": 50.00,
                    "product": {
                        "id": 1,
                        "name": "Produit A",
                        "description": "Description du produit",
                        "price": 50.00
                    }
                }
            ]
        }
    ]
}
```

### `GET /customer/orders/{id}`

Récupère les détails d'une commande spécifique du client.

#### Headers
```
Authorization: Bearer {token}
```

#### Paramètres d'URL
| Paramètre | Type | Description |
|-----------|------|-------------|
| id | integer | ID de la commande |

#### Réponse de succès (200)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "status": "pending",
        "total_amount": 150.75,
        "created_at": "2025-09-20T10:30:00Z",
        "updated_at": "2025-09-20T10:30:00Z",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "items": [
            {
                "id": 1,
                "quantity": 2,
                "price": 50.00,
                "product": {
                    "id": 1,
                    "name": "Produit A",
                    "description": "Description du produit",
                    "price": 50.00,
                    "image": "url-image"
                }
            }
        ]
    }
}
```

---

## 🏪 **API Marchand**

### `GET /merchant/orders`

Récupère toutes les commandes contenant des produits du marchand authentifié.

#### Headers
```
Authorization: Bearer {token}
```

#### Réponse de succès (200)
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "status": "pending",
            "total_amount": 150.75,
            "created_at": "2025-09-20T10:30:00Z",
            "customer_name": "John Doe",
            "merchant_items": [
                {
                    "product_id": 1,
                    "product_name": "Mon Produit",
                    "quantity": 2,
                    "price": 50.00
                }
            ]
        }
    ]
}
```

### `GET /merchant/orders/{id}`

Récupère les détails d'une commande spécifique pour le marchand (uniquement ses produits).

#### Headers
```
Authorization: Bearer {token}
```

#### Paramètres d'URL
| Paramètre | Type | Description |
|-----------|------|-------------|
| id | integer | ID de la commande |

#### Réponse de succès (200)
```json
{
    "success": true,
    "data": {
        "order_id": 123,
        "status": "pending",
        "customer_info": {
            "name": "John Doe",
            "email": "john@example.com"
        },
        "merchant_items": [
            {
                "product_id": 1,
                "product_name": "Mon Produit",
                "quantity": 2,
                "unit_price": 50.00,
                "total_price": 100.00
            }
        ],
        "merchant_total": 100.00,
        "created_at": "2025-09-20T10:30:00Z"
    }
}
```

#### Réponses d'erreur
- **403 Forbidden** : Accès non autorisé (utilisateur non marchand)
- **404 Not Found** : Commande non trouvée ou ne contient pas les produits du marchand

### `DELETE /merchant/orders/{id}`

Annule une commande (marchand uniquement).

#### Headers
```
Authorization: Bearer {token}
```

#### Paramètres d'URL
| Paramètre | Type | Description |
|-----------|------|-------------|
| id | integer | ID de la commande à annuler |

#### Réponse de succès (200)
```json
{
    "success": true,
    "message": "Commande annulée avec succès"
}
```

#### Réponses d'erreur
- **403 Forbidden** : Accès non autorisé (utilisateur non marchand)
- **404 Not Found** : Commande non trouvée ou ne contient pas les produits du marchand
- **500 Internal Server Error** : Erreur lors de l'annulation

---

## 🧾 **Génération de Facture**

### `GET /orders/{id}/invoice`

Génère une facture pour une commande. Accessible aux clients (pour leurs commandes) et aux marchands (pour les commandes contenant leurs produits).

#### Headers
```
Authorization: Bearer {token}
```

#### Paramètres d'URL
| Paramètre | Type | Description |
|-----------|------|-------------|
| id | integer | ID de la commande |

#### Réponse de succès (200)
```json
{
    "success": true,
    "message": "Facture générée avec succès",
    "data": {
        "invoice_url": "https://votre-domaine.com/invoices/123.pdf"
    }
}
```

#### Réponses d'erreur
- **401 Unauthorized** : Utilisateur non authentifié
- **403 Forbidden** : Accès non autorisé à cette commande
- **500 Internal Server Error** : Erreur lors de la génération

---

## 📊 **Statuts des Commandes**

Les commandes peuvent avoir les statuts suivants :
- `pending` : En attente
- `confirmed` : Confirmée
- `processing` : En cours de traitement
- `shipped` : Expédiée
- `delivered` : Livrée
- `cancelled` : Annulée

---

## 🔒 **Sécurité et Permissions**

### Clients
- Peuvent créer des commandes
- Peuvent voir uniquement leurs propres commandes
- Peuvent générer des factures pour leurs commandes

### Marchands
- Peuvent voir les commandes contenant leurs produits
- Peuvent voir les détails des commandes (limité à leurs produits)
- Peuvent annuler des commandes
- Peuvent générer des factures pour les commandes contenant leurs produits

---

## ⚠️ **Codes d'Erreur Communs**

| Code | Description |
|------|-------------|
| 200 | Succès |
| 201 | Créé avec succès |
| 401 | Non authentifié |
| 403 | Accès interdit |
| 404 | Ressource non trouvée |
| 422 | Erreurs de validation |
| 500 | Erreur serveur interne |

---

## 🧪 **Exemples d'Utilisation**

### JavaScript/Fetch
```javascript
// Créer une commande
const createOrder = async () => {
    const response = await fetch('/api/orders', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
            items: [
                { product_id: 1, quantity: 2 },
                { product_id: 5, quantity: 1 }
            ],
            total_amount: 150.75
        })
    });
    
    const data = await response.json();
    return data;
};

// Récupérer les commandes client
const getCustomerOrders = async () => {
    const response = await fetch('/api/customer/orders', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    const data = await response.json();
    return data;
};
```

### cURL
```bash
# Créer une commande
curl -X POST http://votre-domaine.com/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "items": [
      {"product_id": 1, "quantity": 2},
      {"product_id": 5, "quantity": 1}
    ],
    "total_amount": 150.75
  }'

# Récupérer les commandes client
curl -X GET http://votre-domaine.com/api/customer/orders \
  -H "Authorization: Bearer YOUR_TOKEN"
```

