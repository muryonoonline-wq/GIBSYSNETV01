<?php
// Database Configuration
$host = 'localhost';
$dbname = 'gibsysnet';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Check and create tables if they don't exist
    checkAndCreateTables($conn);
    
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// ============================================
// HELPER FUNCTIONS CLASS
// ============================================

class ClientManager {
    
    /**
     * Generate next client code (CL001, CL002, etc.)
     */
    public static function generateClientCode($conn) {
        try {
            $stmt = $conn->query("SELECT MAX(client_code) as max_code FROM clients WHERE client_code LIKE 'CL%'");
            $result = $stmt->fetch();
            
            if ($result && $result['max_code']) {
                $lastNumber = intval(substr($result['max_code'], 2));
                $nextNumber = $lastNumber + 1;
                return 'CL' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            } else {
                return 'CL001';
            }
        } catch (PDOException $e) {
            return 'CL001'; // Fallback code
        }
    }
    
    /**
     * Get client data by ID for editing
     */
    public static function getClientData($conn, $id) {
        try {
            $stmt = $conn->prepare("
                SELECT * FROM clients 
                WHERE id = ? 
                AND is_deleted = 0
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Generate HTML form for editing client
     */
    public static function generateEditForm($client) {
        if (!$client) return '';
        
        $html = '
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label-custom required">Client Code</label>
                    <input type="text" class="form-control form-control-custom" name="client_code" 
                           value="' . htmlspecialchars($client['client_code']) . '" required maxlength="20">
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom required">Client Name</label>
                    <input type="text" class="form-control form-control-custom" name="client_name" 
                           value="' . htmlspecialchars($client['client_name']) . '" required maxlength="100">
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom required">Client Type</label>
                    <select class="form-select form-select-custom" name="client_type" required>
                        <option value="">Select Type</option>
                        <option value="Client" ' . ($client['client_type'] == 'Client' ? 'selected' : '') . '>Client</option>
                        <option value="Agent" ' . ($client['client_type'] == 'Agent' ? 'selected' : '') . '>Agent</option>
                        <option value="Marketing" ' . ($client['client_type'] == 'Marketing' ? 'selected' : '') . '>Marketing</option>
                        <option value="Partner" ' . ($client['client_type'] == 'Partner' ? 'selected' : '') . '>Partner</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom">Category</label>
                    <select class="form-select form-select-custom" name="category">
                        <option value="Corporate" ' . ($client['category'] == 'Corporate' ? 'selected' : '') . '>Corporate</option>
                        <option value="Individual" ' . ($client['category'] == 'Individual' ? 'selected' : '') . '>Individual</option>
                        <option value="Government" ' . ($client['category'] == 'Government' ? 'selected' : '') . '>Government</option>
                        <option value="Other" ' . ($client['category'] == 'Other' ? 'selected' : '') . '>Other</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom">Address</label>
                    <textarea class="form-control form-control-custom" name="address" rows="3">' . htmlspecialchars($client['address'] ?? '') . '</textarea>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label-custom">City</label>
                    <input type="text" class="form-control form-control-custom" name="city" 
                           value="' . htmlspecialchars($client['city'] ?? '') . '" maxlength="50">
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom">Country</label>
                    <input type="text" class="form-control form-control-custom" name="country" 
                           value="' . htmlspecialchars($client['country'] ?? 'Indonesia') . '" maxlength="50">
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom">Phone</label>
                    <input type="text" class="form-control form-control-custom" name="phone" 
                           value="' . htmlspecialchars($client['phone'] ?? '') . '" maxlength="20">
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom">Mobile</label>
                    <input type="text" class="form-control form-control-custom" name="mobile" 
                           value="' . htmlspecialchars($client['mobile'] ?? '') . '" maxlength="20">
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom">Email</label>
                    <input type="email" class="form-control form-control-custom" name="email" 
                           value="' . htmlspecialchars($client['email'] ?? '') . '" maxlength="100">
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label-custom">Contact Person</label>
                    <input type="text" class="form-control form-control-custom" name="contact_person" 
                           value="' . htmlspecialchars($client['contact_person'] ?? '') . '" maxlength="100">
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom">NPWP</label>
                    <input type="text" class="form-control form-control-custom" name="npwp" 
                           value="' . htmlspecialchars($client['npwp'] ?? '') . '" maxlength="25">
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label-custom required">Join Date</label>
                    <input type="date" class="form-control form-control-custom" name="join_date" 
                           value="' . htmlspecialchars($client['join_date']) . '" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom required">Status</label>
                    <select class="form-select form-select-custom" name="status" required>
                        <option value="Active" ' . ($client['status'] == 'Active' ? 'selected' : '') . '>Active</option>
                        <option value="Inactive" ' . ($client['status'] == 'Inactive' ? 'selected' : '') . '>Inactive</option>
                        <option value="Pending" ' . ($client['status'] == 'Pending' ? 'selected' : '') . '>Pending</option>
                    </select>
                </div>
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Restore soft deleted client
     */
    public static function restoreClient($conn, $id) {
        try {
            $stmt = $conn->prepare("
                UPDATE clients SET 
                    is_deleted = 0,
                    deleted_at = NULL,
                    delete_reason = NULL,
                    status = 'Active',
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Soft delete client
     */
    public static function softDeleteClient($conn, $id, $reason = 'No reason provided') {
        try {
            $stmt = $conn->prepare("
                UPDATE clients SET 
                    is_deleted = 1,
                    deleted_at = NOW(),
                    delete_reason = ?,
                    status = 'Inactive',
                    updated_at = NOW()
                WHERE id = ? 
                AND is_deleted = 0
            ");
            
            $stmt->execute([$reason, $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all active clients
     */
    public static function getActiveClients($conn) {
        try {
            $stmt = $conn->prepare("
                SELECT * FROM clients 
                WHERE is_deleted = 0 
                ORDER BY client_name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get deleted clients count
     */
    public static function getDeletedCount($conn) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM clients WHERE is_deleted = 1");
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get status count
     */
    public static function getStatusCount($clients, $status) {
        return count(array_filter($clients, function($item) use ($status) {
            return ($item['status'] ?? '') === $status;
        }));
    }
    
    /**
     * Add new client
     */
    public static function addClient($conn, $data) {
        try {
            // Generate client code if not provided
            if (empty($data['client_code'])) {
                $data['client_code'] = self::generateClientCode($conn);
            }
            
            $stmt = $conn->prepare("
                INSERT INTO clients (
                    client_code, client_name, client_type, category, address, 
                    city, country, phone, mobile, email, contact_person, 
                    npwp, join_date, status, is_deleted
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            
            $stmt->execute([
                $data['client_code'], $data['client_name'], $data['client_type'], 
                $data['category'], $data['address'], $data['city'], $data['country'],
                $data['phone'], $data['mobile'], $data['email'], $data['contact_person'],
                $data['npwp'], $data['join_date'], $data['status']
            ]);
            
            return [
                'success' => true,
                'client_id' => $conn->lastInsertId(),
                'client_code' => $data['client_code']
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update client
     */
    public static function updateClient($conn, $id, $data) {
        try {
            $stmt = $conn->prepare("
                UPDATE clients SET 
                    client_code = ?,
                    client_name = ?,
                    client_type = ?,
                    category = ?,
                    address = ?,
                    city = ?,
                    country = ?,
                    phone = ?,
                    mobile = ?,
                    email = ?,
                    contact_person = ?,
                    npwp = ?,
                    join_date = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE id = ? 
                AND is_deleted = 0
            ");
            
            $stmt->execute([
                $data['client_code'], $data['client_name'], $data['client_type'], 
                $data['category'], $data['address'], $data['city'], $data['country'],
                $data['phone'], $data['mobile'], $data['email'], $data['contact_person'],
                $data['npwp'], $data['join_date'], $data['status'], $id
            ]);
            
            return [
                'success' => $stmt->rowCount() > 0,
                'rows_affected' => $stmt->rowCount()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if client code already exists
     */
    public static function clientCodeExists($conn, $code, $excludeId = null) {
        try {
            if ($excludeId) {
                $stmt = $conn->prepare("SELECT id FROM clients WHERE client_code = ? AND id != ? AND is_deleted = 0");
                $stmt->execute([$code, $excludeId]);
            } else {
                $stmt = $conn->prepare("SELECT id FROM clients WHERE client_code = ? AND is_deleted = 0");
                $stmt->execute([$code]);
            }
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
}

// ============================================
// DATABASE TABLE SETUP FUNCTION
// ============================================

function checkAndCreateTables($conn) {
    try {
        // Check if clients table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'clients'");
        
        if ($stmt->rowCount() == 0) {
            // Create clients table with soft delete support
            $sql = "CREATE TABLE clients (
                id INT PRIMARY KEY AUTO_INCREMENT,
                client_code VARCHAR(20) UNIQUE NOT NULL,
                client_name VARCHAR(100) NOT NULL,
                client_type ENUM('Client', 'Agent', 'Marketing', 'Partner') NOT NULL,
                category ENUM('Corporate', 'Individual', 'Government', 'Other') DEFAULT 'Corporate',
                address TEXT,
                city VARCHAR(50),
                country VARCHAR(50) DEFAULT 'Indonesia',
                phone VARCHAR(20),
                mobile VARCHAR(20),
                email VARCHAR(100),
                contact_person VARCHAR(100),
                npwp VARCHAR(25),
                join_date DATE,
                status ENUM('Active', 'Inactive', 'Pending') DEFAULT 'Active',
                is_deleted TINYINT(1) DEFAULT 0,
                deleted_at DATETIME NULL,
                delete_reason TEXT NULL,
                deleted_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $conn->exec($sql);
            
            // Insert sample data
            $sampleData = [
                ['CL001', 'PT. Sejahtera Abadi', 'Client', 'Corporate', 'Jl. Sudirman No. 123', 'Jakarta', 'Indonesia', '021-55667788', '0811-223344', 'info@sejahtera.com', 'Budi Santoso', '01.234.567.8-912.000', '2023-01-15', 'Active'],
                ['AG001', 'Global Insurance Agency', 'Agent', 'Corporate', 'Jl. Thamrin No. 45', 'Bandung', 'Indonesia', '022-77889900', '0813-445566', 'contact@globalins.com', 'Siti Aminah', '02.345.678.9-013.000', '2023-03-20', 'Active'],
                ['MK001', 'Premium Marketing Group', 'Marketing', 'Corporate', 'Jl. Melati No. 10', 'Surabaya', 'Indonesia', '031-11223344', '0814-556677', 'sales@premium.com', 'Dian Purnama', '03.456.789.0-114.000', '2023-05-10', 'Active']
            ];
            
            $stmt = $conn->prepare("
                INSERT INTO clients (
                    client_code, client_name, client_type, category, address, 
                    city, country, phone, mobile, email, contact_person, 
                    npwp, join_date, status, is_deleted
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            
            foreach ($sampleData as $data) {
                $stmt->execute($data);
            }
            
            error_log("Clients table created successfully with sample data");
        }
        
    } catch (PDOException $e) {
        error_log("Table creation error: " . $e->getMessage());
        // Continue execution even if table creation fails
    }
}

// ============================================
// AJAX HANDLER FOR CLIENT OPERATIONS
// ============================================

// This handles AJAX requests for client operations
if (isset($_GET['action']) && $_GET['action'] == 'ajax') {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        switch ($_GET['operation'] ?? '') {
            case 'generate_code':
                $code = ClientManager::generateClientCode($conn);
                $response = ['success' => true, 'code' => $code];
                break;
                
            case 'get_client':
                if (isset($_GET['id'])) {
                    $client = ClientManager::getClientData($conn, $_GET['id']);
                    if ($client) {
                        $html = ClientManager::generateEditForm($client);
                        $response = ['success' => true, 'html' => $html, 'data' => $client];
                    } else {
                        $response = ['success' => false, 'message' => 'Client not found'];
                    }
                }
                break;
                
            case 'restore_client':
                if (isset($_POST['client_id'])) {
                    $success = ClientManager::restoreClient($conn, $_POST['client_id']);
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Client restored successfully' : 'Failed to restore client'
                    ];
                }
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Unknown operation'];
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
    
    echo json_encode($response);
    exit();
}
?>