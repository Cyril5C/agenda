# 🚀 Déploiement sur Railway.app

Railway.app est une plateforme moderne qui déploie automatiquement votre application depuis Git.

## ✅ Avantages

- ✅ Déploiement en 5 minutes
- ✅ HTTPS automatique
- ✅ Gratuit jusqu'à 5$/mois d'usage
- ✅ PHP 8.1 supporté
- ✅ Logs en temps réel
- ✅ Redémarrage automatique
- ✅ Pas de configuration serveur

## 📋 Étapes de déploiement

### 1. Créer un compte Railway

1. Allez sur https://railway.app
2. Cliquez sur "Start a New Project"
3. Connectez-vous avec GitHub

### 2. Initialiser Git (si pas déjà fait)

```bash
cd /Users/cyril.cincet/Nextcloud4/SitesWeb/pm
git init
git add .
git commit -m "Initial commit"
```

### 3. Créer un repo GitHub

1. Allez sur https://github.com/new
2. Créez un repo (par exemple `pm-events`)
3. Suivez les instructions pour pusher votre code :

```bash
git remote add origin https://github.com/VOTRE_USERNAME/pm-events.git
git branch -M main
git push -u origin main
```

### 4. Déployer sur Railway

1. Sur Railway, cliquez "Deploy from GitHub repo"
2. Sélectionnez votre repo `pm-events`
3. Railway détecte automatiquement PHP
4. Cliquez "Deploy"

### 5. Configurer les variables d'environnement

Dans Railway, allez dans "Variables" et ajoutez :

```
APP_ENV=prod
APP_DEBUG=false
CORS_ORIGIN=https://votre-app.railway.app
```

### 6. Obtenir votre URL

Railway génère automatiquement une URL comme :
`https://pm-events-production.up.railway.app`

Vous pouvez aussi connecter votre propre domaine !

## 🔧 Fichiers de configuration

Les fichiers suivants ont été créés pour Railway :

- `railway.json` - Configuration Railway
- `nixpacks.toml` - Configuration build

**Ils sont déjà configurés, rien à faire !**

## 💰 Prix

- **Gratuit** : 5$ de crédit/mois (largement suffisant pour votre app)
- **Payant** : Si vous dépassez, ~5-10$/mois
- **Pas de carte requise** pour commencer

## 🆚 Comparaison avec OVH

| Critère | Railway | OVH |
|---------|---------|-----|
| Facilité | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| Prix | 0-10$/mois | 3-10€/mois |
| Configuration | Automatique | Manuelle |
| HTTPS | Automatique | Manuel |
| Déploiement | Git push | FTP |
| Support PHP 8.1 | ✅ Oui | ✅ Oui |
| Problèmes | Aucun | Erreurs 500 |

## 📱 Alternative : Render.com

Si Railway ne vous convient pas, essayez Render.com :

1. https://render.com
2. "New Web Service"
3. Connectez votre repo GitHub
4. Sélectionnez "PHP"
5. Start Command : `php -S 0.0.0.0:$PORT`

## 🔄 Migration depuis OVH

Vos données sont déjà dans Git, donc :

1. ✅ Tous vos fichiers PHP sont prêts
2. ✅ `evenements.json` sera déployé automatiquement
3. ✅ Configuration `.env` via Railway Variables
4. ✅ Images uploadées seront sauvegardées

## ❓ Questions ?

**Q: Mes données evenements.json seront-elles perdues ?**
R: Non, elles sont versionnées dans Git. Vous pouvez aussi utiliser une base de données PostgreSQL gratuite sur Railway.

**Q: Puis-je utiliser mon domaine cincet.net ?**
R: Oui ! Railway permet de connecter un domaine personnalisé gratuitement.

**Q: Et les uploads d'images ?**
R: Sur Railway, utilisez un service de stockage comme Cloudinary (gratuit jusqu'à 25GB) ou AWS S3.

## 🎉 Résultat final

Après déploiement, vous aurez :

- ✅ Application en ligne : `https://votre-app.railway.app`
- ✅ HTTPS automatique et sécurisé
- ✅ Déploiement automatique à chaque `git push`
- ✅ Pas d'erreurs 500 mystérieuses
- ✅ Logs en temps réel pour debugger

**C'est simple, moderne et ça marche !**
