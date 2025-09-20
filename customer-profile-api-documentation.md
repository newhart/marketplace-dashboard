# Documentation API - Gestion du Profil Client

Cette documentation décrit les endpoints API pour la gestion du profil des clients dans le système marketplace.

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

## 👤 **Gestion du Profil Client**

### `GET /customer/profile`

Récupère les informations du profil du client authentifié.

#### Headers
```
Authorization: Bearer {token}
```

#### Réponse de succès (200)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "name": "Jean Dupont",
        "firstname": "Jean",
        "lastname": "Dupont",
        "email": "jean.dupont@example.com",
        "phone": "+261 34 12 345 67",
        "birth_date": "1990-05-15",
        "postal_address": "123 Rue de la Paix",
        "geographic_address": "Antananarivo, Madagascar",
        "email_verified_at": "2025-09-20T10:30:00Z",
        "type": "customer",
        "is_approved": true,
        "created_at": "2025-09-15T08:00:00Z",
        "updated_at": "2025-09-20T14:30:00Z",
        "addresses": [
            {
                "id": 1,
                "type": "shipping",
                "title": "Domicile",
                "first_name": "Jean",
                "last_name": "Dupont",
                "address_line_1": "123 Rue de la Paix",
                "city": "Antananarivo",
                "is_default": true,
                "created_at": "2025-09-20T10:30:00Z"
            }
        ]
    }
}
```

---

### `PUT /customer/profile`

Met à jour les informations du profil client.

#### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

#### Corps de la requête
```json
{
    "firstname": "Jean-Pierre",
    "lastname": "Dupont",
    "email": "jeanpierre.dupont@example.com",
    "phone": "+261 34 98 765 43",
    "birth_date": "1990-05-15",
    "postal_address": "456 Avenue de l'Indépendance",
    "geographic_address": "Fianarantsoa, Madagascar"
}
```

#### Paramètres (tous optionnels)
| Paramètre | Type | Description |
|-----------|------|-------------|
| firstname | string | Prénom (max 255 caractères) |
| lastname | string | Nom de famille (max 255 caractères) |
| email | string | Adresse email (doit être unique) |
| phone | string | Numéro de téléphone (max 20 caractères) |
| birth_date | date | Date de naissance (format: YYYY-MM-DD, antérieure à aujourd'hui) |
| postal_address | string | Adresse postale (max 255 caractères) |
| geographic_address | string | Adresse géographique (max 255 caractères) |

#### Réponse de succès (200)
```json
{
    "success": true,
    "message": "Profil mis à jour avec succès",
    "data": {
        "id": 123,
        "name": "Jean-Pierre Dupont",
        "firstname": "Jean-Pierre",
        "lastname": "Dupont",
        "email": "jeanpierre.dupont@example.com",
        "phone": "+261 34 98 765 43",
        "birth_date": "1990-05-15",
        "postal_address": "456 Avenue de l'Indépendance",
        "geographic_address": "Fianarantsoa, Madagascar",
        "email_verified_at": "2025-09-20T10:30:00Z",
        "type": "customer",
        "is_approved": true,
        "created_at": "2025-09-15T08:00:00Z",
        "updated_at": "2025-09-20T15:00:00Z"
    }
}
```

#### Réponses d'erreur
- **422 Unprocessable Entity** : Erreurs de validation
- **403 Forbidden** : Accès non autorisé (utilisateur non client)
- **500 Internal Server Error** : Erreur serveur

---

### `POST /customer/profile/change-password`

Change le mot de passe du client.

#### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

#### Corps de la requête
```json
{
    "current_password": "ancien_mot_de_passe",
    "password": "nouveau_mot_de_passe",
    "password_confirmation": "nouveau_mot_de_passe"
}
```

#### Paramètres
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| current_password | string | Oui | Mot de passe actuel |
| password | string | Oui | Nouveau mot de passe (doit respecter les règles de sécurité) |
| password_confirmation | string | Oui | Confirmation du nouveau mot de passe |

#### Réponse de succès (200)
```json
{
    "success": true,
    "message": "Mot de passe modifié avec succès"
}
```

#### Réponses d'erreur
- **422 Unprocessable Entity** : 
  - Erreurs de validation
  - Mot de passe actuel incorrect
- **403 Forbidden** : Accès non autorisé
- **500 Internal Server Error** : Erreur serveur

---

### `DELETE /customer/profile`

Supprime définitivement le compte client.

⚠️ **ATTENTION** : Cette action est irréversible !

#### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

#### Corps de la requête
```json
{
    "password": "mot_de_passe_actuel",
    "confirmation": "DELETE"
}
```

#### Paramètres
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| password | string | Oui | Mot de passe actuel pour confirmation |
| confirmation | string | Oui | Doit être exactement "DELETE" |

#### Réponse de succès (200)
```json
{
    "success": true,
    "message": "Compte supprimé avec succès"
}
```

#### Réponses d'erreur
- **422 Unprocessable Entity** : 
  - Erreurs de validation
  - Mot de passe incorrect
  - Confirmation incorrecte
- **403 Forbidden** : Accès non autorisé
- **500 Internal Server Error** : Erreur serveur

---

## 🔒 **Sécurité et Validations**

### **Règles de Validation**

#### **Email**
- Format email valide
- Unique dans la base de données
- Maximum 255 caractères
- Converti automatiquement en minuscules

#### **Mot de passe**
- Minimum 8 caractères
- Doit contenir au moins une lettre majuscule
- Doit contenir au moins une lettre minuscule
- Doit contenir au moins un chiffre
- Doit contenir au moins un caractère spécial

#### **Date de naissance**
- Format YYYY-MM-DD
- Doit être antérieure à la date actuelle

#### **Téléphone**
- Maximum 20 caractères
- Format libre (permet différents formats internationaux)

### **Fonctionnalités de Sécurité**

- ✅ Vérification du mot de passe actuel avant changement
- ✅ Confirmation obligatoire pour suppression de compte
- ✅ Révocation des tokens lors de la suppression
- ✅ Validation stricte des données d'entrée
- ✅ Protection contre les accès non autorisés

---

## ⚠️ **Codes d'Erreur**

| Code | Description |
|------|-------------|
| 200 | Succès |
| 403 | Accès interdit (non client ou non authentifié) |
| 422 | Erreurs de validation |
| 500 | Erreur serveur interne |

---

## 🧪 **Exemples d'Utilisation**

### JavaScript/Fetch

```javascript
// Récupérer le profil
const getProfile = async () => {
    const response = await fetch('/api/customer/profile', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    const data = await response.json();
    return data;
};

// Mettre à jour le profil
const updateProfile = async (profileData) => {
    const response = await fetch('/api/customer/profile', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(profileData)
    });
    
    const data = await response.json();
    return data;
};

// Changer le mot de passe
const changePassword = async (passwordData) => {
    const response = await fetch('/api/customer/profile/change-password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(passwordData)
    });
    
    const data = await response.json();
    return data;
};

// Supprimer le compte
const deleteAccount = async (confirmationData) => {
    const response = await fetch('/api/customer/profile', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(confirmationData)
    });
    
    const data = await response.json();
    return data;
};
```

### cURL

```bash
# Récupérer le profil
curl -X GET http://votre-domaine.com/api/customer/profile \
  -H "Authorization: Bearer YOUR_TOKEN"

# Mettre à jour le profil
curl -X PUT http://votre-domaine.com/api/customer/profile \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "firstname": "Jean-Pierre",
    "lastname": "Dupont",
    "email": "jeanpierre.dupont@example.com",
    "phone": "+261 34 98 765 43"
  }'

# Changer le mot de passe
curl -X POST http://votre-domaine.com/api/customer/profile/change-password \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "current_password": "ancien_mot_de_passe",
    "password": "nouveau_mot_de_passe",
    "password_confirmation": "nouveau_mot_de_passe"
  }'

# Supprimer le compte
curl -X DELETE http://votre-domaine.com/api/customer/profile \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "password": "mot_de_passe_actuel",
    "confirmation": "DELETE"
  }'
```

---

## 📝 **Notes Importantes**

1. **Mise à jour du nom complet** : Lorsque `firstname` ou `lastname` sont modifiés, le champ `name` est automatiquement mis à jour avec la concaténation des deux.

2. **Email unique** : Le système vérifie que l'email n'est pas déjà utilisé par un autre utilisateur.

3. **Suppression de compte** : 
   - Supprime définitivement toutes les données associées (adresses, commandes, etc.)
   - Révoque tous les tokens d'authentification
   - Action irréversible

4. **Sécurité des mots de passe** : Les mots de passe sont hachés avec bcrypt et ne sont jamais retournés dans les réponses API.

Cette API offre une gestion complète et sécurisée du profil client ! 👤🔒
