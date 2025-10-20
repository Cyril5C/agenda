#!/bin/bash

# Script pour uploader info-serveur.php sur le serveur de production
# Pour découvrir les chemins exacts du serveur

echo "📤 Upload du fichier info-serveur.php sur le serveur..."
echo ""

if [ ! -f info-serveur.php ]; then
    echo "❌ Fichier info-serveur.php introuvable"
    exit 1
fi

lftp -u "fredericbn","Kt8pv76TIU" ftp.cluster003.hosting.ovh.net << EOF
set ssl:verify-certificate no
cd pm
put info-serveur.php
bye
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Fichier uploadé avec succès !"
    echo ""
    echo "🔍 Accédez aux informations ici :"
    echo "   https://papoumamine.cincet.net/info-serveur.php"
    echo ""
    echo "📋 Ce script vous donnera :"
    echo "   • Les chemins exacts du serveur"
    echo "   • La version PHP"
    echo "   • Les permissions des dossiers"
    echo "   • Test d'écriture de fichiers"
    echo ""
    echo "⚠️  N'oubliez pas de SUPPRIMER ce fichier après consultation !"
    echo ""
else
    echo ""
    echo "❌ Erreur lors de l'upload"
fi
