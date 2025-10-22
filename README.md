# Application Agenda PM

Application web de gestion d'événements avec calendrier, déployée sur Railway.app.

## Démarrage Rapide

### En local
```bash
./start-dev.sh
```
Puis ouvrez http://localhost:8000

### En production
L'application est déployée automatiquement sur Railway : https://votre-url.railway.app

## Configuration

### 1. Fichiers de configuration

- **[config.php](config.php)** : Fichier principal de configuration
- **[.env](.env)** : Configuration locale (non versionné)
- **[.env.example](.env.example)** : Template de configuration

### 2. Installation

1. Copiez `.env.example` en `.env` :
   ```bash
   cp .env.example .env
   ```

2. Initialisez les fichiers de données :
   ```bash
   ./init-data.sh
   ```
   Ce script crée les fichiers JSON nécessaires (`evenements.json`, `images.json`, `infos.json`) à partir des exemples.

3. Éditez `.env` selon vos besoins :
   ```env
   APP_ENV=dev
   APP_DEBUG=true
   CORS_ORIGIN=*
   ```

## Gestion des Images

Les images fonctionnent par **URL externe**. Lors de la création d'un événement, vous pouvez coller l'URL d'une image hébergée ailleurs :
- Votre propre serveur
- Services d'hébergement d'images (Imgur, etc.)
- N'importe quelle URL d'image publique

**Exemple** : `https://example.com/mon-image.jpg`

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

## Gestion des données

⚠️ **Important** : Les fichiers de données JSON ne sont **pas versionnés** dans Git pour éviter les conflits entre développement et production.

### Fichiers de données (NON versionnés)
- `evenements.json` - Événements de l'agenda
- `images.json` - Liste des images de la galerie
- `infos.json` - Informations diverses affichées

### Fichiers d'exemple (versionnés)
- `evenements.json.example` - Structure de base des événements
- `images.json.example` - Structure de base des images
- `infos.json.example` - Structure de base des informations

### Script d'initialisation
Le script `init-data.sh` copie automatiquement les fichiers exemple lors de la première installation :
```bash
./init-data.sh
```

## Structure des fichiers

```
pm/
├── config.php                  # Configuration principale
├── .env                        # Variables d'environnement (NON versionné)
├── .env.example                # Exemple de configuration
├── .gitignore                  # Fichiers à ignorer par Git
├── init-data.sh                # Script d'initialisation des données
├── api.php                     # API des événements
├── infos.php                   # API des informations diverses
├── images.php                  # API des images
├── admin.html                  # Interface d'administration
├── index.html                  # Page d'accueil
├── evenements.json             # Données événements (NON versionné)
├── evenements.json.example     # Exemple de données événements
├── images.json                 # Données images (NON versionné)
├── images.json.example         # Exemple de données images
├── infos.json                  # Données informations (NON versionné)
├── infos.json.example          # Exemple de données informations
└── logs/                       # Dossier des logs (créé automatiquement)
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
