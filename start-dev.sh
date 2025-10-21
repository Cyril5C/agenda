#!/bin/bash

# Script de démarrage du serveur de développement
# Usage: ./start-dev.sh

echo "🚀 Démarrage du serveur de développement..."

# Vérifier que nous sommes dans le bon répertoire
cd "$(dirname "$0")"

# Vérifier que le fichier .env existe
if [ ! -f .env ]; then
    echo "⚠️  Le fichier .env n'existe pas. Copie depuis .env.example..."
    cp .env.example .env
    echo "✅ Fichier .env créé. Vous pouvez le modifier si nécessaire."
fi

# Créer le dossier logs s'il n'existe pas
if [ ! -d logs ]; then
    echo "📁 Création du dossier logs..."
    mkdir -p logs
fi

# Vérifier si un serveur tourne déjà sur le port 8000
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    echo "⚠️  Un serveur tourne déjà sur le port 8000"
    echo "   Pour l'arrêter : kill \$(lsof -ti:8000)"
    exit 1
fi

# Afficher la configuration
echo ""
echo "📋 Configuration:"
grep -v "^#" .env | grep -v "^$"
echo ""

# Démarrer le serveur PHP
echo "🌐 Démarrage du serveur PHP sur http://localhost:8000"
echo ""
echo "   📱 Page principale : http://localhost:8000/index.html"
echo "   ⚙️  Admin          : http://localhost:8000/admin.html"
echo "   🔌 API            : http://localhost:8000/api.php"
echo ""
echo "   Appuyez sur Ctrl+C pour arrêter le serveur"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Lancer le serveur
php -S localhost:8000
