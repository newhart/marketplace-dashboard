# Documentation API Merchant

## Base URL
```
http://votre-domaine.com/api
```

**Note importante** : Tous les endpoints listés ci-dessous sont relatifs à `/api`. Par exemple, `POST /merchant/register` correspond à l'URL complète `http://votre-domaine.com/api/merchant/register`.

## Table des matières
1. [Inscription Marchand](#1-inscription-marchand)
2. [Statut du compte marchand](#2-statut-du-compte-marchand)
3. [Dashboard Marchand](#3-dashboard-marchand)
4. [Liste des produits du marchand](#4-liste-des-produits-du-marchand)
5. [Créer un produit](#5-créer-un-produit)
6. [Afficher un produit](#6-afficher-un-produit)
7. [Modifier un produit](#7-modifier-un-produit)
8. [Supprimer un produit](#8-supprimer-un-produit)
9. [Liste des marchands en attente (Admin)](#9-liste-des-marchands-en-attente-admin)
10. [Approuver un marchand (Admin)](#10-approuver-un-marchand-admin)
11. [Rejeter un marchand (Admin)](#11-rejeter-un-marchand-admin)

---

## 1. Inscription Marchand

### Endpoint
```
POST /merchant/register
```

### Description
Permet à un nouveau marchand de créer un compte. Le compte nécessite une vérification par email et une approbation admin.

### Headers
```
Content-Type: application/json
```

### Authentification
Non requise (accessible aux visiteurs)

### Payload (Request Body)
```json
{
  "email": "marchand@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "manager_lastname": "Dupont",
  "manager_firstname": "Jean",
  "mobile_phone": "+33612345678",
  "landline_phone": "+33123456789",
  "business_address": "123 Rue du Commerce",
  "business_city": "Paris",
  "business_postal_code": "75001",
  "business_type": "Épicerie",
  "business_description": "Vente de produits bio et locaux"
}
```

### Paramètres requis
- `email` (string, requis) : Email du marchand (doit être unique)
- `password` (string, requis) : Mot de passe (minimum 8 caractères)
- `password_confirmation` (string, requis) : Confirmation du mot de passe (doit correspondre)
- `manager_lastname` (string, requis) : Nom du gérant (max 255 caractères)
- `manager_firstname` (string, requis) : Prénom du gérant (max 255 caractères)
- `mobile_phone` (string, requis) : Téléphone mobile (max 20 caractères)
- `business_address` (string, requis) : Adresse du commerce (max 255 caractères)
- `business_city` (string, requis) : Ville du commerce (max 100 caractères)
- `business_postal_code` (string, requis) : Code postal (max 20 caractères)

### Paramètres optionnels
- `landline_phone` (string, optionnel) : Téléphone fixe (max 20 caractères)
- `business_type` (string, optionnel) : Type de commerce (max 100 caractères)
- `business_description` (string, optionnel) : Description de l'activité (max 1000 caractères)

### Réponse réussie (201)
```json
{
  "message": "Merchant account created successfully. Please verify your email address.",
  "user": {
    "id": 15,
    "name": "Jean Dupont",
    "email": "marchand@example.com",
    "type": "merchant",
    "is_approved": false,
    "email_verified_at": null,
    "created_at": "2025-11-15T12:00:00.000000Z",
    "updated_at": "2025-11-15T12:00:00.000000Z"
  },
  "merchant": {
    "id": 10,
    "user_id": 15,
    "manager_lastname": "Dupont",
    "manager_firstname": "Jean",
    "mobile_phone": "+33612345678",
    "landline_phone": "+33123456789",
    "business_address": "123 Rue du Commerce",
    "business_city": "Paris",
    "business_postal_code": "75001",
    "business_type": "Épicerie",
    "business_description": "Vente de produits bio et locaux",
    "approval_status": "pending",
    "rejection_reason": null,
    "created_at": "2025-11-15T12:00:00.000000Z",
    "updated_at": "2025-11-15T12:00:00.000000Z"
  },
  "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ123456"
}
```

### Erreurs possibles

#### 422 - Validation échouée
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["L'email a déjà été pris."],
    "password": ["Le mot de passe doit contenir au moins 8 caractères."],
    "password_confirmation": ["La confirmation du mot de passe ne correspond pas."],
    "manager_lastname": ["Le champ nom du gérant est requis."],
    "mobile_phone": ["Le champ téléphone mobile est requis."]
  }
}
```

---

## 2. Statut du compte marchand

### Endpoint
```
GET /merchant/status
```

### Description
Récupère le statut d'approbation du compte marchand authentifié.

### Headers
```
Authorization: Bearer {token}
```

### Authentification
Requise (Sanctum token) + Email vérifié

### Payload
Aucun payload requis.

### Réponse réussie (200)
```json
{
  "approval_status": "pending",
  "email_verified": true,
  "rejection_reason": null
}
```

**Statuts possibles** :
- `pending` : En attente d'approbation
- `approved` : Approuvé
- `rejected` : Rejeté

### Exemple avec rejet
```json
{
  "approval_status": "rejected",
  "email_verified": true,
  "rejection_reason": "Les documents fournis sont incomplets."
}
```

### Erreurs possibles

#### 403 - Pas un compte marchand
```json
{
  "message": "Not a merchant account"
}
```

#### 404 - Profil marchand introuvable
```json
{
  "message": "Merchant profile not found"
}
```

---

## 3. Dashboard Marchand

### Endpoint
```
GET /merchant/dashboard
```

### Description
Récupère les informations du dashboard du marchand avec ses produits et statistiques.

### Headers
```
Authorization: Bearer {token}
```

### Authentification
Requise + Rôle "merchant" + Email vérifié

### Payload
Aucun payload requis.

### Réponse réussie (200)
```json
{
  "merchant": {
    "id": 10,
    "user_id": 15,
    "manager_lastname": "Dupont",
    "manager_firstname": "Jean",
    "mobile_phone": "+33612345678",
    "landline_phone": "+33123456789",
    "business_address": "123 Rue du Commerce",
    "business_city": "Paris",
    "business_postal_code": "75001",
    "business_type": "Épicerie",
    "business_description": "Vente de produits bio et locaux",
    "approval_status": "approved"
  },
  "products": [
    {
      "id": 1,
      "name": "Tomates bio",
      "description": "Tomates fraîches du jardin",
      "price": 3.50,
      "price_promo": null,
      "stock": 50,
      "unit": "kg",
      "origin": "France",
      "category_id": 1,
      "user_id": 15,
      "created_at": "2025-11-15T10:00:00.000000Z"
    }
  ],
  "stats": {
    "total_products": 12,
    "total_orders": 45,
    "total_revenue": 1250.50,
    "pending_orders": 3
  }
}
```

### Erreurs possibles

#### 401 - Non authentifié
```json
{
  "message": "Unauthenticated."
}
```

#### 403 - Accès refusé
```json
{
  "message": "Access denied"
}
```

---

## 4. Liste des produits du marchand

### Endpoint
```
GET /merchant/products
```

### Description
Récupère tous les produits du marchand authentifié avec leurs catégories et images.

### Headers
```
Authorization: Bearer {token}
```

### Authentification
Requise + Rôle "merchant" + Email vérifié

### Payload
Aucun payload requis.

### Réponse réussie (200)
```json
[
  {
    "id": 1,
    "name": "Tomates bio",
    "description": "Tomates fraîches du jardin",
    "price": 3.50,
    "price_promo": null,
    "stock": 50,
    "unit": "kg",
    "origin": "France",
    "category_id": 1,
    "user_id": 15,
    "created_at": "2025-11-15T10:00:00.000000Z",
    "updated_at": "2025-11-15T10:00:00.000000Z",
    "category": {
      "id": 1,
      "name": "Fruits et Légumes",
      "description": "Produits frais"
    },
    "images": [
      {
        "id": 5,
        "product_id": 1,
        "path": "products/abc123.jpg",
        "is_main": true,
        "created_at": "2025-11-15T10:00:00.000000Z"
      }
    ]
  }
]
```

---

## 5. Créer un produit

### Endpoint
```
POST /merchant/products
```

### Description
Permet au marchand de créer un nouveau produit.

### Headers
```
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

### Authentification
Requise + Rôle "merchant" + Email vérifié

### Payload (Form Data)
```
name: "Tomates bio"
description: "Tomates fraîches du jardin"
price: 3.50
price_promo: 3.00
category_id: 1
origin: "France"
unit: "kg"
stock: 50
photo: [fichier image]
```

### Paramètres requis
- `name` (string, requis) : Nom du produit (max 255 caractères)
- `price` (numeric, requis) : Prix du produit (minimum 0)
- `category_id` (integer, requis) : ID de la catégorie (doit exister)
- `unit` (string, requis) : Unité de mesure (kg, pièce, litre, etc.)

### Paramètres optionnels
- `description` (string, optionnel) : Description du produit
- `price_promo` (numeric, optionnel) : Prix promotionnel (minimum 0)
- `origin` (string, optionnel) : Origine du produit (max 255 caractères)
- `stock` (integer, optionnel) : Quantité en stock (minimum 0, défaut: 0)
- `photo` (file, optionnel) : Image du produit (max 2MB, formats: jpg, jpeg, png, gif)

### Réponse réussie (201)
```json
{
  "message": "Produit créé avec succès",
  "product": {
    "id": 25,
    "name": "Tomates bio",
    "description": "Tomates fraîches du jardin",
    "price": 3.50,
    "price_promo": 3.00,
    "stock": 50,
    "unit": "kg",
    "origin": "France",
    "category_id": 1,
    "user_id": 15,
    "created_at": "2025-11-15T12:00:00.000000Z",
    "updated_at": "2025-11-15T12:00:00.000000Z",
    "category": {
      "id": 1,
      "name": "Fruits et Légumes"
    },
    "images": [
      {
        "id": 10,
        "product_id": 25,
        "path": "products/xyz789.jpg",
        "is_main": true,
        "created_at": "2025-11-15T12:00:00.000000Z"
      }
    ]
  }
}
```

### Erreurs possibles

#### 422 - Validation échouée
```json
{
  "message": "Validation failed",
  "errors": {
    "name": ["Le champ nom est requis."],
    "price": ["Le prix doit être un nombre."],
    "category_id": ["La catégorie sélectionnée n'existe pas."],
    "unit": ["Le champ unité est requis."],
    "photo": ["Le fichier doit être une image.", "Le fichier ne doit pas dépasser 2048 kilo-octets."]
  }
}
```

---

## 6. Afficher un produit

### Endpoint
```
GET /merchant/products/{id}
```

### Description
Récupère les détails d'un produit spécifique du marchand.

### Headers
```
Authorization: Bearer {token}
```

### Authentification
Requise + Rôle "merchant" + Email vérifié

### Paramètres URL
- `id` (integer, requis) : ID du produit

### Exemple d'URL
```
GET /merchant/products/25
```

### Payload
Aucun payload requis.

### Réponse réussie (200)
```json
{
  "id": 25,
  "name": "Tomates bio",
  "description": "Tomates fraîches du jardin",
  "price": 3.50,
  "price_promo": 3.00,
  "stock": 50,
  "unit": "kg",
  "origin": "France",
  "category_id": 1,
  "user_id": 15,
  "created_at": "2025-11-15T12:00:00.000000Z",
  "updated_at": "2025-11-15T12:00:00.000000Z",
  "category": {
    "id": 1,
    "name": "Fruits et Légumes"
  },
  "images": [
    {
      "id": 10,
      "product_id": 25,
      "path": "products/xyz789.jpg",
      "is_main": true,
      "created_at": "2025-11-15T12:00:00.000000Z"
    }
  ]
}
```

### Erreurs possibles

#### 404 - Produit non trouvé
```json
{
  "message": "No query results for model [App\\Models\\Product] {id}"
}
```

---

## 7. Modifier un produit

### Endpoint
```
PUT /merchant/products/{id}
```

### Description
Permet au marchand de modifier un de ses produits existants.

### Headers
```
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**Note** : Pour les requêtes multipart/form-data avec PUT, vous devez ajouter `_method=PUT` dans le body.

### Authentification
Requise + Rôle "merchant" + Email vérifié

### Paramètres URL
- `id` (integer, requis) : ID du produit à modifier

### Exemple d'URL
```
PUT /merchant/products/25
```

### Payload (Form Data)
```
_method: "PUT"
name: "Tomates bio premium"
description: "Tomates fraîches du jardin, qualité supérieure"
price: 4.50
price_promo: 4.00
category_id: 1
origin: "France"
unit: "kg"
stock: 30
photo: [nouveau fichier image optionnel]
```

### Paramètres requis
- `name` (string, requis) : Nom du produit (max 255 caractères)
- `price` (numeric, requis) : Prix du produit (minimum 0)
- `category_id` (integer, requis) : ID de la catégorie (doit exister)
- `unit` (string, requis) : Unité de mesure

### Paramètres optionnels
- `description` (string, optionnel) : Description du produit
- `price_promo` (numeric, optionnel) : Prix promotionnel (minimum 0)
- `origin` (string, optionnel) : Origine du produit (max 255 caractères)
- `stock` (integer, optionnel) : Quantité en stock (minimum 0)
- `photo` (file, optionnel) : Nouvelle image du produit (remplace l'ancienne si fournie)

### Réponse réussie (200)
```json
{
  "message": "Produit mis à jour avec succès",
  "product": {
    "id": 25,
    "name": "Tomates bio premium",
    "description": "Tomates fraîches du jardin, qualité supérieure",
    "price": 4.50,
    "price_promo": 4.00,
    "stock": 30,
    "unit": "kg",
    "origin": "France",
    "category_id": 1,
    "user_id": 15,
    "created_at": "2025-11-15T12:00:00.000000Z",
    "updated_at": "2025-11-15T13:30:00.000000Z",
    "category": {
      "id": 1,
      "name": "Fruits et Légumes"
    },
    "images": [
      {
        "id": 11,
        "product_id": 25,
        "path": "products/new_image_456.jpg",
        "is_main": true,
        "created_at": "2025-11-15T13:30:00.000000Z"
      }
    ]
  }
}
```

### Erreurs possibles

#### 404 - Produit non trouvé
```json
{
  "message": "No query results for model [App\\Models\\Product] {id}"
}
```

#### 422 - Validation échouée
```json
{
  "message": "Validation failed",
  "errors": {
    "price": ["Le prix doit être un nombre positif."],
    "category_id": ["La catégorie sélectionnée n'existe pas."]
  }
}
```

---

## 8. Supprimer un produit

### Endpoint
```
DELETE /merchant/products/{id}
```

### Description
Permet au marchand de supprimer définitivement un de ses produits.

### Headers
```
Authorization: Bearer {token}
```

### Authentification
Requise + Rôle "merchant" + Email vérifié

### Paramètres URL
- `id` (integer, requis) : ID du produit à supprimer

### Exemple d'URL
```
DELETE /merchant/products/25
```

### Payload
Aucun payload requis.

### Réponse réussie (200)
```json
{
  "message": "Produit supprimé avec succès"
}
```

### Erreurs possibles

#### 404 - Produit non trouvé
```json
{
  "message": "No query results for model [App\\Models\\Product] {id}"
}
```

---

## 9. Liste des marchands en attente (Admin)

### Endpoint
```
GET /admin/merchants/pending
```

### Description
Récupère la liste de tous les marchands en attente d'approbation. Réservé aux administrateurs.

### Headers
```
Authorization: Bearer {token}
```

### Authentification
Requise + Rôle "admin" + Email vérifié

### Payload
Aucun payload requis.

### Réponse réussie (200)
```json
{
  "pending_merchants": [
    {
      "id": 10,
      "user_id": 15,
      "manager_lastname": "Dupont",
      "manager_firstname": "Jean",
      "mobile_phone": "+33612345678",
      "landline_phone": "+33123456789",
      "business_address": "123 Rue du Commerce",
      "business_city": "Paris",
      "business_postal_code": "75001",
      "business_type": "Épicerie",
      "business_description": "Vente de produits bio et locaux",
      "approval_status": "pending",
      "rejection_reason": null,
      "created_at": "2025-11-15T12:00:00.000000Z",
      "updated_at": "2025-11-15T12:00:00.000000Z",
      "user": {
        "id": 15,
        "name": "Jean Dupont",
        "email": "marchand@example.com",
        "type": "merchant",
        "is_approved": false,
        "email_verified_at": "2025-11-15T12:30:00.000000Z"
      }
    }
  ]
}
```

### Erreurs possibles

#### 403 - Accès refusé
```json
{
  "message": "Access denied"
}
```

---

## 10. Approuver un marchand (Admin)

### Endpoint
```
POST /admin/merchants/{id}/approve
```

### Description
Approuve le compte d'un marchand. Envoie une notification au marchand. Réservé aux administrateurs.

### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

### Authentification
Requise + Rôle "admin" + Email vérifié

### Paramètres URL
- `id` (integer, requis) : ID du marchand (merchant_id, pas user_id)

### Exemple d'URL
```
POST /admin/merchants/10/approve
```

### Payload
Aucun payload requis.

### Réponse réussie (200)
```json
{
  "message": "Merchant account approved successfully",
  "merchant": {
    "id": 10,
    "user_id": 15,
    "manager_lastname": "Dupont",
    "manager_firstname": "Jean",
    "mobile_phone": "+33612345678",
    "landline_phone": "+33123456789",
    "business_address": "123 Rue du Commerce",
    "business_city": "Paris",
    "business_postal_code": "75001",
    "business_type": "Épicerie",
    "business_description": "Vente de produits bio et locaux",
    "approval_status": "approved",
    "rejection_reason": null,
    "created_at": "2025-11-15T12:00:00.000000Z",
    "updated_at": "2025-11-15T14:00:00.000000Z"
  }
}
```

### Erreurs possibles

#### 404 - Marchand non trouvé
```json
{
  "message": "No query results for model [App\\Models\\Merchant] {id}"
}
```

#### 404 - Utilisateur associé non trouvé
```json
{
  "message": "User not found"
}
```

---

## 11. Rejeter un marchand (Admin)

### Endpoint
```
POST /admin/merchants/{id}/reject
```

### Description
Rejette la demande de compte marchand avec un motif. Envoie une notification au marchand. Réservé aux administrateurs.

### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

### Authentification
Requise + Rôle "admin" + Email vérifié

### Paramètres URL
- `id` (integer, requis) : ID du marchand (merchant_id, pas user_id)

### Exemple d'URL
```
POST /admin/merchants/10/reject
```

### Payload (Request Body)
```json
{
  "rejection_reason": "Les documents fournis sont incomplets. Veuillez fournir votre SIRET et votre Kbis."
}
```

### Paramètres requis
- `rejection_reason` (string, requis) : Motif du rejet (max 1000 caractères)

### Réponse réussie (200)
```json
{
  "message": "Merchant account rejected",
  "merchant": {
    "id": 10,
    "user_id": 15,
    "manager_lastname": "Dupont",
    "manager_firstname": "Jean",
    "mobile_phone": "+33612345678",
    "landline_phone": "+33123456789",
    "business_address": "123 Rue du Commerce",
    "business_city": "Paris",
    "business_postal_code": "75001",
    "business_type": "Épicerie",
    "business_description": "Vente de produits bio et locaux",
    "approval_status": "rejected",
    "rejection_reason": "Les documents fournis sont incomplets. Veuillez fournir votre SIRET et votre Kbis.",
    "created_at": "2025-11-15T12:00:00.000000Z",
    "updated_at": "2025-11-15T14:00:00.000000Z"
  }
}
```

### Erreurs possibles

#### 422 - Validation échouée
```json
{
  "errors": {
    "rejection_reason": ["Le champ motif du rejet est requis."]
  },
  "message": "The rejection reason field is required."
}
```

#### 404 - Marchand non trouvé
```json
{
  "message": "No query results for model [App\\Models\\Merchant] {id}"
}
```

---

## Exemples d'utilisation avec cURL

### Inscription marchand
```bash
curl -X POST http://votre-domaine.com/api/merchant/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "marchand@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "manager_lastname": "Dupont",
    "manager_firstname": "Jean",
    "mobile_phone": "+33612345678",
    "landline_phone": "+33123456789",
    "business_address": "123 Rue du Commerce",
    "business_city": "Paris",
    "business_postal_code": "75001",
    "business_type": "Épicerie",
    "business_description": "Vente de produits bio et locaux"
  }'
```

### Vérifier le statut
```bash
curl -X GET http://votre-domaine.com/api/merchant/status \
  -H "Authorization: Bearer votre_token"
```

### Créer un produit
```bash
curl -X POST http://votre-domaine.com/api/merchant/products \
  -H "Authorization: Bearer votre_token" \
  -F "name=Tomates bio" \
  -F "description=Tomates fraîches du jardin" \
  -F "price=3.50" \
  -F "price_promo=3.00" \
  -F "category_id=1" \
  -F "origin=France" \
  -F "unit=kg" \
  -F "stock=50" \
  -F "photo=@/chemin/vers/image.jpg"
```

### Liste des produits
```bash
curl -X GET http://votre-domaine.com/api/merchant/products \
  -H "Authorization: Bearer votre_token"
```

### Modifier un produit
```bash
curl -X PUT http://votre-domaine.com/api/merchant/products/25 \
  -H "Authorization: Bearer votre_token" \
  -F "_method=PUT" \
  -F "name=Tomates bio premium" \
  -F "description=Tomates fraîches du jardin, qualité supérieure" \
  -F "price=4.50" \
  -F "category_id=1" \
  -F "unit=kg" \
  -F "stock=30"
```

### Supprimer un produit
```bash
curl -X DELETE http://votre-domaine.com/api/merchant/products/25 \
  -H "Authorization: Bearer votre_token"
```

### Approuver un marchand (Admin)
```bash
curl -X POST http://votre-domaine.com/api/admin/merchants/10/approve \
  -H "Authorization: Bearer admin_token"
```

### Rejeter un marchand (Admin)
```bash
curl -X POST http://votre-domaine.com/api/admin/merchants/10/reject \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer admin_token" \
  -d '{
    "rejection_reason": "Documents incomplets"
  }'
```

---

## Statuts et permissions

### Statuts d'approbation marchand
- `pending` : En attente d'approbation
- `approved` : Approuvé et peut vendre
- `rejected` : Rejeté (avec raison fournie)

### Types d'utilisateurs
- `customer` : Client standard
- `merchant` : Marchand (vendeur)
- `admin` : Administrateur

### Middleware requis
- **auth:sanctum** : Token d'authentification Sanctum requis
- **verified** : Email vérifié requis
- **role:merchant** : Rôle marchand requis
- **role:admin** : Rôle administrateur requis

---

## Notes importantes

1. **Inscription** : Après inscription, le marchand doit :
   - Vérifier son email
   - Attendre l'approbation de l'administrateur
   
2. **Upload d'images** : 
   - Format acceptés : JPG, JPEG, PNG, GIF
   - Taille maximale : 2 MB
   - Les images sont stockées dans `storage/app/public/products/`
   
3. **Gestion des produits** :
   - Un marchand ne peut gérer que ses propres produits
   - La suppression d'un produit supprime également ses images
   
4. **Notifications** :
   - Le marchand reçoit une notification lors de l'approbation/rejet
   - Les admins reçoivent une notification lors d'une nouvelle inscription
   
5. **Sécurité** :
   - Tous les endpoints marchands sont protégés par authentification
   - Les routes admin sont réservées aux utilisateurs avec le rôle "admin"
   - Vérification de propriété pour la modification/suppression des produits

