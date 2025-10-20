#!/bin/bash

# Script d'information rapide sur le projet

clear

cat << 'EOF'
╔══════════════════════════════════════════════════════════════════════╗
║                       APPLICATION PM - INFO                          ║
╚══════════════════════════════════════════════════════════════════════╝
EOF

echo ""
echo "📍 Répertoire : $(pwd)"
echo ""

# Configuration actuelle
if [ -f .env ]; then
    echo "⚙️  Configuration actuelle :"
    echo ""
    grep -v "^#" .env | grep -v "^$" | while read line; do
        echo "   $line"
    done
else
    echo "⚠️  Aucun fichier .env trouvé"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Vérifier le serveur
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    echo "✅ Serveur PHP en cours d'exécution sur http://localhost:8000"
    echo ""
    echo "   📱 Page principale : http://localhost:8000/index.html"
    echo "   ⚙️  Admin          : http://localhost:8000/admin.html"
    echo "   🔌 API            : http://localhost:8000/api.php"
else
    echo "⚠️  Aucun serveur en cours sur le port 8000"
    echo ""
    echo "   Pour démarrer : ./start-dev.sh"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Statistiques
if [ -f evenements.json ]; then
    NB_EVENTS=$(cat evenements.json | jq '. | length' 2>/dev/null || echo "?")
    echo "📊 Statistiques :"
    echo "   • $NB_EVENTS événement(s)"
fi

if [ -d images ]; then
    NB_IMAGES=$(ls -1 images/*.{jpg,jpeg,png,gif,webp} 2>/dev/null | wc -l | tr -d ' ')
    echo "   • $NB_IMAGES image(s)"
fi

if [ -f logs/app.log ]; then
    NB_LOGS=$(wc -l < logs/app.log | tr -d ' ')
    echo "   • $NB_LOGS ligne(s) de logs"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "💡 Commandes rapides :"
echo ""
echo "   ./start-dev.sh        Démarrer le serveur"
echo "   ./switch-env.sh       Voir/changer l'environnement"
echo "   ./deploy-test.sh      Tester le déploiement"
echo "   ./deploy-prod.sh      Déployer en production"
echo "   ./info.sh             Afficher ces informations"
echo ""
echo "📚 Documentation :"
echo ""
echo "   cat DEPLOIEMENT_RESUME.txt    Résumé du déploiement"
echo "   cat COMMANDES.txt             Liste de toutes les commandes"
echo "   cat DEPLOY.md                 Guide de déploiement complet"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
