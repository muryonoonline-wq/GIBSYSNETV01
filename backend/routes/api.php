<?php
// backend/routes/api.php

require_once __DIR__ . '/../config/database.php';

class ApiRouter {
    private $db;
    private $routes = [];

    public function __construct() {
        $this->db = new Database();
        $this->initializeRoutes();
    }

    private function initializeRoutes() {
        $this->routes = [
            'POST' => [
                'auth/login' => ['AuthController', 'login'],
                'auth/register' => ['AuthController', 'register'],
                'auth/logout' => ['AuthController', 'logout'],
                'auth/change-password' => ['AuthController', 'changePassword'],
                
                'clients/create' => ['ClientController', 'create'],
                'clients/update' => ['ClientController', 'update'],
                
                'policies/create' => ['PolicyController', 'create'],
                'policies/update' => ['PolicyController', 'update'],
                
                'quotations/create' => ['QuotationController', 'create'],
                'quotations/update' => ['QuotationController', 'update'],
                
                'claims/create' => ['ClaimController', 'create'],
                'claims/update' => ['ClaimController', 'update'],
                
                'documents/upload' => ['DocumentController', 'upload']
            ],
            'GET' => [
                'auth/check' => ['AuthController', 'checkSession'],
                
                'dashboard/data' => ['DashboardController', 'getDashboardData'],
                'dashboard/stats' => ['DashboardController', 'getStats'],
                
                'clients' => ['ClientController', 'getAll'],
                'clients/{id}' => ['ClientController', 'getById'],
                'clients/search' => ['ClientController', 'search'],
                
                'policies' => ['PolicyController', 'getAll'],
                'policies/{id}' => ['PolicyController', 'getById'],
                'policies/expiring' => ['PolicyController', 'getExpiring'],
                
                'quotations' => ['QuotationController', 'getAll'],
                'quotations/{id}' => ['QuotationController', 'getById'],
                'quotations/pending' => ['QuotationController', 'getPending'],
                
                'claims' => ['ClaimController', 'getAll'],
                'claims/{id}' => ['ClaimController', 'getById'],
                'claims/pending' => ['ClaimController', 'getPending'],
                
                'commissions' => ['CommissionController', 'getAll'],
                'commissions/{id}' => ['CommissionController', 'getById'],
                'commissions/broker/{broker_id}' => ['CommissionController', 'getByBroker'],
                
                'reports/summary' => ['ReportController', 'getSummary'],
                'reports/monthly' => ['ReportController', 'getMonthly'],
                'reports/commission' => ['ReportController', 'getCommissionReport'],
                
                'users' => ['UserController', 'getAll'],
                'users/{id}' => ['UserController', 'getById'],
                
                'activity-logs' => ['ActivityController', 'getAll'],
                'settings' => ['SettingController', 'getAll']
            ],
            'PUT' => [
                'clients/{id}' => ['ClientController', 'update'],
                'policies/{id}' => ['PolicyController', 'update'],
                'quotations/{id}' => ['QuotationController', 'update'],
                'claims/{id}' => ['ClaimController', 'update'],
                'users/{id}' => ['UserController', 'update'],
                'settings/{key}' => ['SettingController', 'update']
            ],
            'DELETE' => [
                'clients/{id}' => ['ClientController', 'delete'],
                'policies/{id}' => ['PolicyController', 'delete'],
                'quotations/{id}' => ['QuotationController', 'delete'],
                'claims/{id}' => ['ClaimController', 'delete'],
                'users/{id}' => ['UserController', 'delete']
            ]
        ];
    }

    public function handleRequest($method, $path, $data) {
        try {
            // CORS headers
            header("Access-Control-Allow-Origin: *");
            header("Content-Type: application/json; charset=UTF-8");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Max-Age: 3600");
            header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

            if ($method === 'OPTIONS') {
                http_response_code(200);
                exit();
            }

            // Validate request
            if (!$this->validateRequest($method, $path)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Endpoint not found',
                    'endpoint' => $path
                ]);
                return;
            }

            // Find route
            $route = $this->findRoute($method, $path);
            if (!$route) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Route not found'
                ]);
                return;
            }

            // Extract route parameters
            $params = $this->extractParams($route['pattern'], $path);
            
            // Merge data with params
            $requestData = array_merge($data ?? [], $params);
            
            // Add method and path to request data
            $requestData['_method'] = $method;
            $requestData['_path'] = $path;

            // Check authentication for protected routes
            if (!$this->checkAuthentication($route, $requestData)) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]);
                return;
            }

            // Load controller
            $controllerFile = __DIR__ . '/../controllers/' . $route['controller'] . '.php';
            if (!file_exists($controllerFile)) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Controller not found: ' . $route['controller']
                ]);
                return;
            }

            require_once $controllerFile;
            $controllerName = $route['controller'];
            
            if (!class_exists($controllerName)) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Controller class not found: ' . $controllerName
                ]);
                return;
            }

            // Create controller instance
            $controller = new $controllerName();
            
            // Call method
            if (!method_exists($controller, $route['method'])) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Method not found: ' . $route['method']
                ]);
                return;
            }

            $result = $controller->{$route['method']}($requestData);
            
            // Send response
            http_response_code(200);
            echo json_encode($result, JSON_PRETTY_PRINT);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            error_log("API Error: " . $e->getMessage());
        }
    }

    private function validateRequest($method, $path) {
        // Basic validation
        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])) {
            return false;
        }

        // Sanitize path
        $path = trim($path, '/');
        $path = filter_var($path, FILTER_SANITIZE_URL);

        return !empty($path);
    }

    private function findRoute($method, $path) {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $pattern => $handler) {
            // Convert route pattern to regex
            $regex = str_replace('/', '\/', $pattern);
            $regex = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^\/]+)', $regex);
            $regex = '/^' . $regex . '$/';

            if (preg_match($regex, $path, $matches)) {
                return [
                    'pattern' => $pattern,
                    'controller' => $handler[0],
                    'method' => $handler[1],
                    'is_protected' => !in_array($pattern, [
                        'auth/login', 'auth/register'
                    ])
                ];
            }
        }

        return null;
    }

    private function extractParams($pattern, $path) {
        $params = [];
        
        $patternParts = explode('/', $pattern);
        $pathParts = explode('/', $path);
        
        foreach ($patternParts as $index => $part) {
            if (strpos($part, '{') === 0 && strpos($part, '}') === strlen($part) - 1) {
                $paramName = trim($part, '{}');
                if (isset($pathParts[$index])) {
                    $params[$paramName] = $pathParts[$index];
                }
            }
        }
        
        return $params;
    }

    private function checkAuthentication($route, $data) {
        // Public routes don't need authentication
        if (!$route['is_protected']) {
            return true;
        }

        // Check for token in headers or data
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? 
                ($data['token'] ?? $_GET['token'] ?? '');
        
        // Remove 'Bearer ' prefix if present
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            return false;
        }

        // Validate token (simplified - in production use JWT)
        try {
            $parts = explode('|', base64_decode($token));
            if (count($parts) !== 3) {
                return false;
            }

            $userId = $parts[0];
            $timestamp = $parts[1];

            // Check if token is expired (24 hours)
            if (time() - $timestamp > 86400) {
                return false;
            }

            // Check if user exists and is active
            $user = $this->db->getSingle(
                "SELECT id, user_level, is_active FROM users WHERE id = :id",
                ['id' => $userId]
            );

            if (!$user || !$user['is_active']) {
                return false;
            }

            // Store user info in request data for controller use
            $data['user_id'] = $userId;
            $data['user_level'] = $user['user_level'];

            return true;

        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
}