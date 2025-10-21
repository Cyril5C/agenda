# Installation sur Raspberry Pi en mode Kiosque

Ce guide explique comment configurer le Raspberry Pi pour afficher automatiquement le Planning Papou Mamine en plein écran au démarrage.

**Note :** Ce guide configure le Raspberry Pi comme un simple affichage. L'application tourne sur un autre serveur (PC, serveur web, etc.).

## Prérequis

- Raspberry Pi avec Raspberry Pi OS (avec interface graphique)
- Connexion internet et réseau local
- Accès SSH ou accès direct au Raspberry Pi
- L'application Planning Papou Mamine doit tourner sur un serveur accessible (même réseau local ou internet)

## Installation

### 1. Installation de Firefox

```bash
sudo apt update
sudo apt install -y firefox-esr unclutter
```

**Paquets installés :**
- `firefox-esr` : navigateur web pour afficher l'agenda
- `unclutter` : pour masquer le curseur de la souris (optionnel)

### 2. Copier les fichiers de configuration

Créez un dossier pour les scripts :

```bash
mkdir -p /home/pi/kiosk
cd /home/pi/kiosk
```

Copiez uniquement les fichiers nécessaires depuis votre projet :
- `launch-kiosk.sh`
- `kiosk.service`

Ou téléchargez-les directement depuis votre dépôt.

### 3. Configurer l'URL de l'application

Éditez le fichier `launch-kiosk.sh` et remplacez l'URL par celle de votre serveur :

```bash
nano /home/pi/kiosk/launch-kiosk.sh
```

Modifiez la ligne :
```bash
URL="http://VOTRE_SERVEUR:8000/index.html"
```

**Exemples :**
- Serveur local : `URL="http://192.168.1.100:8000/index.html"`
- Serveur internet : `URL="https://papoumamine.cincet.net/index.html"`

Rendez le script exécutable :
```bash
chmod +x /home/pi/kiosk/launch-kiosk.sh
```

### 4. Modifier le fichier service

Éditez le fichier `kiosk.service` pour ajuster le chemin :

```bash
nano /home/pi/kiosk/kiosk.service
```

Vérifiez que la ligne `ExecStart` pointe vers le bon chemin :
```
ExecStart=/home/pi/kiosk/launch-kiosk.sh
```

### 5. Installer le service systemd

```bash
# Copier le fichier service
sudo cp /home/pi/kiosk/kiosk.service /etc/systemd/system/

# Recharger systemd
sudo systemctl daemon-reload

# Activer le service au démarrage
sudo systemctl enable kiosk.service

# Démarrer le service (ou redémarrer le Raspberry Pi)
sudo systemctl start kiosk.service
```

### 6. Vérifier le fonctionnement

```bash
# Vérifier le statut du service
sudo systemctl status kiosk.service

# Voir les logs en temps réel
sudo journalctl -u kiosk.service -f
```

## Configuration avancée

### Désactiver le curseur de la souris

Pour masquer complètement le curseur de la souris, décommentez la ligne dans [launch-kiosk.sh](launch-kiosk.sh) :

```bash
unclutter -idle 0.5 -root &
```

### Modifier le port

Si vous souhaitez utiliser un autre port que 8000, modifiez la variable `PORT` dans [launch-kiosk.sh](launch-kiosk.sh:9).

### Rotation de l'écran

Si vous avez besoin de faire pivoter l'écran, ajoutez dans `/boot/config.txt` :

```bash
# Rotation 90° (portrait)
display_rotate=1

# Rotation 180° (inversé)
display_rotate=2

# Rotation 270° (portrait inversé)
display_rotate=3
```

Puis redémarrez : `sudo reboot`

## Commandes utiles

```bash
# Arrêter le service
sudo systemctl stop kiosk.service

# Redémarrer le service
sudo systemctl restart kiosk.service

# Désactiver le démarrage automatique
sudo systemctl disable kiosk.service

# Voir les logs
sudo journalctl -u kiosk.service --no-pager

# Tester le script manuellement
/home/pi/pm/launch-kiosk.sh
```

## Dépannage

### L'écran reste noir au démarrage

1. Vérifier les logs : `sudo journalctl -u kiosk.service -n 50`
2. Vérifier que l'interface graphique est activée : `sudo raspi-config` → Boot Options → Desktop/CLI → Desktop Autologin
3. Vérifier que l'utilisateur est bien `pi` dans [kiosk.service](kiosk.service:7)

### Firefox ne se lance pas en plein écran

1. Tester manuellement : `DISPLAY=:0 firefox --kiosk http://votre-url`
2. Vérifier la variable `DISPLAY` dans [kiosk.service](kiosk.service:8) : `echo $DISPLAY`
3. Vérifier les permissions X11 : `xhost +local:`

### L'agenda ne s'affiche pas

1. Vérifier que l'URL du serveur est accessible : `curl http://votre-serveur:8000/index.html`
2. Vérifier la connexion réseau : `ping votre-serveur`
3. Tester l'URL dans Firefox manuellement pour voir les erreurs
4. Vérifier que le serveur de l'application est bien démarré

### Le Raspberry Pi ne se connecte pas au réseau

1. Attendre plus longtemps au démarrage (augmenter le `sleep` dans [launch-kiosk.sh](launch-kiosk.sh:14))
2. Vérifier la configuration WiFi/Ethernet
3. Tester : `ping 8.8.8.8` pour vérifier la connexion internet

## Personnalisation

### Changer l'URL de démarrage

Modifiez la variable `URL` dans [launch-kiosk.sh](launch-kiosk.sh:7) :

```bash
URL="http://localhost:8000/index.html"
```

### Ajuster le délai de démarrage

Si le système met du temps à démarrer, augmentez le délai dans [launch-kiosk.sh](launch-kiosk.sh:10) :

```bash
sleep 10  # Au lieu de 5
```

## Architecture

```
Sur le Raspberry Pi :
/home/pi/kiosk/
├── launch-kiosk.sh      # Script de lancement Firefox
└── kiosk.service        # Service systemd

Sur le serveur (autre machine) :
/votre/projet/pm/
├── index.html           # Page principale (agenda)
├── api.php              # API backend
├── evenements.json      # Données des événements
├── images.json          # Configuration des images
├── start-dev.sh         # Script pour démarrer le serveur
└── ...
```

## Démarrage du serveur sur l'autre machine

Sur la machine qui héberge l'application, utilisez le script [start-dev.sh](start-dev.sh) :

```bash
./start-dev.sh
```

Ou si vous déployez sur Railway/serveur web, l'application sera accessible via l'URL de production.

## Notes

- Le service redémarre automatiquement en cas d'erreur (voir `Restart=on-failure` dans le service)
- La page se rafraîchit automatiquement toutes les 5 minutes
- L'économiseur d'écran est désactivé
- Le navigateur est configuré pour éviter les popups et messages d'erreur
