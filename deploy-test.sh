#!/bin/bash

# Script de TEST de déploiement (dry-run)
# Ce script simule le déploiement SANS envoyer les fichiers

echo "🧪 TEST de déploiement (simulation)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Vérifier que nous sommes dans le bon répertoire
cd "$(dirname "$0")"

# Vérifier que le fichier .lftpignore existe
if [ ! -f .lftpignore ]; then
    echo "❌ Fichier .lftpignore manquant"
    exit 1
fi

echo "📋 Fichiers qui seront EXCLUS du déploiement :"
echo ""
cat .lftpignore | grep -v "^#" | grep -v "^$" | sed 's/^/   ❌ /'
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📦 Fichiers qui SERONT envoyés en production :"
echo ""

# Configuration FTP
FTP_USER="fredericbn"
FTP_PASS="Kt8pv76TIU"
FTP_HOST="ftp.cluster003.hosting.ovh.net"
FTP_DIR="pm"

# Test avec --dry-run
lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" << EOF
set ssl:verify-certificate no
lcd /Users/cyril.cincet/Nextcloud4/SitesWeb/pm
cd $FTP_DIR

# Simulation (dry-run)
mirror -R \
  --exclude-glob-from=.lftpignore \
  --dry-run \
  --verbose \
  . .

bye
EOF

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Test terminé (aucun fichier n'a été envoyé)"
echo ""
echo "💡 Pour déployer réellement, utilisez :"
echo "   ./deploy-prod.sh"
echo ""
