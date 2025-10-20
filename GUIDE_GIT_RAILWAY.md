# 📚 Guide Complet : Git + Railway (Pas à Pas)

## 🎯 Ce que vous allez faire

1. ✅ Initialiser Git sur votre projet
2. ✅ Créer un compte GitHub
3. ✅ Pousser votre code sur GitHub
4. ✅ Déployer automatiquement sur Railway
5. ✅ Avoir votre site en ligne avec HTTPS

**Durée totale : 15 minutes maximum**

---

## 📖 PARTIE 1 : Préparer Git

### Étape 1.1 : Vérifier que Git est installé

Ouvrez un Terminal et tapez :

```bash
git --version
```

✅ **Si vous voyez** : `git version 2.x.x` → Git est installé, passez à l'étape 1.2
❌ **Si erreur** : Installez Git sur https://git-scm.com/download/mac

### Étape 1.2 : Configurer Git (première fois uniquement)

```bash
git config --global user.name "Votre Nom"
git config --global user.email "votre@email.com"
```

Exemple :
```bash
git config --global user.name "Cyril Cincet"
git config --global user.email "cyril@cincet.net"
```

### Étape 1.3 : Initialiser Git dans votre projet

```bash
cd /Users/cyril.cincet/Nextcloud4/SitesWeb/pm
git init
```

✅ **Résultat attendu** : `Initialized empty Git repository in /Users/cyril.cincet/Nextcloud4/SitesWeb/pm/.git/`

### Étape 1.4 : Ajouter tous les fichiers

```bash
git add .
```

💡 **Le point `.` signifie "tous les fichiers"**

### Étape 1.5 : Créer votre premier commit

```bash
git commit -m "Initial commit - Application PM"
```

✅ **Résultat attendu** : Un message indiquant que X fichiers ont été créés

---

## 📖 PARTIE 2 : Créer un compte GitHub

### Étape 2.1 : Créer un compte

1. Allez sur https://github.com
2. Cliquez sur **"Sign up"** (en haut à droite)
3. Remplissez :
   - Username : choisissez un nom d'utilisateur
   - Email : votre email
   - Password : choisissez un mot de passe
4. Validez l'email que GitHub vous envoie

### Étape 2.2 : Créer un nouveau repository

1. Une fois connecté, cliquez sur le **+** en haut à droite
2. Sélectionnez **"New repository"**
3. Remplissez :
   - **Repository name** : `pm-events` (ou autre nom)
   - **Description** : "Application de gestion d'événements"
   - **Public** ou **Private** : choisissez (Private = personne ne voit votre code)
   - ❌ **NE COCHEZ PAS** "Initialize this repository with a README"
4. Cliquez sur **"Create repository"**

✅ **Vous arrivez sur une page avec des instructions** - RESTEZ SUR CETTE PAGE !

---

## 📖 PARTIE 3 : Pousser votre code sur GitHub

### Étape 3.1 : Copier votre URL de repository

Sur la page GitHub, vous voyez une URL qui ressemble à :
```
https://github.com/VOTRE_USERNAME/pm-events.git
```

**Copiez cette URL** (il y a un bouton pour copier)

### Étape 3.2 : Connecter votre projet local à GitHub

Dans votre Terminal, tapez (en remplaçant par VOTRE URL) :

```bash
git remote add origin https://github.com/VOTRE_USERNAME/pm-events.git
```

Exemple :
```bash
git remote add origin https://github.com/cyrilcincet/pm-events.git
```

### Étape 3.3 : Renommer la branche en "main"

```bash
git branch -M main
```

### Étape 3.4 : Pousser votre code

```bash
git push -u origin main
```

🔐 **GitHub va vous demander de vous authentifier :**

- **Username** : votre username GitHub
- **Password** : ⚠️ **PAS votre mot de passe !**

**Vous devez créer un "Personal Access Token" :**

1. Allez sur https://github.com/settings/tokens
2. Cliquez "Generate new token" → "Generate new token (classic)"
3. Donnez un nom : "PM Deployment"
4. Cochez : `repo` (tout)
5. Cliquez "Generate token" en bas
6. **COPIEZ LE TOKEN** (vous ne le reverrez plus !)
7. Utilisez ce token comme mot de passe

✅ **Résultat attendu** : Vos fichiers sont poussés sur GitHub !

### Étape 3.5 : Vérifier sur GitHub

Rafraîchissez la page GitHub → Vous devez voir tous vos fichiers !

---

## 📖 PARTIE 4 : Déployer sur Railway

### Étape 4.1 : Créer un compte Railway

1. Allez sur https://railway.app
2. Cliquez **"Start a New Project"**
3. Cliquez **"Login With GitHub"**
4. Autorisez Railway à accéder à GitHub

### Étape 4.2 : Créer un nouveau projet

1. Cliquez **"Deploy from GitHub repo"**
2. Si demandé, autorisez Railway à accéder à vos repositories
3. Sélectionnez votre repository **"pm-events"**

✅ Railway commence automatiquement à déployer !

### Étape 4.3 : Ajouter les variables d'environnement

1. Cliquez sur votre projet
2. Cliquez sur l'onglet **"Variables"**
3. Cliquez **"+ New Variable"**

Ajoutez ces 3 variables une par une :

**Variable 1 :**
- Name : `APP_ENV`
- Value : `prod`

**Variable 2 :**
- Name : `APP_DEBUG`
- Value : `false`

**Variable 3 :**
- Name : `CORS_ORIGIN`
- Value : `*` (vous changerez après avec votre vraie URL)

4. Cliquez **"Deploy"** (Railway va redémarrer)

### Étape 4.4 : Obtenir votre URL

1. Cliquez sur l'onglet **"Settings"**
2. Cherchez la section **"Domains"**
3. Cliquez **"Generate Domain"**
4. Railway génère une URL type : `https://pm-events-production.up.railway.app`

✅ **Copiez cette URL !**

### Étape 4.5 : Mettre à jour CORS_ORIGIN

1. Retournez dans **"Variables"**
2. Modifiez `CORS_ORIGIN`
3. Mettez votre URL Railway : `https://pm-events-production.up.railway.app`
4. Sauvegardez

### Étape 4.6 : Créer le dossier logs et evenements.json

Railway va créer automatiquement ces fichiers au premier démarrage.

---

## 📖 PARTIE 5 : Tester votre application

### Étape 5.1 : Ouvrir votre site

Ouvrez votre navigateur et allez sur :
```
https://votre-app.up.railway.app/index.html
```

✅ **Votre site doit s'afficher !**

### Étape 5.2 : Tester l'admin

```
https://votre-app.up.railway.app/admin.html
```

✅ **L'interface admin doit fonctionner !**

### Étape 5.3 : Vérifier les logs

Sur Railway, cliquez sur **"View Logs"** pour voir si tout fonctionne.

---

## 🔄 PARTIE 6 : Mettre à jour votre site (plus tard)

Chaque fois que vous modifiez votre code :

```bash
# 1. Sauvegarder vos modifications
git add .
git commit -m "Description de vos changements"

# 2. Pousser sur GitHub
git push

# 3. Railway déploie automatiquement !
```

**C'est tout !** Railway détecte automatiquement les changements et redéploie.

---

## 📱 BONUS : Connecter votre domaine cincet.net

### Sur Railway :

1. Allez dans **Settings** → **Domains**
2. Cliquez **"Custom Domain"**
3. Entrez : `pm.cincet.net` (ou `www.cincet.net`)
4. Railway vous donne une adresse CNAME

### Chez votre registrar de domaine :

1. Connectez-vous là où vous avez acheté cincet.net
2. Allez dans la gestion DNS
3. Ajoutez un enregistrement CNAME :
   - Type : CNAME
   - Name : pm (ou www)
   - Value : l'adresse donnée par Railway
4. Sauvegardez

⏰ **Attendez 5-30 minutes** pour la propagation DNS

✅ **Résultat** : Votre site sera accessible sur `https://pm.cincet.net` !

---

## ❓ Questions fréquentes

### Q: Je n'ai pas de carte bancaire, Railway va me demander de payer ?

**R:** Non ! Railway offre 5$ de crédit gratuit par mois. Votre application consomme environ 1-2$/mois. Pas besoin de carte.

### Q: Et si je veux changer d'hébergeur plus tard ?

**R:** Facile ! Votre code est sur GitHub. Vous pouvez déployer sur Render, Vercel, ou n'importe où en 2 clics.

### Q: Mes données evenements.json seront perdues ?

**R:** Non, elles sont dans Git. Mais pour une vraie app, utilisez une base de données (PostgreSQL gratuit sur Railway).

### Q: Je peux revenir sur OVH ?

**R:** Oui, votre code est sur GitHub. Vous pouvez déployer n'importe où.

---

## 🆘 En cas de problème

### Erreur : "git: command not found"

➡️ Installez Git : https://git-scm.com/download/mac

### Erreur lors du push GitHub

➡️ Vérifiez que vous utilisez bien le Personal Access Token, pas votre mot de passe

### Railway ne démarre pas

➡️ Vérifiez les logs sur Railway → Souvent c'est un problème de variable d'environnement

### L'admin affiche "Erreur de chargement"

➡️ Vérifiez que CORS_ORIGIN est bien configuré avec votre URL Railway

---

## ✅ Checklist finale

- [ ] Git configuré et initialisé
- [ ] Code poussé sur GitHub
- [ ] Projet créé sur Railway
- [ ] Variables d'environnement configurées
- [ ] URL générée par Railway
- [ ] Site accessible et fonctionnel
- [ ] Admin fonctionne

**Félicitations ! Votre application est en ligne ! 🎉**

---

## 📞 Besoin d'aide ?

Si vous êtes bloqué sur une étape, dites-moi où vous en êtes et je vous aide !
