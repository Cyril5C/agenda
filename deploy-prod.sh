#!/bin/bash

# Script de déploiement en PRODUCTION
# ⚠️ Ce script envoie les fichiers sur le serveur OVH

echo "🚀 Déploiement en PRODUCTION"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Vérifier que nous sommes dans le bon répertoire
cd "$(dirname "$0")"

# Vérifier que le fichier .lftpignore existe
if [ ! -f .lftpignore ]; then
    echo "❌ Fichier .lftpignore manquant"
    exit 1
fi

# Vérifier que le fichier .env existe
if [ ! -f .env ]; then
    echo "❌ Fichier .env manquant. Créez-le avant de déployer."
    exit 1
fi

# Avertissement
echo "⚠️  ATTENTION : Vous allez déployer sur le serveur de PRODUCTION"
echo ""
echo "📋 Fichiers qui seront EXCLUS du déploiement :"
echo "   - .env (fichier local de développement)"
echo "   - Scripts .sh (start-dev.sh, switch-env.sh, etc.)"
echo "   - Documentation .md (README.md, QUICK_START.md)"
echo "   - Logs locaux"
echo "   - Fichiers de développement"
echo ""
echo "✅ Fichiers qui seront envoyés :"
echo "   - config.php"
echo "   - api.php, upload.php, images.php"
echo "   - index.html, admin.html"
echo "   - .htaccess (sécurité)"
echo "   - evenements.json (données)"
echo "   - images/ (dossier des images)"
echo ""

read -p "Voulez-vous continuer ? (oui/non) : " confirm

if [ "$confirm" != "oui" ]; then
    echo "❌ Déploiement annulé"
    exit 0
fi

echo ""
echo "📤 Déploiement en cours..."
echo ""

# Configuration FTP
FTP_USER="cincetnefq"
FTP_PASS="rRjVeQKVvpGJ"
FTP_HOST="ftp.cluster021.hosting.ovh.net"
FTP_DIR="pm"

# Commande lftp avec exclusions
lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" << EOF
set ssl:verify-certificate no
lcd /Users/cyril.cincet/Nextcloud4/SitesWeb/pm
cd $FTP_DIR

# Synchronisation avec exclusions
mirror -R \
  --exclude-glob-from=.lftpignore \
  --verbose \
  --delete \
  . .

bye
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "✅ Déploiement terminé avec succès !"
    echo ""
    echo "⚠️  N'OUBLIEZ PAS sur le serveur :"
    echo "   1. Créer/vérifier le fichier .env en mode PRODUCTION"
    echo "   2. Créer le dossier logs/ avec les bonnes permissions"
    echo "   3. Vérifier que .htaccess est actif"
    echo ""
    echo "📋 Configuration recommandée pour .env en production :"
    echo "   APP_ENV=prod"
    echo "   APP_DEBUG=false"
    echo "   CORS_ORIGIN=https://votredomaine.com"
    echo ""
else
    echo ""
    echo "❌ Erreur lors du déploiement"
    exit 1
fi
