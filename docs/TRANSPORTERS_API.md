# API Transporteurs Disponibles

## Endpoint : Obtenir les transporteurs disponibles

Récupère la liste des transporteurs disponibles avec leurs distances calculées par rapport à une adresse de livraison et optionnellement un shop.

### URL

```
GET /api/transporters/available
```

### Authentification

Non requise (endpoint public)

### Paramètres de requête

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `delivery_address_id` | integer | ✅ Oui | ID de l'adresse de livraison (doit exister dans la table `addresses`) |
| `shop_id` | integer | ❌ Non | ID de la boutique (doit exister dans la table `boutiques`). Permet de calculer la distance du shop au transporteur |
| `order_id` | integer | ❌ Non | ID de la commande. Permet de récupérer automatiquement la boutique associée au marchand de la commande |
| `limit` | integer | ❌ Non | Nombre maximum de transporteurs à retourner (défaut: 5, min: 1, max: 50) |

### Notes importantes

- Si `shop_id` ou `order_id` n'est pas fourni, seule la distance du transporteur à l'adresse de livraison sera calculée
- `distance_from_shop` sera `null` si le shop n'est pas fourni
- Les transporteurs sont triés par distance totale (plus proche en premier)
- Seuls les transporteurs actifs (`is_active = true`) sont retournés
- Les transporteurs sans coordonnées géographiques valides sont exclus

### Exemples de requêtes

#### Exemple 1 : Avec adresse de livraison uniquement

```bash
GET /api/transporters/available?delivery_address_id=1
```

#### Exemple 2 : Avec shop_id

```bash
GET /api/transporters/available?delivery_address_id=1&shop_id=2
```

#### Exemple 3 : Avec order_id

```bash
GET /api/transporters/available?delivery_address_id=1&order_id=5
```

#### Exemple 4 : Avec limite personnalisée

```bash
GET /api/transporters/available?delivery_address_id=1&shop_id=2&limit=10
```

### Réponse succès (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Jean Dupont",
      "email": "jean.dupont@example.com",
      "phone": "+261 34 12 345 67",
      "company_name": "Transport Express",
      "vehicle_type": "moto",
      "distance_from_shop": 2.5,
      "distance_from_delivery": 5.3,
      "total_distance": 7.8,
      "price": 3900.0,
      "estimated_time": 16,
      "estimated_time_formatted": "16 min",
      "available": true,
      "rating": null
    },
    {
      "id": 2,
      "name": "Marie Martin",
      "email": "marie.martin@example.com",
      "phone": "+261 33 11 222 33",
      "company_name": "Livraison Rapide",
      "vehicle_type": "voiture",
      "distance_from_shop": 3.2,
      "distance_from_delivery": 4.8,
      "total_distance": 8.0,
      "price": 4000.0,
      "estimated_time": 16,
      "estimated_time_formatted": "16 min",
      "available": true,
      "rating": null
    }
  ],
  "meta": {
    "total": 2,
    "delivery_address": {
      "id": 1,
      "full_address": "123 Rue de la République, Antananarivo 101, Madagascar"
    },
    "shop_address": {
      "id": 2,
      "type": "boutique"
    },
    "note": null
  }
}
```

### Réponse sans shop_id (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Jean Dupont",
      "email": "jean.dupont@example.com",
      "phone": "+261 34 12 345 67",
      "company_name": "Transport Express",
      "vehicle_type": "moto",
      "distance_from_shop": null,
      "distance_from_delivery": 5.3,
      "total_distance": 5.3,
      "price": 2650.0,
      "estimated_time": 11,
      "estimated_time_formatted": "11 min",
      "available": true,
      "rating": null
    }
  ],
  "meta": {
    "total": 1,
    "delivery_address": {
      "id": 1,
      "full_address": "123 Rue de la République, Antananarivo 101, Madagascar"
    },
    "shop_address": null,
    "note": "Distance du shop non calculée. Fournissez shop_id ou order_id pour une estimation complète."
  }
}
```

### Structure de la réponse

#### Données du transporteur

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID unique du transporteur |
| `name` | string | Nom complet du transporteur |
| `email` | string | Adresse email |
| `phone` | string\|null | Numéro de téléphone |
| `company_name` | string\|null | Nom de l'entreprise |
| `vehicle_type` | string\|null | Type de véhicule (ex: "moto", "voiture", "camion") |
| `distance_from_shop` | float\|null | Distance en kilomètres du shop au transporteur (null si shop non fourni) |
| `distance_from_delivery` | float | Distance en kilomètres du transporteur à l'adresse de livraison |
| `total_distance` | float | Distance totale en kilomètres (distance_from_shop + distance_from_delivery) |
| `price` | float | Prix de livraison calculé en F (Francs malgaches) |
| `estimated_time` | integer | Temps estimé de livraison en minutes |
| `estimated_time_formatted` | string | Temps estimé formaté (ex: "16 min", "1 h 30 min") |
| `available` | boolean | Indique si le transporteur est disponible |
| `rating` | float\|null | Note moyenne du transporteur (null si pas de notation) |

#### Métadonnées (meta)

| Champ | Type | Description |
|-------|------|-------------|
| `total` | integer | Nombre total de transporteurs retournés |
| `delivery_address` | object | Informations sur l'adresse de livraison |
| `delivery_address.id` | integer | ID de l'adresse de livraison |
| `delivery_address.full_address` | string | Adresse complète formatée |
| `shop_address` | object\|null | Informations sur l'adresse du shop (null si non fourni) |
| `shop_address.id` | integer\|null | ID de la boutique ou de l'adresse |
| `shop_address.type` | string | Type : "boutique" ou "address" |
| `note` | string\|null | Note informative (null si tout est OK) |

### Calculs effectués

#### Distance
- Utilise la formule Haversine pour calculer la distance entre deux points GPS
- Les coordonnées sont obtenues via géocodage (API Nominatim/OpenStreetMap) si nécessaire
- Distance retournée en kilomètres

#### Prix
- Calculé selon la formule : `max(total_distance × price_per_km, minimum_amount)`
- `price_per_km` : Prix au kilomètre du transporteur (défaut: 500 F)
- `minimum_amount` : Montant minimum (défaut: 2000 F)
- Les paramètres sont configurables via `TransporterPriceSetting`

#### Temps estimé
- Estimation : 2 minutes par kilomètre en moyenne (en ville)
- Pour les longues distances (>50 km) : 1.5 minutes par kilomètre
- Temps retourné en minutes

### Réponses d'erreur

#### 422 Unprocessable Entity - Erreurs de validation

```json
{
  "success": false,
  "message": "Erreurs de validation",
  "errors": {
    "delivery_address_id": [
      "The delivery address id field is required."
    ]
  }
}
```

#### 404 Not Found - Adresse introuvable

```json
{
  "success": false,
  "message": "No query results for model [App\\Models\\Address] 1"
}
```

#### 500 Internal Server Error

```json
{
  "success": false,
  "message": "Erreur lors de la récupération des transporteurs disponibles",
  "error": "Message d'erreur détaillé"
}
```

### Cas d'usage

#### 1. Sélection d'un transporteur pour une commande

```javascript
// Récupérer les transporteurs disponibles pour une commande
const response = await fetch(
  `/api/transporters/available?delivery_address_id=${addressId}&order_id=${orderId}`
);
const { data } = await response.json();

// Afficher les transporteurs triés par distance/prix
data.forEach(transporter => {
  console.log(`${transporter.name}: ${transporter.price} F - ${transporter.estimated_time_formatted}`);
});
```

#### 2. Comparaison des prix

```javascript
// Récupérer tous les transporteurs (limite à 10)
const response = await fetch(
  `/api/transporters/available?delivery_address_id=${addressId}&shop_id=${shopId}&limit=10`
);
const { data } = await response.json();

// Trouver le transporteur le moins cher
const cheapest = data.reduce((prev, current) => 
  prev.price < current.price ? prev : current
);

console.log(`Transporteur le moins cher: ${cheapest.name} - ${cheapest.price} F`);
```

#### 3. Filtrage par disponibilité

```javascript
const response = await fetch(
  `/api/transporters/available?delivery_address_id=${addressId}`
);
const { data } = await response.json();

// Filtrer seulement les transporteurs disponibles
const availableTransporters = data.filter(t => t.available);
```

### Notes techniques

- Les transporteurs sont automatiquement triés par `total_distance` (croissant)
- Les transporteurs sans coordonnées GPS valides sont exclus
- Le géocodage peut prendre quelques secondes pour les nouvelles adresses
- Les coordonnées sont mises en cache pour améliorer les performances
- La limite par défaut est de 5 transporteurs pour optimiser les performances

### Version

- **Version API** : 1.0
- **Dernière mise à jour** : 2026-01-26
