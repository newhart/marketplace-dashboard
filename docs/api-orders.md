# Documentation API - Commandes

Cette documentation décrit l'API de gestion des commandes pour la plateforme de marketplace.

## Authentification

Toutes les routes d'API pour les commandes nécessitent une authentification via Sanctum. Assurez-vous d'inclure le token d'authentification dans l'en-tête de vos requêtes :

```
Authorization: Bearer {votre_token}
```

## Endpoints

### Créer une nouvelle commande

**URL** : `/api/orders`

**Méthode** : `POST`

**Authentification requise** : Oui

**Permissions requises** : Utilisateur authentifié

#### Payload

```javascript
{
  "items": [
    {
      "product_id": 1,  // ID du produit
      "quantity": 2     // Quantité commandée
    },
    {
      "product_id": 3,  // ID d'un autre produit
      "quantity": 1     // Quantité commandée
    }
  ],
  "total_amount": 1600  // Montant total de la commande en F CFA
}
```

#### Réponse de succès

**Code** : `201 Created`

**Exemple de contenu** :

```javascript
{
  "success": true,
  "message": "Commande créée avec succès",
  "data": {
    "order_id": 123,
    "status": "pending",
    "total_amount": 1600
  }
}
```

#### Réponses d'erreur

**Condition** : Si l'utilisateur n'est pas authentifié.

**Code** : `401 Unauthorized`

**Contenu** :

```javascript
{
  "error": "Utilisateur non authentifié"
}
```

**Condition** : Si les données fournies ne sont pas valides.

**Code** : `422 Unprocessable Entity`

**Contenu** :

```javascript
{
  "errors": {
    "items": ["Le champ items est obligatoire."],
    "items.0.product_id": ["Le produit sélectionné n'existe pas."],
    "items.0.quantity": ["La quantité doit être au moins 1."]
  }
}
```

**Condition** : Si une erreur serveur se produit.

**Code** : `500 Internal Server Error`

**Contenu** :

```javascript
{
  "success": false,
  "message": "Erreur lors de la création de la commande",
  "error": "Message d'erreur spécifique"
}
```

## Notifications

Lorsqu'une commande est créée avec succès, les notifications suivantes sont envoyées automatiquement :

1. **Notification aux administrateurs** : Tous les utilisateurs de type "admin" reçoivent une notification concernant la nouvelle commande.

2. **Notification aux marchands** : Chaque marchand propriétaire d'un produit dans la commande reçoit une notification spécifique pour son produit.

Les notifications sont envoyées à la fois par email et stockées dans la base de données.

## Exemple d'utilisation avec Axios

```javascript
// Exemple d'utilisation avec Axios
import axios from 'axios';

// Configuration avec le token d'authentification
const config = {
  headers: {
    'Authorization': `Bearer ${userToken}`,
    'Content-Type': 'application/json'
  }
};

// Données de la commande
const orderData = {
  items: [
    {
      product_id: 1,
      quantity: 2
    },
    {
      product_id: 3,
      quantity: 1
    }
  ],
  total_amount: 1600
};

// Envoi de la requête
axios.post('/api/orders', orderData, config)
  .then(response => {
    console.log('Commande créée:', response.data);
  })
  .catch(error => {
    console.error('Erreur lors de la création de la commande:', error.response.data);
  });
```

## Exemple d'utilisation avec Fetch API

```javascript
// Exemple d'utilisation avec Fetch API
const userToken = 'votre_token_d_authentification';

// Données de la commande
const orderData = {
  items: [
    {
      product_id: 1,
      quantity: 2
    },
    {
      product_id: 3,
      quantity: 1
    }
  ],
  total_amount: 1600
};

// Envoi de la requête
fetch('/api/orders', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${userToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(orderData)
})
.then(response => response.json())
.then(data => {
  console.log('Commande créée:', data);
})
.catch(error => {
  console.error('Erreur lors de la création de la commande:', error);
});
```
