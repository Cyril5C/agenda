# Dépannage - Erreur 500 en Production

## 🚨 Erreur: "Erreur de chargement des événements: Erreur HTTP: 500"

Cette erreur signifie que le serveur PHP rencontre une erreur lors de l'exécution de `api.php`.

## 🔍 Diagnostic

### Étape 0 : Découvrir les chemins du serveur (première fois seulement)

**Si c'est votre premier déploiement sur OVH**, uploadez d'abord le script d'information :

```bash
./upload-info-serveur.sh
```

Puis ouvrez : `https://papoumamine.cincet.net/pm/info-serveur.php`

Ce script vous donnera :
- Les chemins exacts du serveur
- Les permissions des dossiers
- Un test d'écriture de fichiers

**Note :** `__DIR__` dans config.php fonctionne automatiquement et pointe vers le bon chemin. Vous n'avez normalement pas besoin de le modifier.

### Étape 1 : Uploader le script de diagnostic

1. **Uploadez manuellement** le fichier `test-config.php` sur votre serveur :
   ```bash
   # Via lftp
   lftp -u "fredericbn","Kt8pv76TIU" ftp.cluster003.hosting.ovh.net << EOF
   set ssl:verify-certificate no
   cd pm
   put test-config.php
   bye
   EOF
   ```

   Ou via FTP classique, uploadez `test-config.php` dans le dossier `pm/`

2. **Accédez au script** dans votre navigateur :
   ```
   https://votredomaine.com/pm/test-config.php
   ```

3. **Lisez les résultats** :
   - ✅ = OK
   - ❌ = Problème à corriger
   - ⚠️ = Avertissement

### Étape 2 : Identifier le problème

Les causes les plus fréquentes :

#### A. Le fichier `.env` n'existe pas ⚠️

**Solution :** Créez le fichier `.env` sur le serveur

Via FTP/SSH, créez le fichier `pm/.env` avec ce contenu :

```env
APP_ENV=prod
APP_DEBUG=false
CORS_ORIGIN=https://votredomaine.com
```

**Commande SSH :**
```bash
cat > pm/.env << 'EOF'
APP_ENV=prod
APP_DEBUG=false
CORS_ORIGIN=https://votredomaine.com
EOF
chmod 600 pm/.env
```

#### B. Le fichier `config.php` n'existe pas ❌

**Solution :** Redéployez avec le script

```bash
./deploy-prod.sh
```

Le fichier `config.php` sera envoyé.

#### C. Le fichier `evenements.json` n'existe pas ⚠️

**Solution :** Créez un fichier vide

**Via SSH :**
```bash
echo "[]" > pm/evenements.json
chmod 644 pm/evenements.json
```

**Via FTP :** Créez un fichier `evenements.json` avec le contenu : `[]`

#### D. Le dossier `logs/` n'existe pas ou n'est pas accessible ⚠️

**Solution :** Créez le dossier avec les bonnes permissions

**Via SSH :**
```bash
mkdir -p pm/logs
chmod 755 pm/logs
```

**Via FTP :** Créez simplement le dossier `logs/`

#### E. Erreur PHP dans le code ❌

Le script `test-config.php` affichera l'erreur exacte.

Vérifiez :
- Version de PHP (minimum 7.4 recommandé)
- Extensions PHP nécessaires activées
- Permissions des fichiers

## 🔧 Solutions rapides

### Solution 1 : Tout réinitialiser

```bash
# 1. Redéployer tous les fichiers
./deploy-prod.sh

# 2. Créer .env sur le serveur (via SSH ou FTP)
cat > pm/.env << 'EOF'
APP_ENV=prod
APP_DEBUG=false
CORS_ORIGIN=https://votredomaine.com
EOF

# 3. Créer le dossier logs
mkdir -p pm/logs
chmod 755 pm/logs

# 4. Créer evenements.json s'il n'existe pas
echo "[]" > pm/evenements.json
chmod 644 pm/evenements.json

# 5. Vérifier les permissions
chmod 644 pm/*.php pm/*.html
chmod 755 pm/images
```

### Solution 2 : Activer le mode debug temporairement

**⚠️ À utiliser uniquement pour le débogage, pas en production !**

Dans `pm/.env` sur le serveur, changez temporairement :

```env
APP_ENV=prod
APP_DEBUG=true  # ← Changé en true pour voir les erreurs
CORS_ORIGIN=https://votredomaine.com
```

Puis rechargez la page admin. L'erreur PHP complète s'affichera.

**N'oubliez pas de remettre `APP_DEBUG=false` après !**

## 📋 Checklist complète

- [ ] Fichier `config.php` uploadé
- [ ] Fichier `api.php` uploadé
- [ ] Fichier `upload.php` uploadé
- [ ] Fichier `images.php` uploadé
- [ ] Fichier `.htaccess` uploadé
- [ ] Fichier `.env` créé manuellement sur le serveur
- [ ] Dossier `logs/` créé (chmod 755)
- [ ] Fichier `evenements.json` existe (ou vide : `[]`)
- [ ] Dossier `images/` existe (chmod 755)
- [ ] Permissions correctes sur tous les fichiers

## 🧪 Tests après correction

### 1. Test API direct

```bash
curl https://votredomaine.com/pm/api.php
```

**Résultat attendu :** Un tableau JSON (peut être vide : `[]`)

### 2. Test protection .env

```bash
curl https://votredomaine.com/pm/.env
```

**Résultat attendu :** Erreur 403 Forbidden

### 3. Test interface admin

Ouvrez : `https://votredomaine.com/pm/admin.html`

**Résultat attendu :** Liste des événements (même vide)

## 🆘 Si le problème persiste

1. **Vérifiez les logs PHP du serveur** (si vous y avez accès)

2. **Contactez votre hébergeur** pour vérifier :
   - Version PHP (8.0+ recommandé, 7.4 minimum)
   - Extensions PHP actives
   - Permissions des fichiers

3. **Testez en local** :
   ```bash
   ./start-dev.sh
   ```
   Si ça fonctionne en local mais pas en prod, c'est un problème de configuration serveur.

## 🔐 Sécurité

**Après le diagnostic, SUPPRIMEZ le fichier test-config.php du serveur !**

```bash
# Via SSH
rm pm/test-config.php

# Via FTP
Supprimez manuellement test-config.php
```

## 📞 Informations utiles

- **Hébergeur :** OVH (cluster003)
- **FTP :** ftp.cluster003.hosting.ovh.net
- **Dossier :** pm/
- **Fichiers critiques :** config.php, api.php, .env

## 💡 Prévention

Pour éviter ces problèmes à l'avenir :

1. **Toujours tester localement** avant de déployer
2. **Utilisez le script de test** : `./deploy-test.sh`
3. **Vérifiez la checklist** avant chaque déploiement
4. **Gardez une sauvegarde** de votre configuration
