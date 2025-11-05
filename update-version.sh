#!/bin/bash

# Script pour mettre à jour le fichier version.json avec le timestamp actuel
# À exécuter lors du déploiement

TIMESTAMP=$(date +%s)

cat > version.json << EOF
{
    "version": "1.0.0",
    "buildTime": ${TIMESTAMP}
}
EOF

echo "✅ Version mise à jour : ${TIMESTAMP}"
