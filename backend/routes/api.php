<?php
// backend/routes/api.php

class ApiRouter {
    private $routes = [];

    public function __construct() {
        $this->initializeRoutes();
    }

    private function initializeRoutes() {
        $this->routes = [
            'POST' => [
                'auth/login' => ['AuthController', 'login'],
                'auth/logout' => ['AuthController', 'logout']
            ],
            'GET' => [
                'auth/check' => ['AuthController', 'checkSession'],
                'dashboard/data' => ['DashboardController', 'getDashboardData'],
                'clients' => ['MasterController', 'getClients'],
                'policies' => ['MasterController', 'getPolicies']
            ],
            'PUT' => [
                'auth/update' => ['AuthController', 'updateProfile']
            ]
        ];
    }

    public function handleRequest($method, $path, $data) {
        try {
            // Find the route
            if (isset($this->routes[$method][$path])) {
                $route = $this->routes[$method][$path];
                $controllerName = $route[0];
                $methodName = $route[1];

                // Include controller file
                $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                    
                    // Create controller instance
                    $controller = new $controllerName();
                    
                    // Call method
                    if (method_exists($controller, $methodName)) {
                        $result = $controller->$methodName($data);
                        
                        http_response_code(200);
                        echo json_encode($result, JSON_PRETTY_PRINT);
                    } else {
                        throw new Exception("Method $methodName tidak ditemukan di controller $controllerName");
                    }
                } else {
                    throw new Exception("Controller $controllerName tidak ditemukan");
                }
            } else {
                // Route not found
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Endpoint tidak ditemukan',
                    'path' => $path,
                    'method' => $method
                ], JSON_PRETTY_PRINT);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], JSON_PRETTY_PRINT);
        }
    }
}