# API Documentation - Merchant Orders Management

## Base URL

```
https://your-domain.com/api/merchant
```

## Authentication

Toutes les routes nécessitent une authentification via **Laravel Sanctum** avec le rôle **merchant**.

### Headers requis

```
Authorization: Bearer {your_access_token}
Accept: application/json
Content-Type: application/json
```

---

## Endpoints

### 1. Liste des commandes du marchand

Récupère toutes les commandes contenant les produits du marchand connecté.

**Endpoint:** `GET /api/merchant/orders`

**Authentification:** Requise (Sanctum + role:merchant)

**Query Parameters:**

-   `page` (optionnel) - Numéro de page pour la pagination (défaut: 1)

**Réponse succès (200):**

```json
{
    "orders": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 5,
                "status": "pending",
                "total_amount": "15000.00",
                "created_at": "2025-12-01T10:30:00.000000Z",
                "updated_at": "2025-12-01T10:30:00.000000Z",
                "items": [
                    {
                        "id": 1,
                        "order_id": 1,
                        "product_id": 10,
                        "quantity": 2,
                        "price": "5000.00",
                        "created_at": "2025-12-01T10:30:00.000000Z",
                        "updated_at": "2025-12-01T10:30:00.000000Z",
                        "product": {
                            "id": 10,
                            "name": "Tomates fraîches",
                            "description": "Tomates bio de qualité",
                            "price": "5000.00",
                            "price_promo": null,
                            "category_id": 2,
                            "user_id": 3,
                            "stock": 50,
                            "origin": "Sénégal",
                            "unit": "kg",
                            "created_at": "2025-11-28T08:00:00.000000Z",
                            "updated_at": "2025-11-28T08:00:00.000000Z"
                        }
                    }
                ]
            }
        ],
        "first_page_url": "http://your-domain.com/api/merchant/orders?page=1",
        "from": 1,
        "last_page": 3,
        "last_page_url": "http://your-domain.com/api/merchant/orders?page=3",
        "next_page_url": "http://your-domain.com/api/merchant/orders?page=2",
        "path": "http://your-domain.com/api/merchant/orders",
        "per_page": 10,
        "prev_page_url": null,
        "to": 10,
        "total": 25
    }
}
```

**Notes importantes:**

-   Seules les commandes contenant au moins un produit appartenant au marchand sont retournées
-   Les `items` retournés sont uniquement ceux qui appartiennent au marchand (pas tous les items de la commande)
-   Les commandes sont triées par date de création (plus récentes en premier)
-   Pagination de 10 commandes par page

**Erreurs possibles:**

**401 Unauthorized:**

```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden:**

```json
{
    "message": "Accès non autorisé"
}
```

---

### 2. Détails d'une commande

Récupère les détails d'une commande spécifique (uniquement les items du marchand).

**Endpoint:** `GET /api/merchant/orders/{id}`

**Authentification:** Requise (Sanctum)

**Paramètres URL:**

-   `id` (requis) - ID de la commande

**Réponse succès (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 5,
        "status": "pending",
        "total_amount": "15000.00",
        "created_at": "2025-12-01T10:30:00.000000Z",
        "updated_at": "2025-12-01T10:30:00.000000Z",
        "items": [
            {
                "id": 1,
                "order_id": 1,
                "product_id": 10,
                "quantity": 2,
                "price": "5000.00",
                "created_at": "2025-12-01T10:30:00.000000Z",
                "updated_at": "2025-12-01T10:30:00.000000Z",
                "product": {
                    "id": 10,
                    "name": "Tomates fraîches",
                    "description": "Tomates bio de qualité",
                    "price": "5000.00",
                    "price_promo": null,
                    "category_id": 2,
                    "user_id": 3,
                    "stock": 50,
                    "origin": "Sénégal",
                    "unit": "kg"
                }
            }
        ],
        "user": {
            "id": 5,
            "name": "Client Test",
            "email": "client@example.com"
        }
    }
}
```

**Erreurs possibles:**

**404 Not Found:**

```json
{
    "error": "Commande non trouvée ou ne contient pas vos produits"
}
```

---

### 3. Annuler une commande

Annule une commande contenant les produits du marchand.

**Endpoint:** `DELETE /api/merchant/orders/{id}`

**Authentification:** Requise (Sanctum)

**Paramètres URL:**

-   `id` (requis) - ID de la commande à annuler

**Réponse succès (200):**

```json
{
    "success": true,
    "message": "Commande annulée avec succès"
}
```

**Notes importantes:**

-   Le statut de la commande est changé à `cancelled`
-   Le client reçoit une notification d'annulation
-   Les administrateurs sont également notifiés

**Erreurs possibles:**

**404 Not Found:**

```json
{
    "error": "Commande non trouvée ou ne contient pas vos produits"
}
```

**500 Internal Server Error:**

```json
{
    "success": false,
    "message": "Erreur lors de l'annulation de la commande",
    "error": "Message d'erreur détaillé"
}
```

---

## Notifications

### Notification de nouvelle commande

Lorsqu'une commande est créée contenant vos produits, vous recevez automatiquement:

#### 1. Email

Un email avec les détails:

-   Nom du produit
-   Quantité commandée
-   Numéro de commande
-   Montant total

#### 2. Notification en base de données

Stockée dans la table `notifications`:

```json
{
    "order_id": 1,
    "product_id": 10,
    "product_name": "Tomates fraîches",
    "quantity": 2,
    "total_price": "10000.00",
    "user_id": 5,
    "user_name": "Client Test",
    "message": "Nouvelle commande pour votre produit Tomates fraîches"
}
```

#### 3. Push Notification (Broadcasting)

Événement diffusé sur le canal privé: `private-App.Models.User.{merchant_id}`

**Événement:** `Illuminate\Notifications\Events\BroadcastNotificationCreated`

**Payload:**

```json
{
    "order_id": 1,
    "product_id": 10,
    "product_name": "Tomates fraîches",
    "quantity": 2,
    "total_price": "10000.00",
    "user_id": 5,
    "user_name": "Client Test",
    "message": "Nouvelle commande pour votre produit Tomates fraîches",
    "type": "merchant_order"
}
```

### Configuration Push Notifications (React Native)

Pour recevoir les notifications push dans votre application React Native:

1. **Installer Pusher JS:**

```bash
npm install pusher-js
```

2. **S'abonner au canal:**

```javascript
import Pusher from "pusher-js";

// Initialiser Pusher
const pusher = new Pusher("YOUR_PUSHER_KEY", {
    cluster: "YOUR_CLUSTER",
    authEndpoint: "https://your-domain.com/broadcasting/auth",
    auth: {
        headers: {
            Authorization: `Bearer ${accessToken}`,
            Accept: "application/json",
        },
    },
});

// S'abonner au canal privé du marchand
const channel = pusher.subscribe(`private-App.Models.User.${merchantId}`);

// Écouter les notifications
channel.bind(
    "Illuminate\\Notifications\\Events\\BroadcastNotificationCreated",
    (data) => {
        if (data.type === "merchant_order") {
            // Afficher la notification
            console.log("Nouvelle commande:", data);
            // Afficher une notification locale
            showLocalNotification({
                title: "Nouvelle commande",
                body: data.message,
                data: data,
            });
        }
    }
);
```

---

## Statuts des commandes

| Statut       | Description                       |
| ------------ | --------------------------------- |
| `pending`    | Commande en attente de traitement |
| `processing` | Commande en cours de traitement   |
| `completed`  | Commande complétée                |
| `cancelled`  | Commande annulée                  |

---

## Exemples d'utilisation

### Exemple 1: Récupérer toutes les commandes

```javascript
const getOrders = async (page = 1) => {
    try {
        const response = await fetch(
            `https://your-domain.com/api/merchant/orders?page=${page}`,
            {
                method: "GET",
                headers: {
                    Authorization: `Bearer ${accessToken}`,
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
            }
        );

        const data = await response.json();

        if (response.ok) {
            console.log("Commandes:", data.orders.data);
            return data.orders;
        } else {
            console.error("Erreur:", data);
        }
    } catch (error) {
        console.error("Erreur réseau:", error);
    }
};
```

### Exemple 2: Annuler une commande

```javascript
const cancelOrder = async (orderId) => {
    try {
        const response = await fetch(
            `https://your-domain.com/api/merchant/orders/${orderId}`,
            {
                method: "DELETE",
                headers: {
                    Authorization: `Bearer ${accessToken}`,
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
            }
        );

        const data = await response.json();

        if (response.ok) {
            console.log("Succès:", data.message);
            return true;
        } else {
            console.error("Erreur:", data.error);
            return false;
        }
    } catch (error) {
        console.error("Erreur réseau:", error);
        return false;
    }
};
```

---

## Notes de sécurité

1. **Authentification requise:** Toutes les routes nécessitent un token Sanctum valide
2. **Vérification du rôle:** Le middleware `role:merchant` vérifie que l'utilisateur est bien un marchand
3. **Isolation des données:** Un marchand ne peut voir que les commandes contenant ses propres produits
4. **Validation des permissions:** Chaque action vérifie que le marchand a accès à la ressource demandée

---

## Support

Pour toute question ou problème, contactez l'équipe de support technique.
