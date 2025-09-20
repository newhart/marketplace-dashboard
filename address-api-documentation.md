# Documentation API - Gestion des Adresses Client

Cette documentation décrit les endpoints API pour la gestion des adresses des clients dans le système marketplace.

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

## 📍 **Gestion des Adresses Client**

### `GET /customer/addresses`

Récupère toutes les adresses de l'utilisateur authentifié.

#### Headers
```
Authorization: Bearer {token}
```

#### Paramètres de requête (optionnels)
| Paramètre | Type | Description |
|-----------|------|-------------|
| type | string | Filtrer par type : `shipping` ou `billing` |

#### Exemple de requête
```
GET /api/customer/addresses?type=shipping
```

#### Réponse de succès (200)
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "user_id": 123,
            "type": "shipping",
            "title": "Domicile",
            "first_name": "Jean",
            "last_name": "Dupont",
            "company": null,
            "address_line_1": "123 Rue de la Paix",
            "address_line_2": "Appartement 4B",
            "city": "Antananarivo",
            "state": "Analamanga",
            "postal_code": "101",
            "country": "Madagascar",
            "phone": "+261 34 12 345 67",
            "is_default": true,
            "created_at": "2025-09-20T10:30:00Z",
            "updated_at": "2025-09-20T10:30:00Z",
            "full_name": "Jean Dupont",
            "full_address": "123 Rue de la Paix, Appartement 4B, Antananarivo, Analamanga 101"
        }
    ]
}
```

---

### `POST /customer/addresses`

Crée une nouvelle adresse pour l'utilisateur authentifié.

#### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

#### Corps de la requête
```json
{
    "type": "shipping",
    "title": "Bureau",
    "first_name": "Jean",
    "last_name": "Dupont",
    "company": "Mon Entreprise",
    "address_line_1": "456 Avenue de l'Indépendance",
    "address_line_2": "Bureau 201",
    "city": "Antananarivo",
    "state": "Analamanga",
    "postal_code": "101",
    "country": "Madagascar",
    "phone": "+261 34 12 345 67",
    "is_default": false
}
```

#### Paramètres
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| type | string | Oui | Type d'adresse : `shipping` ou `billing` |
| title | string | Non | Titre de l'adresse (ex: "Domicile", "Bureau") |
| first_name | string | Oui | Prénom |
| last_name | string | Oui | Nom de famille |
| company | string | Non | Nom de l'entreprise |
| address_line_1 | string | Oui | Première ligne d'adresse |
| address_line_2 | string | Non | Deuxième ligne d'adresse |
| city | string | Oui | Ville |
| state | string | Non | Province/État |
| postal_code | string | Oui | Code postal |
| country | string | Non | Pays (par défaut: Madagascar) |
| phone | string | Non | Numéro de téléphone |
| is_default | boolean | Non | Définir comme adresse par défaut |

#### Réponse de succès (201)
```json
{
    "success": true,
    "message": "Adresse créée avec succès",
    "data": {
        "id": 2,
        "user_id": 123,
        "type": "shipping",
        "title": "Bureau",
        "first_name": "Jean",
        "last_name": "Dupont",
        "company": "Mon Entreprise",
        "address_line_1": "456 Avenue de l'Indépendance",
        "address_line_2": "Bureau 201",
        "city": "Antananarivo",
        "state": "Analamanga",
        "postal_code": "101",
        "country": "Madagascar",
        "phone": "+261 34 12 345 67",
        "is_default": false,
        "created_at": "2025-09-20T11:00:00Z",
        "updated_at": "2025-09-20T11:00:00Z"
    }
}
```

---

### `GET /customer/addresses/{id}`

Récupère une adresse spécifique de l'utilisateur.

#### Headers
```
Authorization: Bearer {token}
```

#### Paramètres d'URL
| Paramètre | Type | Description |
|-----------|------|-------------|
| id | integer | ID de l'adresse |

#### Réponse de succès (200)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 123,
        "type": "shipping",
        "title": "Domicile",
        "first_name": "Jean",
        "last_name": "Dupont",
        "company": null,
        "address_line_1": "123 Rue de la Paix",
        "address_line_2": "Appartement 4B",
        "city": "Antananarivo",
        "state": "Analamanga",
        "postal_code": "101",
        "country": "Madagascar",
        "phone": "+261 34 12 345 67",
        "is_default": true,
        "created_at": "2025-09-20T10:30:00Z",
        "updated_at": "2025-09-20T10:30:00Z"
    }
}
```

---

### `PUT /customer/addresses/{id}`

Met à jour une adresse existante.

#### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

#### Paramètres d'URL
| Paramètre | Type | Description |
|-----------|------|-------------|
| id | integer | ID de l'adresse à modifier |

#### Corps de la requête
```json
{
    "title": "Nouveau Domicile",
    "address_line_1": "789 Nouvelle Rue",
    "city": "Fianarantsoa",
    "postal_code": "301",
    "is_default": true
}
```

#### Réponse de succès (200)
```json
{
    "success": true,
    "message": "Adresse mise à jour avec succès",
    "data": {
        "id": 1,
        "user_id": 123,
        "type": "shipping",
        "title": "Nouveau Domicile",
        "first_name": "Jean",
        "last_name": "Dupont",
        "company": null,
        "address_line_1": "789 Nouvelle Rue",
        "address_line_2": "Appartement 4B",
        "city": "Fianarantsoa",
        "state": "Analamanga",
        "postal_code": "301",
        "country": "Madagascar",
        "phone": "+261 34 12 345 67",
        "is_default": true,
        "created_at": "2025-09-20T10:30:00Z",
        "updated_at": "2025-09-20T11:30:00Z"
    }
}
```

---

### `DELETE /customer/addresses/{id}`

Supprime une adresse.

#### Headers
```
Authorization: Bearer {token}
```

#### Paramètres d'URL
| Paramètre | Type | Description |
|-----------|------|-------------|
| id | integer | ID de l'adresse à supprimer |

#### Réponse de succès (200)
```json
{
    "success": true,
    "message": "Adresse supprimée avec succès"
}
```

---

### `PATCH /customer/addresses/{id}/set-default`

Définit une adresse comme adresse par défaut.

#### Headers
```
Authorization: Bearer {token}
```

#### Paramètres d'URL
| Paramètre | Type | Description |
|-----------|------|-------------|
| id | integer | ID de l'adresse à définir par défaut |

#### Réponse de succès (200)
```json
{
    "success": true,
    "message": "Adresse définie comme adresse par défaut",
    "data": {
        "id": 1,
        "user_id": 123,
        "type": "shipping",
        "title": "Domicile",
        "first_name": "Jean",
        "last_name": "Dupont",
        "company": null,
        "address_line_1": "123 Rue de la Paix",
        "address_line_2": "Appartement 4B",
        "city": "Antananarivo",
        "state": "Analamanga",
        "postal_code": "101",
        "country": "Madagascar",
        "phone": "+261 34 12 345 67",
        "is_default": true,
        "created_at": "2025-09-20T10:30:00Z",
        "updated_at": "2025-09-20T11:45:00Z"
    }
}
```

---

## 📋 **Types d'Adresses**

| Type | Description |
|------|-------------|
| `shipping` | Adresse de livraison |
| `billing` | Adresse de facturation |

---

## 🔧 **Fonctionnalités Spéciales**

### **Adresse par Défaut**
- Chaque utilisateur peut avoir une adresse par défaut par type (`shipping` et `billing`)
- Définir une adresse comme par défaut désactive automatiquement l'ancienne adresse par défaut du même type
- Les adresses par défaut sont retournées en premier dans la liste

### **Attributs Calculés**
- `full_name` : Concaténation de `first_name` + `last_name`
- `full_address` : Adresse complète formatée

---

## ⚠️ **Codes d'Erreur**

| Code | Description |
|------|-------------|
| 200 | Succès |
| 201 | Créé avec succès |
| 401 | Non authentifié |
| 404 | Adresse non trouvée |
| 422 | Erreurs de validation |
| 500 | Erreur serveur interne |

---

## 🧪 **Exemples d'Utilisation**

### JavaScript/Fetch
```javascript
// Récupérer toutes les adresses de livraison
const getShippingAddresses = async () => {
    const response = await fetch('/api/customer/addresses?type=shipping', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    const data = await response.json();
    return data;
};

// Créer une nouvelle adresse
const createAddress = async (addressData) => {
    const response = await fetch('/api/customer/addresses', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(addressData)
    });
    
    const data = await response.json();
    return data;
};

// Définir une adresse par défaut
const setDefaultAddress = async (addressId) => {
    const response = await fetch(`/api/customer/addresses/${addressId}/set-default`, {
        method: 'PATCH',
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
# Récupérer toutes les adresses
curl -X GET http://votre-domaine.com/api/customer/addresses \
  -H "Authorization: Bearer YOUR_TOKEN"

# Créer une nouvelle adresse
curl -X POST http://votre-domaine.com/api/customer/addresses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "type": "shipping",
    "title": "Domicile",
    "first_name": "Jean",
    "last_name": "Dupont",
    "address_line_1": "123 Rue de la Paix",
    "city": "Antananarivo",
    "postal_code": "101",
    "is_default": true
  }'

# Définir une adresse par défaut
curl -X PATCH http://votre-domaine.com/api/customer/addresses/1/set-default \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🔗 **Intégration avec les Commandes**

Les adresses créées via cette API peuvent être utilisées lors de la création de commandes. Il suffit de référencer l'ID de l'adresse dans la requête de commande :

```json
{
    "items": [...],
    "shipping_address_id": 1,
    "billing_address_id": 2,
    "total_amount": 150.75
}
```

Cette API offre une gestion complète des adresses pour améliorer l'expérience utilisateur lors du processus de commande ! 🏠📦
