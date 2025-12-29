<?php
// backend/controllers/DashboardController.php

require_once __DIR__ . '/../config/database.php';

class DashboardController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getDashboardData($data) {
        $userId = $data['user_id'] ?? 0;
        $userLevel = $data['user_level'] ?? 'user';

        try {
            $dashboardData = [];

            // Get user-specific data based on level
            switch($userLevel) {
                case 'super_admin':
                case 'admin':
                    $dashboardData = $this->getAdminDashboardData();
                    break;
                case 'manager':
                    $dashboardData = $this->getManagerDashboardData($userId);
                    break;
                case 'broker':
                    $dashboardData = $this->getBrokerDashboardData($userId);
                    break;
                default:
                    $dashboardData = $this->getUserDashboardData($userId);
            }

            // Add common data
            $dashboardData['user'] = $this->getUserInfo($userId);
            $dashboardData['notifications'] = $this->getNotifications($userId);
            $dashboardData['recent_activities'] = $this->getRecentActivities($userId);

            return $this->response(true, 'Dashboard data retrieved', $dashboardData);
        } catch (Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
            return $this->response(false, 'Failed to load dashboard data');
        }
    }

    private function getAdminDashboardData() {
        $data = [];

        // Get stats from view
        $stats = $this->db->getSingle("SELECT * FROM view_dashboard_stats");
        $data['stats'] = $stats ?: [];

        // Get recent policies
        $data['recent_policies'] = $this->db->getAll(
            "SELECT p.*, c.company_name, c.contact_person 
             FROM policies p 
             JOIN clients c ON p.client_id = c.id 
             ORDER BY p.created_at DESC LIMIT 5"
        );

        // Get pending quotations
        $data['pending_quotations'] = $this->db->getAll(
            "SELECT q.*, c.company_name 
             FROM quotations q 
             JOIN clients c ON q.client_id = c.id 
             WHERE q.status IN ('draft', 'sent') 
             ORDER BY q.created_at DESC LIMIT 5"
        );

        // Get upcoming renewals
        $data['upcoming_renewals'] = $this->db->getAll(
            "SELECT p.policy_number, c.company_name, p.end_date, 
                    DATEDIFF(p.end_date, CURDATE()) as days_left 
             FROM policies p 
             JOIN clients c ON p.client_id = c.id 
             WHERE p.status = 'active' 
             AND p.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
             ORDER BY p.end_date ASC LIMIT 5"
        );

        // Get broker performance
        $data['broker_performance'] = $this->db->getAll("SELECT * FROM view_broker_performance");

        return $data;
    }

    private function getBrokerDashboardData($brokerId) {
        $data = [];

        // Broker-specific stats
        $stats = $this->db->getSingle(
            "SELECT 
                COUNT(DISTINCT p.id) as total_policies,
                SUM(p.premium) as total_premium,
                SUM(c.commission_amount) as total_commission,
                COUNT(DISTINCT cl.id) as total_clients,
                COUNT(DISTINCT CASE WHEN q.status IN ('draft', 'sent') THEN q.id END) as pending_quotations,
                COUNT(DISTINCT CASE WHEN clm.status IN ('filed', 'under_review') THEN clm.id END) as pending_claims
             FROM users u
             LEFT JOIN policies p ON u.id = p.broker_id AND p.status = 'active'
             LEFT JOIN commissions c ON u.id = c.broker_id AND c.payment_status = 'paid'
             LEFT JOIN clients cl ON u.id = cl.assigned_broker_id AND cl.status = 'active'
             LEFT JOIN quotations q ON u.id = q.broker_id AND q.status IN ('draft', 'sent')
             LEFT JOIN claims clm ON u.id = clm.assigned_to AND clm.status IN ('filed', 'under_review')
             WHERE u.id = :broker_id",
            ['broker_id' => $brokerId]
        );
        $data['stats'] = $stats ?: [];

        // Broker's clients
        $data['clients'] = $this->db->getAll(
            "SELECT * FROM clients 
             WHERE assigned_broker_id = :broker_id 
             AND status = 'active' 
             ORDER BY company_name LIMIT 5",
            ['broker_id' => $brokerId]
        );

        // Broker's policies
        $data['recent_policies'] = $this->db->getAll(
            "SELECT p.*, c.company_name 
             FROM policies p 
             JOIN clients c ON p.client_id = c.id 
             WHERE p.broker_id = :broker_id 
             ORDER BY p.created_at DESC LIMIT 5",
            ['broker_id' => $brokerId]
        );

        // Broker's commissions
        $data['recent_commissions'] = $this->db->getAll(
            "SELECT c.*, p.policy_number, cl.company_name 
             FROM commissions c 
             JOIN policies p ON c.policy_id = p.id 
             JOIN clients cl ON p.client_id = cl.id 
             WHERE c.broker_id = :broker_id 
             ORDER BY c.created_at DESC LIMIT 5",
            ['broker_id' => $brokerId]
        );

        return $data;
    }

    private function getUserDashboardData($userId) {
        $data = [];

        // User-specific data (if user is also a client)
        $data['my_policies'] = $this->db->getAll(
            "SELECT p.*, c.company_name 
             FROM policies p 
             JOIN clients c ON p.client_id = c.id 
             WHERE c.email = (SELECT email FROM users WHERE id = :user_id) 
             AND p.status = 'active' 
             ORDER BY p.end_date ASC",
            ['user_id' => $userId]
        );

        // User's documents
        $data['documents'] = $this->db->getAll(
            "SELECT * FROM documents 
             WHERE uploaded_by = :user_id 
             ORDER BY created_at DESC LIMIT 5",
            ['user_id' => $userId]
        );

        // Simple stats for user
        $data['stats'] = [
            'total_policies' => count($data['my_policies']),
            'pending_payments' => 0,
            'active_claims' => 0
        ];

        return $data;
    }

    private function getManagerDashboardData($managerId) {
        // Similar to admin but filtered by department
        $data = $this->getAdminDashboardData();
        
        // Filter data by manager's department
        $manager = $this->db->getSingle(
            "SELECT department FROM users WHERE id = :id",
            ['id' => $managerId]
        );
        
        if ($manager && $manager['department']) {
            $data['stats']['department'] = $manager['department'];
        }
        
        return $data;
    }

    private function getUserInfo($userId) {
        return $this->db->getSingle(
            "SELECT id, user_id, username, full_name, email, user_level, department, position 
             FROM users WHERE id = :id",
            ['id' => $userId]
        );
    }

    private function getNotifications($userId) {
        return $this->db->getAll(
            "SELECT * FROM notifications 
             WHERE user_id = :user_id AND is_read = 0 
             ORDER BY created_at DESC LIMIT 10",
            ['user_id' => $userId]
        );
    }

    private function getRecentActivities($userId) {
        return $this->db->getAll(
            "SELECT activity_type, description, created_at 
             FROM activity_logs 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC LIMIT 5",
            ['user_id' => $userId]
        );
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