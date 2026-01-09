# API d'Approbation des Commerçants

## Vue d'ensemble

Cette API permet aux administrateurs de gérer l'approbation des comptes commerçants. Elle comprend trois endpoints principaux pour lister les commerçants en attente, approuver ou rejeter un compte commerçant.

**Base URL** : `/api`

**Authentification** : Tous les endpoints nécessitent une authentification via Sanctum et le rôle `admin`.

---

## Endpoints

### 1. Lister les commerçants en attente

Récupère la liste de tous les comptes commerçants dont le statut d'approbation est `pending`.

#### Endpoint
```
GET /admin/merchants/pending
```

#### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

#### Middleware
- `auth:sanctum`
- `verified`
- `role:admin`

#### Réponse réussie (200 OK)

```json
{
  "pending_merchants": [
    {
      "id": 1,
      "user_id": 5,
      "manager_lastname": "Dupont",
      "manager_firstname": "Jean",
      "mobile_phone": "+261 34 12 345 67",
      "landline_phone": "+261 20 22 345 67",
      "business_address": "Lot IVB 25 Ambohibao",
      "business_city": "Antananarivo",
      "business_postal_code": "101",
      "business_type": "E-commerce",
      "business_description": "Vente de produits électroniques et accessoires",
      "approval_status": "pending",
      "rejection_reason": null,
      "created_at": "2025-12-20T08:00:00.000000Z",
      "updated_at": "2025-12-20T08:00:00.000000Z",
      "user": {
        "id": 5,
        "name": "Jean Dupont",
        "email": "jean.dupont@example.com",
        "type": "merchant",
        "is_approved": false,
        "email_verified_at": "2025-12-20T08:05:00.000000Z",
        "created_at": "2025-12-20T08:00:00.000000Z",
        "updated_at": "2025-12-20T08:00:00.000000Z"
      }
    }
  ]
}
```

#### Réponse vide (200 OK)
Si aucun commerçant n'est en attente, la liste sera vide :

```json
{
  "pending_merchants": []
}
```

#### Codes d'erreur possibles

| Code | Description |
|------|-------------|
| 401 | Non authentifié - Token manquant ou invalide |
| 403 | Accès refusé - L'utilisateur n'a pas le rôle admin |
| 500 | Erreur serveur |

---

### 2. Approuver un commerçant

Approuve un compte commerçant. Met à jour le statut d'approbation, active le compte utilisateur et envoie une notification par email au commerçant.

#### Endpoint
```
POST /admin/merchants/{id}/approve
```

#### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Middleware
- `auth:sanctum`
- `verified`
- `role:admin`

#### Paramètres URL

| Paramètre | Type | Description |
|-----------|------|-------------|
| `id` | integer | ID du commerçant à approuver |

#### Corps de la requête

Aucun corps de requête requis.

#### Exemple de requête

```bash
curl -X POST "https://api.example.com/api/admin/merchants/1/approve" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Réponse réussie (200 OK)

```json
{
  "message": "Merchant account approved successfully",
  "merchant": {
    "id": 1,
    "user_id": 5,
    "manager_lastname": "Dupont",
    "manager_firstname": "Jean",
    "mobile_phone": "+261 34 12 345 67",
    "landline_phone": "+261 20 22 345 67",
    "business_address": "Lot IVB 25 Ambohibao",
    "business_city": "Antananarivo",
    "business_postal_code": "101",
    "business_type": "E-commerce",
    "business_description": "Vente de produits électroniques et accessoires",
    "approval_status": "approved",
    "rejection_reason": null,
    "created_at": "2025-12-20T08:00:00.000000Z",
    "updated_at": "2025-12-24T10:30:00.000000Z"
  }
}
```

#### Codes d'erreur possibles

| Code | Description |
|------|-------------|
| 401 | Non authentifié - Token manquant ou invalide |
| 403 | Accès refusé - L'utilisateur n'a pas le rôle admin |
| 404 | Commerçant non trouvé - L'ID fourni n'existe pas |
| 404 | User not found - L'utilisateur associé au commerçant n'existe pas |
| 500 | Erreur serveur |

#### Comportement

Lors de l'approbation :
1. Le statut `approval_status` du commerçant passe à `approved`
2. Le champ `is_approved` de l'utilisateur associé passe à `true`
3. Une notification email est envoyée au commerçant pour l'informer de l'approbation

---

### 3. Rejeter un commerçant

Rejette un compte commerçant. Met à jour le statut d'approbation avec une raison de rejet et envoie une notification par email au commerçant.

#### Endpoint
```
POST /admin/merchants/{id}/reject
```

#### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Middleware
- `auth:sanctum`
- `verified`
- `role:admin`

#### Paramètres URL

| Paramètre | Type | Description |
|-----------|------|-------------|
| `id` | integer | ID du commerçant à rejeter |

#### Corps de la requête

```json
{
  "rejection_reason": "string (requis, max: 1000 caractères)"
}
```

#### Validation

| Champ | Règle | Description |
|-------|-------|-------------|
| `rejection_reason` | `required` | La raison du rejet est obligatoire |
| `rejection_reason` | `string` | Doit être une chaîne de caractères |
| `rejection_reason` | `max:1000` | Maximum 1000 caractères |

#### Exemple de requête

```bash
curl -X POST "https://api.example.com/api/admin/merchants/1/reject" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "rejection_reason": "Documents incomplets. Veuillez fournir un extrait K-bis valide."
  }'
```

#### Réponse réussie (200 OK)

```json
{
  "message": "Merchant account rejected",
  "merchant": {
    "id": 1,
    "user_id": 5,
    "manager_lastname": "Dupont",
    "manager_firstname": "Jean",
    "mobile_phone": "+261 34 12 345 67",
    "landline_phone": "+261 20 22 345 67",
    "business_address": "Lot IVB 25 Ambohibao",
    "business_city": "Antananarivo",
    "business_postal_code": "101",
    "business_type": "E-commerce",
    "business_description": "Vente de produits électroniques et accessoires",
    "approval_status": "rejected",
    "rejection_reason": "Documents incomplets. Veuillez fournir un extrait K-bis valide.",
    "created_at": "2025-12-20T08:00:00.000000Z",
    "updated_at": "2025-12-24T10:30:00.000000Z"
  }
}
```

#### Codes d'erreur possibles

| Code | Description |
|------|-------------|
| 401 | Non authentifié - Token manquant ou invalide |
| 403 | Accès refusé - L'utilisateur n'a pas le rôle admin |
| 404 | Commerçant non trouvé - L'ID fourni n'existe pas |
| 404 | User not found - L'utilisateur associé au commerçant n'existe pas |
| 422 | Erreur de validation - Le champ `rejection_reason` est manquant ou invalide |
| 500 | Erreur serveur |

#### Exemple de réponse d'erreur de validation (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "rejection_reason": [
      "The rejection reason field is required."
    ]
  }
}
```

#### Comportement

Lors du rejet :
1. Le statut `approval_status` du commerçant passe à `rejected`
2. La raison de rejet est enregistrée dans `rejection_reason`
3. Une notification email est envoyée au commerçant avec la raison du rejet

---

## Modèles de données

### Merchant

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID unique du commerçant |
| `user_id` | integer | ID de l'utilisateur associé |
| `manager_lastname` | string | Nom du gérant |
| `manager_firstname` | string | Prénom du gérant |
| `mobile_phone` | string | Téléphone portable |
| `landline_phone` | string (nullable) | Téléphone fixe |
| `business_address` | string | Adresse de l'entreprise |
| `business_city` | string | Ville de l'entreprise |
| `business_postal_code` | string | Code postal |
| `business_type` | string (nullable) | Type d'entreprise |
| `business_description` | text (nullable) | Description de l'entreprise |
| `approval_status` | enum | Statut d'approbation : `pending`, `approved`, `rejected` |
| `rejection_reason` | text (nullable) | Raison du rejet (si rejeté) |
| `created_at` | datetime | Date de création |
| `updated_at` | datetime | Date de mise à jour |

### User (associé)

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID unique de l'utilisateur |
| `name` | string | Nom complet |
| `email` | string | Adresse email |
| `type` | string | Type d'utilisateur : `merchant` |
| `is_approved` | boolean | Statut d'approbation de l'utilisateur |
| `email_verified_at` | datetime (nullable) | Date de vérification de l'email |

---

## Notifications

### MerchantApprovalNotification

Lors de l'approbation ou du rejet, une notification email est automatiquement envoyée au commerçant via `MerchantApprovalNotification`.

- **Approval** : Notification de confirmation d'approbation
- **Rejection** : Notification avec la raison du rejet

---

## Exemples d'utilisation

### JavaScript (Fetch API)

```javascript
// Lister les commerçants en attente
async function getPendingMerchants() {
  const response = await fetch('/api/admin/merchants/pending', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  return data.pending_merchants;
}

// Approuver un commerçant
async function approveMerchant(merchantId) {
  const response = await fetch(`/api/admin/merchants/${merchantId}/approve`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data;
}

// Rejeter un commerçant
async function rejectMerchant(merchantId, reason) {
  const response = await fetch(`/api/admin/merchants/${merchantId}/reject`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      rejection_reason: reason
    })
  });
  
  const data = await response.json();
  return data;
}
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'https://api.example.com/api']);

// Lister les commerçants en attente
$response = $client->get('/admin/merchants/pending', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ]
]);
$pendingMerchants = json_decode($response->getBody(), true);

// Approuver un commerçant
$response = $client->post("/admin/merchants/{$merchantId}/approve", [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ]
]);
$result = json_decode($response->getBody(), true);

// Rejeter un commerçant
$response = $client->post("/admin/merchants/{$merchantId}/reject", [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ],
    'json' => [
        'rejection_reason' => 'Documents incomplets.'
    ]
]);
$result = json_decode($response->getBody(), true);
```

### Python (Requests)

```python
import requests

base_url = "https://api.example.com/api"
headers = {
    "Authorization": f"Bearer {token}",
    "Accept": "application/json"
}

# Lister les commerçants en attente
response = requests.get(f"{base_url}/admin/merchants/pending", headers=headers)
pending_merchants = response.json()["pending_merchants"]

# Approuver un commerçant
response = requests.post(
    f"{base_url}/admin/merchants/{merchant_id}/approve",
    headers=headers
)
result = response.json()

# Rejeter un commerçant
response = requests.post(
    f"{base_url}/admin/merchants/{merchant_id}/reject",
    headers={**headers, "Content-Type": "application/json"},
    json={
        "rejection_reason": "Documents incomplets."
    }
)
result = response.json()
```

---

## Notes importantes

1. **Authentification requise** : Tous les endpoints nécessitent un token d'authentification valide.
2. **Rôle admin** : Seuls les utilisateurs avec le rôle `admin` peuvent accéder à ces endpoints.
3. **Notifications automatiques** : Les notifications email sont envoyées automatiquement lors de l'approbation ou du rejet.
4. **Statut utilisateur** : Lors de l'approbation, le champ `is_approved` de l'utilisateur est également mis à jour.
5. **Raison de rejet obligatoire** : Le rejet d'un commerçant nécessite obligatoirement une raison.
6. **Relations** : Les données du commerçant incluent toujours les informations de l'utilisateur associé dans les réponses.

---

## Codes de statut HTTP

| Code | Signification | Description |
|------|---------------|-------------|
| 200 | OK | Requête réussie |
| 401 | Unauthorized | Authentification requise |
| 403 | Forbidden | Permissions insuffisantes (rôle admin requis) |
| 404 | Not Found | Ressource non trouvée |
| 422 | Unprocessable Entity | Erreur de validation |
| 500 | Internal Server Error | Erreur serveur |

---

## Changelog

- **2025-12-24** : Documentation initiale de l'API d'approbation des commerçants
