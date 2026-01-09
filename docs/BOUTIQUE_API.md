# API Boutique Documentation

## Overview

L'API Boutique permet aux commerçants de gérer leurs boutiques/magasins et aux clients de consulter les boutiques actives.

**Endpoints Commerçants (Authentifiés):** `/api/merchant/boutiques`
**Endpoints Publics:** `/api/boutiques`

**Authentication:** Sanctum Token (Bearer Token) pour les endpoints commerçants uniquement.

---

## Endpoints

### Public Endpoints

#### 1. List All Active Boutiques (Public)

Récupère la liste de toutes les boutiques actives. Aucune authentification requise.

```
GET /api/boutiques
```

##### Query Parameters

| Paramètre  | Type    | Requis | Description                                        |
| ---------- | ------- | ------ | -------------------------------------------------- |
| `per_page` | integer | Non    | Nombre de résultats par page (défaut: 15, max: 50) |
| `search`   | string  | Non    | Rechercher par nom, ville ou description           |
| `category` | string  | Non    | Filtrer par catégorie                              |
| `page`     | integer | Non    | Numéro de page (défaut: 1)                         |

##### Request Headers

```
Accept: application/json
```

##### Example Request

```bash
curl -X GET "http://localhost/api/boutiques?per_page=10&search=Boulangerie&category=Épicerie" \
  -H "Accept: application/json"
```

##### Success Response (200 OK)

```json
{
    "data": [
        {
            "id": 1,
            "merchant_id": 5,
            "name": "la ora na Haritiana",
            "description": "L'épicerie du centre",
            "category": "Épicerie",
            "photo": "http://localhost/storage/boutiques/photo_123.jpg",
            "latitude": -18.8573274,
            "longitude": 47.5560462,
            "city": "Tananarive",
            "postal_code": "101",
            "postal_box": "BP 123",
            "opening_date": "2024-01-15",
            "closing_date": null,
            "opening_hours": {
                "monday": "08:00-18:00",
                "tuesday": "08:00-18:00",
                "wednesday": "08:00-18:00",
                "thursday": "08:00-18:00",
                "friday": "08:00-18:00",
                "saturday": "08:00-14:00",
                "sunday": null
            },
            "is_active": true,
            "created_at": "2025-12-19T14:30:00Z",
            "updated_at": "2025-12-19T14:30:00Z",
            "products": [
                {
                    "id": 101,
                    "name": "Pain complet",
                    "short_description": "Pain bio",
                    "unity": "pièce",
                    "description": "Délicieux pain complet...",
                    "image": "http://localhost/storage/products/pain.jpg",
                    "price": "1.50",
                    "rating": 4.5,
                    "category": { "id": 1, "name": "Boulangerie" },
                    "origin": "France",
                    "stock": 50
                }
            ]
        }
    ],
    "pagination": {
        "total": 15,
        "count": 10,
        "per_page": 10,
        "current_page": 1,
        "total_pages": 2
    }
}
```

> [!NOTE]
> Cet endpoint retourne uniquement les boutiques actives (`is_active = true`).

---

#### 2. Get Boutique Details (Public)

Récupère les détails complets d'une boutique active. Aucune authentification requise.

```
GET /api/boutiques/{id}
```

##### Path Parameters

| Paramètre | Type    | Description       |
| --------- | ------- | ----------------- |
| `id`      | integer | ID de la boutique |

##### Request Headers

```
Accept: application/json
```

##### Example Request

```bash
curl -X GET "http://localhost/api/boutiques/1" \
  -H "Accept: application/json"
```

##### Success Response (200 OK)

```json
{
    "id": 1,
    "merchant_id": 5,
    "name": "la ora na Haritiana",
    "description": "L'épicerie du centre",
    "category": "Épicerie",
    "photo": "http://localhost/storage/boutiques/photo_123.jpg",
    "latitude": -18.8573274,
    "longitude": 47.5560462,
    "city": "Tananarive",
    "postal_code": "101",
    "postal_box": "BP 123",
    "opening_date": "2024-01-15",
    "closing_date": null,
    "opening_hours": {
        "monday": "08:00-18:00",
        "tuesday": "08:00-18:00",
        "wednesday": "08:00-18:00",
        "thursday": "08:00-18:00",
        "friday": "08:00-18:00",
        "saturday": "08:00-14:00",
        "sunday": null
    },
    "is_active": true,
    "created_at": "2025-12-19T14:30:00Z",
    "updated_at": "2025-12-19T14:30:00Z",
    "products": [
        {
            "id": 101,
            "name": "Pain complet",
            "short_description": "Pain bio",
            "unity": "pièce",
            "description": "Délicieux pain complet...",
            "image": "http://localhost/storage/products/pain.jpg",
            "price": "1.50",
            "rating": 4.5,
            "category": { "id": 1, "name": "Boulangerie" },
            "origin": "France",
            "stock": 50
        }
    ]
}
```

##### Error Response (404 Not Found)

```json
{
    "message": "Boutique non trouvée ou inactive"
}
```

> [!NOTE]
> Cet endpoint retourne uniquement les boutiques actives. Si la boutique existe mais est inactive, une erreur 404 sera retournée.

---

### Merchant Endpoints (Authentification Requise)

#### 3. List Boutiques

Récupère la liste de toutes les boutiques du commerçant connecté.

```
GET /api/merchant/boutiques
```

#### Query Parameters

| Paramètre   | Type    | Requis | Description                                        |
| ----------- | ------- | ------ | -------------------------------------------------- |
| `per_page`  | integer | Non    | Nombre de résultats par page (défaut: 15, max: 50) |
| `search`    | string  | Non    | Rechercher par nom ou ville                        |
| `category`  | string  | Non    | Filtrer par catégorie                              |
| `is_active` | boolean | Non    | Filtrer par statut actif/inactif                   |
| `page`      | integer | Non    | Numéro de page (défaut: 1)                         |

#### Request Headers

```
Authorization: Bearer {token}
Accept: application/json
```

#### Example Request

```bash
curl -X GET "http://localhost/api/merchant/boutiques?per_page=10&search=Paris&is_active=true" \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json"
```

#### Success Response (200 OK)

```json
{
    "data": [
        {
            "id": 1,
            "merchant_id": 5,
            "name": "la ora na Haritiana",
            "description": "L'épicerie du centre",
            "category": "Épicerie",
            "photo": "http://localhost/storage/boutiques/photo_123.jpg",
            "latitude": -18.8573274,
            "longitude": 47.5560462,
            "city": "Tananarive",
            "postal_code": "101",
            "postal_box": "BP 123",
            "opening_date": "2024-01-15",
            "closing_date": null,
            "opening_hours": {
                "monday": "08:00-18:00",
                "tuesday": "08:00-18:00",
                "wednesday": "08:00-18:00",
                "thursday": "08:00-18:00",
                "friday": "08:00-18:00",
                "saturday": "08:00-14:00",
                "sunday": null
            },
            "is_active": true,
            "created_at": "2025-12-19T14:30:00Z",
            "updated_at": "2025-12-19T14:30:00Z",
            "products": [
                {
                    "id": 101,
                    "name": "Pain complet",
                    "short_description": "Pain bio",
                    "unity": "pièce",
                    "description": "Délicieux pain complet...",
                    "image": "http://localhost/storage/products/pain.jpg",
                    "price": "1.50",
                    "rating": 4.5,
                    "category": { "id": 1, "name": "Boulangerie" },
                    "origin": "France",
                    "stock": 50
                }
            ]
        },
        {
            "id": 2,
            "merchant_id": 5,
            "name": "Boutique Centre Ville",
            "description": "Boutique principale",
            "category": "Magasin général",
            "photo": "http://localhost/storage/boutiques/photo_456.jpg",
            "latitude": -18.8575,
            "longitude": 47.557,
            "city": "Tananarive",
            "postal_code": "101",
            "postal_box": "BP 456",
            "opening_date": "2023-06-01",
            "closing_date": null,
            "opening_hours": {
                "monday": "07:00-19:00",
                "tuesday": "07:00-19:00",
                "wednesday": "07:00-19:00",
                "thursday": "07:00-19:00",
                "friday": "07:00-19:00",
                "saturday": "07:00-15:00",
                "sunday": "09:00-13:00"
            },
            "is_active": true,
            "created_at": "2025-12-18T10:15:00Z",
            "updated_at": "2025-12-18T10:15:00Z",
            "products": []
        }
    ],
    "pagination": {
        "total": 2,
        "count": 2,
        "per_page": 10,
        "current_page": 1,
        "total_pages": 1
    }
}
```

#### Error Response

```json
{
    "message": "Unauthorized",
    "status": 401
}
```

---

#### 4. Create Boutique

Crée une nouvelle boutique pour le commerçant connecté.

```
POST /api/merchant/boutiques
```

#### Request Headers

```
Authorization: Bearer {token}
Accept: application/json
Content-Type: multipart/form-data
```

#### Request Body

| Champ           | Type         | Requis | Contraintes                                |
| --------------- | ------------ | ------ | ------------------------------------------ |
| `name`          | string       | Oui    | Max 255 caractères                         |
| `description`   | text         | Non    | -                                          |
| `category`      | string       | Non    | Max 255 caractères                         |
| `photo`         | file/string  | Non    | Image file (max 2MB) ou Base64 string      |
| `latitude`      | float        | Non    | Entre -90 et 90                            |
| `longitude`     | float        | Non    | Entre -180 et 180                          |
| `city`          | string       | Non    | Max 255 caractères                         |
| `postal_code`   | string       | Non    | Max 20 caractères                          |
| `postal_box`    | string       | Non    | Max 255 caractères                         |
| `opening_date`  | date         | Non    | Format: YYYY-MM-DD                         |
| `closing_date`  | date         | Non    | Doit être >= opening_date                  |
| `opening_hours` | array/string | Non    | JSON Object ou JSON string (pour FormData) |
| `is_active`     | boolean      | Non    | Défaut: true                               |

#### Example Request

```bash
curl -X POST "http://localhost/api/merchant/boutiques" \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json" \
  -F "name=la ora na Haritiana" \
  -F "description=L'épicerie du centre" \
  -F "category=Épicerie" \
  -F "photo=@/path/to/photo.jpg" \
  -F "latitude=-18.8573274" \
  -F "longitude=47.5560462" \
  -F "city=Tananarive" \
  -F "postal_code=101" \
  -F "postal_box=BP 123" \
  -F "opening_date=2025-01-15" \
  -F "is_active=true"
```

#### Success Response (201 Created)

```json
{
    "message": "Boutique créée avec succès",
    "data": {
        "id": 3,
        "merchant_id": 5,
        "name": "la ora na Haritiana",
        "description": "L'épicerie du centre",
        "category": "Épicerie",
        "photo": "http://localhost/storage/boutiques/boutiques/photo_789.jpg",
        "latitude": -18.8573274,
        "longitude": 47.5560462,
        "city": "Tananarive",
        "postal_code": "101",
        "postal_box": "BP 123",
        "opening_date": "2025-01-15",
        "closing_date": null,
        "opening_hours": null,
        "is_active": true,
        "created_at": "2025-12-19T15:45:30Z",
        "updated_at": "2025-12-19T15:45:30Z",
        "products": [
            {
                "id": 102,
                "name": "Croissant",
                "short_description": "Pur beurre",
                "unity": "pièce",
                "description": "Croissant au beurre...",
                "image": "http://localhost/storage/products/croissant.jpg",
                "price": "1.00",
                "rating": 4.8,
                "category": { "id": 1, "name": "Boulangerie" },
                "origin": "France",
                "stock": 100
            }
        ]
    }
}
```

#### Error Response (422 Unprocessable Entity)

```json
{
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."],
        "latitude": ["The latitude must be between -90 and 90."],
        "photo": ["The photo must be an image."]
    }
}
```

#### Error Response (403 Forbidden)

```json
{
    "message": "Vous devez avoir un compte commerçant"
}
```

---

#### 5. Get Boutique Details (Merchant)

Récupère les détails d'une boutique spécifique.

```
GET /api/merchant/boutiques/{id}
```

#### Path Parameters

| Paramètre | Type    | Description       |
| --------- | ------- | ----------------- |
| `id`      | integer | ID de la boutique |

#### Request Headers

```
Authorization: Bearer {token}
Accept: application/json
```

#### Example Request

```bash
curl -X GET "http://localhost/api/merchant/boutiques/1" \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json"
```

#### Success Response (200 OK)

```json
{
    "id": 1,
    "merchant_id": 5,
    "name": "la ora na Haritiana",
    "description": "L'épicerie du centre",
    "category": "Épicerie",
    "photo": "http://localhost/storage/boutiques/photo_123.jpg",
    "latitude": -18.8573274,
    "longitude": 47.5560462,
    "city": "Tananarive",
    "postal_code": "101",
    "postal_box": "BP 123",
    "opening_date": "2024-01-15",
    "closing_date": null,
    "opening_hours": {
        "monday": "08:00-18:00",
        "tuesday": "08:00-18:00",
        "wednesday": "08:00-18:00",
        "thursday": "08:00-18:00",
        "friday": "08:00-18:00",
        "saturday": "08:00-14:00",
        "sunday": null
    },
    "is_active": true,
    "created_at": "2025-12-19T14:30:00Z",
    "updated_at": "2025-12-19T14:30:00Z",
    "products": [
        {
            "id": 101,
            "name": "Pain complet",
            "short_description": "Pain bio",
            "unity": "pièce",
            "description": "Délicieux pain complet...",
            "image": "http://localhost/storage/products/pain.jpg",
            "price": "1.50",
            "rating": 4.5,
            "category": { "id": 1, "name": "Boulangerie" },
            "origin": "France",
            "stock": 50
        }
    ]
}
```

#### Error Response (404 Not Found)

```json
{
    "message": "No query results for model [App\\Models\\Boutique]."
}
```

---

#### 6. Update Boutique

Met à jour une boutique existante.

```
PUT /api/merchant/boutiques/{id}
```

#### Path Parameters

| Paramètre | Type    | Description       |
| --------- | ------- | ----------------- |
| `id`      | integer | ID de la boutique |

#### Request Headers

```
Authorization: Bearer {token}
Accept: application/json
Content-Type: multipart/form-data
```

#### Request Body

Tous les champs sont identiques à la création (PUT):

| Champ           | Type         | Requis | Contraintes                                |
| --------------- | ------------ | ------ | ------------------------------------------ |
| `name`          | string       | Oui    | Max 255 caractères                         |
| `description`   | text         | Non    | -                                          |
| `category`      | string       | Non    | Max 255 caractères                         |
| `photo`         | file/string  | Non    | Image file (max 2MB) ou Base64 string      |
| `latitude`      | float        | Non    | Entre -90 et 90                            |
| `longitude`     | float        | Non    | Entre -180 et 180                          |
| `city`          | string       | Non    | Max 255 caractères                         |
| `postal_code`   | string       | Non    | Max 20 caractères                          |
| `postal_box`    | string       | Non    | Max 255 caractères                         |
| `opening_date`  | date         | Non    | Format: YYYY-MM-DD                         |
| `closing_date`  | date         | Non    | Doit être >= opening_date                  |
| `opening_hours` | array/string | Non    | JSON Object ou JSON string (pour FormData) |
| `is_active`     | boolean      | Non    | -                                          |

#### Example Request

```bash
curl -X PUT "http://localhost/api/merchant/boutiques/1" \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json" \
  -F "name=la ora na Haritiana - Mise à jour" \
  -F "description=L'épicerie du centre - Nouvelle description" \
  -F "category=Épicerie générale" \
  -F "city=Antananarivo" \
  -F "is_active=true"
```

#### Success Response (200 OK)

```json
{
    "message": "Boutique mise à jour avec succès",
    "data": {
        "id": 1,
        "merchant_id": 5,
        "name": "la ora na Haritiana - Mise à jour",
        "description": "L'épicerie du centre - Nouvelle description",
        "category": "Épicerie générale",
        "photo": "http://localhost/storage/boutiques/photo_123.jpg",
        "latitude": -18.8573274,
        "longitude": 47.5560462,
        "city": "Antananarivo",
        "postal_code": "101",
        "postal_box": "BP 123",
        "opening_date": "2024-01-15",
        "closing_date": null,
        "opening_hours": {
            "monday": "08:00-18:00",
            "tuesday": "08:00-18:00",
            "wednesday": "08:00-18:00",
            "thursday": "08:00-18:00",
            "friday": "08:00-18:00",
            "saturday": "08:00-14:00",
            "sunday": null
        },
        "is_active": true,
        "created_at": "2025-12-19T14:30:00Z",
        "updated_at": "2025-12-19T16:00:00Z",
        "products": [
            {
                "id": 101,
                "name": "Pain complet",
                "short_description": "Pain bio",
                "unity": "pièce",
                "description": "Délicieux pain complet...",
                "image": "http://localhost/storage/products/pain.jpg",
                "price": "1.50",
                "rating": 4.5,
                "category": { "id": 1, "name": "Boulangerie" },
                "origin": "France",
                "stock": 50
            }
        ]
    }
}
```

#### Error Response (422 Unprocessable Entity)

```json
{
    "message": "Validation failed",
    "errors": {
        "latitude": ["The latitude must be between -90 and 90."]
    }
}
```

---

#### 7. Delete Boutique

Supprime une boutique et ses photos associées.

```
DELETE /api/merchant/boutiques/{id}
```

#### Path Parameters

| Paramètre | Type    | Description       |
| --------- | ------- | ----------------- |
| `id`      | integer | ID de la boutique |

#### Request Headers

```
Authorization: Bearer {token}
Accept: application/json
```

#### Example Request

```bash
curl -X DELETE "http://localhost/api/merchant/boutiques/1" \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json"
```

#### Success Response (200 OK)

```json
{
    "message": "Boutique supprimée avec succès"
}
```

#### Error Response (404 Not Found)

```json
{
    "message": "No query results for model [App\\Models\\Boutique]."
}
```

---

## Data Types Reference

### Boutique Object

```json
{
    "id": "integer",
    "merchant_id": "integer",
    "name": "string",
    "description": "string|null",
    "category": "string|null",
    "photo": "string|null (URL)",
    "latitude": "float|null",
    "longitude": "float|null",
    "city": "string|null",
    "postal_code": "string|null",
    "postal_box": "string|null",
    "opening_date": "date|null (YYYY-MM-DD)",
    "closing_date": "date|null (YYYY-MM-DD)",
    "opening_hours": "object|null",
    "is_active": "boolean",
    "created_at": "datetime (ISO 8601)",
    "updated_at": "datetime (ISO 8601)",
    "products": "array (List of ProductResource)"
}
```

### Opening Hours Format

```json
{
    "monday": "08:00-18:00",
    "tuesday": "08:00-18:00",
    "wednesday": "08:00-18:00",
    "thursday": "08:00-18:00",
    "friday": "08:00-18:00",
    "saturday": "08:00-14:00",
    "sunday": null
}
```

---

## Error Codes

| Code | Message               | Description                 |
| ---- | --------------------- | --------------------------- |
| 200  | OK                    | Requête réussie             |
| 201  | Created               | Ressource créée avec succès |
| 401  | Unauthorized          | Token manquant ou invalide  |
| 403  | Forbidden             | Pas de compte commerçant    |
| 404  | Not Found             | Boutique non trouvée        |
| 422  | Unprocessable Entity  | Erreur de validation        |
| 500  | Internal Server Error | Erreur serveur              |

---

## Authentication

Les endpoints commerçants (sous `/api/merchant/boutiques`) requièrent une authentification via Sanctum. Incluez le token dans l'en-tête `Authorization`:

```
Authorization: Bearer {your_token}
```

Les endpoints publics (sous `/api/boutiques`) ne requièrent **aucune authentification**.

Pour obtenir un token, utilisez l'endpoint de connexion:

```bash
POST /api/login
Content-Type: application/json

{
  "email": "merchant@example.com",
  "password": "password"
}
```

---

## Validation Rules

### Name (name)

-   Required
-   String
-   Maximum 255 characters

### Description (description)

-   Optional
-   String
-   No length limit

### Category (category)

-   Optional
-   String
-   Maximum 255 characters

### Photo (photo)

-   Optional
-   Image file or Base64 String
-   Maximum 2MB (for file)
-   Supported formats: jpeg, png, jpg, gif, webp, or Base64 data URI

### Latitude (latitude)

-   Optional
-   Numeric
-   Between -90 and 90

### Longitude (longitude)

-   Optional
-   Numeric
-   Between -180 and 180

### City (city)

-   Optional
-   String
-   Maximum 255 characters

### Postal Code (postal_code)

-   Optional
-   String
-   Maximum 20 characters

### Postal Box (postal_box)

-   Optional
-   String
-   Maximum 255 characters

### Opening Date (opening_date)

-   Optional
-   Date format: YYYY-MM-DD

### Closing Date (closing_date)

-   Optional
-   Date format: YYYY-MM-DD
-   Must be after or equal to opening_date

### Opening Hours (opening_hours)

-   Optional
-   JSON array with day keys
-   Or JSON string representing the array (automatically decoded)
-   Each value should be a time range string or null

### Is Active (is_active)

-   Optional
-   Boolean
-   Default: true

---

## Examples

### Create a complete boutique with all fields

```bash
curl -X POST "http://localhost/api/merchant/boutiques" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Accept: application/json" \
  -F "name=Boutique Complète" \
  -F "description=Une boutique avec tous les détails" \
  -F "category=Magasin général" \
  -F "photo=@/path/to/photo.jpg" \
  -F "latitude=-18.8573274" \
  -F "longitude=47.5560462" \
  -F "city=Antananarivo" \
  -F "postal_code=101" \
  -F "postal_box=BP 123" \
  -F "opening_date=2025-01-15" \
  -F "opening_hours={\"monday\":\"08:00-18:00\",\"tuesday\":\"08:00-18:00\",\"wednesday\":\"08:00-18:00\",\"thursday\":\"08:00-18:00\",\"friday\":\"08:00-18:00\",\"saturday\":\"08:00-14:00\",\"sunday\":null}" \
  -F "is_active=true"
```

### Search boutiques by name

```bash
curl -X GET "http://localhost/api/merchant/boutiques?search=Haritiana&per_page=5" \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json"
```

### Update only specific fields

```bash
curl -X PUT "http://localhost/api/merchant/boutiques/1" \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json" \
  -F "name=Nouveau nom" \
  -F "is_active=false"
```

---

## Rate Limiting

À ce stade, il n'y a pas de limite de débit appliquée. Veuillez vous engager à utiliser l'API de manière responsable.

---

## Support

Pour toute question ou problème, veuillez contacter l'équipe de support.
