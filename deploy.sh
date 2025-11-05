#!/bin/bash

# Script de déploiement
# Ce script met à jour la version avant de pousser sur Railway

echo "🚀 Déploiement en cours..."

# Mettre à jour le fichier version.json
echo "📝 Mise à jour de la version..."
./update-version.sh

# Ajouter le fichier version.json au commit
git add version.json

# Vérifier s'il y a des changements à commiter
if git diff --cached --quiet; then
    echo "✅ Aucun changement de version à commiter"
else
    echo "📦 Commit de la nouvelle version..."
    git commit -m "🔄 Mise à jour version pour déploiement

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
fi

# Push vers GitHub (Railway se déclenchera automatiquement)
echo "⬆️  Push vers GitHub..."
git push

echo "✅ Déploiement terminé ! Railway va se mettre à jour automatiquement."
echo "ℹ️  Les utilisateurs seront automatiquement rechargés dans les 5 minutes."
