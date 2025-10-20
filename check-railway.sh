#!/bin/bash

# Script pour vérifier si votre site Railway est accessible

echo "🔍 Vérification de votre site Railway"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "📋 Checklist de déploiement Railway :"
echo ""

echo "✅ Étape 1 : Code poussé sur GitHub"
if git remote get-url origin &> /dev/null; then
    REPO_URL=$(git remote get-url origin)
    echo "   GitHub : $REPO_URL"
else
    echo "   ❌ Pas de repository GitHub configuré"
    echo ""
    echo "   Exécutez :"
    echo "   git remote add origin https://github.com/VOTRE_USERNAME/agenda.git"
    exit 1
fi

echo ""
echo "✅ Étape 2 : Vérifier que le code est bien poussé"
git fetch origin main 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   GitHub repository accessible ✓"
else
    echo "   ⚠️  Impossible d'accéder au repository"
    echo "   Avez-vous bien fait 'git push' ?"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🌐 Pour trouver votre URL Railway :"
echo ""
echo "1. Allez sur : https://railway.app"
echo "2. Connectez-vous avec GitHub"
echo "3. Cherchez votre projet 'agenda'"
echo "4. Cliquez dessus"
echo "5. L'URL est affichée en haut"
echo ""
echo "   OU"
echo ""
echo "6. Allez dans Settings → Domains"
echo "7. Cliquez 'Generate Domain' si aucune URL"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "💡 URLs communes Railway :"
echo ""
echo "   https://agenda-production.up.railway.app"
echo "   https://pm-events-production.up.railway.app"
echo "   https://VOTRE-NOM-production.up.railway.app"
echo ""
echo "   Ajoutez /index.html à la fin pour voir votre site"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

read -p "📝 Avez-vous trouvé votre URL sur Railway ? (oui/non) : " found

if [ "$found" = "oui" ]; then
    read -p "🌐 Entrez votre URL Railway (sans https://) : " railway_url

    if [ ! -z "$railway_url" ]; then
        echo ""
        echo "🧪 Test de l'URL..."

        # Nettoyer l'URL
        railway_url=$(echo "$railway_url" | sed 's|https://||' | sed 's|http://||' | sed 's|/.*||')

        echo "   Tentative d'accès à : https://$railway_url/ping.php"

        response=$(curl -s -o /dev/null -w "%{http_code}" "https://$railway_url/ping.php" 2>/dev/null)

        if [ "$response" = "200" ]; then
            echo "   ✅ Site accessible !"
            echo ""
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo "🎉 Votre site est en ligne !"
            echo ""
            echo "📱 Accédez à votre site ici :"
            echo "   👉 https://$railway_url/index.html"
            echo ""
            echo "⚙️  Interface admin :"
            echo "   👉 https://$railway_url/admin.html"
            echo ""
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        else
            echo "   ⚠️  HTTP $response - Le site ne répond pas encore"
            echo ""
            echo "   Possibilités :"
            echo "   - Le déploiement est en cours (attendez 2-3 minutes)"
            echo "   - L'URL est incorrecte"
            echo "   - Il y a une erreur dans le déploiement"
            echo ""
            echo "   Vérifiez les logs sur Railway : https://railway.app"
        fi
    fi
else
    echo ""
    echo "📖 Consultez le guide complet :"
    echo "   cat TROUVER_URL_RAILWAY.md"
    echo ""
    echo "   ou"
    echo ""
    echo "   open TROUVER_URL_RAILWAY.md"
fi

echo ""
