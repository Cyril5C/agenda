<?php
/**
 * Client CalDAV pour gérer les événements via Nextcloud
 * Utilise sabre/dav pour communiquer avec le serveur CalDAV
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Sabre\DAV\Client;
use Sabre\VObject;

class CalDAVClient {
    private $client;
    private $calendarPath;
    private $username;

    public function __construct() {
        $caldavUrl = config('caldav_url');
        $this->username = config('caldav_username');
        $password = config('caldav_password');
        $calendarName = config('caldav_calendar');

        if (empty($caldavUrl) || empty($this->username) || empty($password)) {
            throw new Exception('Configuration CalDAV incomplète. Vérifiez les variables CALDAV_URL, CALDAV_USERNAME et CALDAV_PASSWORD.');
        }

        // Déterminer si l'URL est déjà complète (contient /calendars/) ou juste la base DAV
        if (strpos($caldavUrl, '/calendars/') !== false) {
            // URL complète du calendrier fournie (ex: https://host/dav/calendars/user/cal/)
            $baseUrl = rtrim($caldavUrl, '/');
            $this->calendarPath = '/';
        } else {
            // URL de base DAV fournie, construire le chemin
            $baseUrl = rtrim($caldavUrl, '/');
            $this->calendarPath = "/calendars/{$this->username}/{$calendarName}/";
        }

        // Configuration du client
        $settings = [
            'baseUri' => $baseUrl,
            'userName' => $this->username,
            'password' => $password,
        ];

        $this->client = new Client($settings);
    }

    /**
     * Récupère tous les événements du calendrier
     * @param string $start Date de début au format YYYY-MM-DD (optionnel)
     * @param string $end Date de fin au format YYYY-MM-DD (optionnel)
     * @return array Tableau d'événements au format de l'application
     */
    public function getEvents($start = null, $end = null) {
        try {
            // Requête REPORT CalDAV pour récupérer les événements
            $xml = '<?xml version="1.0" encoding="utf-8" ?>
                <C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
                    <D:prop>
                        <D:getetag />
                        <C:calendar-data />
                    </D:prop>
                    <C:filter>
                        <C:comp-filter name="VCALENDAR">
                            <C:comp-filter name="VEVENT">';

            if ($start && $end) {
                $xml .= '<C:time-range start="' . $this->formatDateForCalDAV($start) . '"
                                      end="' . $this->formatDateForCalDAV($end) . '"/>';
            }

            $xml .= '       </C:comp-filter>
                        </C:comp-filter>
                    </C:filter>
                </C:calendar-query>';

            $response = $this->client->request('REPORT', $this->calendarPath, $xml, [
                'Depth' => '1',
                'Content-Type' => 'application/xml; charset=utf-8',
            ]);

            return $this->parseCalendarResponse($response['body']);

        } catch (Exception $e) {
            error_log('Erreur CalDAV getEvents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crée un nouvel événement
     * @param array $event Événement au format de l'application
     * @return bool|string UID de l'événement créé ou false en cas d'erreur
     */
    public function createEvent($event) {
        try {
            $vcalendar = new VObject\Component\VCalendar();

            $vevent = $vcalendar->add('VEVENT', [
                'SUMMARY' => $event['titre'],
                'DTSTART' => $this->formatDateTimeForEvent($event),
            ]);

            // Ajouter la couleur comme catégorie
            if (!empty($event['couleur'])) {
                $vevent->add('CATEGORIES', $this->colorToCategory($event['couleur']));
            }

            // Support des événements récurrents
            if (!empty($event['recurrent'])) {
                $vevent->add('RRULE', $this->convertRecurrentToRRule($event['recurrent']));
            }

            // Générer un UID unique
            $uid = $this->generateUID();
            $vevent->UID = $uid;
            $vevent->DTSTAMP = new DateTime();

            // Créer le fichier .ics
            $icsContent = $vcalendar->serialize();
            $eventPath = $this->calendarPath . $uid . '.ics';

            $response = $this->client->request('PUT', $eventPath, $icsContent, [
                'Content-Type' => 'text/calendar; charset=utf-8',
            ]);

            return ($response['statusCode'] >= 200 && $response['statusCode'] < 300) ? $uid : false;

        } catch (Exception $e) {
            error_log('Erreur CalDAV createEvent: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour un événement existant
     * @param string $uid UID de l'événement
     * @param array $event Nouvelles données de l'événement
     * @return bool
     */
    public function updateEvent($uid, $event) {
        try {
            // Supprimer l'ancien et recréer (plus simple que PATCH)
            $this->deleteEvent($uid);

            // Forcer l'UID pour garder le même
            $vcalendar = new VObject\Component\VCalendar();

            $vevent = $vcalendar->add('VEVENT', [
                'SUMMARY' => $event['titre'],
                'DTSTART' => $this->formatDateTimeForEvent($event),
                'UID' => $uid,
            ]);

            if (!empty($event['couleur'])) {
                $vevent->add('CATEGORIES', $this->colorToCategory($event['couleur']));
            }

            if (!empty($event['recurrent'])) {
                $vevent->add('RRULE', $this->convertRecurrentToRRule($event['recurrent']));
            }

            $vevent->DTSTAMP = new DateTime();

            $icsContent = $vcalendar->serialize();
            $eventPath = $this->calendarPath . $uid . '.ics';

            $response = $this->client->request('PUT', $eventPath, $icsContent, [
                'Content-Type' => 'text/calendar; charset=utf-8',
            ]);

            return $response['statusCode'] >= 200 && $response['statusCode'] < 300;

        } catch (Exception $e) {
            error_log('Erreur CalDAV updateEvent: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un événement
     * @param string $uid UID de l'événement
     * @return bool
     */
    public function deleteEvent($uid) {
        try {
            $eventPath = $this->calendarPath . $uid . '.ics';

            $response = $this->client->request('DELETE', $eventPath);

            return $response['statusCode'] >= 200 && $response['statusCode'] < 300;

        } catch (Exception $e) {
            error_log('Erreur CalDAV deleteEvent: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Parse la réponse XML CalDAV et convertit en format application
     */
    private function parseCalendarResponse($xmlBody) {
        $events = [];

        try {
            $xml = new SimpleXMLElement($xmlBody);
            $xml->registerXPathNamespace('d', 'DAV:');
            $xml->registerXPathNamespace('cal', 'urn:ietf:params:xml:ns:caldav');

            $responses = $xml->xpath('//d:response');

            foreach ($responses as $response) {
                $calendarData = $response->xpath('.//cal:calendar-data');

                if (!empty($calendarData)) {
                    $icsContent = (string) $calendarData[0];
                    $vcalendar = VObject\Reader::read($icsContent);

                    foreach ($vcalendar->VEVENT as $vevent) {
                        $event = $this->convertVEventToAppFormat($vevent);
                        if ($event) {
                            $events[] = $event;
                        }
                    }
                }
            }

        } catch (Exception $e) {
            error_log('Erreur parsing CalDAV: ' . $e->getMessage());
        }

        return $events;
    }

    /**
     * Convertit un VEVENT en format application
     */
    private function convertVEventToAppFormat($vevent) {
        $event = [
            'uid' => (string) $vevent->UID,
            'titre' => (string) $vevent->SUMMARY,
        ];

        // Date et heure
        if (isset($vevent->DTSTART)) {
            $dtstart = $vevent->DTSTART->getDateTime();
            $event['date'] = $dtstart->format('Y-m-d');

            // Si l'événement a une heure (pas tout le jour)
            if (!isset($vevent->DTSTART['VALUE']) || $vevent->DTSTART['VALUE'] != 'DATE') {
                $event['heure'] = $dtstart->format('H:i');
            }
        }

        // Couleur depuis les catégories
        if (isset($vevent->CATEGORIES)) {
            $event['couleur'] = $this->categoryToColor((string) $vevent->CATEGORIES);
        } else {
            $event['couleur'] = '#feff9c'; // Couleur par défaut
        }

        // Récurrence
        if (isset($vevent->RRULE)) {
            $event['recurrent'] = $this->convertRRuleToRecurrent((string) $vevent->RRULE);
        }

        return $event;
    }

    /**
     * Formate une date/heure pour un événement CalDAV
     */
    private function formatDateTimeForEvent($event) {
        $dateStr = $event['date'];

        if (!empty($event['heure'])) {
            // Événement avec heure
            return new DateTime($dateStr . ' ' . $event['heure']);
        } else {
            // Événement toute la journée
            $dt = new DateTime($dateStr);
            $dt->setTime(0, 0, 0);
            return $dt;
        }
    }

    /**
     * Formate une date pour les requêtes CalDAV (format iCalendar)
     */
    private function formatDateForCalDAV($date) {
        $dt = new DateTime($date);
        return $dt->format('Ymd\THis\Z');
    }

    /**
     * Convertit le format de récurrence de l'app en RRULE
     */
    private function convertRecurrentToRRule($recurrent) {
        $map = [
            'quotidien' => 'FREQ=DAILY',
            'hebdomadaire' => 'FREQ=WEEKLY',
            'mensuel' => 'FREQ=MONTHLY',
            'annuel' => 'FREQ=YEARLY',
        ];

        return $map[$recurrent] ?? 'FREQ=DAILY';
    }

    /**
     * Convertit une RRULE en format de récurrence de l'app
     */
    private function convertRRuleToRecurrent($rrule) {
        if (strpos($rrule, 'FREQ=DAILY') !== false) return 'quotidien';
        if (strpos($rrule, 'FREQ=WEEKLY') !== false) return 'hebdomadaire';
        if (strpos($rrule, 'FREQ=MONTHLY') !== false) return 'mensuel';
        if (strpos($rrule, 'FREQ=YEARLY') !== false) return 'annuel';

        return null;
    }

    /**
     * Convertit une couleur en catégorie
     */
    private function colorToCategory($color) {
        $categories = [
            '#feff9c' => 'Jaune',
            '#a7ffeb' => 'Vert',
            '#cbf0f8' => 'Bleu',
            '#aecbfa' => 'Bleu clair',
            '#d7aefb' => 'Violet',
            '#fdcfe8' => 'Rose',
            '#e6c9a8' => 'Marron',
            '#e8eaed' => 'Gris',
        ];

        return $categories[$color] ?? 'Jaune';
    }

    /**
     * Convertit une catégorie en couleur
     */
    private function categoryToColor($category) {
        $colors = [
            'Jaune' => '#feff9c',
            'Vert' => '#a7ffeb',
            'Bleu' => '#cbf0f8',
            'Bleu clair' => '#aecbfa',
            'Violet' => '#d7aefb',
            'Rose' => '#fdcfe8',
            'Marron' => '#e6c9a8',
            'Gris' => '#e8eaed',
        ];

        return $colors[$category] ?? '#feff9c';
    }

    /**
     * Génère un UID unique pour un événement
     */
    private function generateUID() {
        return uniqid('event-', true) . '@' . gethostname();
    }
}
?>
