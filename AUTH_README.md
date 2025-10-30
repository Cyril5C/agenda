# Configuration de l'authentification admin

## Mot de passe par défaut

En mode développement, le mot de passe par défaut est : `admin123`

## Configuration en production

Pour configurer le mot de passe en production sur Railway :

1. Générer un hash SHA256 de votre mot de passe :
   ```bash
   echo -n "votre_mot_de_passe" | shasum -a 256
   ```

2. Aller dans les paramètres de votre projet Railway

3. Ajouter une variable d'environnement :
   - Nom : `ADMIN_PASSWORD_HASH`
   - Valeur : le hash SHA256 généré à l'étape 1

4. Redéployer l'application

## Exemple

Pour le mot de passe `admin123` :
```bash
echo -n "admin123" | shasum -a 256
# Résultat : 240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9
```

Ajouter dans Railway :
```
ADMIN_PASSWORD_HASH=240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9
```

## Accès à l'admin

- Page de connexion : `/login.html`
- Page admin : `/admin.html` (redirige vers login si non authentifié)
