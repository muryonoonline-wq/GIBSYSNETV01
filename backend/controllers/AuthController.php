<?php
// backend/controllers/AuthController.php

class AuthController {
    private $db;
    private $conn;

    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database;
        $this->conn = $database->getConnection();
    }

    public function login($data) {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->response(false, 'Username dan password harus diisi');
        }

        // Demo credentials for testing without database
        $demoUsers = [
            'superadmin' => [
                'password' => 'password123',
                'user_id' => 'SU001',
                'full_name' => 'Super Administrator',
                'user_level' => 'super_admin',
                'email' => 'superadmin@gibsysnet.com'
            ],
            'admin' => [
                'password' => 'password123',
                'user_id' => 'AD001',
                'full_name' => 'System Administrator',
                'user_level' => 'admin',
                'email' => 'admin@gibsysnet.com'
            ],
            'manager' => [
                'password' => 'password123',
                'user_id' => 'MN001',
                'full_name' => 'Sales Manager',
                'user_level' => 'manager',
                'email' => 'manager@gibsysnet.com'
            ],
            'broker1' => [
                'password' => 'password123',
                'user_id' => 'BR001',
                'full_name' => 'John Broker',
                'user_level' => 'broker',
                'email' => 'john.broker@gibsysnet.com'
            ],
            'broker2' => [
                'password' => 'password123',
                'user_id' => 'BR002',
                'full_name' => 'Sarah Broker',
                'user_level' => 'broker',
                'email' => 'sarah.broker@gibsysnet.com'
            ],
            'user' => [
                'password' => 'password123',
                'user_id' => 'US001',
                'full_name' => 'Regular User',
                'user_level' => 'user',
                'email' => 'user@gibsysnet.com'
            ],
            'compliance' => [
                'password' => 'password123',
                'user_id' => 'CO001',
                'full_name' => 'Compliance Officer',
                'user_level' => 'admin',
                'email' => 'compliance@gibsysnet.com'
            ]
        ];

        // Check if user exists in demo data
        if (isset($demoUsers[$username])) {
            $user = $demoUsers[$username];
            
            // Check password (in production, use password_verify)
            if ($password === $user['password']) {
                // Generate token (simplified for demo)
                $token = bin2hex(random_bytes(32));
                
                // Prepare user data
                $userData = [
                    'id' => 1,
                    'user_id' => $user['user_id'],
                    'username' => $username,
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'user_level' => $user['user_level'],
                    'token' => $token,
                    'permissions' => $this->getUserPermissions($user['user_level']),
                    'last_login' => date('Y-m-d H:i:s')
                ];

                return $this->response(true, 'Login berhasil', $userData);
            }
        }

        return $this->response(false, 'Username atau password salah');
    }

    private function getUserPermissions($userLevel) {
        $permissions = [
            'super_admin' => [
                'view_dashboard', 'manage_users', 'view_reports', 'generate_reports',
                'manage_clients', 'create_policy', 'edit_policy', 'delete_policy',
                'process_claims', 'manage_compliance', 'system_config', 'backup_restore'
            ],
            'admin' => [
                'view_dashboard', 'manage_users', 'view_reports', 'generate_reports',
                'manage_clients', 'create_policy', 'edit_policy', 'process_claims',
                'manage_compliance'
            ],
            'manager' => [
                'view_dashboard', 'view_reports', 'generate_reports', 'manage_clients',
                'create_policy', 'edit_policy'
            ],
            'broker' => [
                'view_dashboard', 'view_reports', 'manage_clients', 'create_policy'
            ],
            'user' => [
                'view_dashboard', 'view_reports'
            ]
        ];

        return $permissions[$userLevel] ?? ['view_dashboard'];
    }

    private function response($success, $message, $data = null) {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public function logout() {
        return $this->response(true, 'Logout berhasil');
    }

    public function checkSession() {
        return $this->response(true, 'Session valid', [
            'is_logged_in' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}