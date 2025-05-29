# API Documentation pour l'Inscription des Acheteurs

Cette documentation décrit les endpoints API pour l'inscription et la vérification des comptes acheteurs sur la plateforme.

## Endpoints Publics

### Inscription d'un Acheteur

**Endpoint:** `POST /api/customer/register`

**Description:** Permet à un utilisateur de s'inscrire en tant qu'acheteur.

**Corps de la Requête:**
```json
{
  "lastname": "Dupont",
  "firstname": "Jean",
  "birth_date": "1990-01-01",
  "phone": "0612345678",
  "postal_address": "123 Rue du Commerce",
  "geographic_address": "Paris, France",
  "email": "jean.dupont@exemple.com",
  "password": "motdepasse123",
  "password_confirmation": "motdepasse123"
}
```

**Réponse (201 Created):**
```json
{
  "message": "Account created successfully. Please verify your email address.",
  "user": {
    "id": 1,
    "name": "Jean Dupont",
    "email": "jean.dupont@exemple.com",
    "type": "customer",
    "is_approved": true
  },
  "token": "1|XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
}
```

### Inscription/Connexion avec les Réseaux Sociaux

**Endpoint:** `POST /api/customer/social-register`

**Description:** Permet à un utilisateur de s'inscrire ou de se connecter en utilisant un compte de réseau social.

**Corps de la Requête:**
```json
{
  "provider": "google",
  "provider_id": "123456789",
  "email": "jean.dupont@exemple.com",
  "name": "Jean Dupont"
}
```

**Réponse (200 OK):**
```json
{
  "message": "Social authentication successful",
  "user": {
    "id": 1,
    "name": "Jean Dupont",
    "email": "jean.dupont@exemple.com",
    "type": "customer",
    "is_approved": true
  },
  "token": "1|XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
}
```

## Vérification d'Email

Après l'inscription, un email de vérification est automatiquement envoyé à l'adresse email fournie. L'utilisateur doit cliquer sur le lien dans l'email pour vérifier son adresse.

### Vérifier l'Email

**Endpoint:** `GET /api/verify-email/{id}/{hash}`

**Description:** Point de terminaison pour vérifier l'adresse email d'un utilisateur. Ce lien est envoyé par email.

**Paramètres:**
- `id`: ID de l'utilisateur
- `hash`: Hash de vérification

**Réponse (200 OK):**
```json
{
  "message": "Email verified successfully"
}
```

### Renvoyer l'Email de Vérification

**Endpoint:** `POST /api/email/verification-notification`

**Description:** Permet à un utilisateur de demander un nouvel email de vérification.

**Headers:**
```
Authorization: Bearer {token}
```

**Réponse (200 OK):**
```json
{
  "message": "Verification link sent"
}
```

## Flux d'Inscription et de Vérification

1. L'acheteur s'inscrit via l'endpoint `/api/customer/register` ou via les réseaux sociaux avec `/api/customer/social-register`
2. Pour l'inscription standard, un email de vérification est envoyé à l'adresse email fournie
3. L'acheteur vérifie son email en cliquant sur le lien dans l'email
4. L'acheteur peut maintenant se connecter et utiliser la plateforme
5. Si l'acheteur s'inscrit via les réseaux sociaux, son email est considéré comme déjà vérifié et il peut immédiatement utiliser la plateforme

## Différences avec le Compte Commerçant

Contrairement aux comptes commerçants, les comptes acheteurs :
- Ne nécessitent pas d'approbation par un administrateur
- Sont automatiquement approuvés après vérification de l'email
- Ont un formulaire d'inscription différent avec des champs adaptés aux particuliers
- Peuvent s'inscrire via les réseaux sociaux (Google, Facebook)
