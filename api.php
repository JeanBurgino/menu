<?php
/**
 * Menüplaner API v2.0
 * 
 * Optimierte Version mit:
 * - Sauberem Routing
 * - Besserer Fehlerbehandlung  
 * - Input-Validierung
 * - Response-Formatting
 */

// Konfiguration laden
require_once __DIR__ . '/config.php';

// Bring! Config laden (optional)
if (file_exists(__DIR__ . '/bring-config.php')) {
    require_once __DIR__ . '/bring-config.php';
}

/**
 * API Handler Class
 */
class MenuPlannerAPI {
    
    private $db;
    private $method;
    private $endpoint;
    private $params;
    
    public function __construct() {
        // CORS Headers
        $this->setCORSHeaders();
        
        // Request-Info parsen
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->endpoint = $this->parseEndpoint();
        $this->params = $_GET;
        
        // Database Instance
        $this->db = Database::getInstance();
        
        // OPTIONS Request für CORS Preflight
        if ($this->method === 'OPTIONS') {
            $this->sendJSON(['status' => 'ok']);
        }
    }
    
    /**
     * Handle Request
     */
    public function handleRequest() {
        try {
            $action = $this->params['action'] ?? '';
            
            // Route zu entsprechender Funktion
            switch($action) {
                // Benutzer
                case 'users':
                    return $this->handleUsers();
                case 'update_user':
                    return $this->updateUser();
                case 'delete_user':
                    return $this->deleteUser();
                
                // Rezepte
                case 'recipes':
                    return $this->handleRecipes();
                case 'update_recipe':
                    return $this->updateRecipe();
                case 'delete_recipe':
                    return $this->deleteRecipe();
                case 'recipe_ingredients':
                    return $this->handleRecipeIngredients();
                
                // Wochenplan
                case 'weekplan':
                    return $this->handleWeekPlan();
                case 'update_meal':
                    return $this->updateMeal();
                case 'toggle_lock':
                    return $this->toggleLock();
                case 'toggle_disabled':
                    return $this->toggleDisabled();
                
                // Bring! Export
                case 'save_bring_recipe':
                    return $this->saveBringRecipe();
                case 'export_to_bring_direct':
                    return $this->exportToBringDirect();
                
                default:
                    throw new Exception('Ungültige Aktion: ' . $action, 400);
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }
    
    // ==================== BENUTZER ====================
    
    private function handleUsers() {
        if ($this->method === 'GET') {
            return $this->getUsers();
        } elseif ($this->method === 'POST') {
            return $this->createUser();
        }
        throw new Exception('Method not allowed', 405);
    }
    
    private function getUsers() {
        $users = $this->db->fetchAll(
            "SELECT id, name, profile_image, profile_picture, is_admin 
             FROM users 
             ORDER BY id"
        );
        
        // Boolean konvertieren
        foreach ($users as &$user) {
            $user['is_admin'] = (bool)$user['is_admin'];
        }
        
        $this->sendJSON($users);
    }
    
    private function createUser() {
        $data = $this->getRequestData();
        
        // Validierung
        $this->validate($data, ['name']);
        
        $id = $this->db->query(
            "INSERT INTO users (name, profile_image, is_admin) VALUES (?, ?, ?)",
            [
                $data['name'],
                $data['profile_image'] ?? '👤',
                $data['is_admin'] ?? false
            ]
        );
        
        $this->sendJSON([
            'id' => $this->db->lastInsertId(),
            'name' => $data['name'],
            'profile_image' => $data['profile_image'] ?? '👤',
            'is_admin' => $data['is_admin'] ?? false
        ], 201);
    }
    
    private function updateUser() {
        $id = $this->params['id'] ?? 0;
        $data = $this->getRequestData();
        
        $this->validate($data, ['name']);
        
        $this->db->execute(
            "UPDATE users 
             SET name = ?, profile_image = ?, profile_picture = ? 
             WHERE id = ?",
            [
                $data['name'],
                $data['profile_image'] ?? '👤',
                $data['profile_picture'] ?? null,
                $id
            ]
        );
        
        $this->sendJSON(['success' => true, 'id' => $id]);
    }
    
    private function deleteUser() {
        $id = $this->params['id'] ?? 0;
        
        // Schutz für Admin (ID 2)
        if ($id == 2) {
            throw new Exception('Admin-Benutzer kann nicht gelöscht werden', 403);
        }
        
        $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);
        $this->sendJSON(['success' => true]);
    }
    
    // ==================== REZEPTE ====================
    
    private function handleRecipes() {
        if ($this->method === 'GET') {
            return $this->getRecipes();
        } elseif ($this->method === 'POST') {
            return $this->createRecipe();
        }
        throw new Exception('Method not allowed', 405);
    }
    
    private function getRecipes() {
        $recipes = $this->db->fetchAll("
            SELECT r.id, r.title, r.ingredients, r.is_lunch, r.is_dinner, r.is_weekend,
                   r.created_by, r.created_at, r.last_modified_by, r.last_modified_at,
                   u1.name as creator_name, u1.profile_picture as creator_picture,
                   u2.name as modifier_name, u2.profile_picture as modifier_picture
            FROM recipes r
            LEFT JOIN users u1 ON r.created_by = u1.id
            LEFT JOIN users u2 ON r.last_modified_by = u2.id
            ORDER BY r.title
        ");
        
        // Boolean konvertieren und Zutaten laden
        foreach ($recipes as &$recipe) {
            $recipe['is_lunch'] = (bool)$recipe['is_lunch'];
            $recipe['is_dinner'] = (bool)$recipe['is_dinner'];
            $recipe['is_weekend'] = (bool)$recipe['is_weekend'];
            
            // Zutaten laden
            $recipe['ingredient_list'] = $this->db->fetchAll(
                "SELECT id, name, specification, position 
                 FROM recipe_ingredients 
                 WHERE recipe_id = ? 
                 ORDER BY position ASC",
                [$recipe['id']]
            );
        }
        
        $this->sendJSON($recipes);
    }
    
    private function createRecipe() {
        $data = $this->getRequestData();
        
        $this->validate($data, ['title']);
        
        $this->db->query(
            "INSERT INTO recipes (title, ingredients, is_lunch, is_dinner, is_weekend, created_by, last_modified_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['title'],
                $data['ingredients'] ?? null,
                $data['is_lunch'] ?? true,
                $data['is_dinner'] ?? true,
                $data['is_weekend'] ?? false,
                $data['created_by'] ?? null,
                $data['created_by'] ?? null
            ]
        );
        
        $this->sendJSON([
            'id' => $this->db->lastInsertId(),
            'title' => $data['title'],
            'success' => true
        ], 201);
    }
    
    private function updateRecipe() {
        $id = $this->params['id'] ?? 0;
        $data = $this->getRequestData();
        
        $this->validate($data, ['title']);
        
        $this->db->execute(
            "UPDATE recipes 
             SET title = ?, ingredients = ?, is_lunch = ?, is_dinner = ?, is_weekend = ?, last_modified_by = ?
             WHERE id = ?",
            [
                $data['title'],
                $data['ingredients'] ?? null,
                $data['is_lunch'] ?? true,
                $data['is_dinner'] ?? true,
                $data['is_weekend'] ?? false,
                $data['user_id'] ?? null,
                $id
            ]
        );
        
        $this->sendJSON(['success' => true, 'id' => $id]);
    }
    
    private function deleteRecipe() {
        $id = $this->params['id'] ?? 0;
        
        // In Transaction wegen Zutaten
        $this->db->beginTransaction();
        try {
            $this->db->execute("DELETE FROM recipe_ingredients WHERE recipe_id = ?", [$id]);
            $this->db->execute("DELETE FROM recipes WHERE id = ?", [$id]);
            $this->db->commit();
            
            $this->sendJSON(['success' => true]);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    // ==================== ZUTATEN ====================
    
    private function handleRecipeIngredients() {
        $id = $this->params['id'] ?? 0;
        
        if ($this->method === 'GET') {
            return $this->getRecipeIngredients($id);
        } elseif ($this->method === 'POST') {
            return $this->saveRecipeIngredients($id);
        }
        throw new Exception('Method not allowed', 405);
    }
    
    private function getRecipeIngredients($recipeId) {
        $ingredients = $this->db->fetchAll(
            "SELECT id, name, specification, position 
             FROM recipe_ingredients 
             WHERE recipe_id = ? 
             ORDER BY position ASC",
            [$recipeId]
        );
        
        $this->sendJSON($ingredients);
    }
    
    private function saveRecipeIngredients($recipeId) {
        $data = $this->getRequestData();
        $ingredients = $data['ingredients'] ?? [];
        
        $this->db->beginTransaction();
        try {
            // Alte Zutaten löschen
            $this->db->execute("DELETE FROM recipe_ingredients WHERE recipe_id = ?", [$recipeId]);
            
            // Neue Zutaten einfügen
            $position = 0;
            foreach ($ingredients as $ingredient) {
                if (empty($ingredient['name'])) continue;
                
                $this->db->execute(
                    "INSERT INTO recipe_ingredients (recipe_id, name, specification, position) 
                     VALUES (?, ?, ?, ?)",
                    [
                        $recipeId,
                        $ingredient['name'],
                        $ingredient['specification'] ?? '',
                        $position++
                    ]
                );
            }
            
            $this->db->commit();
            $this->sendJSON(['success' => true, 'count' => $position]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    // ==================== WOCHENPLAN ====================
    
    private function handleWeekPlan() {
        if ($this->method === 'GET') {
            return $this->getWeekPlan();
        } elseif ($this->method === 'POST') {
            return $this->saveWeekPlan();
        }
        throw new Exception('Method not allowed', 405);
    }
    
    private function getWeekPlan() {
        $week = $this->getCurrentWeek();

        if (isset($this->params['week'])) {
            $week['weekNumber'] = (int)$this->params['week'];
            $week['year'] = (int)$this->params['year'];
        }

        $rows = $this->db->fetchAll("
            SELECT wp.*, r.title as recipe_title,
                   u.name as modified_by_name,
                   u.profile_image as modified_by_image,
                   u.profile_picture as modified_by_picture
            FROM week_plan wp
            LEFT JOIN recipes r ON wp.recipe_id = r.id
            LEFT JOIN users u ON wp.last_modified_by = u.id
            WHERE wp.week_number = ? AND wp.year = ?
        ", [$week['weekNumber'], $week['year']]);

        // Strukturieren
        $weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
        $plan = [];

        foreach ($weekdays as $day) {
            $plan[$day] = ['Mittag' => null, 'Abendessen' => null];
        }

        foreach ($rows as $row) {
            if (isset($plan[$row['weekday']])) {
                $plan[$row['weekday']][$row['meal_type']] = [
                    'id' => $row['id'],
                    'recipe_id' => $row['recipe_id'],
                    'recipe_title' => $row['recipe_title'],
                    'is_locked' => (bool)$row['is_locked'],
                    'is_disabled' => isset($row['is_disabled']) ? (bool)$row['is_disabled'] : false,
                    'last_modified_by' => $row['last_modified_by'],
                    'modified_by_name' => $row['modified_by_name'],
                    'modified_by_image' => $row['modified_by_image'],
                    'modified_by_picture' => $row['modified_by_picture']
                ];
            }
        }

        $this->sendJSON([
            'weekNumber' => $week['weekNumber'],
            'year' => $week['year'],
            'plan' => $plan
        ]);
    }
    
    private function saveWeekPlan() {
        $data = $this->getRequestData();
        $week = $this->getCurrentWeek();

        $weekNumber = $data['weekNumber'] ?? $week['weekNumber'];
        $year = $data['year'] ?? $week['year'];
        $plan = $data['plan'] ?? [];
        $userId = $data['user_id'] ?? null;

        $this->db->beginTransaction();
        try {
            foreach ($plan as $weekday => $meals) {
                // Prüfe ob $meals ein Array ist
                if (!is_array($meals)) {
                    continue;
                }

                foreach ($meals as $mealType => $mealData) {
                    // Prüfe ob mealData ein Array ist und recipe_id gesetzt ist
                    if (is_array($mealData) && !empty($mealData['recipe_id'])) {
                        $recipeId = $mealData['recipe_id'];

                        // Prüfe ob recipe_id existiert
                        $recipeExists = $this->db->fetchOne(
                            "SELECT id FROM recipes WHERE id = ?",
                            [$recipeId]
                        );

                        // Überspringe, wenn Rezept nicht existiert
                        if (!$recipeExists) {
                            $this->logError("Recipe with ID {$recipeId} does not exist, skipping", 400);
                            continue;
                        }

                        $modifiedBy = $mealData['last_modified_by'] ?? $userId;
                        $isLocked = isset($mealData['is_locked']) ? ($mealData['is_locked'] ? 1 : 0) : 0;

                        $this->db->execute("
                            INSERT INTO week_plan (week_number, year, weekday, meal_type, recipe_id, is_locked, last_modified_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                recipe_id = VALUES(recipe_id),
                                is_locked = VALUES(is_locked),
                                last_modified_by = VALUES(last_modified_by)
                        ", [
                            $weekNumber, $year, $weekday, $mealType,
                            $recipeId,
                            $isLocked,
                            $modifiedBy
                        ]);
                    }
                }
            }

            $this->db->commit();
            $this->sendJSON(['success' => true, 'weekNumber' => $weekNumber, 'year' => $year]);

        } catch (Exception $e) {
            $this->db->rollback();
            // Log detailed error
            $this->logError('saveWeekPlan failed: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString(), 500);
            throw $e;
        }
    }
    
    private function updateMeal() {
        $data = $this->getRequestData();
        $week = $this->getCurrentWeek();

        $weekNumber = $data['weekNumber'] ?? $week['weekNumber'];
        $year = $data['year'] ?? $week['year'];
        $weekday = $data['weekday'];
        $mealType = $data['mealType'];
        $recipeId = $data['recipe_id'] ?? null;
        $isLocked = $data['is_locked'] ?? false;
        $userId = $data['user_id'] ?? null;

        if ($recipeId) {
            // Prüfe ob recipe_id existiert
            $recipeExists = $this->db->fetchOne(
                "SELECT id FROM recipes WHERE id = ?",
                [$recipeId]
            );

            if (!$recipeExists) {
                throw new Exception("Rezept mit ID {$recipeId} existiert nicht", 404);
            }

            $this->db->execute("
                INSERT INTO week_plan (week_number, year, weekday, meal_type, recipe_id, is_locked, last_modified_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    recipe_id = VALUES(recipe_id),
                    is_locked = VALUES(is_locked),
                    last_modified_by = VALUES(last_modified_by)
            ", [$weekNumber, $year, $weekday, $mealType, $recipeId, $isLocked, $userId]);
        } else {
            $this->db->execute(
                "DELETE FROM week_plan
                 WHERE week_number = ? AND year = ? AND weekday = ? AND meal_type = ?",
                [$weekNumber, $year, $weekday, $mealType]
            );
        }

        $this->sendJSON(['success' => true]);
    }
    
    private function toggleLock() {
        $data = $this->getRequestData();

        $this->db->execute("
            UPDATE week_plan
            SET is_locked = NOT is_locked
            WHERE week_number = ? AND year = ? AND weekday = ? AND meal_type = ?
        ", [
            $data['weekNumber'],
            $data['year'],
            $data['weekday'],
            $data['mealType']
        ]);

        $this->sendJSON(['success' => true]);
    }

    private function toggleDisabled() {
        $data = $this->getRequestData();
        $week = $this->getCurrentWeek();

        $weekNumber = $data['weekNumber'] ?? $week['weekNumber'];
        $year = $data['year'] ?? $week['year'];
        $weekday = $data['weekday'];
        $mealType = $data['mealType'];

        // Prüfe ob Eintrag existiert
        $existing = $this->db->fetchOne(
            "SELECT id, is_disabled FROM week_plan
             WHERE week_number = ? AND year = ? AND weekday = ? AND meal_type = ?",
            [$weekNumber, $year, $weekday, $mealType]
        );

        if ($existing) {
            // Toggle is_disabled (Rezept wird NICHT entfernt)
            $this->db->execute("
                UPDATE week_plan
                SET is_disabled = NOT is_disabled
                WHERE week_number = ? AND year = ? AND weekday = ? AND meal_type = ?
            ", [$weekNumber, $year, $weekday, $mealType]);
        } else {
            // Erstelle neuen Eintrag mit is_disabled = 1
            $this->db->execute("
                INSERT INTO week_plan (week_number, year, weekday, meal_type, recipe_id, is_locked, is_disabled)
                VALUES (?, ?, ?, ?, NULL, 0, 1)
            ", [$weekNumber, $year, $weekday, $mealType]);
        }

        $this->sendJSON(['success' => true]);
    }
    
    // ==================== BRING! EXPORT ====================
    
    private function saveBringRecipe() {
        $data = $this->getRequestData();
        
        $recipe = $data['recipe'] ?? [];
        $week = $data['week'] ?? 0;
        $year = $data['year'] ?? 0;
        
        // Erstelle Dateiname
        $filename = "bring_recipe_w{$week}_{$year}_" . time() . ".json";
        $filepath = BRING_EXPORTS_PATH . "/" . $filename;
        
        // Speichere JSON
        file_put_contents($filepath, json_encode($recipe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Erstelle öffentliche URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $dir = dirname($_SERVER['SCRIPT_NAME']);
        $url = $protocol . '://' . $host . $dir . '/exports/bring/' . $filename;
        
        $this->sendJSON(['success' => true, 'url' => $url, 'filename' => $filename]);
    }
    
    private function exportToBringDirect() {
        // Prüfe ob Bring! Config existiert
        if (!defined('BRING_EMAIL') || !defined('BRING_PASSWORD')) {
            throw new Exception('Bring! Konfiguration fehlt', 500);
        }
        
        if (!BRING_USE_API) {
            throw new Exception('Bring! API-Modus ist nicht aktiviert', 400);
        }
        
        if (empty(BRING_EMAIL) || empty(BRING_PASSWORD)) {
            throw new Exception('Bring! Login-Daten fehlen', 400);
        }
        
        $data = $this->getRequestData();
        $items = $data['items'] ?? [];
        
        if (empty($items)) {
            throw new Exception('Keine Artikel zum Exportieren', 400);
        }
        
        // Bring! API initialisieren
        $bring = new BringAPI(BRING_EMAIL, BRING_PASSWORD);
        
        // Login
        if (!$bring->login()) {
            throw new Exception('Bring! Login fehlgeschlagen', 401);
        }
        
        // Liste finden
        $list = $bring->getListByName(BRING_LIST_NAME);
        if (!$list) {
            throw new Exception('Liste "' . BRING_LIST_NAME . '" nicht gefunden', 404);
        }
        
        // Batch-Add Items
        $result = $bring->batchAddItems($list['listUuid'], $items);
        
        $this->sendJSON([
            'success' => $result['success'],
            'list_name' => BRING_LIST_NAME,
            'items_count' => $result['total'],
            'successful' => $result['successful'],
            'failed' => $result['failed']
        ]);
    }
    
    // ==================== HELPER METHODS ====================
    
    private function parseEndpoint() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($uri, '/');
    }
    
    private function getRequestData() {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }
    
    private function getCurrentWeek() {
        $date = new DateTime();
        return [
            'weekNumber' => (int)$date->format('W'),
            'year' => (int)$date->format('Y')
        ];
    }
    
    private function validate($data, $required) {
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Feld '{$field}' ist erforderlich", 400);
            }
        }
    }
    
    private function setCORSHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
    }
    
    private function sendJSON($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    private function sendError($message, $code = 500) {
        $this->logError($message, $code);
        $this->sendJSON([
            'error' => $message,
            'code' => $code,
            'action' => $this->params['action'] ?? 'unknown'
        ], $code);
    }

    private function logError($message, $code) {
        $logFile = LOGS_PATH . '/api_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $action = $this->params['action'] ?? 'unknown';
        $method = $this->method;
        $logMessage = "[{$timestamp}] [HTTP {$code}] [{$method} {$action}] {$message}\n";
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// ==================== EXECUTE API ====================

try {
    $api = new MenuPlannerAPI();
    $api->handleRequest();
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
