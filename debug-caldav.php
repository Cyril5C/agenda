<?php
/**
 * Script de debug détaillé pour CalDAV
 */

require_once __DIR__ . '/config.php';

$url = config('caldav_url');
$username = config('caldav_username');
$password = config('caldav_password');

echo "=== Debug CalDAV ===\n\n";
echo "URL: $url\n";
echo "Username: $username\n\n";

// Test basique avec curl
echo "--- Test 1: PROPFIND basique ---\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Depth: 0',
    'Content-Type: application/xml; charset=utf-8'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo substr($response, 0, 500) . "\n\n";

// Test avec OPTIONS
echo "--- Test 2: OPTIONS (voir capacités DAV) ---\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Headers:\n";
echo substr($response, 0, 800) . "\n\n";
?>
