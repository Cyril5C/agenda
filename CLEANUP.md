# Nettoyage automatique des événements passés

## Fonctionnalité

Le script `cleanup-old-events.php` supprime automatiquement les événements avec une date passée, **tout en conservant les événements récurrents**.

## Comportement

- ✅ **Supprime** : Les événements avec une date < aujourd'hui
- ✅ **Conserve** : TOUS les événements récurrents (peu importe leur ancienneté)
- ✅ **Conserve** : Les événements avec une date >= aujourd'hui
- ✅ **Fonctionne** : Avec Gist ET fichiers locaux

## Installation

### 1. Tester manuellement

```bash
./setup-cleanup-cron.sh
```

Ce script va :
- Tester le nettoyage
- Afficher le résultat
- Vous donner la commande cron à ajouter

### 2. Configuration automatique (cron)

#### Sur le serveur de production

Ajoutez cette ligne à votre crontab (`crontab -e`) :

```cron
# Nettoyer les événements passés chaque jour à minuit
0 0 * * * cd /path/to/project && php cleanup-old-events.php >> logs/cleanup.log 2>&1
```

Remplacez `/path/to/project` par le chemin réel de votre projet.

#### Sur Railway

Railway ne supporte pas nativement les crons. Vous pouvez :

**Option 1 : Service externe (Recommandé)**
Utilisez un service comme [cron-job.org](https://cron-job.org) pour appeler une URL :

```
https://votre-app.railway.app/cleanup-old-events.php
```

**Option 2 : GitHub Actions**
Créez un workflow GitHub Actions qui s'exécute chaque jour :

```yaml
# .github/workflows/cleanup.yml
name: Cleanup Old Events
on:
  schedule:
    - cron: '0 0 * * *'  # Chaque jour à minuit UTC
jobs:
  cleanup:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run cleanup
        run: |
          curl https://votre-app.railway.app/cleanup-old-events.php
```

## Logs

Les logs de nettoyage sont enregistrés dans `logs/cleanup.log`

**Voir les derniers logs :**
```bash
tail -f logs/cleanup.log
```

**Exemple de log :**
```
[2025-01-11 00:00:01] === Début du nettoyage des événements ===
[2025-01-11 00:00:01] Nombre d'événements avant nettoyage: 15
[2025-01-11 00:00:01] Date du jour: 2025-01-11
[2025-01-11 00:00:01] Suppression: Médecin (date: 2025-01-10)
[2025-01-11 00:00:01] Suppression: Course (date: 2025-01-09)
[2025-01-11 00:00:01] Événements supprimés: 2
[2025-01-11 00:00:01] Événements récurrents conservés: 5
[2025-01-11 00:00:01] Nombre d'événements après nettoyage: 13
[2025-01-11 00:00:01] ✅ Sauvegarde réussie
[2025-01-11 00:00:01] === Fin du nettoyage ===
```

## Test manuel

Pour tester le nettoyage manuellement :

```bash
php cleanup-old-events.php
```

## Sécurité

- ✅ Le script ne supprime JAMAIS les événements récurrents
- ✅ Logs détaillés pour tracer chaque action
- ✅ Sauvegarde automatique après nettoyage
- ✅ Support Gist avec fallback fichier local

## Fréquence recommandée

**Quotidienne à minuit** : `0 0 * * *`

Vous pouvez ajuster selon vos besoins :
- Chaque semaine : `0 0 * * 0` (dimanche minuit)
- Deux fois par semaine : `0 0 * * 0,3` (dimanche et mercredi)
- Toutes les 2 semaines : `0 0 1,15 * *` (1er et 15 du mois)
