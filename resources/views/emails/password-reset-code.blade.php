<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de réinitialisation de mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 20px;
        }
        .logo-fallback {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .code-container {
            background-color: #f8fafc;
            border: 2px dashed #2563eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .code {
            font-size: 36px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 5px;
            font-family: 'Courier New', monospace;
        }
        .code-label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 10px;
        }
        .expiry-info {
            background-color: #fef3cd;
            border-left: 4px solid #fbbf24;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .security-notice {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="Marketplace Logo" class="logo">
            <h1>Code de réinitialisation de mot de passe</h1>
        </div>

        <p>Bonjour <strong>{{ $user->name }}</strong>,</p>
        
        <p>Vous avez demandé la réinitialisation de votre mot de passe. Voici votre code de vérification :</p>

        <div class="code-container">
            <div class="code-label">Votre code de vérification</div>
            <div class="code">{{ $code }}</div>
        </div>

        <div class="expiry-info">
            <strong>⏰ Important :</strong> Ce code expire dans <strong>{{ $expires_in_minutes }} minutes</strong>.
        </div>

        <p>Pour réinitialiser votre mot de passe :</p>
        <ol>
            <li>Saisissez ce code dans l'application</li>
            <li>Choisissez votre nouveau mot de passe</li>
            <li>Confirmez votre nouveau mot de passe</li>
        </ol>

        <div class="security-notice">
            <strong>🔒 Sécurité :</strong>
            <ul style="margin: 10px 0;">
                <li>Ne partagez jamais ce code avec qui que ce soit</li>
                <li>Notre équipe ne vous demandera jamais ce code par téléphone ou email</li>
                <li>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email</li>
            </ul>
        </div>

        <p>Si vous rencontrez des difficultés, n'hésitez pas à nous contacter.</p>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} Marketplace. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
