<?php
/**
 * Script d'information simplifié - Compatible PHP 5.6
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Info Serveur</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; }
        h2 { color: #e74c3c; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        td:first-child { font-weight: bold; width: 200px; }
        .warning { background: #fff3cd; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Informations Serveur</h1>

    <div class="warning">
        <strong>SUPPRIMEZ ce fichier après consultation !</strong>
    </div>

    <div class="box">
        <h2>Chemins</h2>
        <table>
            <tr>
                <td>__DIR__</td>
                <td><?php echo __DIR__; ?></td>
            </tr>
            <tr>
                <td>__FILE__</td>
                <td><?php echo __FILE__; ?></td>
            </tr>
            <tr>
                <td>DOCUMENT_ROOT</td>
                <td><?php echo isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'Non défini'; ?></td>
            </tr>
            <tr>
                <td>realpath(__DIR__)</td>
                <td><?php echo realpath(__DIR__); ?></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>PHP</h2>
        <table>
            <tr>
                <td>Version PHP</td>
                <td><strong><?php echo PHP_VERSION; ?></strong></td>
            </tr>
            <tr>
                <td>OS</td>
                <td><?php echo PHP_OS; ?></td>
            </tr>
            <tr>
                <td>Upload max</td>
                <td><?php echo ini_get('upload_max_filesize'); ?></td>
            </tr>
            <tr>
                <td>Post max</td>
                <td><?php echo ini_get('post_max_size'); ?></td>
            </tr>
            <tr>
                <td>Memory limit</td>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Fichiers dans ce dossier</h2>
        <table>
            <?php
            $files = scandir(__DIR__);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $path = __DIR__ . '/' . $file;
                $type = is_dir($path) ? 'DOSSIER' : 'Fichier';
                echo "<tr><td>$type</td><td>$file</td></tr>";
            }
            ?>
        </table>
    </div>

    <div class="box">
        <h2>Test écriture</h2>
        <?php
        $testFile = __DIR__ . '/test.txt';
        if (@file_put_contents($testFile, 'Test - ' . date('Y-m-d H:i:s'))) {
            echo "<p style='color: green;'>✓ Écriture OK</p>";
            @unlink($testFile);
        } else {
            echo "<p style='color: red;'>✗ Écriture impossible</p>";
        }

        // Test logs
        if (is_dir(__DIR__ . '/logs')) {
            if (is_writable(__DIR__ . '/logs')) {
                echo "<p style='color: green;'>✓ Dossier logs/ OK</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Dossier logs/ non accessible en écriture</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ Dossier logs/ n'existe pas</p>";
        }

        // Test images
        if (is_dir(__DIR__ . '/images')) {
            if (is_writable(__DIR__ . '/images')) {
                echo "<p style='color: green;'>✓ Dossier images/ OK</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Dossier images/ non accessible en écriture</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Dossier images/ n'existe pas</p>";
        }

        // Test evenements.json
        if (file_exists(__DIR__ . '/evenements.json')) {
            echo "<p style='color: green;'>✓ evenements.json existe</p>";
        } else {
            echo "<p style='color: orange;'>⚠ evenements.json n'existe pas</p>";
        }

        // Test .env
        if (file_exists(__DIR__ . '/.env')) {
            echo "<p style='color: green;'>✓ .env existe</p>";
        } else {
            echo "<p style='color: orange;'>⚠ .env n'existe pas</p>";
        }
        ?>
    </div>

    <div class="box">
        <h2>Pour config.php</h2>
        <p>Utilisez :</p>
        <pre style="background: #333; color: white; padding: 10px;">
'db_file' => __DIR__ . '/evenements.json',
'upload_dir' => __DIR__ . '/images/',
'log_file' => __DIR__ . '/logs/app.log',
        </pre>
        <p><strong>__DIR__</strong> = <?php echo __DIR__; ?></p>
    </div>

    <div class="warning">
        <strong>IMPORTANT : Supprimez info.php après consultation !</strong>
    </div>

</body>
</html>
