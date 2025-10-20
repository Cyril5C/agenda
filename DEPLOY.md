# Guide de Déploiement en Production

## 🚨 Important : Sécurité

**Votre commande originale envoyait TOUT, y compris :**
- ❌ Le fichier `.env` (avec vos configurations locales)
- ❌ Les scripts de développement
- ❌ Les logs locaux
- ❌ Les fichiers système (.DS_Store, etc.)

**Maintenant, seuls les fichiers nécessaires sont envoyés en production.**

## 📋 Avant de déployer

### 1. Tester le déploiement (simulation)

```bash
./deploy-test.sh
```

Ce script vous montre :
- ✅ Les fichiers qui seront envoyés
- ❌ Les fichiers qui seront exclus

**Aucun fichier n'est réellement envoyé.**

### 2. Vérifier les fichiers exclus

Le fichier [.lftpignore](.lftpignore) contient la liste des exclusions :

```
.env                    # Votre config locale (NE JAMAIS envoyer)
.env.example           # Template
start-dev.sh           # Script de développement
switch-env.sh          # Script de développement
*.md                   # Documentation
.DS_Store              # Fichiers système
logs/                  # Logs locaux
*.sh                   # Tous les scripts
```

## 🚀 Déploiement

### Option 1 : Déploiement avec le script (RECOMMANDÉ)

```bash
./deploy-prod.sh
```

Le script va :
1. Vous demander confirmation
2. Afficher ce qui sera envoyé/exclu
3. Déployer sur OVH avec les exclusions
4. Vous rappeler les étapes post-déploiement

### Option 2 : Commande manuelle (si besoin)

```bash
lftp -u "fredericbn","Kt8pv76TIU" ftp.cluster003.hosting.ovh.net << EOF
set ssl:verify-certificate no
lcd /Users/cyril.cincet/Nextcloud4/SitesWeb/pm
cd pm
mirror -R --exclude-glob-from=.lftpignore --verbose . .
bye
EOF
```

## ⚙️ Configuration sur le serveur de production

### 1. Créer le fichier .env en production

**⚠️ IMPORTANT : Le fichier .env n'est PAS envoyé automatiquement !**

Vous devez le créer manuellement sur le serveur :

```bash
# Via SSH ou FTP, créez le fichier pm/.env avec ce contenu :

APP_ENV=prod
APP_DEBUG=false
CORS_ORIGIN=https://votredomaine.com
```

**Template disponible :** Consultez [.env.production](.env.production) pour le contenu complet.

### 2. Créer le dossier logs

```bash
# Via SSH :
mkdir -p pm/logs
chmod 755 pm/logs
```

Ou via FTP : créez simplement le dossier `logs/` dans `pm/`

### 3. Vérifier les permissions

```bash
# Fichiers : 644
chmod 644 pm/*.php pm/*.html pm/*.json

# Dossiers : 755
chmod 755 pm/images pm/logs

# Fichier .env : 600 (sécurisé)
chmod 600 pm/.env

# .htaccess : 644
chmod 644 pm/.htaccess
```

### 4. Vérifier .htaccess

Le fichier [.htaccess](.htaccess) protège automatiquement :
- Le fichier `.env`
- Les logs
- Les fichiers de config

Vérifiez qu'il est bien présent sur le serveur.

## 🧪 Tester la production

### 1. Vérifier que l'API fonctionne

```bash
curl https://votredomaine.com/pm/api.php
```

Devrait retourner la liste des événements au format JSON.

### 2. Vérifier que .env est protégé

```bash
curl https://votredomaine.com/pm/.env
```

Devrait retourner une erreur 403 Forbidden (si .htaccess est actif).

### 3. Vérifier les logs

Après quelques requêtes, vérifiez que le fichier `logs/app.log` se crée sur le serveur.

## 📊 Comparaison des commandes

### ❌ Ancienne commande (dangereuse)

```bash
mirror -R . .
# Envoie TOUT, y compris .env, logs, scripts, etc.
```

### ✅ Nouvelle commande (sécurisée)

```bash
mirror -R --exclude-glob-from=.lftpignore . .
# Envoie uniquement les fichiers nécessaires en production
```

## 🔄 Workflow recommandé

1. **Développement local**
   ```bash
   ./start-dev.sh
   # Travaillez en local
   ```

2. **Test du déploiement**
   ```bash
   ./deploy-test.sh
   # Vérifiez ce qui sera envoyé
   ```

3. **Déploiement**
   ```bash
   ./deploy-prod.sh
   # Déployez en production
   ```

4. **Configuration serveur**
   - Créez/vérifiez le fichier `.env` en prod
   - Créez le dossier `logs/`
   - Testez l'application

## 🆘 Dépannage

### Le fichier .env est accessible publiquement

Vérifiez que :
1. Le fichier `.htaccess` est présent
2. Votre hébergeur supporte `.htaccess` (Apache)
3. `mod_rewrite` est activé

### Les logs ne se créent pas

```bash
# Vérifier les permissions
chmod 755 pm/logs
```

### CORS bloque les requêtes

Vérifiez dans `pm/.env` :
```env
CORS_ORIGIN=https://votredomaine.com
```

Remplacez par votre vrai domaine.

## 📝 Checklist de déploiement

- [ ] Tester en local avec `./start-dev.sh`
- [ ] Simuler le déploiement avec `./deploy-test.sh`
- [ ] Vérifier les exclusions dans `.lftpignore`
- [ ] Déployer avec `./deploy-prod.sh`
- [ ] Créer le fichier `.env` sur le serveur (mode prod)
- [ ] Créer le dossier `logs/` sur le serveur
- [ ] Vérifier les permissions
- [ ] Tester l'API : `curl https://votredomaine.com/pm/api.php`
- [ ] Vérifier que `.env` est protégé (403 Forbidden)
- [ ] Tester l'interface web
- [ ] Vérifier les logs après quelques requêtes

## 🔐 Sécurité

**NE JAMAIS :**
- ❌ Envoyer le fichier `.env` local en production
- ❌ Mettre `APP_DEBUG=true` en production
- ❌ Laisser les logs accessibles publiquement
- ❌ Utiliser `CORS_ORIGIN=*` en production (sauf si nécessaire)

**TOUJOURS :**
- ✅ Créer un `.env` spécifique pour la production
- ✅ Utiliser `APP_ENV=prod` et `APP_DEBUG=false`
- ✅ Restreindre CORS à votre domaine
- ✅ Protéger les fichiers sensibles avec `.htaccess`
