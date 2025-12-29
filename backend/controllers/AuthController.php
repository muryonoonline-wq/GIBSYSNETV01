<?php
// backend/controllers/AuthController.php

require_once __DIR__ . '/../config/database.php';

class AuthController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function login($data) {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->response(false, 'Username dan password harus diisi');
        }

        try {
            // Cari user di database
            $sql = "SELECT * FROM users WHERE username = :username AND is_active = 1";
            $user = $this->db->getSingle($sql, ['username' => $username]);

            if (!$user) {
                return $this->response(false, 'Username tidak ditemukan');
            }

            // Verifikasi password
            if (password_verify($password, $user['password_hash'])) {
                // Update last login
                $this->db->update('users', 
                    ['last_login' => date('Y-m-d H:i:s')], 
                    'id = :id', 
                    ['id' => $user['id']]
                );

                // Log activity
                $this->logActivity($user['id'], 'login', 'User logged in successfully');

                // Generate token (simplified)
                $token = $this->generateToken($user['id']);
                
                // Get user permissions based on level
                $permissions = $this->getUserPermissions($user['user_level']);
                
                // Prepare response data
                $userData = [
                    'id' => $user['id'],
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'user_level' => $user['user_level'],
                    'department' => $user['department'],
                    'position' => $user['position'],
                    'avatar_url' => $user['avatar_url'],
                    'token' => $token,
                    'permissions' => $permissions,
                    'last_login' => $user['last_login']
                ];

                return $this->response(true, 'Login berhasil', $userData);
            } else {
                return $this->response(false, 'Password salah');
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return $this->response(false, 'Terjadi kesalahan sistem');
        }
    }

    public function register($data) {
        $required = ['username', 'password', 'full_name', 'email', 'user_level'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->response(false, "Field {$field} harus diisi");
            }
        }

        try {
            // Check if username or email already exists
            $checkUser = $this->db->getSingle(
                "SELECT id FROM users WHERE username = :username OR email = :email",
                ['username' => $data['username'], 'email' => $data['email']]
            );

            if ($checkUser) {
                return $this->response(false, 'Username atau email sudah terdaftar');
            }

            // Generate user ID
            $user_id = $this->generateUserId($data['user_level']);

            // Prepare user data
            $userData = [
                'user_id' => $user_id,
                'username' => $data['username'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'user_level' => $data['user_level'],
                'department' => $data['department'] ?? '',
                'position' => $data['position'] ?? '',
                'is_active' => 1,
                'created_by' => $data['created_by'] ?? 1
            ];

            $userId = $this->db->insert('users', $userData);

            if ($userId) {
                // Log activity
                $this->logActivity($userId, 'register', 'User registered successfully');
                
                return $this->response(true, 'Registrasi berhasil', ['user_id' => $userId]);
            } else {
                return $this->response(false, 'Gagal menyimpan data user');
            }
        } catch (Exception $e) {
            error_log("Register error: " . $e->getMessage());
            return $this->response(false, 'Terjadi kesalahan sistem');
        }
    }

    public function logout($data) {
        $userId = $data['user_id'] ?? 0;
        
        if ($userId) {
            $this->logActivity($userId, 'logout', 'User logged out');
        }
        
        return $this->response(true, 'Logout berhasil');
    }

    public function changePassword($data) {
        $userId = $data['user_id'] ?? 0;
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';

        if (empty($userId) || empty($currentPassword) || empty($newPassword)) {
            return $this->response(false, 'Data tidak lengkap');
        }

        try {
            // Get current password hash
            $user = $this->db->getSingle(
                "SELECT password_hash FROM users WHERE id = :id",
                ['id' => $userId]
            );

            if (!$user) {
                return $this->response(false, 'User tidak ditemukan');
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return $this->response(false, 'Password saat ini salah');
            }

            // Update password
            $updated = $this->db->update('users', 
                ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)], 
                'id = :id', 
                ['id' => $userId]
            );

            if ($updated) {
                $this->logActivity($userId, 'change_password', 'Password changed successfully');
                return $this->response(true, 'Password berhasil diubah');
            } else {
                return $this->response(false, 'Gagal mengubah password');
            }
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return $this->response(false, 'Terjadi kesalahan sistem');
        }
    }

    private function generateToken($userId) {
        $timestamp = time();
        $random = bin2hex(random_bytes(16));
        return base64_encode($userId . '|' . $timestamp . '|' . $random);
    }

    private function generateUserId($userLevel) {
        $prefix = '';
        switch($userLevel) {
            case 'super_admin': $prefix = 'SU'; break;
            case 'admin': $prefix = 'AD'; break;
            case 'manager': $prefix = 'MN'; break;
            case 'broker': $prefix = 'BR'; break;
            default: $prefix = 'US'; break;
        }

        $year = date('y');
        $month = date('m');
        
        // Cari sequence terakhir
        $sql = "SELECT MAX(CAST(SUBSTRING(user_id, 5) AS UNSIGNED)) as last_seq 
                FROM users 
                WHERE user_id LIKE :prefix";
        $result = $this->db->getSingle($sql, ['prefix' => $prefix . $year . $month . '%']);
        
        $sequence = ($result['last_seq'] ?? 0) + 1;
        
        return $prefix . $year . $month . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    private function getUserPermissions($userLevel) {
        $permissions = [
            'super_admin' => [
                'view_dashboard', 'manage_users', 'view_reports', 'generate_reports',
                'manage_clients', 'create_policy', 'edit_policy', 'delete_policy',
                'process_claims', 'manage_compliance', 'system_config', 'backup_restore',
                'view_audit_log', 'manage_commissions'
            ],
            'admin' => [
                'view_dashboard', 'manage_users', 'view_reports', 'generate_reports',
                'manage_clients', 'create_policy', 'edit_policy', 'process_claims',
                'manage_compliance', 'view_audit_log'
            ],
            'manager' => [
                'view_dashboard', 'view_reports', 'generate_reports', 'manage_clients',
                'create_policy', 'edit_policy', 'view_commissions'
            ],
            'broker' => [
                'view_dashboard', 'view_reports', 'manage_clients', 'create_policy',
                'view_own_commissions', 'upload_documents'
            ],
            'user' => [
                'view_dashboard', 'view_own_policies', 'view_own_documents',
                'submit_claims', 'update_profile'
            ]
        ];

        return $permissions[$userLevel] ?? ['view_dashboard'];
    }

    private function logActivity($userId, $activityType, $description) {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $this->db->insert('activity_logs', [
                'user_id' => $userId,
                'activity_type' => $activityType,
                'description' => $description,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent
            ]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }

    private function response($success, $message, $data = null) {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}