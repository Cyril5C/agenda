#!/bin/bash

# Script d'initialisation des fichiers de données
# À exécuter lors du premier déploiement ou après un git clone

echo "🔧 Initialisation des fichiers de données..."

# Créer evenements.json s'il n'existe pas
if [ ! -f evenements.json ]; then
    echo "📅 Création de evenements.json depuis l'exemple..."
    cp evenements.json.example evenements.json
else
    echo "✅ evenements.json existe déjà"
fi

# Créer images.json s'il n'existe pas
if [ ! -f images.json ]; then
    echo "🖼️  Création de images.json depuis l'exemple..."
    cp images.json.example images.json
else
    echo "✅ images.json existe déjà"
fi

# Créer infos.json s'il n'existe pas
if [ ! -f infos.json ]; then
    echo "ℹ️  Création de infos.json depuis l'exemple..."
    cp infos.json.example infos.json
else
    echo "✅ infos.json existe déjà"
fi

# Créer message-aidants.json s'il n'existe pas
if [ ! -f message-aidants.json ]; then
    echo "📝 Création de message-aidants.json depuis l'exemple..."
    cp message-aidants.json.example message-aidants.json
else
    echo "✅ message-aidants.json existe déjà"
fi

echo "✅ Initialisation terminée !"
