<?php
/**
 * Notion API Client für PHP
 *
 * Version 1.0
 * Dokumentation: https://developers.notion.com/reference/intro
 */

class NotionAPI {
    private $apiToken;
    private $databaseId;
    private $baseUrl;
    private $apiVersion;
    private $timeout;
    private $debug;

    private $lastError = null;

    public function __construct($apiToken, $databaseId, $options = []) {
        $this->apiToken = $apiToken;
        $this->databaseId = $databaseId;
        $this->baseUrl = $options['baseUrl'] ?? (defined('NOTION_API_URL') ? NOTION_API_URL : 'https://api.notion.com/v1');
        $this->apiVersion = $options['apiVersion'] ?? (defined('NOTION_API_VERSION') ? NOTION_API_VERSION : '2022-06-28');
        $this->timeout = $options['timeout'] ?? (defined('NOTION_API_TIMEOUT') ? NOTION_API_TIMEOUT : 30);
        $this->debug = $options['debug'] ?? (defined('NOTION_DEBUG') ? NOTION_DEBUG : false);
    }

    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Erstellt eine neue Seite in der Notion Datenbank
     *
     * @param array $weekPlanData Array mit Wochenplan-Daten
     * @return array Success/Error Information
     */
    public function createWeekPlanPage($weekPlanData) {
        $this->log('=== CREATE NOTION PAGE START ===');

        if (empty($this->apiToken)) {
            $this->lastError = 'Notion API Token ist nicht konfiguriert';
            $this->log($this->lastError, 'ERROR');
            return ['success' => false, 'error' => $this->lastError];
        }

        if (empty($this->databaseId)) {
            $this->lastError = 'Notion Database ID ist nicht konfiguriert';
            $this->log($this->lastError, 'ERROR');
            return ['success' => false, 'error' => $this->lastError];
        }

        $url = $this->baseUrl . '/pages';

        // Erstelle den Page-Inhalt
        $pageData = $this->buildPageData($weekPlanData);

        $response = $this->request('POST', $url, json_encode($pageData), $this->getAuthHeaders());

        if ($response['success']) {
            $this->log('✅ Notion Seite erfolgreich erstellt');
            return [
                'success' => true,
                'page_id' => $response['data']['id'] ?? null,
                'url' => $response['data']['url'] ?? null
            ];
        }

        $errorMsg = 'Notion API Fehler: ' . ($response['error'] ?? 'Unknown error');
        $this->log($errorMsg, 'ERROR');
        $this->lastError = $errorMsg;
        return [
            'success' => false,
            'error' => $this->lastError,
            'http_code' => $response['http_code'] ?? null
        ];
    }

    /**
     * Baut die Page-Daten für Notion auf TEST
     */
    private function buildPageData($weekPlanData) {
        $weekNumber = $weekPlanData['week_number'] ?? date('W');
        $year = $weekPlanData['year'] ?? date('Y');
        $userName = $weekPlanData['user_name'] ?? 'Unbekannt';
        $meals = $weekPlanData['meals'] ?? [];

        // Erstelle den Titel
        $title = "Wochenplan KW {$weekNumber}/{$year} - {$userName}";

        // Erstelle den Content-Block mit allen Mahlzeiten
        $contentBlocks = [];

        // Gruppiere Mahlzeiten nach Wochentag
        $weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

        foreach ($weekdays as $weekday) {
            // Überschrift für den Tag
            $contentBlocks[] = [
                'object' => 'block',
                'type' => 'heading_2',
                'heading_2' => [
                    'rich_text' => [
                        [
                            'type' => 'text',
                            'text' => ['content' => $weekday]
                        ]
                    ]
                ]
            ];

            // Finde Mahlzeiten für diesen Tag
            $dayMeals = array_filter($meals, function($meal) use ($weekday) {
                return ($meal['weekday'] ?? '') === $weekday;
            });

            if (empty($dayMeals)) {
                $contentBlocks[] = [
                    'object' => 'block',
                    'type' => 'paragraph',
                    'paragraph' => [
                        'rich_text' => [
                            [
                                'type' => 'text',
                                'text' => ['content' => 'Keine Mahlzeiten geplant']
                            ]
                        ]
                    ]
                ];
            } else {
                foreach ($dayMeals as $meal) {
                    $mealType = $meal['meal_type'] ?? '';
                    $recipeTitle = $meal['recipe_title'] ?? 'Unbekanntes Rezept';
                    $ingredients = $meal['ingredients'] ?? [];

                    // Mahlzeit-Überschrift
                    $contentBlocks[] = [
                        'object' => 'block',
                        'type' => 'heading_3',
                        'heading_3' => [
                            'rich_text' => [
                                [
                                    'type' => 'text',
                                    'text' => ['content' => "{$mealType}: {$recipeTitle}"],
                                    'annotations' => ['bold' => true]
                                ]
                            ]
                        ]
                    ];

                    // Zutaten als Liste
                    if (!empty($ingredients)) {
                        $contentBlocks[] = [
                            'object' => 'block',
                            'type' => 'paragraph',
                            'paragraph' => [
                                'rich_text' => [
                                    [
                                        'type' => 'text',
                                        'text' => ['content' => 'Zutaten:'],
                                        'annotations' => ['italic' => true]
                                    ]
                                ]
                            ]
                        ];

                        foreach ($ingredients as $ingredient) {
                            $contentBlocks[] = [
                                'object' => 'block',
                                'type' => 'bulleted_list_item',
                                'bulleted_list_item' => [
                                    'rich_text' => [
                                        [
                                            'type' => 'text',
                                            'text' => ['content' => $ingredient]
                                        ]
                                    ]
                                ]
                            ];
                        }
                    }
                }
            }

            // Trennlinie zwischen Tagen
            $contentBlocks[] = [
                'object' => 'block',
                'type' => 'divider',
                'divider' => new stdClass()
            ];
        }

        // Notion Page-Struktur
        return [
            'parent' => [
                'database_id' => $this->databaseId
            ],
            'properties' => [
                'Name' => [
                    'title' => [
                        [
                            'text' => [
                                'content' => $title
                            ]
                        ]
                    ]
                ],
                'Woche' => [
                    'number' => (int)$weekNumber
                ],
                'Jahr' => [
                    'number' => (int)$year
                ],
                'Benutzer' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $userName
                            ]
                        ]
                    ]
                ]
            ],
            'children' => $contentBlocks
        ];
    }

    /**
     * Sendet HTTP-Request an Notion API
     */
    private function request($method, $url, $body = null, $headers = []) {
        $this->log("Request: {$method} {$url}");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                $this->log("Request Body: " . substr($body, 0, 500) . (strlen($body) > 500 ? '...' : ''));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $this->log("Response HTTP Code: {$httpCode}");

        if ($curlError) {
            $this->log("CURL Error: {$curlError}", 'ERROR');
            return [
                'success' => false,
                'error' => $curlError,
                'http_code' => $httpCode
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            $this->log("Response: Success");
            return [
                'success' => true,
                'data' => $data,
                'http_code' => $httpCode
            ];
        }

        $error = $data['message'] ?? $response;
        $this->log("Response Error: {$error}", 'ERROR');

        return [
            'success' => false,
            'error' => $error,
            'data' => $data,
            'http_code' => $httpCode
        ];
    }

    /**
     * Erstellt die Auth-Header für Notion API
     */
    private function getAuthHeaders() {
        return [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
            'Notion-Version: ' . $this->apiVersion
        ];
    }

    /**
     * Logging-Funktion
     */
    private function log($message, $level = 'INFO') {
        if (!$this->debug && $level !== 'ERROR') {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] NotionAPI: {$message}\n";

        // Schreibe in Log-Datei
        if (defined('LOGS_PATH')) {
            $logFile = LOGS_PATH . '/notion_api.log';
            @file_put_contents($logFile, $logMessage, FILE_APPEND);
        }

        // Ausgabe für Debugging
        if ($this->debug) {
            error_log($logMessage);
        }
    }
}
