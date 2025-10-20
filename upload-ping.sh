#!/bin/bash

# Upload du fichier de test ping.php

echo "📤 Upload de ping.php..."

lftp -u "fredericbn","Kt8pv76TIU" ftp.cluster003.hosting.ovh.net << EOF
set ssl:verify-certificate no
cd pm
put ping.php
put info-serveur.php
bye
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Fichiers uploadés !"
    echo ""
    echo "🔍 Testez :"
    echo "   https://papoumamine.cincet.net/pm/ping.php"
    echo "   https://papoumamine.cincet.net/pm/info-serveur.php"
    echo ""
    echo "Si ping.php fonctionne mais pas info-serveur.php, il y a une erreur PHP dans info-serveur.php"
    echo ""
else
    echo "❌ Erreur lors de l'upload"
fi
