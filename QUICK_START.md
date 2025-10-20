# Démarrage Rapide

## En local (développement)

1. **Démarrer le serveur**
   ```bash
   ./start-dev.sh
   ```

2. **Ouvrir dans le navigateur**
   - Page principale : http://localhost:8000/index.html
   - Administration : http://localhost:8000/admin.html

3. **Arrêter le serveur**
   - Appuyez sur `Ctrl+C` dans le terminal

## Configuration rapide

### Vérifier l'environnement actuel
```bash
./switch-env.sh
```

### Passer en mode développement
```bash
./switch-env.sh dev
```

### Passer en mode production
```bash
./switch-env.sh prod
```

## Fichiers importants

- **[.env](.env)** : Configuration de l'environnement (ne PAS versionner)
- **[config.php](config.php)** : Configuration centralisée de l'application
- **[logs/app.log](logs/)** : Logs de l'application

## En cas de problème

1. Vérifier que le port 8000 n'est pas déjà utilisé :
   ```bash
   lsof -i :8000
   ```

2. Consulter les logs :
   ```bash
   cat logs/app.log
   ```

3. Vérifier la configuration :
   ```bash
   cat .env
   ```

## Déploiement en production

1. Uploadez tous les fichiers sur votre serveur
2. Copiez `.env.example` en `.env`
3. Éditez `.env` et changez :
   ```env
   APP_ENV=prod
   APP_DEBUG=false
   CORS_ORIGIN=https://votredomaine.com
   ```
4. Vérifiez que le fichier `.htaccess` est actif
5. Testez votre application

## Structure des URLs

- `/` ou `/index.html` - Page d'accueil (affichage des événements)
- `/admin.html` - Interface d'administration
- `/api.php` - API REST pour les événements
- `/upload.php` - Upload et suppression d'images
- `/images.php` - Liste des images disponibles

## Commandes utiles

```bash
# Voir les processus PHP en cours
ps aux | grep php

# Arrêter un serveur PHP sur le port 8000
kill $(lsof -ti:8000)

# Voir les dernières lignes des logs
tail -f logs/app.log

# Vider les logs
> logs/app.log
```
