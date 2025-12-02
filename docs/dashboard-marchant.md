# API Documentation: Tableau de Bord Marchand

Cette documentation décrit les endpoints API disponibles pour le tableau de bord des marchands dans l'application Marketplace.

## Table des matières

1. [Introduction](#introduction)
2. [Authentification](#authentification)
3. [Endpoints du Tableau de Bord](#endpoints-du-tableau-de-bord)
   - [Tableau de bord](#tableau-de-bord)
   - [Gestion des produits](#gestion-des-produits)
4. [Modèles de données](#modèles-de-données)
5. [Codes d'erreur](#codes-derreur)

## Introduction

L'API du tableau de bord marchand permet aux marchands de gérer leurs produits et de visualiser les statistiques de leur boutique. Cette API est conçue pour être utilisée par l'interface utilisateur du tableau de bord marchand.

## Authentification

Toutes les requêtes à l'API doivent inclure un token d'authentification valide dans l'en-tête `Authorization`. Le token peut être obtenu en s'authentifiant via l'API d'authentification.

```
Authorization: Bearer {token}
```

Tous les endpoints du tableau de bord marchand nécessitent que l'utilisateur soit authentifié et ait le rôle 'merchant'.

## Endpoints du Tableau de Bord

### Tableau de bord

#### Récupérer les informations du tableau de bord

```
GET /merchant/dashboard
```

Récupère les informations générales du tableau de bord du marchand, y compris les statistiques et la liste des produits.

**Réponse**

```json
{
  "merchant": {
    "id": 1,
    "user_id": 1,
    "manager_lastname": "Dupont",
    "manager_firstname": "Jean",
    "mobile_phone": "0612345678",
    "landline_phone": "0123456789",
    "business_address": "123 Rue du Commerce",
    "business_city": "Paris",
    "business_postal_code": "75001",
    "business_type": "Épicerie",
    "business_description": "Épicerie fine locale"
  },
  "products": [
    {
      "id": 1,
      "name": "Produit 1",
      "description": "Description du produit 1",
      "price": 10.99,
      "price_promo": null,
      "category_id": 1,
      "user_id": 1,
      "stock": 50,
      "origin": "France",
      "unit": "kg",
      "created_at": "2025-05-29T10:00:00.000000Z",
      "updated_at": "2025-05-29T10:00:00.000000Z",
      "category": {
        "id": 1,
        "name": "Catégorie 1"
      },
      "images": [
        {
          "id": 1,
          "path": "products/image1.jpg",
          "is_main": true
        }
      ]
    }
  ],
  "stats": {
    "total_products": 1,
    "pending_orders": 0,
    "completed_orders": 0,
    "total_revenue": 0
  }
}
```

### Gestion des produits

#### Lister tous les produits

```
GET /merchant/products
```

Récupère la liste paginée des produits du marchand.

**Paramètres de requête**

| Nom | Type | Description |
|-----|------|-------------|
| per_page | integer | Nombre d'éléments par page (défaut: 15, min: 1, max: 50) |
| page | integer | Page courante (défaut: 1) |

**Réponse**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Produit 1",
      "description": "Description du produit 1",
      "price": 10.99,
      "price_promo": null,
      "category_id": 1,
      "user_id": 1,
      "stock": 50,
      "origin": "France",
      "unit": "kg",
      "created_at": "2025-05-29T10:00:00.000000Z",
      "updated_at": "2025-05-29T10:00:00.000000Z",
      "category": {
        "id": 1,
        "name": "Catégorie 1"
      },
      "images": [
        {
          "id": 1,
          "path": "products/image1.jpg",
          "is_main": true
        }
      ]
    }
  ],
  "pagination": {
    "total": 42,
    "count": 15,
    "per_page": 15,
    "current_page": 1,
    "total_pages": 3
  }
}
```

#### Afficher un produit spécifique

```
GET /merchant/products/{id}
```

Récupère les détails d'un produit spécifique.

**Paramètres**

| Nom | Type | Description |
|-----|------|-------------|
| id | integer | ID du produit à afficher |

**Réponse**

```json
{
  "id": 1,
  "name": "Produit 1",
  "description": "Description du produit 1",
  "price": 10.99,
  "price_promo": null,
  "category_id": 1,
  "user_id": 1,
  "stock": 50,
  "origin": "France",
  "unit": "kg",
  "created_at": "2025-05-29T10:00:00.000000Z",
  "updated_at": "2025-05-29T10:00:00.000000Z",
  "category": {
    "id": 1,
    "name": "Catégorie 1"
  },
  "images": [
    {
      "id": 1,
      "path": "products/image1.jpg",
      "is_main": true
    }
  ]
}
```

#### Formulaire de création de produit

```
GET /merchant/products/create
```

Récupère les données nécessaires pour afficher le formulaire de création de produit.

**Réponse**

```json
{
  "categories": [
    {
      "id": 1,
      "name": "Catégorie 1"
    },
    {
      "id": 2,
      "name": "Catégorie 2"
    }
  ]
}
```

#### Créer un produit

```
POST /merchant/products
```

Crée un nouveau produit pour le marchand.

**Paramètres (JSON)**

```json
{
  "name": "Nom du produit", // obligatoire
  "description": "Description détaillée du produit", // optionnel
  "price": 10.99, // obligatoire
  "price_promo": 8.99, // optionnel
  "category_id": 1, // obligatoire
  "origin": "France", // optionnel
  "unit": "kg", // obligatoire
  "stock": 50 // optionnel, défaut: 0
}
```

**Note**: L'image du produit (`photo`) doit être envoyée comme un fichier dans une requête multipart/form-data (max: 2048KB).

**Réponse**

```json
{
  "message": "Produit créé avec succès",
  "product": {
    "id": 1,
    "name": "Produit 1",
    "description": "Description du produit 1",
    "price": 10.99,
    "price_promo": null,
    "category_id": 1,
    "user_id": 1,
    "stock": 50,
    "origin": "France",
    "unit": "kg",
    "created_at": "2025-05-29T10:00:00.000000Z",
    "updated_at": "2025-05-29T10:00:00.000000Z",
    "category": {
      "id": 1,
      "name": "Catégorie 1"
    },
    "images": [
      {
        "id": 1,
        "path": "products/image1.jpg",
        "is_main": true
      }
    ]
  }
}
```

#### Formulaire d'édition de produit

```
GET /merchant/products/{id}/edit
```

Récupère les données nécessaires pour afficher le formulaire d'édition d'un produit.

**Paramètres**

| Nom | Type | Description |
|-----|------|-------------|
| id | integer | ID du produit à éditer |

**Réponse**

```json
{
  "product": {
    "id": 1,
    "name": "Produit 1",
    "description": "Description du produit 1",
    "price": 10.99,
    "price_promo": null,
    "category_id": 1,
    "user_id": 1,
    "stock": 50,
    "origin": "France",
    "unit": "kg",
    "created_at": "2025-05-29T10:00:00.000000Z",
    "updated_at": "2025-05-29T10:00:00.000000Z",
    "category": {
      "id": 1,
      "name": "Catégorie 1"
    },
    "images": [
      {
        "id": 1,
        "path": "products/image1.jpg",
        "is_main": true
      }
    ]
  },
  "categories": [
    {
      "id": 1,
      "name": "Catégorie 1"
    },
    {
      "id": 2,
      "name": "Catégorie 2"
    }
  ]
}
```

#### Mettre à jour un produit

```
PUT /merchant/products/{id}
```

Met à jour un produit existant.

**Paramètres**

| Nom | Type | Description |
|-----|------|-------------|
| id | integer | ID du produit à mettre à jour |
| name | string | Nom du produit (obligatoire) |
| description | string | Description du produit (optionnel) |
| price | numeric | Prix du produit (obligatoire) |
| price_promo | numeric | Prix promotionnel (optionnel) |
| category_id | integer | ID de la catégorie (obligatoire) |
| origin | string | Origine du produit (optionnel) |
| unit | string | Unité de vente (obligatoire) |
| stock | integer | Quantité en stock (optionnel, défaut: 0) |
| photo | file | Image du produit (optionnel, max: 2048KB) |

**Réponse**

```json
{
  "message": "Produit mis à jour avec succès",
  "product": {
    "id": 1,
    "name": "Produit 1 mis à jour",
    "description": "Description mise à jour",
    "price": 12.99,
    "price_promo": 9.99,
    "category_id": 2,
    "user_id": 1,
    "stock": 75,
    "origin": "Espagne",
    "unit": "kg",
    "created_at": "2025-05-29T10:00:00.000000Z",
    "updated_at": "2025-05-30T11:00:00.000000Z",
    "category": {
      "id": 2,
      "name": "Catégorie 2"
    },
    "images": [
      {
        "id": 2,
        "path": "products/image2.jpg",
        "is_main": true
      }
    ]
  }
}
```

#### Supprimer un produit

```
DELETE /merchant/products/{id}
```

Supprime un produit existant.

**Paramètres**

| Nom | Type | Description |
|-----|------|-------------|
| id | integer | ID du produit à supprimer |

**Réponse**

```json
{
  "message": "Produit supprimé avec succès"
}
```

## Modèles de données

### Produit

| Champ | Type | Description |
|-------|------|-------------|
| id | integer | Identifiant unique du produit |
| name | string | Nom du produit |
| description | string | Description du produit |
| price | decimal | Prix du produit |
| price_promo | decimal | Prix promotionnel (optionnel) |
| category_id | integer | ID de la catégorie |
| user_id | integer | ID du marchand (utilisateur) |
| stock | integer | Quantité en stock |
| origin | string | Origine du produit |
| unit | string | Unité de vente (kg, pièce, etc.) |
| created_at | datetime | Date de création |
| updated_at | datetime | Date de dernière modification |

### Catégorie

| Champ | Type | Description |
|-------|------|-------------|
| id | integer | Identifiant unique de la catégorie |
| name | string | Nom de la catégorie |
| created_by | integer | ID de l'utilisateur qui a créé la catégorie |
| user_id | integer | ID de l'utilisateur propriétaire |
| parent_id | integer | ID de la catégorie parente (optionnel) |
| created_at | datetime | Date de création |
| updated_at | datetime | Date de dernière modification |

### Image

| Champ | Type | Description |
|-------|------|-------------|
| id | integer | Identifiant unique de l'image |
| path | string | Chemin d'accès à l'image |
| is_main | boolean | Indique si c'est l'image principale |
| imageable_id | integer | ID de l'objet associé (produit) |
| imageable_type | string | Type de l'objet associé |
| created_at | datetime | Date de création |
| updated_at | datetime | Date de dernière modification |

## Codes d'erreur

| Code | Description |
|------|-------------|
| 401 | Non autorisé - Authentification requise |
| 403 | Interdit - Vous n'avez pas les permissions nécessaires |
| 404 | Non trouvé - La ressource demandée n'existe pas |
| 422 | Entité non traitable - Erreurs de validation |
| 500 | Erreur serveur - Une erreur est survenue côté serveur |

## Implémentation du Service

L'API utilise le service `MerchantProductService` pour gérer toutes les opérations liées aux produits. Ce service fournit les fonctionnalités suivantes:

- Récupération de tous les produits du marchand
- Récupération des catégories disponibles
- Validation des données de produit
- Création de nouveaux produits
- Mise à jour des produits existants
- Suppression de produits
- Gestion des images de produit
- Récupération des statistiques du tableau de bord

Cette architecture en couches permet une meilleure séparation des préoccupations et facilite la maintenance et les tests.