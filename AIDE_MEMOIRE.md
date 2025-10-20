# 📝 Aide-Mémoire : Déploiement Railway en 3 étapes

## 🚀 Version Ultra-Rapide

### Étape 1 : Initialiser Git (1 minute)

```bash
./init-git.sh
```

Le script vous guide automatiquement !

### Étape 2 : Pousser sur GitHub (3 minutes)

1. **Créez un repo sur GitHub** : https://github.com/new
   - Nom : `pm-events`
   - NE COCHEZ RIEN

2. **Connectez votre projet** (remplacez VOTRE_USERNAME) :
   ```bash
   git remote add origin https://github.com/VOTRE_USERNAME/pm-events.git
   git branch -M main
   git push -u origin main
   ```

3. **Token GitHub** : https://github.com/settings/tokens
   - Generate new token (classic)
   - Cochez `repo`
   - Copiez le token → utilisez-le comme mot de passe

### Étape 3 : Déployer sur Railway (2 minutes)

1. **Railway** : https://railway.app
2. **Login with GitHub**
3. **Deploy from GitHub repo** → Sélectionnez `pm-events`
4. **Variables** → Ajoutez :
   - `APP_ENV` = `prod`
   - `APP_DEBUG` = `false`
   - `CORS_ORIGIN` = `*`
5. **Settings** → **Generate Domain**

✅ **C'est en ligne !**

---

## 📚 Documentation complète

- **Guide détaillé** : [GUIDE_GIT_RAILWAY.md](GUIDE_GIT_RAILWAY.md)
- **Railway** : [DEPLOIEMENT_RAILWAY.md](DEPLOIEMENT_RAILWAY.md)

---

## 🔄 Mettre à jour le site (après modifications)

```bash
git add .
git commit -m "Description de vos changements"
git push
```

Railway redéploie automatiquement en 30 secondes !

---

## 🆘 Problèmes courants

### "git: command not found"
➡️ Installez Git : https://git-scm.com/download/mac

### Erreur lors du push GitHub
➡️ Utilisez le **Personal Access Token**, pas le mot de passe

### Railway ne démarre pas
➡️ Vérifiez les **logs** dans Railway

---

## 💰 Prix

- Railway : **Gratuit** (5$/mois de crédit inclus)
- Votre app consomme : ~1-2$/mois
- **Pas de carte bancaire nécessaire**

---

## 🎯 URLs importantes

- **GitHub** : https://github.com
- **Railway** : https://railway.app
- **Token GitHub** : https://github.com/settings/tokens
- **Guide complet** : [GUIDE_GIT_RAILWAY.md](GUIDE_GIT_RAILWAY.md)

---

## ✅ Avantages Railway vs OVH

| Critère | Railway | OVH actuel |
|---------|---------|------------|
| Déploiement | 5 min | 2 heures |
| HTTPS | ✅ Auto | ❌ Erreurs |
| Erreurs 500 | ❌ Non | ✅ Oui |
| Prix | 0-5$/mois | 3-10€/mois |
| Facilité | ⭐⭐⭐⭐⭐ | ⭐⭐ |

**Résultat : Railway est plus simple, plus rapide et fonctionne !**
