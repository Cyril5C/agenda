<?php
/**
 * Client CalDAV simple basé sur curl
 * Pas de dépendances externes complexes, juste curl et SimpleXML
 */

require_once __DIR__ . '/config.php';

class SimpleCalDAVClient {
    private $baseUrl;
    private $calendarUrl;
    private $username;
    private $password;

    public function __construct() {
        $this->baseUrl = config('caldav_url');
        $this->username = config('caldav_username');
        $this->password = config('caldav_password');
        $calendar = config('caldav_calendar');

        if (empty($this->baseUrl) || empty($this->username) || empty($this->password) || empty($calendar)) {
            throw new Exception('Configuration CalDAV incomplète');
        }

        // Construire l'URL complète du calendrier
        $this->calendarUrl = rtrim($this->baseUrl, '/') . "/calendars/{$this->username}/{$calendar}/";
    }

    /**
     * Récupère tous les événements du calendrier
     */
    public function getEvents($startDate = null, $endDate = null) {
        // Requête REPORT CalDAV
        $xml = '<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
    <D:prop>
        <D:getetag />
        <C:calendar-data />
    </D:prop>
    <C:filter>
        <C:comp-filter name="VCALENDAR">
            <C:comp-filter name="VEVENT" />
        </C:comp-filter>
    </C:filter>
</C:calendar-query>';

        $response = $this->request('REPORT', $this->calendarUrl, $xml, [
            'Depth: 1',
            'Content-Type: application/xml; charset=utf-8'
        ]);

        if ($response['code'] != 207) {
            error_log("CalDAV getEvents error: HTTP {$response['code']}");
            return [];
        }

        return $this->parseEvents($response['body']);
    }

    /**
     * Crée un nouvel événement
     */
    public function createEvent($event) {
        $uid = $this->generateUID();
        $ics = $this->buildICS($event, $uid);

        $eventUrl = $this->calendarUrl . $uid . '.ics';

        $response = $this->request('PUT', $eventUrl, $ics, [
            'Content-Type: text/calendar; charset=utf-8',
            'If-None-Match: *' // Ne créer que si n'existe pas
        ]);

        if ($response['code'] >= 200 && $response['code'] < 300) {
            return $uid;
        }

        error_log("CalDAV createEvent error: HTTP {$response['code']} - {$response['body']}");
        return false;
    }

    /**
     * Met à jour un événement existant
     */
    public function updateEvent($uid, $event) {
        $ics = $this->buildICS($event, $uid);
        $eventUrl = $this->calendarUrl . $uid . '.ics';

        $response = $this->request('PUT', $eventUrl, $ics, [
            'Content-Type: text/calendar; charset=utf-8'
        ]);

        return $response['code'] >= 200 && $response['code'] < 300;
    }

    /**
     * Supprime un événement
     */
    public function deleteEvent($uid) {
        $eventUrl = $this->calendarUrl . $uid . '.ics';

        $response = $this->request('DELETE', $eventUrl);

        return $response['code'] >= 200 && $response['code'] < 300;
    }

    /**
     * Parse les événements depuis la réponse XML CalDAV
     */
    private function parseEvents($xmlBody) {
        $events = [];

        try {
            $xml = @simplexml_load_string($xmlBody);
            if ($xml === false) {
                return [];
            }

            $xml->registerXPathNamespace('d', 'DAV:');
            $xml->registerXPathNamespace('cal', 'urn:ietf:params:xml:ns:caldav');

            $responses = $xml->xpath('//d:response');

            foreach ($responses as $response) {
                $calData = $response->xpath('.//cal:calendar-data');

                if (!empty($calData)) {
                    $icsContent = (string) $calData[0];
                    $event = $this->parseICS($icsContent);

                    if ($event) {
                        $events[] = $event;
                    }
                }
            }

        } catch (Exception $e) {
            error_log("CalDAV parseEvents error: " . $e->getMessage());
        }

        return $events;
    }

    /**
     * Parse un fichier ICS pour extraire les données de l'événement
     */
    private function parseICS($icsContent) {
        $lines = explode("\n", $icsContent);
        $event = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // UID
            if (strpos($line, 'UID:') === 0) {
                $event['uid'] = substr($line, 4);
            }

            // Titre (SUMMARY)
            if (strpos($line, 'SUMMARY:') === 0) {
                $event['titre'] = $this->unescapeICS(substr($line, 8));
            }

            // Date de début (DTSTART)
            if (strpos($line, 'DTSTART') === 0) {
                $event = array_merge($event, $this->parseDTSTART($line));
            }

            // Récurrence (RRULE)
            if (strpos($line, 'RRULE:') === 0) {
                $rrule = substr($line, 6);
                $event['recurrent'] = $this->parseRRule($rrule);
            }

            // Catégories (pour la couleur)
            if (strpos($line, 'CATEGORIES:') === 0) {
                $category = substr($line, 11);
                $event['couleur'] = $this->categoryToColor($category);
            }
        }

        // Couleur par défaut si non spécifiée
        if (!isset($event['couleur'])) {
            $event['couleur'] = '#feff9c';
        }

        return !empty($event['titre']) ? $event : null;
    }

    /**
     * Parse la ligne DTSTART pour extraire date et heure
     */
    private function parseDTSTART($line) {
        $result = [];

        // DTSTART;VALUE=DATE:20251212 (événement toute la journée)
        if (strpos($line, 'VALUE=DATE:') !== false) {
            preg_match('/VALUE=DATE:(\d{8})/', $line, $matches);
            if (!empty($matches[1])) {
                $dateStr = $matches[1];
                $result['date'] = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
            }
        }
        // DTSTART:20251212T140000Z (événement avec heure en UTC)
        // DTSTART:20251212T140000 (événement avec heure locale)
        else {
            preg_match('/DTSTART[^:]*:(\d{8})T(\d{6})/', $line, $matches);
            if (!empty($matches[1]) && !empty($matches[2])) {
                $dateStr = $matches[1];
                $timeStr = $matches[2];

                $result['date'] = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
                $result['heure'] = substr($timeStr, 0, 2) . ':' . substr($timeStr, 2, 2);
            }
        }

        return $result;
    }

    /**
     * Construit un fichier ICS à partir d'un événement
     */
    private function buildICS($event, $uid) {
        $now = gmdate('Ymd\THis\Z');

        // Date de début
        $dtstart = $this->formatDTSTART($event);

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Agenda Papou Mamine//FR\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:$uid\r\n";
        $ics .= "DTSTAMP:$now\r\n";
        $ics .= $dtstart;
        $ics .= "SUMMARY:" . $this->escapeICS($event['titre']) . "\r\n";

        // Catégorie (couleur)
        if (!empty($event['couleur'])) {
            $category = $this->colorToCategory($event['couleur']);
            $ics .= "CATEGORIES:$category\r\n";
        }

        // Récurrence
        if (!empty($event['recurrent'])) {
            $rrule = $this->buildRRule($event['recurrent']);
            $ics .= "RRULE:$rrule\r\n";
        }

        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Formate DTSTART pour ICS
     */
    private function formatDTSTART($event) {
        $date = str_replace('-', '', $event['date']); // 20251212

        if (!empty($event['heure'])) {
            // Événement avec heure
            $time = str_replace(':', '', $event['heure']) . '00'; // 140000
            return "DTSTART:{$date}T{$time}\r\n";
        } else {
            // Événement toute la journée
            return "DTSTART;VALUE=DATE:{$date}\r\n";
        }
    }

    /**
     * Conversion récurrence app -> RRULE
     */
    private function buildRRule($recurrent) {
        $map = [
            'quotidien' => 'FREQ=DAILY',
            'hebdomadaire' => 'FREQ=WEEKLY',
            'mensuel' => 'FREQ=MONTHLY',
            'annuel' => 'FREQ=YEARLY',
        ];

        return $map[$recurrent] ?? 'FREQ=DAILY';
    }

    /**
     * Conversion RRULE -> récurrence app
     */
    private function parseRRule($rrule) {
        if (strpos($rrule, 'FREQ=DAILY') !== false) return 'quotidien';
        if (strpos($rrule, 'FREQ=WEEKLY') !== false) return 'hebdomadaire';
        if (strpos($rrule, 'FREQ=MONTHLY') !== false) return 'mensuel';
        if (strpos($rrule, 'FREQ=YEARLY') !== false) return 'annuel';
        return null;
    }

    /**
     * Mapping couleur -> catégorie
     */
    private function colorToCategory($color) {
        $map = [
            '#feff9c' => 'Jaune',
            '#a7ffeb' => 'Vert',
            '#cbf0f8' => 'Bleu',
            '#aecbfa' => 'Bleu clair',
            '#d7aefb' => 'Violet',
            '#fdcfe8' => 'Rose',
            '#e6c9a8' => 'Marron',
            '#e8eaed' => 'Gris',
        ];

        return $map[$color] ?? 'Jaune';
    }

    /**
     * Mapping catégorie -> couleur
     */
    private function categoryToColor($category) {
        $map = [
            'Jaune' => '#feff9c',
            'Vert' => '#a7ffeb',
            'Bleu' => '#cbf0f8',
            'Bleu clair' => '#aecbfa',
            'Violet' => '#d7aefb',
            'Rose' => '#fdcfe8',
            'Marron' => '#e6c9a8',
            'Gris' => '#e8eaed',
        ];

        return $map[$category] ?? '#feff9c';
    }

    /**
     * Échappe les caractères spéciaux pour ICS
     */
    private function escapeICS($text) {
        return str_replace([',', ';', '\\', "\n"], ['\\,', '\\;', '\\\\', '\\n'], $text);
    }

    /**
     * Déséchappe les caractères spéciaux depuis ICS
     */
    private function unescapeICS($text) {
        return str_replace(['\\,', '\\;', '\\\\', '\\n'], [',', ';', '\\', "\n"], $text);
    }

    /**
     * Génère un UID unique
     */
    private function generateUID() {
        return 'event-' . uniqid() . '@papoumamine';
    }

    /**
     * Effectue une requête HTTP vers le serveur CalDAV
     */
    private function request($method, $url, $body = null, $headers = []) {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'code' => $httpCode,
            'body' => $response
        ];
    }
}
?>
