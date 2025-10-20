#!/bin/bash

# Script pour basculer entre les environnements dev/prod
# Usage: ./switch-env.sh [dev|prod]

ENV=$1

if [ -z "$ENV" ]; then
    echo "Usage: ./switch-env.sh [dev|prod]"
    echo ""
    echo "Configuration actuelle:"
    if [ -f .env ]; then
        grep "^APP_ENV=" .env
        grep "^APP_DEBUG=" .env
        grep "^CORS_ORIGIN=" .env
    else
        echo "❌ Fichier .env non trouvé"
    fi
    exit 1
fi

if [ "$ENV" != "dev" ] && [ "$ENV" != "prod" ]; then
    echo "❌ Environnement invalide. Utilisez 'dev' ou 'prod'"
    exit 1
fi

# Vérifier que le fichier .env existe
if [ ! -f .env ]; then
    echo "⚠️  Le fichier .env n'existe pas. Copie depuis .env.example..."
    cp .env.example .env
fi

# Basculer vers l'environnement demandé
if [ "$ENV" = "dev" ]; then
    echo "🔄 Basculement vers l'environnement de DÉVELOPPEMENT..."
    sed -i.bak 's/^APP_ENV=.*/APP_ENV=dev/' .env
    sed -i.bak 's/^APP_DEBUG=.*/APP_DEBUG=true/' .env
    sed -i.bak 's/^CORS_ORIGIN=.*/CORS_ORIGIN=*/' .env
    rm -f .env.bak
    echo "✅ Configuration dev activée:"
    echo "   - Erreurs affichées"
    echo "   - Debug activé"
    echo "   - CORS ouvert (*)"
else
    echo "🔄 Basculement vers l'environnement de PRODUCTION..."

    # Demander le domaine pour CORS
    echo ""
    echo "⚠️  En production, il est important de restreindre CORS"
    read -p "   Entrez votre domaine (ex: https://votredomaine.com) ou '*' pour tout autoriser: " DOMAIN

    if [ -z "$DOMAIN" ]; then
        DOMAIN="*"
    fi

    sed -i.bak 's/^APP_ENV=.*/APP_ENV=prod/' .env
    sed -i.bak 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
    sed -i.bak "s|^CORS_ORIGIN=.*|CORS_ORIGIN=$DOMAIN|" .env
    rm -f .env.bak

    echo ""
    echo "✅ Configuration prod activée:"
    echo "   - Erreurs masquées"
    echo "   - Debug désactivé"
    echo "   - CORS: $DOMAIN"
    echo ""
    echo "⚠️  N'oubliez pas de:"
    echo "   1. Protéger le fichier .env (non accessible publiquement)"
    echo "   2. Vérifier les permissions du dossier logs/"
    echo "   3. Configurer un .htaccess si nécessaire"
fi

echo ""
echo "📋 Configuration actuelle dans .env:"
grep -v "^#" .env | grep -v "^$"
