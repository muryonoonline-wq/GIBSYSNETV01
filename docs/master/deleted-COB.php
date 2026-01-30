<?php
// deleted-COB.php - Deleted COB Products Management System
// ============================================
// ERROR REPORTING & SECURITY
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// SESSION & SECURITY CHECK
// ============================================
session_start();

// ============================================
// DATABASE CONNECTION
// ============================================
$databaseConfigPath = null;
$configPaths = [
    $_SERVER['DOCUMENT_ROOT'] . '/gibsysnet/backend/config/database.php',
    dirname(__DIR__, 2) . '/backend/config/database.php',
    __DIR__ . '/../../backend/config/database.php',
    'C:/xampp/htdocs/gibsysnet/backend/config/database.php',
];

foreach ($configPaths as $path) {
    if (file_exists($path)) {
        $databaseConfigPath = $path;
        break;
    }
}

if ($databaseConfigPath) {
    require_once $databaseConfigPath;
    if (!isset($conn) || !($conn instanceof PDO)) {
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
            die('Database connection failed');
        }
    }
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
        die('Database connection failed');
    }
}

// ============================================
// AJAX HANDLERS
// ============================================

// Get COB Product Details
if (isset($_GET['get_cob_product']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    try {
        $id = $_GET['id'];
        
        $stmt = $conn->prepare("
            SELECT * FROM cob_products 
            WHERE id = ? AND is_deleted = 1
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        if ($product) {
            echo json_encode([
                'success' => true,
                'product' => $product
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Deleted COB product not found'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
    exit();
}

// Restore COB Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_restore'])) {
    header('Content-Type: application/json');
    
    try {
        $id = $_POST['cob_product_id'];
        $restore_reason = trim($_POST['restore_reason'] ?? 'Restored by admin');
        $restored_by = 'admin'; // In production: $_SESSION['user_id']
        
        if (empty($id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid COB Product ID'
            ]);
            exit();
        }
        
        // Restore COB Product
        $stmt = $conn->prepare("
            UPDATE cob_products 
            SET 
                is_deleted = 0,
                delete_reason = NULL,
                deleted_at = NULL,
                deleted_by = NULL,
                restored_at = NOW(),
                restored_by = ?,
                is_active = 1,
                updated_at = NOW(),
                updated_by = ?
            WHERE id = ? AND is_deleted = 1
        ");
        $stmt->execute([$restored_by, $restored_by, $id]);
        
        $affectedRows = $stmt->rowCount();
        
        if ($affectedRows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'COB product restored successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'COB product not found or already restored'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
    exit();
}

// Permanent Delete COB Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_permanent_delete'])) {
    header('Content-Type: application/json');
    
    try {
        $id = $_POST['cob_product_id'];
        $permanent_delete_reason = trim($_POST['permanent_delete_reason'] ?? '');
        $deleted_by = 'admin'; // In production: $_SESSION['user_id']
        
        if (empty($id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid COB Product ID'
            ]);
            exit();
        }
        
        if (empty($permanent_delete_reason)) {
            echo json_encode([
                'success' => false,
                'message' => 'Reason for permanent deletion is required'
            ]);
            exit();
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Permanent delete from main table
            $deleteStmt = $conn->prepare("DELETE FROM cob_products WHERE id = ?");
            $deleteStmt->execute([$id]);
            $affectedRows = $deleteStmt->rowCount();
            
            if ($affectedRows > 0) {
                $conn->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'COB product permanently deleted'
                ]);
            } else {
                $conn->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'COB product not found or not in deleted state'
                ]);
            }
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
    exit();
}

// Export to CSV/Excel
if (isset($_GET['export']) && in_array($_GET['export'], ['csv', 'excel'])) {
    $exportType = $_GET['export'];
    
    if ($exportType === 'csv' || $exportType === 'excel') {
        // Fetch data for export
        $query = "SELECT * FROM cob_products WHERE is_deleted = 1 ORDER BY deleted_at DESC";
        $stmt = $conn->query($query);
        $products = $stmt->fetchAll();
        
        // Set headers for download
        $filename = 'Deleted_COB_Products_' . date('Y-m-d_H-i-s') . '.csv';
        
        if ($exportType === 'excel') {
            header('Content-Type: application/vnd.ms-excel');
            $filename = 'Deleted_COB_Products_' . date('Y-m-d_H-i-s') . '.xls';
        } else {
            header('Content-Type: text/csv; charset=utf-8');
        }
        
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Add headers
        $headers = ['No', 'Product Code', 'Product Name', 'Type', 'Category', 'Sub-category', 'Status', 
                   'Deleted At', 'Deleted By', 'Delete Reason', 'Created At', 'Created By'];
        fputcsv($output, $headers);
        
        // Add data rows
        $counter = 1;
        foreach ($products as $row) {
            $data = [
                $counter++,
                $row['product_code'],
                $row['product_name'],
                $row['type'] == 'general' ? 'General Insurance' : 'Life Insurance',
                $row['category'],
                $row['sub_category'],
                $row['is_active'] ? 'Active' : 'Inactive',
                date('M d, Y H:i', strtotime($row['deleted_at'])),
                $row['deleted_by'] ?? 'Unknown',
                $row['delete_reason'] ?? 'No reason provided',
                date('M d, Y H:i', strtotime($row['created_at'])),
                $row['created_by'] ?? 'System'
            ];
            fputcsv($output, $data);
        }
        
        fclose($output);
        exit();
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function safe_html($value) {
    return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}

function formatDate($date, $format = 'F j, Y') {
    if (!$date || $date == '0000-00-00 00:00:00' || $date == '0000-00-00') {
        return 'N/A';
    }
    try {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

function formatDateTime($date) {
    return formatDate($date, 'M d, Y H:i');
}

// ============================================
// FETCH DELETED COB PRODUCTS DATA
// ============================================
$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Build query with filters
$query = "
    SELECT 
        id, type, category, sub_category, product_name, product_code, 
        description, is_active, is_deleted, delete_reason, deleted_at, 
        deleted_by, created_at, updated_at, created_by, updated_by
    FROM cob_products 
    WHERE is_deleted = 1
";

$params = [];
$conditions = [];

// Add search condition
if (!empty($search)) {
    $conditions[] = "(product_code LIKE ? OR product_name LIKE ? OR category LIKE ? OR sub_category LIKE ? OR description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// Add type filter
if (!empty($typeFilter)) {
    $conditions[] = "type = ?";
    $params[] = $typeFilter;
}

// Add category filter
if (!empty($categoryFilter)) {
    $conditions[] = "category = ?";
    $params[] = $categoryFilter;
}

// Add date filter
if (!empty($dateFilter)) {
    switch ($dateFilter) {
        case 'today':
            $conditions[] = "DATE(deleted_at) = CURDATE()";
            break;
        case 'week':
            $conditions[] = "deleted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $conditions[] = "deleted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $conditions[] = "deleted_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
}

// Combine conditions
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Add ordering
$query .= " ORDER BY deleted_at DESC, product_name ASC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $cob_products = $stmt->fetchAll();
    $total_deleted_cob_products = count($cob_products);
} catch (Exception $e) {
    $cob_products = [];
    $total_deleted_cob_products = 0;
}

// Get counts
try {
    $activeCount = $conn->query("SELECT COUNT(*) as count FROM cob_products WHERE is_deleted = 0 AND is_active = 1")->fetch()['count'] ?? 0;
    $totalCount = $conn->query("SELECT COUNT(*) as count FROM cob_products WHERE is_deleted = 0")->fetch()['count'] ?? 0;
} catch (Exception $e) {
    $activeCount = 0;
    $totalCount = 0;
}

// Set default for super admin
$is_super_admin = true;

// Product Structure for dropdowns
$productStructure = [
    'general' => [
        'name' => 'General Insurance',
        'categories' => [
            'property' => 'Property Insurance',
            'motor' => 'Motor Insurance',
            'marine' => 'Marine Insurance',
            'miscellaneous' => 'Miscellaneous Insurance'
        ]
    ],
    'life' => [
        'name' => 'Life Insurance',
        'categories' => [
            'traditional' => 'Traditional Life Insurance',
            'investment' => 'Investment Linked Insurance',
            'health' => 'Health & Medical Insurance',
            'pension' => 'Pension & Annuity'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleted COB Products Management | GIBSYSNET</title>
    
    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Glassmorphism & Modern Styling -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #ff5858 0%, #f09819 100%);
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
        
        /* Top Navigation Bar */
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
            background: var(--danger-gradient);
            border-radius: 16px 0 0 16px;
        }
        
        .stats-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }
        
        .stats-card.deleted::before { background: var(--danger-gradient); }
        .stats-card.active::before { background: var(--success-gradient); }
        .stats-card.total::before { background: var(--primary-gradient); }
        
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
        
        .stats-icon.deleted { background: var(--danger-gradient); }
        .stats-icon.active { background: var(--success-gradient); }
        .stats-icon.total { background: var(--primary-gradient); }
        
        .stats-number {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
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
            cursor: pointer;
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
        
        .btn-restore-sm {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1) 0%, rgba(39, 174, 96, 0.1) 100%);
            color: #27ae60;
            border-color: rgba(46, 204, 113, 0.2);
        }
        
        .btn-restore-sm:hover {
            background: #27ae60;
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
        
        /* Form */
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
    </style>
</head>
<body class="animate-fade-in">
    <!-- Main Container -->
    <div class="container-fluid py-4 px-4 px-lg-5">
        <!-- Top Navigation Bar -->
        <div class="top-nav-bar glass-card">
            <div class="nav-left">
                <a href="master-cob.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Active COB Products
                </a>
                <div>
                    <h2 class="page-title">Deleted COB Products Management</h2>
                    <p class="page-subtitle">Manage deleted COB products records in recycle bin</p>
                </div>
            </div>
            <div class="nav-right">
                <button class="export-button" onclick="showExportOptions()">
                    <i class="fas fa-file-export"></i> Export Report
                </button>
            </div>
        </div>

        <!-- Header -->
        <nav class="nav-glass rounded-4 p-4 mb-5 animate-slide-up">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <div>
                    <h1 class="h2 fw-bold mb-1 d-flex align-items-center gap-3">
                        <div class="p-3 rounded-4" style="background: var(--danger-gradient);">
                            <i class="bi bi-trash text-white fs-4"></i>
                        </div>
                        <div>
                            <span class="text-gradient">Deleted COB Products Database</span>
                            <small class="d-block text-muted fs-6 fw-normal mt-1">
                                <i class="bi bi-trash me-1"></i> Total <?= number_format($total_deleted_cob_products) ?> deleted COB products in recycle bin
                            </small>
                        </div>
                    </h1>
                </div>
                
                <div class="d-flex flex-wrap gap-3">
                    <a href="master-cob.php" class="btn btn-outline-primary d-flex align-items-center gap-2">
                        <i class="bi bi-boxes"></i>
                        Active COB Products
                    </a>
                </div>
            </div>
        </nav>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5 animate-slide-up" style="animation-delay: 0.1s">
            <div class="col-xl-4 col-md-6">
                <div class="stats-card deleted h-100">
                    <div class="stats-icon deleted">
                        <i class="bi bi-trash"></i>
                    </div>
                    <div class="stats-number"><?= number_format($total_deleted_cob_products) ?></div>
                    <div class="stats-label">Deleted COB Products</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-clock-history me-1"></i>
                        In recycle bin
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-md-6">
                <div class="stats-card active h-100">
                    <div class="stats-icon active">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stats-number"><?= number_format($activeCount) ?></div>
                    <div class="stats-label">Active COB Products</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-check-lg text-success me-1"></i>
                        Currently active
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-md-6">
                <div class="stats-card total h-100">
                    <div class="stats-icon total">
                        <i class="bi bi-database"></i>
                    </div>
                    <div class="stats-number"><?= number_format($totalCount) ?></div>
                    <div class="stats-label">Total Records</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-boxes me-1"></i>
                        All COB products in system
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Time Info -->
        <div class="alert alert-info d-flex align-items-center mb-4">
            <i class="bi bi-clock-history me-3 fs-4"></i>
            <div>
                <strong>Current Time:</strong> <?= date('H:i') ?> • 
                <strong>Date:</strong> <?= date('F j, Y') ?> • 
                <strong>Items in Trash:</strong> <?= number_format($total_deleted_cob_products) ?>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section mb-4 animate-slide-up" style="animation-delay: 0.2s">
            <form method="GET" action="" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label class="form-label fw-semibold mb-2">
                            <i class="bi bi-search me-2"></i>Search Products
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control form-control-modern border-start-0" 
                                   name="search" value="<?= safe_html($search) ?>" 
                                   placeholder="Search by name, code, description...">
                            <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label class="form-label fw-semibold mb-2">Insurance Type</label>
                        <select class="form-select form-select-modern" name="type" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="general" <?= $typeFilter == 'general' ? 'selected' : '' ?>>General</option>
                            <option value="life" <?= $typeFilter == 'life' ? 'selected' : '' ?>>Life</option>
                        </select>
                    </div>
                    
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label class="form-label fw-semibold mb-2">Category</label>
                        <select class="form-select form-select-modern" name="category" id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php foreach ($productStructure as $type => $typeData): ?>
                                <?php foreach ($typeData['categories'] as $catKey => $catName): ?>
                                    <option value="<?= $catKey ?>" <?= $categoryFilter == $catKey ? 'selected' : '' ?>>
                                        <?= $catName ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label class="form-label fw-semibold mb-2">Deleted Date</label>
                        <select class="form-select form-select-modern" name="date" id="dateFilter">
                            <option value="">All Time</option>
                            <option value="today" <?= $dateFilter == 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="week" <?= $dateFilter == 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                            <option value="month" <?= $dateFilter == 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                            <option value="year" <?= $dateFilter == 'year' ? 'selected' : '' ?>>Last Year</option>
                        </select>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-12">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary flex-grow-1" type="submit">
                                <i class="bi bi-funnel me-1"></i>Apply Filters
                            </button>
                            <button class="btn btn-outline-secondary" type="button" onclick="resetFilters()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Main Table Card -->
        <div class="glass-card-solid p-4 mb-4 animate-slide-up" style="animation-delay: 0.3s">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-0">Deleted COB Products List</h5>
                    <p class="text-muted mb-0">Showing <?= number_format($total_deleted_cob_products) ?> deleted records</p>
                </div>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary d-flex align-items-center gap-2" onclick="exportToExcel()">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                    <button class="btn btn-outline-danger d-flex align-items-center gap-2" onclick="exportToPDF()">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                    <button class="btn btn-outline-success d-flex align-items-center gap-2" onclick="exportToCSV()">
                        <i class="bi bi-file-earmark-text"></i> CSV
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table id="deletedProductsTable" class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">#</th>
                            <th>PRODUCT CODE</th>
                            <th>PRODUCT NAME</th>
                            <th>TYPE</th>
                            <th>CATEGORY</th>
                            <th>DELETED AT</th>
                            <th>DELETED BY</th>
                            <th>REASON</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($cob_products) > 0): ?>
                            <?php foreach ($cob_products as $index => $row): ?>
                                <?php 
                                $productCode = safe_html($row['product_code'] ?? '');
                                $productName = safe_html($row['product_name'] ?? '');
                                $type = safe_html($row['type'] ?? '');
                                $category = safe_html($row['category'] ?? '');
                                $deleteReason = safe_html($row['delete_reason'] ?? 'No reason provided');
                                $deletedAt = formatDateTime($row['deleted_at'] ?? '');
                                $deletedBy = safe_html($row['deleted_by'] ?? 'Unknown');
                                ?>
                                <tr data-id="<?= $row['id'] ?>">
                                    <td class="fw-bold text-center"><?= $index + 1 ?></td>
                                    
                                    <td>
                                        <div class="fw-bold text-primary"><?= $productCode ?></div>
                                        <small class="text-muted">ID: <?= $row['id'] ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= $productName ?></div>
                                        <?php if (!empty($row['description'])): ?>
                                            <small class="text-muted"><?= substr($row['description'], 0, 60) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($type == 'general'): ?>
                                            <span class="badge bg-info">General</span>
                                        <?php elseif ($type == 'life'): ?>
                                            <span class="badge bg-success">Life</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= ucfirst($type) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $categoryName = $category;
                                        foreach ($productStructure as $typeData) {
                                            foreach ($typeData['categories'] as $key => $name) {
                                                if ($key == $category) {
                                                    $categoryName = $name;
                                                    break 2;
                                                }
                                            }
                                        }
                                        echo safe_html($categoryName);
                                        ?>
                                    </td>
                                    <td>
                                        <div class="text-muted"><?= $deletedAt ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= $deletedBy ?></div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="<?= $deleteReason ?>">
                                            <?= $deleteReason ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn btn-view" 
                                                    onclick="viewProduct('<?= $row['id'] ?>')"
                                                    title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="action-btn btn-restore-sm" 
                                                    onclick="restoreProduct('<?= $row['id'] ?>')"
                                                    title="Restore Product">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                            <button class="action-btn btn-delete" 
                                                    onclick="deletePermanently('<?= $row['id'] ?>')"
                                                    title="Delete Permanently">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="bi bi-trash display-1 text-muted mb-4"></i>
                                        <h4 class="fw-bold mb-3">Recycle Bin is Empty</h4>
                                        <p class="text-muted mb-4">No deleted COB products found in the recycle bin.</p>
                                        <a href="master-cob.php" class="btn btn-primary">
                                            <i class="bi bi-arrow-left me-2"></i>Back to Active Products
                                        </a>
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
                Showing deleted products only • 
                Last updated: <?= date('F j, Y, g:i a') ?>
            </p>
        </div>
    </div>

    <!-- View Product Modal -->
    <div class="modal fade" id="viewProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-eye me-2"></i>Product Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewProductContent">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Product Modal -->
    <div class="modal fade" id="restoreProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Restore Product
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="restoreForm">
                    <input type="hidden" id="restoreProductId">
                    <div class="modal-body">
                        <p>Are you sure you want to restore this product?</p>
                        <div class="mb-3">
                            <label class="form-label">Restore Reason (Optional)</label>
                            <textarea class="form-control" id="restoreReason" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Restore</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Permanently Modal -->
    <div class="modal fade" id="deletePermanentlyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-trash3 me-2"></i>Delete Permanently
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="deletePermanentlyForm">
                    <input type="hidden" id="deleteProductId">
                    <div class="modal-body">
                        <p class="text-danger fw-bold">WARNING: This action cannot be undone!</p>
                        <p>Are you sure you want to permanently delete this product?</p>
                        <div class="mb-3">
                            <label class="form-label">Reason for permanent deletion *</label>
                            <textarea class="form-control" id="deleteReason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    // SweetAlert2 Toast
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });

    // View Product Function
    async function viewProduct(id) {
        try {
            const response = await fetch(`?get_cob_product=true&id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                const product = data.product;
                const html = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Product Information</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Product Code:</strong></td>
                                            <td>${product.product_code}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Product Name:</strong></td>
                                            <td>${product.product_name}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td><span class="badge ${product.type === 'general' ? 'bg-info' : 'bg-success'}">
                                                ${product.type === 'general' ? 'General' : 'Life'} Insurance
                                            </span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Category:</strong></td>
                                            <td>${product.category}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Sub-category:</strong></td>
                                            <td>${product.sub_category}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>${product.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Deletion Information</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Deleted At:</strong></td>
                                            <td>${new Date(product.deleted_at).toLocaleString()}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Deleted By:</strong></td>
                                            <td>${product.deleted_by || 'Unknown'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Reason:</strong></td>
                                            <td>${product.delete_reason || 'No reason provided'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created At:</strong></td>
                                            <td>${new Date(product.created_at).toLocaleString()}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created By:</strong></td>
                                            <td>${product.created_by || 'System'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${product.description ? `
                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title">Description</h6>
                            <p>${product.description}</p>
                        </div>
                    </div>` : ''}
                `;
                
                document.getElementById('viewProductContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('viewProductModal')).show();
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message || 'Failed to load product details'
                });
            }
        } catch (error) {
            Toast.fire({
                icon: 'error',
                title: 'Failed to load product details'
            });
        }
    }

    // Restore Product Function
    function restoreProduct(id) {
        document.getElementById('restoreProductId').value = id;
        document.getElementById('restoreReason').value = '';
        new bootstrap.Modal(document.getElementById('restoreProductModal')).show();
    }

    // Delete Permanently Function
    function deletePermanently(id) {
        document.getElementById('deleteProductId').value = id;
        document.getElementById('deleteReason').value = '';
        new bootstrap.Modal(document.getElementById('deletePermanentlyModal')).show();
    }

    // Restore Form Submission
    document.getElementById('restoreForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const id = document.getElementById('restoreProductId').value;
        const reason = document.getElementById('restoreReason').value;
        
        try {
            const formData = new FormData();
            formData.append('ajax_restore', 'true');
            formData.append('cob_product_id', id);
            formData.append('restore_reason', reason);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
                bootstrap.Modal.getInstance(document.getElementById('restoreProductModal')).hide();
                setTimeout(() => location.reload(), 1500);
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        } catch (error) {
            Toast.fire({
                icon: 'error',
                title: 'Failed to restore product'
            });
        }
    });

    // Delete Permanently Form Submission
    document.getElementById('deletePermanentlyForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const id = document.getElementById('deleteProductId').value;
        const reason = document.getElementById('deleteReason').value;
        
        if (!reason.trim()) {
            Toast.fire({
                icon: 'warning',
                title: 'Please provide a reason for deletion'
            });
            return;
        }
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the product!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete permanently!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('ajax_permanent_delete', 'true');
                    formData.append('cob_product_id', id);
                    formData.append('permanent_delete_reason', reason);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        Toast.fire({
                            icon: 'success',
                            title: data.message
                        });
                        bootstrap.Modal.getInstance(document.getElementById('deletePermanentlyModal')).hide();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: data.message
                        });
                    }
                } catch (error) {
                    Toast.fire({
                        icon: 'error',
                        title: 'Failed to delete product'
                    });
                }
            }
        });
    });

    // Export Functions - USING SERVER-SIDE EXPORT
    function showExportOptions() {
        Swal.fire({
            title: 'Export Report',
            text: 'Select export format',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Excel',
            cancelButtonText: 'PDF',
            showDenyButton: true,
            denyButtonText: 'CSV'
        }).then((result) => {
            if (result.isConfirmed) {
                exportToExcel();
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                exportToPDF();
            } else if (result.isDenied) {
                exportToCSV();
            }
        });
    }

    function exportToExcel() {
        // Show loading
        Swal.fire({
            title: 'Preparing Excel Export',
            text: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Create a form and submit it to trigger server-side export
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = '';
        form.style.display = 'none';
        
        // Add all current filter parameters
        const search = document.querySelector('input[name="search"]').value;
        const type = document.querySelector('select[name="type"]').value;
        const category = document.querySelector('select[name="category"]').value;
        const date = document.querySelector('select[name="date"]').value;
        
        if (search) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'search';
            input.value = search;
            form.appendChild(input);
        }
        
        if (type) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'type';
            input.value = type;
            form.appendChild(input);
        }
        
        if (category) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'category';
            input.value = category;
            form.appendChild(input);
        }
        
        if (date) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'date';
            input.value = date;
            form.appendChild(input);
        }
        
        // Add export parameter
        const exportInput = document.createElement('input');
        exportInput.type = 'hidden';
        exportInput.name = 'export';
        exportInput.value = 'excel';
        form.appendChild(exportInput);
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        // Close loading after a delay (the page will reload with download)
        setTimeout(() => {
            Swal.close();
        }, 1000);
    }

    function exportToCSV() {
        // Show loading
        Swal.fire({
            title: 'Preparing CSV Export',
            text: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Create a form and submit it to trigger server-side export
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = '';
        form.style.display = 'none';
        
        // Add all current filter parameters
        const search = document.querySelector('input[name="search"]').value;
        const type = document.querySelector('select[name="type"]').value;
        const category = document.querySelector('select[name="category"]').value;
        const date = document.querySelector('select[name="date"]').value;
        
        if (search) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'search';
            input.value = search;
            form.appendChild(input);
        }
        
        if (type) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'type';
            input.value = type;
            form.appendChild(input);
        }
        
        if (category) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'category';
            input.value = category;
            form.appendChild(input);
        }
        
        if (date) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'date';
            input.value = date;
            form.appendChild(input);
        }
        
        // Add export parameter
        const exportInput = document.createElement('input');
        exportInput.type = 'hidden';
        exportInput.name = 'export';
        exportInput.value = 'csv';
        form.appendChild(exportInput);
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        // Close loading after a delay (the page will reload with download)
        setTimeout(() => {
            Swal.close();
        }, 1000);
    }

    function exportToPDF() {
        Toast.fire({
            icon: 'info',
            title: 'PDF export requires additional setup. Please use Excel or CSV export for now.'
        });
    }

    // Filter Functions
    function clearSearch() {
        document.querySelector('input[name="search"]').value = '';
        document.getElementById('filterForm').submit();
    }

    function resetFilters() {
        document.querySelector('input[name="search"]').value = '';
        document.querySelector('select[name="type"]').value = '';
        document.querySelector('select[name="category"]').value = '';
        document.querySelector('select[name="date"]').value = '';
        document.getElementById('filterForm').submit();
    }

    // Auto-hide alerts
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    </script>
</body>
</html>