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

        // Debug: Logge die Database ID (immer als ERROR um sicherzustellen dass es geloggt wird)
        $this->log('NotionAPI initialisiert mit Database ID: [' . $this->databaseId . '] (Länge: ' . strlen($this->databaseId) . ')', 'ERROR');
    }

    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Erstellt Einträge in der Notion Datenbank für den Wochenplan
     * Erstellt einen Eintrag pro Wochentag
     *
     * @param array $weekPlanData Array mit Wochenplan-Daten
     * @return array Success/Error Information
     */
    public function createWeekPlanPage($weekPlanData) {
        $this->log('=== CREATE NOTION PAGES START ===');

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

        $weekNumber = $weekPlanData['week_number'] ?? date('W');
        $year = $weekPlanData['year'] ?? date('Y');
        $userName = $weekPlanData['user_name'] ?? 'Unbekannt';
        $meals = $weekPlanData['meals'] ?? [];

        $url = $this->baseUrl . '/pages';
        $weekdays = ['Sonntag', 'Samstag', 'Freitag', 'Donnerstag', 'Mittwoch', 'Dienstag', 'Montag'];

        $createdPages = [];
        $errors = [];

        // Erstelle einen Eintrag pro Wochentag
        foreach ($weekdays as $weekday) {
            // Finde Mahlzeiten für diesen Tag
            $mittagessen = '';
            $abendessen = '';
            $benutzer = $userName;

            foreach ($meals as $meal) {
                if (($meal['weekday'] ?? '') === $weekday) {
                    $mealType = $meal['meal_type'] ?? '';
                    $recipeTitle = $meal['recipe_title'] ?? '';

                    // Debug: Logge die Mahlzeit
                    $this->log("{$weekday}: meal_type='{$mealType}', recipe='{$recipeTitle}'", 'ERROR');

                    if ($mealType === 'Mittagessen' || $mealType === 'Mittag') {
                        $mittagessen = $recipeTitle;
                        if (!empty($meal['modified_by_name'])) {
                            $benutzer = $meal['modified_by_name'];
                        }
                    } elseif ($mealType === 'Abendessen' || $mealType === 'Abend') {
                        $abendessen = $recipeTitle;
                        if (!empty($meal['modified_by_name'])) {
                            $benutzer = $meal['modified_by_name'];
                        }
                    }
                }
            }

            // Erstelle Page-Daten für diesen Tag
            $pageData = $this->buildPageDataForDay($weekday, $mittagessen, $abendessen, $benutzer, $weekNumber, $year);

            $this->log("Erstelle Eintrag für {$weekday}");

            $response = $this->request('POST', $url, json_encode($pageData), $this->getAuthHeaders());

            if ($response['success']) {
                $createdPages[] = [
                    'weekday' => $weekday,
                    'page_id' => $response['data']['id'] ?? null,
                    'url' => $response['data']['url'] ?? null
                ];
                $this->log("✅ {$weekday} erstellt");
            } else {
                $errorMsg = "Fehler bei {$weekday}: " . ($response['error'] ?? 'Unknown error');
                $errors[] = $errorMsg;
                $this->log($errorMsg, 'ERROR');
            }
        }

        if (count($errors) > 0) {
            $this->lastError = implode('; ', $errors);
            return [
                'success' => false,
                'error' => $this->lastError,
                'created_pages' => $createdPages
            ];
        }

        $this->log('✅ Alle Einträge erfolgreich erstellt');
        return [
            'success' => true,
            'created_pages' => $createdPages,
            'total' => count($createdPages)
        ];
    }

    /**
     * Baut die Page-Daten für einen einzelnen Tag auf
     */
    private function buildPageDataForDay($weekday, $mittagessen, $abendessen, $benutzer, $weekNumber, $year) {
        // Erstelle einen Namen für die Seite (ohne Wochentag)
        $name = "KW {$weekNumber}/{$year}";

        return [
            'parent' => [
                'database_id' => $this->databaseId
            ],
            'properties' => [
                'Wochentag' => [
                    'title' => [
                        [
                            'text' => [
                                'content' => $weekday
                            ]
                        ]
                    ]
                ],
                'Name' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $name
                            ]
                        ]
                    ]
                ],
                'Mittagessen' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $mittagessen ?: '-'
                            ]
                        ]
                    ]
                ],
                'Abendessen' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $abendessen ?: '-'
                            ]
                        ]
                    ]
                ],
                'Benutzer' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $benutzer
                            ]
                        ]
                    ]
                ],
                'Woche' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => (string)$weekNumber
                            ]
                        ]
                    ]
                ],
                'Jahr' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => (string)$year
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Baut die Page-Daten für Notion auf (alte Methode, nicht mehr verwendet)
     */
    private function buildPageData_OLD($weekPlanData) {
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
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $title
                            ]
                        ]
                    ]
                ],
                'Woche' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => (string)$weekNumber
                            ]
                        ]
                    ]
                ],
                'Jahr' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => (string)$year
                            ]
                        ]
                    ]
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
