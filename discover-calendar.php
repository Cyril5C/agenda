<?php
/**
 * Script pour découvrir automatiquement les calendriers disponibles
 */

require_once __DIR__ . '/config.php';

$baseUrl = 'https://ncloud9.zaclys.com/remote.php/dav';
$username = config('caldav_username');
$password = config('caldav_password');

echo "🔍 Découverte des calendriers CalDAV\n";
echo "====================================\n\n";

echo "Serveur: $baseUrl\n";
echo "Utilisateur: $username\n\n";

// Étape 1: Trouver le calendar-home-set
echo "📋 Étape 1: Trouver le calendar-home-set...\n";

$principalUrl = "$baseUrl/principals/users/$username/";

$xml = '<?xml version="1.0" encoding="utf-8" ?>
<d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
  <d:prop>
    <c:calendar-home-set />
  </d:prop>
</d:propfind>';

$ch = curl_init($principalUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Depth: 0',
    'Content-Type: application/xml; charset=utf-8'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($httpCode != 207) {
    echo "❌ Erreur: Code HTTP $httpCode\n";
    echo "Réponse:\n" . substr($response, 0, 500) . "\n";
    exit(1);
}

// Parser la réponse pour trouver calendar-home-set
$xml = @simplexml_load_string($response);
if ($xml === false) {
    echo "❌ Impossible de parser la réponse XML\n";
    exit(1);
}

$xml->registerXPathNamespace('d', 'DAV:');
$xml->registerXPathNamespace('c', 'urn:ietf:params:xml:ns:caldav');

$calendarHome = $xml->xpath('//c:calendar-home-set/d:href');
if (empty($calendarHome)) {
    echo "❌ calendar-home-set non trouvé\n";
    exit(1);
}

$calendarHomeUrl = (string) $calendarHome[0];
echo "✅ Calendar home: $calendarHomeUrl\n\n";

// Étape 2: Lister les calendriers disponibles
echo "📋 Étape 2: Lister les calendriers...\n";

$fullCalendarHomeUrl = "https://ncloud9.zaclys.com" . $calendarHomeUrl;

$xml = '<?xml version="1.0" encoding="utf-8" ?>
<d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">
  <d:prop>
    <d:displayname />
    <d:resourcetype />
    <cs:getctag />
  </d:prop>
</d:propfind>';

$ch = curl_init($fullCalendarHomeUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Depth: 1',
    'Content-Type: application/xml; charset=utf-8'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n\n";

if ($httpCode != 207) {
    echo "❌ Erreur: Code HTTP $httpCode\n";
    exit(1);
}

// Parser les calendriers
$xml = @simplexml_load_string($response);
if ($xml === false) {
    echo "❌ Impossible de parser la réponse XML\n";
    exit(1);
}

$xml->registerXPathNamespace('d', 'DAV:');
$xml->registerXPathNamespace('c', 'urn:ietf:params:xml:ns:caldav');

$responses = $xml->xpath('//d:response');

echo "📅 Calendriers trouvés:\n";
echo "======================\n\n";

foreach ($responses as $resp) {
    $href = (string) $resp->xpath('d:href')[0];

    // Vérifier si c'est un calendrier (pas juste le dossier parent)
    $resourceType = $resp->xpath('d:propstat/d:prop/d:resourcetype/c:calendar');

    if (!empty($resourceType)) {
        $displayName = $resp->xpath('d:propstat/d:prop/d:displayname');
        $name = !empty($displayName) ? (string) $displayName[0] : 'Sans nom';

        $fullUrl = "https://ncloud9.zaclys.com" . $href;

        echo "📌 $name\n";
        echo "   URL: $fullUrl\n";

        // Extraire le nom du calendrier de l'URL
        if (preg_match('#/calendars/[^/]+/([^/]+)/#', $href, $matches)) {
            $calName = $matches[1];
            echo "   Nom interne: $calName\n";
            echo "\n   ✅ Configuration .env:\n";
            echo "   CALDAV_URL=https://ncloud9.zaclys.com/remote.php/dav\n";
            echo "   CALDAV_CALENDAR=$calName\n";
        }

        echo "\n";
    }
}

echo "\n💡 Utilisez un des noms de calendrier ci-dessus dans CALDAV_CALENDAR\n";
?>
