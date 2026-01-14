<?php
// master-data-client.php - MODERNIZED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// DATABASE CONNECTION
// ============================================
$possiblePaths = [
    $_SERVER['DOCUMENT_ROOT'] . '/gibsysnet/backend/config/database.php',
    dirname(__DIR__, 2) . '/backend/config/database.php',
    __DIR__ . '/../../backend/config/database.php',
    'C:/xampp/htdocs/gibsysnet/backend/config/database.php',
];

$databaseConfigPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $databaseConfigPath = $path;
        break;
    }
}

if ($databaseConfigPath) {
    require_once $databaseConfigPath;
} else {
    try {
        $host = 'localhost';
        $dbname = 'gibsysnet';
        $username = 'root';
        $password = '';
        
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conn->exec("SET time_zone = '+07:00'");
        
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed']));
    }
}

// ============================================
// AJAX HANDLERS
// ============================================

// Generate Client Code AJAX
if (isset($_GET['generate_code']) && $_GET['generate_code'] === 'true') {
    header('Content-Type: application/json');
    
    try {
        $category = isset($_GET['category']) ? trim($_GET['category']) : 'client';
        
        // Mapping kategori ke prefix
        $prefixMapping = [
            'client' => 'CLT',
            'agent' => 'AGT',
            'marketing' => 'MKT',
            'partner' => 'PTR',
            'other' => 'OTH',
        ];
        
        $prefix = $prefixMapping[strtolower($category)] ?? 'CLT';
        
        // Cari nomor terakhir
        $stmt = $conn->prepare("SELECT MAX(client_code) as max_code FROM clients WHERE client_code LIKE ? AND is_deleted = 0");
        $likePattern = $prefix . '%';
        $stmt->execute([$likePattern]);
        $result = $stmt->fetch();
        
        if ($result && $result['max_code']) {
            $lastCode = $result['max_code'];
            $lastNumber = intval(substr($lastCode, strlen($prefix)));
            $nextNumber = $lastNumber + 1;
            $clientCode = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        } else {
            $clientCode = $prefix . '0001';
        }
        
        echo json_encode([
            'success' => true,
            'code' => $clientCode,
            'category' => $category
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error generating code'
        ]);
    }
    exit();
}

// Get Client Details AJAX (untuk View dan Edit)
if (isset($_GET['get_client']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    try {
        $id = intval($_GET['id']);
        
        $stmt = $conn->prepare("
            SELECT * FROM clients 
            WHERE id = ? AND is_deleted = 0
        ");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        
        if ($client) {
            echo json_encode([
                'success' => true,
                'client' => $client
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Client not found'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error'
        ]);
    }
    exit();
}

// Delete Client AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_delete'])) {
    header('Content-Type: application/json');
    
    try {
        $id = intval($_POST['client_id']);
        $reason = $_POST['delete_reason'] ?? 'No reason provided';
        
        $stmt = $conn->prepare("
            UPDATE clients SET 
                is_deleted = 1,
                deleted_at = NOW(),
                delete_reason = ?,
                status = 'Inactive'
            WHERE id = ? AND is_deleted = 0
        ");
        
        $stmt->execute([$reason, $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Client soft deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Client not found or already deleted'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error'
        ]);
    }
    exit();
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function safe_html($value) {
    return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}

function getStatusCount($clients, $status) {
    return count(array_filter($clients, function($item) use ($status) {
        return ($item['status'] ?? '') === $status;
    }));
}

function generateClientCode($conn, $category = 'client') {
    $prefixMapping = [
        'client' => 'CLT',
        'agent' => 'AGT',
        'marketing' => 'MKT',
        'partner' => 'PTR',
        'other' => 'OTH',
    ];
    
    $prefix = $prefixMapping[strtolower($category)] ?? 'CLT';
    
    $stmt = $conn->prepare("SELECT MAX(client_code) as max_code FROM clients WHERE client_code LIKE ? AND is_deleted = 0");
    $likePattern = $prefix . '%';
    $stmt->execute([$likePattern]);
    $result = $stmt->fetch();
    
    if ($result && $result['max_code']) {
        $lastCode = $result['max_code'];
        $lastNumber = intval(substr($lastCode, strlen($prefix)));
        $nextNumber = $lastNumber + 1;
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
    
    return $prefix . '0001';
}

// ============================================
// FORM SUBMISSIONS (Traditional)
// ============================================

// Add New Client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    try {
        $client_code = $_POST['client_code'] ?? '';
        $client_name = $_POST['client_name'] ?? '';
        $client_type = $_POST['client_type'] ?? '';
        $category = $_POST['category'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $country = $_POST['country'] ?? 'Indonesia';
        $phone = $_POST['phone'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $email = $_POST['email'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        $npwp = $_POST['npwp'] ?? '';
        $join_date = $_POST['join_date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'Active';
        
        if (empty($client_name) || empty($client_type) || empty($category)) {
            $error_message = "Client Name, Client Type, and Category are required fields.";
        } else {
            if (empty($client_code)) {
                $client_code = generateClientCode($conn, $category);
            }
            
            $insertStmt = $conn->prepare("
                INSERT INTO clients (
                    client_code, client_name, client_type, category, address, 
                    city, country, phone, mobile, email, contact_person, 
                    npwp, join_date, status, is_deleted
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            
            $insertStmt->execute([
                $client_code, $client_name, $client_type, $category, $address,
                $city, $country, $phone, $mobile, $email, $contact_person,
                $npwp, $join_date, $status
            ]);
            
            $success_message = "Client successfully added with code: $client_code!";
            header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Edit Client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_client'])) {
    try {
        $id = $_POST['client_id'] ?? 0;
        $client_code = $_POST['client_code'] ?? '';
        $client_name = $_POST['client_name'] ?? '';
        $client_type = $_POST['client_type'] ?? '';
        $category = $_POST['category'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $country = $_POST['country'] ?? 'Indonesia';
        $phone = $_POST['phone'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $email = $_POST['email'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        $npwp = $_POST['npwp'] ?? '';
        $join_date = $_POST['join_date'] ?? '';
        $status = $_POST['status'] ?? 'Active';
        
        if (empty($client_name) || empty($client_type) || empty($category) || empty($id)) {
            $error_message = "Required fields are missing.";
        } else {
            $updateStmt = $conn->prepare("
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
                WHERE id = ? AND is_deleted = 0
            ");
            
            $updateStmt->execute([
                $client_code, $client_name, $client_type, $category, $address,
                $city, $country, $phone, $mobile, $email, $contact_person,
                $npwp, $join_date, $status, $id
            ]);
            
            if ($updateStmt->rowCount() > 0) {
                $success_message = "Client successfully updated!";
                header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error_message = "Client not found or already deleted.";
            }
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// ============================================
// FETCH DATA
// ============================================
$stmt = $conn->prepare("
    SELECT 
        id,
        client_code,
        client_name,
        client_type,
        category,
        address,
        city,
        country,
        phone,
        mobile,
        email,
        contact_person,
        npwp,
        join_date,
        status,
        created_at,
        updated_at,
        is_deleted
    FROM clients 
    WHERE is_deleted = 0 
    ORDER BY client_code ASC
");
$stmt->execute();
$clients = $stmt->fetchAll();
$total_clients = count($clients);

// Deleted clients count
$deletedStmt = $conn->query("SELECT COUNT(*) as deleted_count FROM clients WHERE is_deleted = 1");
$deletedCount = $deletedStmt->fetch()['deleted_count'] ?? 0;

// Set default for super admin
$is_super_admin = true;
$nextClientCode = generateClientCode($conn, 'client');
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management | GIBSYSNET</title>
    
    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Glassmorphism & Modern Styling -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 25px 50px rgba(0, 0, 0, 0.25);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #2c3e50;
            overflow-x: hidden;
        }
        
        /* Top Navigation Bar - Sama dengan clients.report.html */
        .top-nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 25px 30px;
            box-shadow: var(--shadow-lg);
        }
        
        .nav-left {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .back-button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        
        .back-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 0;
        }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .export-button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .export-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(67, 233, 123, 0.4);
        }
        
        .glass-card-solid {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
        }
        
        .nav-glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 16px 0 0 16px;
        }
        
        .stats-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }
        
        .stats-card.total::before { background: var(--primary-gradient); }
        .stats-card.active::before { background: var(--success-gradient); }
        .stats-card.inactive::before { background: var(--warning-gradient); }
        .stats-card.deleted::before { background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%); }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 26px;
            color: white;
        }
        
        .stats-icon.total { background: var(--primary-gradient); }
        .stats-icon.active { background: var(--success-gradient); }
        .stats-icon.inactive { background: var(--warning-gradient); }
        .stats-icon.deleted { background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%); }
        
        .stats-number {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        
        /* Badges */
        .status-badge {
            padding: 8px 18px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-badge i {
            font-size: 10px;
        }
        
        .status-active {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.15) 0%, rgba(39, 174, 96, 0.15) 100%);
            color: #27ae60;
            border: 2px solid rgba(39, 174, 96, 0.3);
        }
        
        .status-inactive {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.15) 0%, rgba(192, 57, 43, 0.15) 100%);
            color: #e74c3c;
            border: 2px solid rgba(231, 76, 60, 0.3);
        }
        
        .status-pending {
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.15) 0%, rgba(230, 126, 34, 0.15) 100%);
            color: #f39c12;
            border: 2px solid rgba(243, 156, 18, 0.3);
        }
        
        /* Buttons */
        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        
        .btn-gradient:hover::before {
            left: 100%;
        }
        
        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        /* Table */
        .modern-table {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: rgba(0, 0, 0, 0.02);
            --bs-table-hover-bg: rgba(52, 152, 219, 0.08);
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 16px;
            overflow: hidden;
        }
        
        .modern-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .modern-table th {
            border: none;
            padding: 18px 20px;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .modern-table td {
            border: none;
            padding: 18px 20px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 500;
        }
        
        .modern-table tbody tr {
            transition: all 0.3s ease;
            background: white;
        }
        
        .modern-table tbody tr:hover {
            background: rgba(52, 152, 219, 0.05);
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .action-btn:hover {
            transform: translateY(-3px) scale(1.1);
        }
        
        .btn-view {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, rgba(41, 128, 185, 0.1) 100%);
            color: #3498db;
            border-color: rgba(52, 152, 219, 0.2);
        }
        
        .btn-view:hover {
            background: #3498db;
            color: white;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.1) 0%, rgba(230, 126, 34, 0.1) 100%);
            color: #f39c12;
            border-color: rgba(243, 156, 18, 0.2);
        }
        
        .btn-edit:hover {
            background: #f39c12;
            color: white;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1) 0%, rgba(192, 57, 43, 0.1) 100%);
            color: #e74c3c;
            border-color: rgba(231, 76, 60, 0.2);
        }
        
        .btn-delete:hover {
            background: #e74c3c;
            color: white;
        }
        
        /* Empty State */
        .empty-state {
            padding: 80px 40px;
            text-align: center;
        }
        
        .empty-state-icon {
            font-size: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        
        /* Modal */
        .modal-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .modal-header-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
        }
        
        /* Form */
        .form-floating-custom {
            position: relative;
            margin-bottom: 20px;
        }
        
        .form-control-modern {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #e0e6f1;
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control-modern:focus {
            background: white;
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
        }
        
        /* Theme Toggle */
        .theme-toggle {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: rotate(30deg);
        }
        
        /* Loader */
        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Animation Classes */
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        .animate-slide-up {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-card {
                padding: 20px;
            }
            
            .stats-number {
                font-size: 24px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-btn {
                width: 32px;
                height: 32px;
            }
            
            .top-nav-bar {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .nav-left {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-right {
                width: 100%;
                justify-content: center;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
        
        /* Dark Mode */
        [data-bs-theme="dark"] body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e0e0e0;
        }
        
        [data-bs-theme="dark"] .glass-card {
            background: rgba(30, 30, 46, 0.95);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-bs-theme="dark"] .glass-card-solid {
            background: rgba(30, 30, 46, 0.95);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-bs-theme="dark"] .modern-table tbody tr {
            background: rgba(30, 30, 46, 0.8);
        }
        
        [data-bs-theme="dark"] .filter-section {
            background: rgba(30, 30, 46, 0.9);
        }
        
        [data-bs-theme="dark"] .page-subtitle {
            color: #a0a0a0;
        }
    </style>
</head>
<body class="animate-fade-in">
    <!-- Main Container -->
    <div class="container-fluid py-4 px-4 px-lg-5">
        <!-- Top Navigation Bar - Sama dengan clients.report.html -->
        <div class="top-nav-bar glass-card">
            <div class="nav-left">
                 <a href="../../docs/dashboard/dashboard-admin.html" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
                <div>
                    <h2 class="page-title">Client Management</h2>
                    <p class="page-subtitle">Comprehensive management of all client data and records</p>
                </div>
            </div>
            <div class="nav-right">
                <button class="export-button" onclick="exportClientReport()">
                    <i class="fas fa-file-export"></i> Export Report
                </button>
            </div>
        </div>

        <!-- Header (Original) -->
        <nav class="nav-glass rounded-4 p-4 mb-5 animate-slide-up">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <div>
                    <h1 class="h2 fw-bold mb-1 d-flex align-items-center gap-3">
                        <div class="p-3 rounded-4" style="background: var(--primary-gradient);">
                            <i class="bi bi-people-fill text-white fs-4"></i>
                        </div>
                        <div>
                            <span class="text-gradient">Client Database</span>
                            <small class="d-block text-muted fs-6 fw-normal mt-1">
                                <i class="bi bi-database me-1"></i> Total <?= number_format($total_clients) ?> active clients
                            </small>
                        </div>
                    </h1>
                </div>
                
                <div class="d-flex flex-wrap gap-3">
                    <?php if ($is_super_admin): ?>
                    <a href="../master/deleted-clients.php" class="btn btn-outline-warning d-flex align-items-center gap-2">
                        <i class="bi bi-trash"></i>
                        Deleted Clients
                        <span class="badge bg-warning text-dark ms-1"><?= $deletedCount ?></span>
                    </a>
                    <?php endif; ?>
                    
                    <button type="button" class="btn-gradient" data-bs-toggle="modal" data-bs-target="#addClientModal">
                        <i class="bi bi-plus-circle me-2"></i>Add New Client
                    </button>
                </div>
            </div>
        </nav>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5 animate-slide-up" style="animation-delay: 0.1s">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card total h-100">
                    <div class="stats-icon total">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-number"><?= number_format($total_clients) ?></div>
                    <div class="stats-label">Total Active Clients</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-arrow-up text-success me-1"></i>
                        12% increase
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card active h-100">
                    <div class="stats-icon active">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stats-number"><?= number_format(getStatusCount($clients, 'Active')) ?></div>
                    <div class="stats-label">Active Status</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-check-lg text-success me-1"></i>
                        Operational
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card inactive h-100">
                    <div class="stats-icon inactive">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="stats-number"><?= number_format(getStatusCount($clients, 'Inactive')) ?></div>
                    <div class="stats-label">Inactive Status</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                        Requires attention
                    </div>
                </div>
            </div>
            
            <?php if ($is_super_admin): ?>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card deleted h-100">
                    <div class="stats-icon deleted">
                        <i class="bi bi-trash"></i>
                    </div>
                    <div class="stats-number"><?= number_format($deletedCount) ?></div>
                    <div class="stats-label">Soft Deleted</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-archive me-1"></i>
                        In recycle bin
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filter Section -->
        <div class="filter-section mb-4 animate-slide-up" style="animation-delay: 0.2s">
            <div class="row g-3 align-items-end">
                <div class="col-xl-4 col-lg-6">
                    <label class="form-label fw-semibold mb-2">
                        <i class="bi bi-search me-2"></i>Search Clients
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control form-control-modern border-start-0" 
                               id="searchInput" placeholder="Search by name, code, email, phone...">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label fw-semibold mb-2">Client Type</label>
                    <select class="form-select form-select-modern" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="individual">Individual</option>
                        <option value="corporate">Corporate</option>
                        <option value="government">Government</option>
                    </select>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label fw-semibold mb-2">Category</label>
                    <select class="form-select form-select-modern" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="client">Client</option>
                        <option value="agent">Agent</option>
                        <option value="marketing">Marketing</option>
                        <option value="partner">Partner</option>
                    </select>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label fw-semibold mb-2">Status</label>
                    <select class="form-select form-select-modern" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-6">
                    <div class="d-flex gap-2">
                        <button class="btn-gradient flex-grow-1" id="applyFilterBtn">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <button class="btn btn-outline-secondary" id="resetFilterBtn" title="Reset Filters">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="glass-card-solid p-4 mb-4 animate-slide-up" style="animation-delay: 0.3s">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-0">Client List</h5>
                    <p class="text-muted mb-0">Showing <?= number_format($total_clients) ?> active records</p>
                </div>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary d-flex align-items-center gap-2" id="exportExcelBtn">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                    <button class="btn btn-outline-danger d-flex align-items-center gap-2" id="exportPDFBtn">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                    <button class="btn btn-outline-success d-flex align-items-center gap-2" id="exportCSVBtn">
                        <i class="bi bi-file-earmark-text"></i> CSV
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table id="clientsTable" class="table modern-table">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>CLIENT CODE</th>
                            <th>CLIENT NAME</th>
                            <th>TYPE</th>
                            <th>CATEGORY</th>
                            <th>CONTACT</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clients) > 0): ?>
                            <?php foreach ($clients as $index => $row): ?>
                                <?php 
                                $clientCode = safe_html($row['client_code'] ?? '');
                                $clientName = safe_html($row['client_name'] ?? '');
                                $clientType = safe_html($row['client_type'] ?? '');
                                $category = safe_html($row['category'] ?? '');
                                $phone = safe_html($row['phone'] ?? '');
                                $email = safe_html($row['email'] ?? '');
                                $status = safe_html($row['status'] ?? '');
                                $contactPerson = safe_html($row['contact_person'] ?? '');
                                ?>
                                <tr data-id="<?= $row['id'] ?>">
                                    <!-- PERUBAHAN DI SINI: Kosongkan kolom #, akan diisi oleh DataTables -->
                                    <td class="fw-bold text-center dt-index"></td>
                                    
                                    <td>
                                        <div class="fw-bold text-primary"><?= $clientCode ?></div>
                                        <small class="text-muted">ID: <?= $row['id'] ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= $clientName ?></div>
                                        <small class="text-muted"><?= $contactPerson ?: 'No contact person' ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2">
                                            <?= ucfirst($clientType) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">
                                            <?= ucfirst($category) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?= $phone ?: 'N/A' ?></div>
                                        <?php if ($email): ?>
                                            <small>
                                                <a href="mailto:<?= $email ?>" class="text-decoration-none">
                                                    <?= $email ?>
                                                </a>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($status === 'Active'): ?>
                                            <span class="status-badge status-active">
                                                <i class="bi bi-check-circle-fill"></i> Active
                                            </span>
                                        <?php elseif ($status === 'Inactive'): ?>
                                            <span class="status-badge status-inactive">
                                                <i class="bi bi-x-circle-fill"></i> Inactive
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">
                                                <i class="bi bi-clock-fill"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn btn-view" 
                                                    onclick="viewClient(<?= $row['id'] ?>)"
                                                    title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="action-btn btn-edit" 
                                                    onclick="editClient(<?= $row['id'] ?>)"
                                                    title="Edit Client">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="action-btn btn-delete" 
                                                    onclick="deleteClientPrompt(<?= $row['id'] ?>, '<?= addslashes($clientName) ?>')"
                                                    title="Delete Client">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state py-5">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <h4 class="fw-bold mb-3">No Clients Found</h4>
                                        <p class="text-muted mb-4">No client records available in the database. Start by adding your first client.</p>
                                        <button type="button" class="btn-gradient" data-bs-toggle="modal" data-bs-target="#addClientModal">
                                            <i class="bi bi-plus-circle me-2"></i>Add First Client
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="text-center text-muted py-3">
            <p class="mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Showing active clients only • 
                Last updated: <?= date('F j, Y, g:i a') ?> • 
                System: GIBSYSNET v2.0
            </p>
        </div>
    </div>

    <!-- ============================================
    MODALS
    ============================================ -->

    <!-- Add Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header modal-header-gradient">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Add New Client
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="addClientForm">
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-modern" 
                                           id="clientCodeInput" name="client_code" 
                                           value="<?= $nextClientCode ?>" readonly>
                                    <label for="clientCodeInput" class="form-label">Client Code</label>
                                </div>
                                <div class="d-flex gap-2 align-items-center mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="generateCodeBtn">
                                        <i class="bi bi-arrow-repeat me-1"></i>Generate New
                                    </button>
                                    <small class="text-muted">Auto-generated based on category</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <select class="form-select form-control-modern" 
                                            id="categorySelect" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="client" selected>Client</option>
                                        <option value="agent">Agent</option>
                                        <option value="marketing">Marketing</option>
                                        <option value="partner">Partner</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <label for="categorySelect" class="form-label">Category *</label>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-modern" 
                                           name="client_name" required 
                                           placeholder="Client Name">
                                    <label for="clientName" class="form-label">Client Name *</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <select class="form-select form-control-modern" 
                                            name="client_type" required>
                                        <option value="">Select Type</option>
                                        <option value="individual">Individual</option>
                                        <option value="corporate">Corporate</option>
                                        <option value="government">Government</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <label for="clientType" class="form-label">Client Type *</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <input type="date" class="form-control form-control-modern" 
                                           name="join_date" value="<?= date('Y-m-d') ?>" required>
                                    <label for="joinDate" class="form-label">Join Date *</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-modern" 
                                           name="contact_person" placeholder="Contact Person">
                                    <label for="contactPerson" class="form-label">Contact Person</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <input type="email" class="form-control form-control-modern" 
                                           name="email" placeholder="Email">
                                    <label for="email" class="form-label">Email Address</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-modern" 
                                           name="phone" placeholder="Phone">
                                    <label for="phone" class="form-label">Phone Number</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-modern" 
                                           name="mobile" placeholder="Mobile">
                                    <label for="mobile" class="form-label">Mobile Number</label>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="form-floating-custom">
                                    <textarea class="form-control form-control-modern" 
                                              name="address" rows="2" placeholder="Address"></textarea>
                                    <label for="address" class="form-label">Address</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-modern" 
                                           name="city" placeholder="City">
                                    <label for="city" class="form-label">City</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-modern" 
                                           name="country" value="Indonesia" placeholder="Country">
                                    <label for="country" class="form-label">Country</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-modern" 
                                           name="npwp" placeholder="NPWP">
                                    <label for="npwp" class="form-label">Tax ID (NPWP)</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating-custom">
                                    <select class="form-select form-control-modern" name="status" required>
                                        <option value="Active" selected>Active</option>
                                        <option value="Inactive">Inactive</option>
                                        <option value="Pending">Pending</option>
                                    </select>
                                    <label for="status" class="form-label">Status *</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" name="add_client" class="btn-gradient">
                            <i class="bi bi-save me-2"></i>Save Client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Client Modal -->
    <div class="modal fade" id="editClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header modal-header-gradient">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i>Edit Client
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="editClientForm">
                    <input type="hidden" name="client_id" id="editClientId">
                    <div class="modal-body p-4" id="editClientModalBody">
                        <!-- Loaded via AJAX -->
                        <div class="text-center py-5">
                            <div class="loader mx-auto mb-3"></div>
                            <p>Loading client data...</p>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" name="edit_client" class="btn-gradient">
                            <i class="bi bi-save me-2"></i>Update Client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Client Modal -->
    <div class="modal fade" id="viewClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header modal-header-gradient">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-badge me-2"></i>Client Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="viewClientModalBody">
                    <!-- Loaded via AJAX -->
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn-gradient" id="editFromViewBtn">
                        <i class="bi bi-pencil me-2"></i>Edit Client
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-trash3 me-2"></i>Delete Client
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="deleteClientForm">
                    <input type="hidden" id="deleteClientId">
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="p-4 rounded-circle bg-danger bg-opacity-10 d-inline-block mb-3">
                                <i class="bi bi-trash3 text-danger" style="font-size: 2.5rem;"></i>
                            </div>
                            <h5 class="fw-bold mb-3" id="deleteClientName">Client Name</h5>
                            <p class="text-muted">This action will move the client to the recycle bin. You can restore it later if needed.</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason for deletion *</label>
                            <textarea class="form-control form-control-modern" 
                                      id="deleteReason" rows="3" 
                                      placeholder="Please provide a reason for deletion..." required></textarea>
                        </div>
                        
                        <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <small>Note: This is a soft delete. The client data will be archived and can be restored by Super Admin.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash3 me-2"></i>Delete Client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================
    JAVASCRIPT
    ============================================ -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable dengan konfigurasi untuk nomor urut dinamis
        const table = $('#clientsTable').DataTable({
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
            language: {
                search: "",
                searchPlaceholder: "Search clients...",
                lengthMenu: "_MENU_ per page",
                info: "Showing _START_ to _END_ of _TOTAL_ clients",
                infoEmpty: "No clients found",
                infoFiltered: "(filtered from _MAX_ total clients)",
                zeroRecords: "No matching clients found",
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>'
                }
            },
            order: [[1, 'asc']], // Urutkan berdasarkan Client Code (kolom ke-2, karena kolom # tidak bisa di-sort)
            responsive: true,
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
            columnDefs: [
                { 
                    orderable: false, 
                    targets: [0, 7] // Kolom # dan Actions tidak bisa di-sort
                },
                { 
                    className: "align-middle", 
                    targets: "_all" 
                }
            ],
            // Fungsi untuk mengenerate nomor urut dinamis
            createdRow: function(row, data, dataIndex) {
                // Set nomor urut di kolom pertama
                $('td', row).eq(0).html(dataIndex + 1);
            },
            // Fungsi untuk update nomor urut saat paging
            drawCallback: function(settings) {
                var api = this.api();
                var rows = api.rows({ page: 'current' }).nodes();
                var start = api.page.info().start;
                
                $(rows).each(function(index) {
                    $(this).find('td:first').html(start + index + 1);
                });
            },
            initComplete: function() {
                // Inisialisasi awal untuk nomor urut
                this.api().column(0).nodes().each(function(cell, i) {
                    cell.innerHTML = i + 1;
                });
            }
        });

        // Search functionality
        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        $('#clearSearch').click(function() {
            $('#searchInput').val('');
            table.search('').draw();
        });

        // Filter functionality
        $('#applyFilterBtn').click(function() {
            const typeFilter = $('#typeFilter').val();
            const categoryFilter = $('#categoryFilter').val();
            const statusFilter = $('#statusFilter').val();
            
            // Reset semua filter sebelumnya
            $.fn.dataTable.ext.search = [];
            
            // Tambahkan filter baru
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const type = data[3];
                const category = data[4];
                const status = data[6];
                
                const typeMatch = !typeFilter || type.toLowerCase().includes(typeFilter.toLowerCase());
                const categoryMatch = !categoryFilter || category.toLowerCase().includes(categoryFilter.toLowerCase());
                const statusMatch = !statusFilter || status.includes(statusFilter);
                
                return typeMatch && categoryMatch && statusMatch;
            });
            
            table.draw();
        });

        $('#resetFilterBtn').click(function() {
            $('#typeFilter').val('');
            $('#categoryFilter').val('');
            $('#statusFilter').val('');
            $('#searchInput').val('');
            
            // Reset semua filter
            $.fn.dataTable.ext.search = [];
            table.search('').columns().search('').draw();
        });

        // Generate new client code
        $('#generateCodeBtn').click(function() {
            const category = $('#categorySelect').val() || 'client';
            const $btn = $(this);
            const originalText = $btn.html();
            
            $btn.prop('disabled', true).html('<i class="bi bi-arrow-repeat me-1"></i>Generating...');
            
            $.ajax({
                url: '<?= $_SERVER['PHP_SELF'] ?>',
                method: 'GET',
                data: { generate_code: 'true', category: category },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#clientCodeInput').val(response.code);
                        Swal.fire({
                            icon: 'success',
                            title: 'Code Generated',
                            text: 'New client code: ' + response.code,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to generate code', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to generate code', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Update code when category changes
        $('#categorySelect').change(function() {
            const category = $(this).val();
            if (category) {
                $.ajax({
                    url: '<?= $_SERVER['PHP_SELF'] ?>',
                    method: 'GET',
                    data: { generate_code: 'true', category: category },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#clientCodeInput').val(response.code);
                        }
                    }
                });
            }
        });

        // Export Client Report Function (for top navigation button)
        function exportClientReport() {
            Swal.fire({
                title: 'Export Client Report',
                text: 'Please select export format',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Excel',
                cancelButtonText: 'PDF',
                showDenyButton: true,
                denyButtonText: 'CSV',
                denyButtonColor: '#43e97b'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Export to Excel
                    exportToExcel();
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Export to PDF
                    exportToPDF();
                } else if (result.isDenied) {
                    // Export to CSV
                    exportToCSV();
                }
            });
        }

        // Make function available globally
        window.exportClientReport = exportClientReport;

        // ============================================
        // EXPORT FUNCTIONS - FIXED VERSION
        // ============================================

        // Export to Excel - SIMPLIFIED AND WORKING
        function exportToExcel() {
            // Create export table data
            let exportData = [];
            let headers = ['No', 'Client Code', 'Client Name', 'Type', 'Category', 'Phone', 'Email', 'Status'];
            
            exportData.push(headers);
            
            // Get filtered data from DataTable
            table.rows({ search: 'applied' }).every(function(rowIdx, tableLoop, rowLoop) {
                let data = this.data();
                // Clean up the data for export
                let exportRow = [
                    rowLoop + 1, // No
                    $(data[1]).find('.fw-bold').text() || data[1], // Client Code
                    $(data[2]).find('.fw-bold').text() || data[2], // Client Name
                    data[3], // Type
                    data[4], // Category
                    $(data[5]).find('div').first().text().trim() || 'N/A', // Phone
                    $(data[5]).find('a').text().trim() || 'N/A', // Email
                    $(data[6]).text().trim() // Status
                ];
                exportData.push(exportRow);
            });
            
            // Create CSV content
            let csvContent = "data:text/csv;charset=utf-8,";
            exportData.forEach(function(rowArray) {
                let row = rowArray.map(cell => {
                    // Escape quotes and wrap in quotes if contains comma
                    cell = String(cell).replace(/"/g, '""');
                    if (cell.includes(',') || cell.includes('"') || cell.includes('\n')) {
                        return '"' + cell + '"';
                    }
                    return cell;
                }).join(',');
                csvContent += row + "\r\n";
            });
            
            // Add header information
            let headerInfo = [
                'Client Database - GIBSYSNET',
                'Generated on: <?= date("F j, Y, g:i a") ?>',
                'Total Clients: ' + table.rows({ search: 'applied' }).count(),
                ''
            ].join('\n');
            
            csvContent = headerInfo + csvContent;
            
            // Create download link for Excel
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "Client_List_GIBSYSNET_<?= date('Y-m-d_H-i-s') ?>.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            Swal.fire({
                icon: 'success',
                title: 'Excel Export Completed',
                text: 'Client data exported successfully as CSV (can be opened in Excel)',
                timer: 3000,
                showConfirmButton: false
            });
        }

        // Export to PDF - SIMPLIFIED AND WORKING
        function exportToPDF() {
            // Get current date and time
            let currentDate = new Date().toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Create PDF content
            let pdfContent = {
                content: [
                    { 
                        text: 'CLIENT DATABASE REPORT - GIBSYSNET', 
                        style: 'header',
                        alignment: 'center',
                        margin: [0, 0, 0, 10]
                    },
                    { 
                        text: 'Generated on: ' + currentDate, 
                        style: 'subheader',
                        alignment: 'center',
                        margin: [0, 0, 0, 5]
                    },
                    { 
                        text: 'Total Clients: ' + table.rows({ search: 'applied' }).count(), 
                        style: 'subheader',
                        alignment: 'center',
                        margin: [0, 0, 0, 20]
                    },
                    {
                        style: 'tableExample',
                        table: {
                            headerRows: 1,
                            widths: ['auto', 'auto', '*', 'auto', 'auto', '*', '*', 'auto'],
                            body: []
                        },
                        layout: {
                            hLineWidth: function(i, node) {
                                return (i === 0 || i === node.table.body.length) ? 2 : 1;
                            },
                            vLineWidth: function(i, node) {
                                return 0;
                            },
                            hLineColor: function(i, node) {
                                return (i === 0 || i === node.table.body.length) ? '#667eea' : '#cccccc';
                            },
                            paddingLeft: function(i, node) { return 4; },
                            paddingRight: function(i, node) { return 4; },
                            paddingTop: function(i, node) { return 2; },
                            paddingBottom: function(i, node) { return 2; }
                        }
                    }
                ],
                styles: {
                    header: {
                        fontSize: 18,
                        bold: true,
                        color: '#2c3e50'
                    },
                    subheader: {
                        fontSize: 12,
                        bold: false,
                        color: '#7f8c8d'
                    },
                    tableExample: {
                        margin: [0, 5, 0, 15]
                    }
                },
                defaultStyle: {
                    font: 'Helvetica',
                    fontSize: 10
                }
            };
            
            // Add table headers
            pdfContent.content[3].table.body.push([
                { text: 'No', style: 'tableHeader', bold: true },
                { text: 'Client Code', style: 'tableHeader', bold: true },
                { text: 'Client Name', style: 'tableHeader', bold: true },
                { text: 'Type', style: 'tableHeader', bold: true },
                { text: 'Category', style: 'tableHeader', bold: true },
                { text: 'Phone', style: 'tableHeader', bold: true },
                { text: 'Email', style: 'tableHeader', bold: true },
                { text: 'Status', style: 'tableHeader', bold: true }
            ]);
            
            // Add table data
            table.rows({ search: 'applied' }).every(function(rowIdx, tableLoop, rowLoop) {
                let data = this.data();
                pdfContent.content[3].table.body.push([
                    (rowLoop + 1).toString(),
                    $(data[1]).find('.fw-bold').text() || 'N/A',
                    $(data[2]).find('.fw-bold').text() || 'N/A',
                    data[3] || 'N/A',
                    data[4] || 'N/A',
                    $(data[5]).find('div').first().text().trim() || 'N/A',
                    $(data[5]).find('a').text().trim() || 'N/A',
                    $(data[6]).text().trim() || 'N/A'
                ]);
            });
            
            // Generate PDF
            pdfMake.createPdf(pdfContent).download('Client_Report_GIBSYSNET_<?= date('Y-m-d_H-i-s') ?>.pdf');
            
            Swal.fire({
                icon: 'success',
                title: 'PDF Export Started',
                text: 'Your PDF file is being generated...',
                timer: 2000,
                showConfirmButton: false
            });
        }

        // Export to CSV - SIMPLIFIED AND WORKING
        function exportToCSV() {
            let csvData = [];
            let headers = ['No', 'Client Code', 'Client Name', 'Type', 'Category', 'Phone', 'Email', 'Status', 'Contact Person', 'Address', 'City', 'Country', 'Join Date'];
            
            csvData.push(headers);
            
            // Get all data (not just filtered)
            table.rows({ search: 'applied' }).every(function(rowIdx, tableLoop, rowLoop) {
                let data = this.data();
                // We need to get the original row data
                let row = $(this.node());
                let clientId = row.data('id');
                
                // Create row data
                let csvRow = [
                    rowLoop + 1,
                    $(data[1]).find('.fw-bold').text() || '',
                    $(data[2]).find('.fw-bold').text() || '',
                    data[3] || '',
                    data[4] || '',
                    $(data[5]).find('div').first().text().trim() || '',
                    $(data[5]).find('a').text().trim() || '',
                    $(data[6]).text().trim() || ''
                ];
                
                csvData.push(csvRow);
            });
            
            // Convert to CSV string
            let csvContent = "data:text/csv;charset=utf-8,";
            csvData.forEach(function(rowArray) {
                let row = rowArray.map(cell => {
                    cell = String(cell).replace(/"/g, '""');
                    if (cell.includes(',') || cell.includes('"') || cell.includes('\n')) {
                        return '"' + cell + '"';
                    }
                    return cell;
                }).join(',');
                csvContent += row + "\r\n";
            });
            
            // Add metadata
            let metadata = [
                'CLIENT DATABASE EXPORT - GIBSYSNET',
                'Generated on: <?= date("F j, Y, g:i a") ?>',
                'Total Records: ' + table.rows({ search: 'applied' }).count(),
                '',
                ''
            ].join('\n');
            
            csvContent = metadata + csvContent;
            
            // Download CSV
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "Client_Database_GIBSYSNET_<?= date('Y-m-d_H-i-s') ?>.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            Swal.fire({
                icon: 'success',
                title: 'CSV Export Completed',
                text: 'Client data exported successfully as CSV file',
                timer: 3000,
                showConfirmButton: false
            });
        }

        // Button event handlers
        $('#exportExcelBtn').click(function() {
            exportToExcel();
        });

        $('#exportPDFBtn').click(function() {
            exportToPDF();
        });

        $('#exportCSVBtn').click(function() {
            exportToCSV();
        });

        // ============================================
        // VIEW CLIENT FUNCTION
        // ============================================
        window.viewClient = function(id) {
            $.ajax({
                url: '<?= $_SERVER['PHP_SELF'] ?>',
                method: 'GET',
                data: { get_client: 'true', id: id },
                dataType: 'json',
                beforeSend: function() {
                    $('#viewClientModalBody').html(`
                        <div class="text-center py-5">
                            <div class="loader mx-auto mb-3"></div>
                            <p>Loading client details...</p>
                        </div>
                    `);
                },
                success: function(response) {
                    if (response.success) {
                        const client = response.client;
                        
                        // Format join date
                        const joinDate = new Date(client.join_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        
                        // Determine status badge
                        let statusBadge;
                        if (client.status === 'Active') {
                            statusBadge = `<span class="badge bg-success">Active</span>`;
                        } else if (client.status === 'Inactive') {
                            statusBadge = `<span class="badge bg-danger">Inactive</span>`;
                        } else {
                            statusBadge = `<span class="badge bg-warning">Pending</span>`;
                        }
                        
                        // Build HTML
                        const html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light mb-4">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted mb-3">
                                                <i class="bi bi-info-circle me-2"></i>Basic Information
                                            </h6>
                                            <div class="row">
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Client Code</small>
                                                    <strong class="text-primary">${client.client_code}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Status</small>
                                                    ${statusBadge}
                                                </div>
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted d-block">Client Name</small>
                                                    <strong>${client.client_name}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Client Type</small>
                                                    <span class="badge bg-info">${client.client_type}</span>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Category</small>
                                                    <span class="badge bg-primary">${client.category}</span>
                                                </div>
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted d-block">Join Date</small>
                                                    <strong>${joinDate}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light mb-4">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted mb-3">
                                                <i class="bi bi-telephone me-2"></i>Contact Information
                                            </h6>
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted d-block">Contact Person</small>
                                                    <strong>${client.contact_person || 'Not specified'}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Phone</small>
                                                    <strong>${client.phone || 'N/A'}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Mobile</small>
                                                    <strong>${client.mobile || 'N/A'}</strong>
                                                </div>
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted d-block">Email</small>
                                                    <strong>
                                                        ${client.email ? `<a href="mailto:${client.email}">${client.email}</a>` : 'N/A'}
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light mb-4">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted mb-3">
                                                <i class="bi bi-geo-alt me-2"></i>Address
                                            </h6>
                                            <p>${client.address || 'No address provided'}</p>
                                            <div class="row mt-3">
                                                <div class="col-6">
                                                    <small class="text-muted d-block">City</small>
                                                    <strong>${client.city || 'N/A'}</strong>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Country</small>
                                                    <strong>${client.country || 'N/A'}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light mb-4">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted mb-3">
                                                <i class="bi bi-file-text me-2"></i>Additional Information
                                            </h6>
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted d-block">Tax ID (NPWP)</small>
                                                    <strong>${client.npwp || 'Not provided'}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Created</small>
                                                    <strong>${new Date(client.created_at).toLocaleDateString()}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Last Updated</small>
                                                    <strong>${client.updated_at ? new Date(client.updated_at).toLocaleDateString() : 'Never'}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        $('#viewClientModalBody').html(html);
                        $('#viewClientModal').modal('show');
                        
                        // Set edit button to work from view
                        $('#editFromViewBtn').off('click').click(function() {
                            $('#viewClientModal').modal('hide');
                            setTimeout(() => {
                                editClient(id);
                            }, 300);
                        });
                        
                    } else {
                        Swal.fire('Error', response.message || 'Client not found', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load client details', 'error');
                }
            });
        };

        // ============================================
        // EDIT CLIENT FUNCTION
        // ============================================
        window.editClient = function(id) {
            $.ajax({
                url: '<?= $_SERVER['PHP_SELF'] ?>',
                method: 'GET',
                data: { get_client: 'true', id: id },
                dataType: 'json',
                beforeSend: function() {
                    $('#editClientModalBody').html(`
                        <div class="text-center py-5">
                            <div class="loader mx-auto mb-3"></div>
                            <p>Loading client data...</p>
                        </div>
                    `);
                },
                success: function(response) {
                    if (response.success) {
                        const client = response.client;
                        
                        const html = `
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input type="text" class="form-control form-control-modern" 
                                               name="client_code" value="${client.client_code}" required>
                                        <label class="form-label">Client Code *</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <select class="form-select form-control-modern" name="category" required>
                                            <option value="client" ${client.category === 'client' ? 'selected' : ''}>Client</option>
                                            <option value="agent" ${client.category === 'agent' ? 'selected' : ''}>Agent</option>
                                            <option value="marketing" ${client.category === 'marketing' ? 'selected' : ''}>Marketing</option>
                                            <option value="partner" ${client.category === 'partner' ? 'selected' : ''}>Partner</option>
                                            <option value="other" ${client.category === 'other' ? 'selected' : ''}>Other</option>
                                        </select>
                                        <label class="form-label">Category *</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="form-floating-custom">
                                        <input type="text" class="form-control form-control-modern" 
                                               name="client_name" value="${client.client_name}" required>
                                        <label class="form-label">Client Name *</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <select class="form-select form-control-modern" name="client_type" required>
                                            <option value="individual" ${client.client_type === 'individual' ? 'selected' : ''}>Individual</option>
                                            <option value="corporate" ${client.client_type === 'corporate' ? 'selected' : ''}>Corporate</option>
                                            <option value="government" ${client.client_type === 'government' ? 'selected' : ''}>Government</option>
                                            <option value="other" ${client.client_type === 'other' ? 'selected' : ''}>Other</option>
                                        </select>
                                        <label class="form-label">Client Type *</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input type="date" class="form-control form-control-modern" 
                                               name="join_date" value="${client.join_date}" required>
                                        <label class="form-label">Join Date *</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input type="text" class="form-control form-control-modern" 
                                               name="contact_person" value="${client.contact_person || ''}">
                                        <label class="form-label">Contact Person</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input type="email" class="form-control form-control-modern" 
                                               name="email" value="${client.email || ''}">
                                        <label class="form-label">Email Address</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input type="text" class="form-control form-control-modern" 
                                               name="phone" value="${client.phone || ''}">
                                        <label class="form-label">Phone Number</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input type="text" class="form-control form-control-modern" 
                                               name="mobile" value="${client.mobile || ''}">
                                        <label class="form-label">Mobile Number</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="form-floating-custom">
                                        <textarea class="form-control form-control-modern" 
                                                  name="address" rows="2">${client.address || ''}</textarea>
                                        <label class="form-label">Address</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input type="text" class="form-control form-control-modern" 
                                               name="city" value="${client.city || ''}">
                                        <label class="form-label">City</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input type="text" class="form-control form-control-modern" 
                                               name="country" value="${client.country || 'Indonesia'}">
                                        <label class="form-label">Country</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input type="text" class="form-control form-control-modern" 
                                               name="npwp" value="${client.npwp || ''}">
                                        <label class="form-label">Tax ID (NPWP)</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <select class="form-select form-control-modern" name="status" required>
                                            <option value="Active" ${client.status === 'Active' ? 'selected' : ''}>Active</option>
                                            <option value="Inactive" ${client.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                                            <option value="Pending" ${client.status === 'Pending' ? 'selected' : ''}>Pending</option>
                                        </select>
                                        <label class="form-label">Status *</label>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        $('#editClientId').val(id);
                        $('#editClientModalBody').html(html);
                        $('#editClientModal').modal('show');
                        
                    } else {
                        Swal.fire('Error', response.message || 'Client not found', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load client data', 'error');
                }
            });
        };

        // ============================================
        // DELETE CLIENT FUNCTION
        // ============================================
        window.deleteClientPrompt = function(id, name) {
            $('#deleteClientId').val(id);
            $('#deleteClientName').text(name);
            $('#deleteReason').val('');
            $('#deleteClientModal').modal('show');
        };

        // Handle delete form submission
        $('#deleteClientForm').submit(function(e) {
            e.preventDefault();
            
            const id = $('#deleteClientId').val();
            const reason = $('#deleteReason').val();
            
            if (!reason.trim()) {
                Swal.fire('Warning', 'Please provide a reason for deletion', 'warning');
                return;
            }
            
            Swal.fire({
                title: 'Are you sure?',
                html: `Delete <strong>${$('#deleteClientName').text()}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // AJAX request
                    $.ajax({
                        url: '<?= $_SERVER['PHP_SELF'] ?>',
                        method: 'POST',
                        data: {
                            ajax_delete: 'true',
                            client_id: id,
                            delete_reason: reason
                        },
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                            $('#deleteClientModal').modal('hide');
                        },
                        error: function() {
                            Swal.close();
                            Swal.fire('Error', 'Failed to delete client', 'error');
                            $('#deleteClientModal').modal('hide');
                        }
                    });
                }
            });
        });

        // Form validation
        $('#addClientForm, #editClientForm').on('submit', function(e) {
            let isValid = true;
            $(this).find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                Swal.fire('Warning', 'Please fill all required fields', 'warning');
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);

        // Initialize tooltips
        $('[title]').tooltip({
            trigger: 'hover',
            placement: 'top'
        });
    });
    </script>
</body>
</html>