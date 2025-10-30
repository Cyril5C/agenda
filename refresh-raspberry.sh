#!/bin/bash

# Script pour forcer le rechargement de Chromium sur le Raspberry Pi
# Usage: ./refresh-raspberry.sh

echo "Forçage du rechargement de Chromium sur le Raspberry Pi..."

# Option 1 : Utiliser xdotool pour simuler Ctrl+Shift+R
if command -v xdotool &> /dev/null; then
    echo "Utilisation de xdotool pour forcer le rechargement..."
    export DISPLAY=:0
    xdotool search --onlyvisible --class chromium windowactivate --sync key --clearmodifiers ctrl+shift+r
    echo "✓ Rechargement forcé avec succès !"
else
    echo "xdotool n'est pas installé. Installation..."
    sudo apt-get update && sudo apt-get install -y xdotool

    if [ $? -eq 0 ]; then
        echo "✓ xdotool installé avec succès"
        echo "Forçage du rechargement..."
        export DISPLAY=:0
        xdotool search --onlyvisible --class chromium windowactivate --sync key --clearmodifiers ctrl+shift+r
        echo "✓ Rechargement forcé avec succès !"
    else
        echo "✗ Erreur lors de l'installation de xdotool"
        echo "Tentative de redémarrage de Chromium à la place..."

        # Option de secours : redémarrer Chromium
        pkill chromium
        sleep 2
        DISPLAY=:0 chromium-browser --kiosk --noerrdialogs --disable-infobars https://agenda-production-5113.up.railway.app/ &
        echo "✓ Chromium redémarré"
    fi
fi
