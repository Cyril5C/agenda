# Guide de déploiement

## Déploiement automatique avec refresh des clients

Ce projet dispose d'un système de détection automatique de nouvelle version qui force le rechargement des pages des utilisateurs après un déploiement.

### Comment déployer

Au lieu d'utiliser `git push` directement, utilisez le script de déploiement :

```bash
./deploy.sh
```

Ce script va :
1. ✅ Mettre à jour le fichier `version.json` avec un nouveau timestamp
2. ✅ Commiter automatiquement la nouvelle version
3. ✅ Pousser les changements sur GitHub
4. ✅ Railway va automatiquement déployer la nouvelle version
5. ✅ Les utilisateurs seront automatiquement rechargés dans les 5 minutes

### Fonctionnement

- La page vérifie la version toutes les **5 minutes**
- Si une nouvelle version est détectée, la page se recharge automatiquement
- Les logs de la console indiquent quand une nouvelle version est détectée

### Déploiement manuel (sans le script)

Si vous voulez déployer sans mettre à jour la version automatiquement :

```bash
git add .
git commit -m "Votre message"
git push
```

Dans ce cas, les utilisateurs devront rafraîchir manuellement leur page.

### Ajuster la fréquence de vérification

Pour changer la fréquence de vérification (actuellement 5 minutes), modifiez cette ligne dans `index.html` :

```javascript
// Vérifier la version toutes les 5 minutes (300000 ms)
setInterval(checkVersion, 300000);
```

Changez `300000` pour :
- `60000` = 1 minute
- `120000` = 2 minutes
- `600000` = 10 minutes
