<?php
/**
 * Bring! API Client für PHP - Enhanced Version
 * 
 * Version 2.1 mit verbessertem Error-Handling und Debugging
 * Basierend auf: https://github.com/foxriver76/node-bring-api
 */

class BringAPI {
    private $email;
    private $password;
    private $accessToken;
    private $refreshToken;
    private $tokenExpiry;
    private $uuid;
    private $name;
    private $baseUrl;
    private $timeout;
    private $rateLimitMs;
    private $debug;
    
    // Offizielle API-Keys aus node-bring-api
    private $headers = [
        'X-BRING-API-KEY' => 'cof4Nc6D8saplXjE3h3HXqHH8m7VU2i1Gs0g85Sp',
        'X-BRING-CLIENT' => 'webApp',
        'X-BRING-CLIENT-SOURCE' => 'webApp',
        'X-BRING-COUNTRY' => 'CH'
    ];
    
    private $lastError = null;
    
    public function __construct($email, $password, $options = []) {
        $this->email = $email;
        $this->password = $password;
        $this->baseUrl = $options['baseUrl'] ?? (defined('BRING_API_URL') ? BRING_API_URL : 'https://api.getbring.com/rest/v2');
        $this->timeout = $options['timeout'] ?? (defined('BRING_API_TIMEOUT') ? BRING_API_TIMEOUT : 30);
        $this->rateLimitMs = $options['rateLimitMs'] ?? (defined('BRING_RATE_LIMIT_MS') ? BRING_RATE_LIMIT_MS : 100);
        $this->debug = $options['debug'] ?? (defined('BRING_DEBUG') ? BRING_DEBUG : true); // Default auf true für Debugging
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    public function login() {
        $this->log('=== LOGIN START ===');
        $url = $this->baseUrl . '/bringauth';
        
        $body = http_build_query([
            'email' => $this->email,
            'password' => $this->password
        ]);
        
        $response = $this->request('POST', $url, $body, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        if ($response['success'] && isset($response['data']['access_token'])) {
            $data = $response['data'];
            $this->accessToken = $data['access_token'];
            $this->refreshToken = $data['refresh_token'];
            $this->uuid = $data['uuid'];
            $this->name = $data['name'];
            $this->tokenExpiry = time() + (2 * 3600);
            
            $this->log('✅ Login erfolgreich: ' . $this->name);
            return true;
        }
        
        $errorMsg = 'Login fehlgeschlagen: ' . ($response['error'] ?? 'Unknown error');
        $this->log($errorMsg, 'ERROR');
        $this->lastError = $errorMsg;
        return false;
    }
    
    private function ensureValidToken() {
        if (!$this->accessToken) {
            return $this->login();
        }
        if (time() >= $this->tokenExpiry - 300) {
            return $this->login(); // Re-login wenn Token abläuft
        }
        return true;
    }
    
    public function getLists() {
        if (!$this->ensureValidToken()) {
            $this->lastError = 'Token-Authentifizierung fehlgeschlagen';
            return ['error' => $this->lastError];
        }
        
        $url = $this->baseUrl . '/bringusers/' . $this->uuid . '/lists';
        $response = $this->request('GET', $url, null, $this->getAuthHeaders());
        
        if ($response['success']) {
            return $response['data'];
        }
        
        $this->lastError = $response['error'] ?? 'Unknown error';
        return ['error' => $this->lastError, 'http_code' => $response['http_code']];
    }
    
    public function getListByName($listName) {
        $this->log('Suche Liste: ' . $listName);
        $lists = $this->getLists();
        
        if (isset($lists['error'])) {
            return null;
        }
        
        if (isset($lists['lists'])) {
            foreach ($lists['lists'] as $list) {
                if ($list['name'] === $listName) {
                    $this->log('✅ Liste gefunden: ' . $listName);
                    return $list;
                }
            }
        }
        
        $this->log('❌ Liste nicht gefunden: ' . $listName, 'ERROR');
        $this->lastError = 'Liste "' . $listName . '" nicht gefunden';
        return null;
    }
    
    public function saveItem($listUuid, $itemName, $specification = '') {
        if (!$this->ensureValidToken()) {
            $this->lastError = 'Token-Authentifizierung fehlgeschlagen';
            return ['success' => false, 'error' => $this->lastError];
        }
        
        $url = $this->baseUrl . '/bringlists/' . $listUuid;
        
        // WICHTIG: Body Format muss EXAKT so sein!
        $body = http_build_query([
            'purchase' => $itemName,
            'recently' => '',
            'specification' => $specification,
            'remove' => '',
            'sender' => 'null'
        ], '', '&', PHP_QUERY_RFC3986);
        
        $this->log('Füge hinzu: ' . $itemName . ($specification ? ' (' . $specification . ')' : ''));
        
        $headers = array_merge(
            $this->getAuthHeaders(),
            ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8']
        );
        
        $response = $this->request('PUT', $url, $body, $headers);
        $success = $response['success'] && $response['http_code'] === 204;
        
        if (!$success) {
            $errorMsg = 'HTTP ' . $response['http_code'] . ': ' . ($response['error'] ?? 'Unknown');
            $this->log('❌ Fehler: ' . $itemName . ' - ' . $errorMsg, 'ERROR');
            $this->lastError = $errorMsg;
        }
        
        return [
            'success' => $success,
            'http_code' => $response['http_code'],
            'item' => $itemName,
            'error' => $success ? null : ($response['error'] ?? 'Unknown error')
        ];
    }
    
    public function batchAddItems($listUuid, $items) {
        $this->log('=== BATCH ADD: ' . count($items) . ' Items ===');
        
        $results = [];
        $successCount = 0;
        $failCount = 0;
        $errors = [];
        
        foreach ($items as $index => $item) {
            $result = $this->saveItem(
                $listUuid, 
                $item['name'], 
                $item['specification'] ?? ''
            );
            
            $results[] = $result;
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
                $errors[] = $item['name'] . ': ' . ($result['error'] ?? 'Unknown');
            }
            
            usleep($this->rateLimitMs * 1000);
        }
        
        $this->log("✅ Erfolgreich: {$successCount}, ❌ Fehler: {$failCount}");
        
        return [
            'success' => $failCount === 0,
            'total' => count($items),
            'successful' => $successCount,
            'failed' => $failCount,
            'results' => $results,
            'errors' => $errors
        ];
    }
    
    private function request($method, $url, $body = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $baseHeaders = array_merge(
            [
                'X-BRING-API-KEY: ' . $this->headers['X-BRING-API-KEY'],
                'X-BRING-CLIENT: ' . $this->headers['X-BRING-CLIENT'],
                'X-BRING-CLIENT-SOURCE: ' . $this->headers['X-BRING-CLIENT-SOURCE'],
                'X-BRING-COUNTRY: ' . $this->headers['X-BRING-COUNTRY'],
                'User-Agent: MenuPlanner/2.0'
            ],
            $headers
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $baseHeaders);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $this->log('cURL Error: ' . $curlError, 'ERROR');
            return [
                'success' => false,
                'error' => 'cURL: ' . $curlError,
                'http_code' => $httpCode
            ];
        }
        
        $data = null;
        if (!empty($response)) {
            $data = json_decode($response, true);
        }
        
        $isSuccess = $httpCode >= 200 && $httpCode < 300;
        
        if (!$isSuccess) {
            $this->log('HTTP Error ' . $httpCode . ': ' . substr($response, 0, 200), 'ERROR');
        }
        
        return [
            'success' => $isSuccess,
            'http_code' => $httpCode,
            'data' => $data,
            'raw_response' => $response,
            'error' => !$isSuccess ? ($data['message'] ?? $data['error'] ?? 'HTTP ' . $httpCode) : null
        ];
    }
    
    private function getAuthHeaders() {
        return [
            'Authorization: Bearer ' . $this->accessToken,
            'X-BRING-USER-UUID: ' . $this->uuid
        ];
    }
    
    private function log($message, $level = 'INFO') {
        if (!$this->debug && $level === 'INFO') {
            return;
        }
        
        if (defined('LOGS_PATH')) {
            $logFile = LOGS_PATH . '/bring_api.log';
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
            @file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
        
        if ($level === 'ERROR') {
            error_log("BringAPI: {$message}");
        }
    }
    
    public function getUserInfo() {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email
        ];
    }
}
