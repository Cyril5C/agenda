#!/bin/bash

# Script pour uploader le fichier de test sur le serveur de production
# Pour diagnostiquer les erreurs 500

echo "📤 Upload du fichier test-config.php sur le serveur..."
echo ""

if [ ! -f test-config.php ]; then
    echo "❌ Fichier test-config.php introuvable"
    exit 1
fi

lftp -u "fredericbn","Kt8pv76TIU" ftp.cluster003.hosting.ovh.net << EOF
set ssl:verify-certificate no
cd pm
put test-config.php
bye
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Fichier uploadé avec succès !"
    echo ""
    echo "🔍 Accédez au diagnostic ici :"
    echo "   https://votredomaine.com/pm/test-config.php"
    echo ""
    echo "⚠️  N'oubliez pas de SUPPRIMER ce fichier après le diagnostic !"
    echo ""
else
    echo ""
    echo "❌ Erreur lors de l'upload"
fi
