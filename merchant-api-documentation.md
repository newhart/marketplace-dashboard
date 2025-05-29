# API Documentation pour l'Inscription des Commerçants

Cette documentation décrit les endpoints API pour l'inscription, la validation et la gestion des comptes commerçants sur la plateforme.

## Endpoints Publics

### Inscription d'un Commerçant

**Endpoint:** `POST /api/merchant/register`

**Description:** Permet à un utilisateur de s'inscrire en tant que commerçant.

**Corps de la Requête:**
```json
{
  "email": "exemple@commerce.com",
  "password": "motdepasse123",
  "password_confirmation": "motdepasse123",
  "manager_lastname": "Dupont",
  "manager_firstname": "Jean",
  "mobile_phone": "0612345678",
  "landline_phone": "0123456789",
  "business_address": "123 Rue du Commerce",
  "business_city": "Paris",
  "business_postal_code": "75001",
  "business_type": "Restaurant",
  "business_description": "Restaurant traditionnel français"
}
```

**Réponse (201 Created):**
```json
{
  "message": "Merchant account created successfully. Please verify your email address.",
  "user": {
    "id": 1,
    "name": "Jean Dupont",
    "email": "exemple@commerce.com",
    "type": "merchant",
    "is_approved": false
  },
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
    "business_type": "Restaurant",
    "business_description": "Restaurant traditionnel français",
    "approval_status": "pending"
  },
  "token": "1|XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
}
```

## Endpoints Authentifiés

### Vérifier le Statut du Compte Commerçant

**Endpoint:** `GET /api/merchant/status`

**Description:** Permet à un commerçant de vérifier le statut de son compte.

**Headers:**
```
Authorization: Bearer {token}
```

**Réponse (200 OK):**
```json
{
  "approval_status": "pending",
  "email_verified": false,
  "rejection_reason": null
}
```

## Endpoints Admin

### Liste des Commerçants en Attente

**Endpoint:** `GET /api/admin/merchants/pending`

**Description:** Permet à un administrateur de voir tous les comptes commerçants en attente d'approbation.

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Réponse (200 OK):**
```json
{
  "pending_merchants": [
    {
      "id": 1,
      "user_id": 1,
      "manager_lastname": "Dupont",
      "manager_firstname": "Jean",
      "mobile_phone": "0612345678",
      "approval_status": "pending",
      "created_at": "2025-05-29T07:30:00.000000Z",
      "user": {
        "id": 1,
        "name": "Jean Dupont",
        "email": "exemple@commerce.com"
      }
    }
  ]
}
```

### Approuver un Compte Commerçant

**Endpoint:** `POST /api/admin/merchants/{id}/approve`

**Description:** Permet à un administrateur d'approuver un compte commerçant.

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Réponse (200 OK):**
```json
{
  "message": "Merchant account approved successfully",
  "merchant": {
    "id": 1,
    "approval_status": "approved"
  }
}
```

### Rejeter un Compte Commerçant

**Endpoint:** `POST /api/admin/merchants/{id}/reject`

**Description:** Permet à un administrateur de rejeter un compte commerçant.

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Corps de la Requête:**
```json
{
  "rejection_reason": "Les informations fournies sont incomplètes ou incorrectes."
}
```

**Réponse (200 OK):**
```json
{
  "message": "Merchant account rejected",
  "merchant": {
    "id": 1,
    "approval_status": "rejected",
    "rejection_reason": "Les informations fournies sont incomplètes ou incorrectes."
  }
}
```

## Flux d'Inscription et d'Approbation

1. Le commerçant s'inscrit via l'endpoint `/api/merchant/register`
2. Un email de vérification est envoyé à l'adresse email fournie
3. Le commerçant vérifie son email en cliquant sur le lien dans l'email
4. L'administrateur voit le compte en attente via `/api/admin/merchants/pending`
5. L'administrateur approuve ou rejette le compte
6. Le commerçant est notifié par email de la décision
7. Le commerçant peut vérifier le statut de son compte via `/api/merchant/status`
