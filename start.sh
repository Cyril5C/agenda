#!/bin/bash
# Script de démarrage avec configuration PHP

echo "Démarrage du serveur PHP avec configuration custom..."
echo "upload_max_filesize=10M"
echo "post_max_size=10M"

exec php \
  -d upload_max_filesize=10M \
  -d post_max_size=10M \
  -d max_execution_time=120 \
  -d memory_limit=256M \
  -S 0.0.0.0:${PORT:-8000} \
  router.php
