#!/bin/bash

# Script de lancement en mode kiosque pour Raspberry Pi
# Lance Firefox en plein écran sur l'URL de l'agenda

# ⚠️ CONFIGURATION IMPORTANTE ⚠️
# Modifiez l'URL ci-dessous avec l'adresse de votre application
# Exemples :
# URL="http://192.168.1.100:8000/index.html"

URL="https://agenda-production-5113.up.railway.app"

# Attendre que le système et le réseau soient prêts
sleep 10

# Désactiver l'économiseur d'écran et la mise en veille
xset s off
xset -dpms
xset s noblank

# Masquer le curseur de la souris (optionnel)
# Décommentez la ligne suivante pour masquer le curseur
# unclutter -idle 0.5 -root &

# Lancer Firefox en mode kiosque (plein écran)
firefox \
  --kiosk \
  "$URL"
