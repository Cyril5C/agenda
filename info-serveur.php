<?php
/**
 * Script d'information sur le serveur
 * À uploader en production pour connaître les chemins exacts
 *
 * Accès : http://votredomaine.com/pm/info-serveur.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Informations Serveur</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            padding: 20px;
            background: #f5f5f5;
            max-width: 1200px;
            margin: 0 auto;
        }
        .box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #e74c3c;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        td:first-child {
            font-weight: bold;
            width: 250px;
            color: #555;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            color: #e74c3c;
            font-size: 14px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .copy-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }
        .copy-btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <h1>📁 Informations Serveur - OVH Mutualisé</h1>

    <div class="warning">
        <strong>⚠️ IMPORTANT :</strong> Supprimez ce fichier après avoir noté les informations (sécurité).
    </div>

    <div class="box">
        <h2>🗂️ Chemins du système</h2>
        <table>
            <tr>
                <td>__DIR__ (Dossier actuel)</td>
                <td>
                    <code id="dir"><?php echo __DIR__; ?></code>
                    <button class="copy-btn" onclick="copyToClipboard('dir')">Copier</button>
                </td>
            </tr>
            <tr>
                <td>__FILE__ (Ce fichier)</td>
                <td>
                    <code id="file"><?php echo __FILE__; ?></code>
                    <button class="copy-btn" onclick="copyToClipboard('file')">Copier</button>
                </td>
            </tr>
            <tr>
                <td>DOCUMENT_ROOT</td>
                <td>
                    <code id="docroot"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Non défini'; ?></code>
                    <button class="copy-btn" onclick="copyToClipboard('docroot')">Copier</button>
                </td>
            </tr>
            <tr>
                <td>SCRIPT_FILENAME</td>
                <td>
                    <code id="scriptfile"><?php echo $_SERVER['SCRIPT_FILENAME'] ?? 'Non défini'; ?></code>
                    <button class="copy-btn" onclick="copyToClipboard('scriptfile')">Copier</button>
                </td>
            </tr>
            <tr>
                <td>Chemin absolu</td>
                <td>
                    <code id="realpath"><?php echo realpath(__DIR__); ?></code>
                    <button class="copy-btn" onclick="copyToClipboard('realpath')">Copier</button>
                </td>
            </tr>
            <tr>
                <td>Répertoire de travail</td>
                <td>
                    <code id="getcwd"><?php echo getcwd(); ?></code>
                    <button class="copy-btn" onclick="copyToClipboard('getcwd')">Copier</button>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>📂 Fichiers dans ce répertoire</h2>
        <table>
            <?php
            $files = scandir(__DIR__);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $path = __DIR__ . '/' . $file;
                $type = is_dir($path) ? '📁 Dossier' : '📄 Fichier';
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $size = is_file($path) ? filesize($path) . ' octets' : '-';
                echo "<tr>";
                echo "<td>$type : $file</td>";
                echo "<td><code>Permissions: $perms | Taille: $size</code></td>";
                echo "</tr>";
            }
            ?>
        </table>
    </div>

    <div class="box">
        <h2>🔧 Configuration PHP</h2>
        <table>
            <tr>
                <td>Version PHP</td>
                <td><code><?php echo PHP_VERSION; ?></code></td>
            </tr>
            <tr>
                <td>Système d'exploitation</td>
                <td><code><?php echo PHP_OS; ?></code></td>
            </tr>
            <tr>
                <td>Interface serveur</td>
                <td><code><?php echo php_sapi_name(); ?></code></td>
            </tr>
            <tr>
                <td>Upload max filesize</td>
                <td><code><?php echo ini_get('upload_max_filesize'); ?></code></td>
            </tr>
            <tr>
                <td>Post max size</td>
                <td><code><?php echo ini_get('post_max_size'); ?></code></td>
            </tr>
            <tr>
                <td>Memory limit</td>
                <td><code><?php echo ini_get('memory_limit'); ?></code></td>
            </tr>
            <tr>
                <td>Max execution time</td>
                <td><code><?php echo ini_get('max_execution_time'); ?> secondes</code></td>
            </tr>
            <tr>
                <td>Display errors</td>
                <td><code><?php echo ini_get('display_errors') ? 'On' : 'Off'; ?></code></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>🌐 Variables serveur</h2>
        <table>
            <tr>
                <td>HTTP_HOST</td>
                <td><code><?php echo $_SERVER['HTTP_HOST'] ?? 'Non défini'; ?></code></td>
            </tr>
            <tr>
                <td>SERVER_NAME</td>
                <td><code><?php echo $_SERVER['SERVER_NAME'] ?? 'Non défini'; ?></code></td>
            </tr>
            <tr>
                <td>SERVER_ADDR</td>
                <td><code><?php echo $_SERVER['SERVER_ADDR'] ?? 'Non défini'; ?></code></td>
            </tr>
            <tr>
                <td>SERVER_SOFTWARE</td>
                <td><code><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Non défini'; ?></code></td>
            </tr>
            <tr>
                <td>REQUEST_URI</td>
                <td><code><?php echo $_SERVER['REQUEST_URI'] ?? 'Non défini'; ?></code></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>✅ Pour votre configuration</h2>
        <p><strong>Dans config.php, utilisez :</strong></p>
        <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto;">'db_file' => __DIR__ . '/evenements.json',
'upload_dir' => __DIR__ . '/images/',
'log_file' => __DIR__ . '/logs/app.log',</pre>

        <p style="margin-top: 20px;"><strong>__DIR__</strong> est une constante magique PHP qui retourne toujours le chemin absolu du répertoire contenant le fichier PHP en cours.</p>

        <p><strong>✅ Avantage :</strong> Fonctionne automatiquement en local et en production, peu importe le chemin absolu.</p>
    </div>

    <div class="box">
        <h2>🧪 Test de création de fichier</h2>
        <?php
        $testFile = __DIR__ . '/test-write.txt';
        $testContent = 'Test de création de fichier - ' . date('Y-m-d H:i:s');

        if (@file_put_contents($testFile, $testContent)) {
            echo "<p style='color: green;'>✅ <strong>Écriture réussie !</strong></p>";
            echo "<p>Fichier créé : <code>$testFile</code></p>";

            if (file_exists($testFile)) {
                echo "<p>Contenu : <code>" . file_get_contents($testFile) . "</code></p>";
                @unlink($testFile);
                echo "<p style='color: green;'>✅ Fichier de test supprimé.</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ <strong>Impossible d'écrire dans ce répertoire</strong></p>";
            echo "<p>Vérifiez les permissions du dossier (chmod 755)</p>";
        }

        // Test dossier logs
        $logsDir = __DIR__ . '/logs';
        echo "<hr>";
        if (is_dir($logsDir)) {
            echo "<p style='color: green;'>✅ Dossier <code>logs/</code> existe</p>";
            if (is_writable($logsDir)) {
                echo "<p style='color: green;'>✅ Dossier <code>logs/</code> accessible en écriture</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Dossier <code>logs/</code> non accessible en écriture (chmod 755 recommandé)</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Dossier <code>logs/</code> n'existe pas (créez-le : mkdir logs && chmod 755 logs)</p>";
        }

        // Test dossier images
        $imagesDir = __DIR__ . '/images';
        if (is_dir($imagesDir)) {
            echo "<p style='color: green;'>✅ Dossier <code>images/</code> existe</p>";
            if (is_writable($imagesDir)) {
                echo "<p style='color: green;'>✅ Dossier <code>images/</code> accessible en écriture</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Dossier <code>images/</code> non accessible en écriture (chmod 755 recommandé)</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Dossier <code>images/</code> n'existe pas</p>";
        }
        ?>
    </div>

    <div class="warning">
        <strong>🔒 SÉCURITÉ :</strong> Supprimez ce fichier <code>info-serveur.php</code> après avoir noté les informations !
    </div>

    <script>
        function copyToClipboard(id) {
            const element = document.getElementById(id);
            const text = element.textContent;

            navigator.clipboard.writeText(text).then(() => {
                alert('Copié dans le presse-papier : ' + text);
            }).catch(() => {
                // Fallback pour anciens navigateurs
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Copié dans le presse-papier : ' + text);
            });
        }
    </script>
</body>
</html>
