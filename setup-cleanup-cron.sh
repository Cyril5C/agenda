#!/bin/bash

# Script pour configurer le nettoyage automatique des événements passés
# Ce script aide à configurer une tâche cron pour nettoyer les événements chaque jour à minuit

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "🧹 Configuration du nettoyage automatique des événements"
echo "================================================"
echo ""
echo "Ce script nettoiera automatiquement les événements passés chaque jour à minuit."
echo "Les événements RÉCURRENTS seront toujours conservés."
echo ""
echo "📍 Dossier du projet: $PROJECT_DIR"
echo ""

# Tester le script manuellement
echo "🔍 Test du script de nettoyage..."
php "$PROJECT_DIR/cleanup-old-events.php"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Le script fonctionne correctement !"
    echo ""
else
    echo ""
    echo "❌ Erreur lors de l'exécution du script"
    echo "Vérifiez les logs dans logs/cleanup.log"
    exit 1
fi

echo "📝 Pour configurer le cron automatique, ajoutez cette ligne à votre crontab :"
echo ""
echo "# Nettoyer les événements passés chaque jour à minuit"
echo "0 0 * * * cd $PROJECT_DIR && php cleanup-old-events.php >> logs/cleanup.log 2>&1"
echo ""
echo "Pour éditer votre crontab, utilisez : crontab -e"
echo ""
echo "📊 Pour voir les logs de nettoyage : cat logs/cleanup.log"
echo ""
