# Gestion des Environnements - Application PM

Ce projet dispose désormais d'un système de gestion des environnements (développement/production).

## Configuration

### 1. Fichiers de configuration

- **[config.php](config.php)** : Fichier principal de configuration qui gère les environnements
- **[.env](.env)** : Fichier de configuration d'environnement (non versionné)
- **[.env.example](.env.example)** : Exemple de fichier de configuration

### 2. Installation

1. Copiez le fichier `.env.example` en `.env` :
   ```bash
   cp .env.example .env
   ```

2. Modifiez les valeurs dans `.env` selon votre environnement :
   ```env
   APP_ENV=dev          # Valeurs possibles : dev, prod
   APP_DEBUG=true       # true pour activer le debug, false pour le désactiver
   CORS_ORIGIN=*        # En production, mettez votre domaine : https://votredomaine.com
   ```

## Environnements

### Mode Développement (dev)
- Affichage complet des erreurs
- Logs détaillés
- CORS ouvert à tous les domaines
- Configuration pour debug

**Configuration dans `.env` :**
```env
APP_ENV=dev
APP_DEBUG=true
CORS_ORIGIN=*
```

### Mode Production (prod)
- Erreurs masquées (pour la sécurité)
- Logs des erreurs uniquement
- CORS restreint à votre domaine
- Configuration optimisée

**Configuration dans `.env` :**
```env
APP_ENV=prod
APP_DEBUG=false
CORS_ORIGIN=https://votredomaine.com
```

## Fonctionnalités

### Logging
Les erreurs sont automatiquement loguées dans le fichier `logs/app.log` avec :
- Date et heure
- Environnement (dev/prod)
- Message d'erreur
- Contexte (données JSON)

Exemple de log :
```
[2025-10-20 14:30:15] dev - Erreur upload {"error":1,"filename":"photo.jpg"}
```

### Configuration centralisée
Toutes les configurations sont centralisées dans [config.php](config.php) :
- Chemins des fichiers
- Taille maximale des uploads
- Types de fichiers autorisés
- Configuration CORS
- Gestion des erreurs

### Sécurité
- Le fichier `.env` est ignoré par Git (via [.gitignore](.gitignore))
- Les erreurs sensibles ne sont pas affichées en production
- CORS configurable par environnement

## Structure des fichiers

```
pm/
├── config.php           # Configuration principale
├── .env                 # Variables d'environnement (NON versionné)
├── .env.example         # Exemple de configuration
├── .gitignore           # Fichiers à ignorer par Git
├── api.php              # API des événements (utilise config.php)
├── upload.php           # Upload d'images (utilise config.php)
├── images.php           # Gestion des images
├── admin.html           # Interface d'administration
├── index.html           # Page d'accueil
├── evenements.json      # Base de données JSON
├── images/              # Dossier des images uploadées
└── logs/                # Dossier des logs (créé automatiquement)
```

## Utilisation de la configuration dans le code

Pour utiliser la configuration dans vos fichiers PHP :

```php
<?php
// Charger la configuration
require_once __DIR__ . '/config.php';

// Récupérer une valeur de configuration
$uploadDir = config('upload_dir');
$maxSize = config('upload_max_size');

// Logger une erreur
logError('Message d\'erreur', ['contexte' => 'données']);

// Vérifier l'environnement
if (APP_ENV === 'dev') {
    // Code spécifique au développement
}

// Vérifier le mode debug
if (APP_DEBUG) {
    // Code de debug
}
```

## Déploiement en production

1. Uploadez tous les fichiers sur votre serveur
2. Copiez `.env.example` en `.env` sur le serveur
3. Configurez `.env` pour la production :
   ```env
   APP_ENV=prod
   APP_DEBUG=false
   CORS_ORIGIN=https://votredomaine.com
   ```
4. Assurez-vous que le dossier `logs/` est accessible en écriture
5. Vérifiez que `.env` n'est pas accessible publiquement

## Sécurité Apache (.htaccess)

Pour protéger vos fichiers sensibles, créez un fichier `.htaccess` :

```apache
# Protéger le fichier .env
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

# Protéger les logs
<Files "*.log">
    Order allow,deny
    Deny from all
</Files>
```

## Scripts utiles

### Démarrage rapide en développement

```bash
./start-dev.sh
```

Ce script va :
- Vérifier/créer le fichier `.env`
- Créer le dossier `logs/` si nécessaire
- Afficher la configuration actuelle
- Démarrer le serveur PHP sur http://localhost:8000

### Changement d'environnement

```bash
# Basculer en mode développement
./switch-env.sh dev

# Basculer en mode production
./switch-env.sh prod

# Voir la configuration actuelle
./switch-env.sh
```

## Sécurité Apache (.htaccess)

Un fichier [.htaccess](.htaccess) est fourni pour protéger vos fichiers sensibles :
- Fichiers `.env` et de configuration
- Logs
- Scripts shell
- Fichiers Git

Ce fichier sera automatiquement utilisé si votre serveur web est Apache avec `mod_rewrite` activé.

## URLs de développement

Une fois le serveur lancé avec `./start-dev.sh` :

- **Page principale** : http://localhost:8000/index.html
- **Administration** : http://localhost:8000/admin.html
- **API événements** : http://localhost:8000/api.php
- **Upload images** : http://localhost:8000/upload.php

## Support

Pour toute question ou problème, consultez les logs dans `logs/app.log`.
