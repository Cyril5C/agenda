<?php
// Script temporaire pour vérifier la configuration PHP
echo json_encode([
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'php_ini_loaded_file' => php_ini_loaded_file(),
    'php_ini_scanned_files' => php_ini_scanned_files()
], JSON_PRETTY_PRINT);
