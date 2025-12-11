# Configuration CalDAV - Guide V2

## 🔧 Configuration requise

### 1. Créer un calendrier dans Nextcloud

1. Connecte-toi à [https://ncloud9.zaclys.com](https://ncloud9.zaclys.com)
2. Va dans l'application **Calendrier**
3. Crée un nouveau calendrier (ex: `agenda-papou-mamine`)
4. Note le nom exact du calendrier

### 2. Créer un mot de passe d'application (Recommandé)

Pour plus de sécurité, crée un mot de passe d'application au lieu d'utiliser ton mot de passe principal :

1. Va dans **Paramètres** → **Sécurité**
2. Section **Mots de passe d'application**
3. Entre un nom : `Agenda Planning`
4. Clique sur **Créer un nouveau mot de passe d'application**
5. Copie le mot de passe généré (tu ne pourras plus le revoir !)

### 3. Configurer les variables d'environnement

Crée un fichier `.env` à la racine du projet (ou `.env.local` pour le local) :

```bash
# Configuration CalDAV
CALDAV_URL=https://ncloud9.zaclys.com/remote.php/dav
CALDAV_USERNAME=ton_nom_utilisateur
CALDAV_PASSWORD=ton_mot_de_passe_application
CALDAV_CALENDAR=agenda-papou-mamine
```

Remplace :
- `ton_nom_utilisateur` : ton nom d'utilisateur Nextcloud
- `ton_mot_de_passe_application` : le mot de passe d'application créé à l'étape 2
- `agenda-papou-mamine` : le nom exact de ton calendrier

### 4. Tester la connexion

```bash
php test-caldav.php
```

Tu devrais voir :
```
✅ Client CalDAV initialisé
✅ 0 événement(s) trouvé(s)
✅ Événement créé avec UID: event-xxx
✅ Événement supprimé
✨ Tests terminés!
```

## 🚀 Sur Railway (Production)

1. Va dans ton projet Railway → **Variables**
2. Ajoute ces variables :
   - `CALDAV_URL` : `https://ncloud9.zaclys.com/remote.php/dav`
   - `CALDAV_USERNAME` : ton username
   - `CALDAV_PASSWORD` : ton mot de passe d'application
   - `CALDAV_CALENDAR` : le nom de ton calendrier
3. Railway va redémarrer automatiquement

## 🔍 Dépannage

### Erreur "Configuration CalDAV incomplète"
→ Vérifie que toutes les variables sont définies dans `.env`

### Erreur 401 Unauthorized
→ Vérifie ton username et password
→ Assure-toi d'utiliser un mot de passe d'application

### Erreur 404 Not Found
→ Vérifie le nom du calendrier (sensible à la casse)
→ Assure-toi que le calendrier existe dans Nextcloud

### Connexion timeout
→ Vérifie que l'URL `https://ncloud9.zaclys.com` est accessible

## 📖 Structure CalDAV Nextcloud

Les événements sont stockés dans :
```
https://ncloud9.zaclys.com/remote.php/dav/calendars/{username}/{calendar-name}/
```

Chaque événement est un fichier `.ics` :
```
https://ncloud9.zaclys.com/remote.php/dav/calendars/{username}/{calendar-name}/event-xxx.ics
```

## 🔐 Sécurité

- ✅ **Utilise TOUJOURS un mot de passe d'application**, jamais ton mot de passe principal
- ✅ Le fichier `.env` est dans `.gitignore` (ne sera jamais commité)
- ✅ Sur Railway, les variables sont sécurisées et chiffrées
- ✅ La connexion utilise HTTPS (chiffrement SSL/TLS)

## 🎯 Prochaines étapes

Une fois le test réussi :
1. L'API sera adaptée pour utiliser CalDAV
2. L'interface admin permettra de créer/modifier/supprimer des événements
3. Les événements seront synchronisés avec ton calendrier Nextcloud
4. Tu pourras consulter/modifier depuis n'importe quelle app calendrier !
