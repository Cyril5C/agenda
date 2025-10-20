# Configuration Cloudinary - Guide Complet

## 1. Créer un compte Cloudinary (GRATUIT)

### Étape 1 : Inscription
1. Allez sur https://cloudinary.com/users/register_free
2. Remplissez le formulaire :
   - Email
   - Mot de passe
   - Cloud name (choisissez un nom unique, ex: `agenda-pm`)
3. Cliquez sur "Create Account"
4. Confirmez votre email

### Étape 2 : Récupérer vos identifiants
1. Une fois connecté, allez sur le **Dashboard**
2. Vous verrez une section "Account Details" avec :
   ```
   Cloud name: votre-cloud-name
   API Key: 123456789012345
   API Secret: abcdefghijklmnopqrstuvwxyz123
   ```
3. **Notez ces 3 informations** (vous en aurez besoin)

## 2. Configurer Railway avec Cloudinary

### Étape 1 : Ajouter les variables d'environnement sur Railway
1. Allez sur https://railway.app
2. Sélectionnez votre projet `pm`
3. Cliquez sur l'onglet **"Variables"**
4. Ajoutez ces 3 variables :
   ```
   CLOUDINARY_CLOUD_NAME = votre-cloud-name
   CLOUDINARY_API_KEY = votre-api-key
   CLOUDINARY_API_SECRET = votre-api-secret
   ```
5. Cliquez sur "Add" pour chaque variable
6. Railway va automatiquement redéployer votre application

### Étape 2 : Configurer en local
1. Ouvrez le fichier `.env` (à la racine du projet)
2. Ajoutez ces lignes :
   ```
   CLOUDINARY_CLOUD_NAME=votre-cloud-name
   CLOUDINARY_API_KEY=votre-api-key
   CLOUDINARY_API_SECRET=votre-api-secret
   ```
3. Sauvegardez le fichier

## 3. Plan gratuit Cloudinary

Le plan gratuit de Cloudinary offre :
- **25 crédits gratuits par mois**
- Jusqu'à **25 000 transformations**
- **25 GB de stockage**
- **25 GB de bande passante**

C'est largement suffisant pour votre application d'agenda !

## 4. Comment ça fonctionne

### Avant (système de fichiers local) :
```
User -> upload.php -> sauvegarde dans /images/photo.jpg
     -> images.php?nom=photo.jpg -> lit /images/photo.jpg
```

### Après (Cloudinary) :
```
User -> upload.php -> envoie à Cloudinary -> retourne URL
     -> images.php?nom=photo.jpg -> redirige vers URL Cloudinary
```

### Avantages :
- Les images persistent même quand Railway redémarre
- CDN mondial rapide
- Optimisation automatique des images
- Transformations d'images (redimensionnement, recadrage, etc.)

## 5. Vérifier que ça fonctionne

### Test en local :
1. Lancez le serveur : `./start-dev.sh`
2. Ouvrez l'admin : http://localhost:8000/admin.html
3. Créez un événement avec une image
4. Vérifiez que l'image s'affiche

### Test sur Railway :
1. Allez sur votre URL Railway
2. Créez un événement avec une image
3. Vérifiez que l'image s'affiche
4. Allez sur https://console.cloudinary.com/console/media_library
5. Vous devriez voir votre image uploadée !

## 6. Résolution de problèmes

### Erreur "Invalid credentials"
- Vérifiez que les variables d'environnement sont bien configurées
- Sur Railway : vérifiez l'onglet "Variables"
- En local : vérifiez le fichier `.env`

### L'image ne s'affiche pas
- Ouvrez la console du navigateur (F12)
- Regardez les erreurs réseau
- Vérifiez que l'URL Cloudinary est bien formée

### "Upload failed"
- Vérifiez votre quota Cloudinary (Dashboard)
- Vérifiez que le fichier fait moins de 10 MB
- Vérifiez le format (jpg, png, gif, webp)

## 7. Prochaines étapes

Une fois Cloudinary configuré :
1. Les nouvelles images seront automatiquement uploadées sur Cloudinary
2. Les anciennes images locales continueront de fonctionner
3. Vous pouvez migrer les anciennes images manuellement si besoin

---

**Besoin d'aide ?**
- Documentation Cloudinary : https://cloudinary.com/documentation
- Support Cloudinary : https://support.cloudinary.com
