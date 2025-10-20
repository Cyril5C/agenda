#!/bin/bash

# Script pour initialiser Git facilement
# Usage: ./init-git.sh

echo "🚀 Initialisation de Git pour votre projet PM"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Vérifier que Git est installé
if ! command -v git &> /dev/null; then
    echo "❌ Git n'est pas installé"
    echo ""
    echo "📥 Installez Git ici : https://git-scm.com/download/mac"
    echo ""
    exit 1
fi

echo "✅ Git est installé : $(git --version)"
echo ""

# Vérifier la configuration Git
echo "🔧 Configuration Git..."
echo ""

# Demander le nom si pas configuré
if [ -z "$(git config --global user.name)" ]; then
    read -p "👤 Votre nom (ex: Cyril Cincet) : " git_name
    git config --global user.name "$git_name"
    echo "✅ Nom configuré : $git_name"
else
    echo "✅ Nom déjà configuré : $(git config --global user.name)"
fi

# Demander l'email si pas configuré
if [ -z "$(git config --global user.email)" ]; then
    read -p "📧 Votre email (ex: cyril@cincet.net) : " git_email
    git config --global user.email "$git_email"
    echo "✅ Email configuré : $git_email"
else
    echo "✅ Email déjà configuré : $(git config --global user.email)"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Vérifier si Git est déjà initialisé
if [ -d .git ]; then
    echo "⚠️  Git est déjà initialisé dans ce projet"
    echo ""
    read -p "Voulez-vous réinitialiser ? (oui/non) : " reinit
    if [ "$reinit" = "oui" ]; then
        rm -rf .git
        echo "✅ Ancien Git supprimé"
    else
        echo "❌ Initialisation annulée"
        exit 0
    fi
fi

# Initialiser Git
echo "📦 Initialisation de Git..."
git init
echo "✅ Git initialisé"
echo ""

# Créer .gitignore s'il n'existe pas
if [ ! -f .gitignore ]; then
    echo "📝 Création du .gitignore..."
    # Le .gitignore existe déjà normalement
fi

# Ajouter tous les fichiers
echo "➕ Ajout de tous les fichiers..."
git add .
echo "✅ Fichiers ajoutés"
echo ""

# Créer le premier commit
echo "💾 Création du premier commit..."
git commit -m "Initial commit - Application PM"
echo "✅ Premier commit créé"
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Git est prêt !"
echo ""
echo "📋 Prochaines étapes :"
echo ""
echo "1️⃣  Créez un repository sur GitHub :"
echo "   👉 https://github.com/new"
echo "   - Repository name : pm-events"
echo "   - Private ou Public : à votre choix"
echo "   - NE COCHEZ PAS 'Initialize with README'"
echo ""
echo "2️⃣  Ensuite, exécutez ces commandes :"
echo ""
echo "   git remote add origin https://github.com/VOTRE_USERNAME/pm-events.git"
echo "   git branch -M main"
echo "   git push -u origin main"
echo ""
echo "   ⚠️  Remplacez VOTRE_USERNAME par votre vrai username GitHub"
echo ""
echo "3️⃣  Pour le mot de passe GitHub :"
echo "   👉 Créez un Personal Access Token ici :"
echo "   https://github.com/settings/tokens"
echo "   - Generate new token (classic)"
echo "   - Cochez 'repo'"
echo "   - Générez et COPIEZ le token"
echo "   - Utilisez ce token comme mot de passe"
echo ""
echo "📖 Guide complet : lisez GUIDE_GIT_RAILWAY.md"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
