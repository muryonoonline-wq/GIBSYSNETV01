<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// FIXED DATABASE CONNECTION - REVISI LENGKAP
// ============================================

// Coba beberapa path yang mungkin
$possiblePaths = [
    // Path 1: Dari root dokumen (direkomendasikan)
    $_SERVER['DOCUMENT_ROOT'] . '/gibsysnet/backend/config/database.php',
    
    // Path 2: Relatif naik 3 level dari docs/master
    dirname(__DIR__, 2) . '/backend/config/database.php',
    
    // Path 3: Alternatif relatif path
    __DIR__ . '/../../backend/config/database.php',
    
    // Path 4: Path absolut untuk XAMPP
    'C:/xampp/htdocs/gibsysnet/backend/config/database.php',
];

$databaseConfigPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $databaseConfigPath = $path;
        break;
    }
}

// Jika file config ditemukan, include
if ($databaseConfigPath) {
    require_once $databaseConfigPath;
} else {
    // Fallback: Buat koneksi database langsung
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
        die("Database connection failed: " . $e->getMessage());
    }
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

function generateClientCode($conn) {
    // Generate next client code: CL001, CL002, etc.
    $stmt = $conn->query("SELECT MAX(client_code) as max_code FROM clients WHERE client_code LIKE 'CL%'");
    $result = $stmt->fetch();
    
    if ($result && $result['max_code']) {
        $lastNumber = intval(substr($result['max_code'], 2));
        $nextNumber = $lastNumber + 1;
        return 'CL' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    } else {
        return 'CL001';
    }
}

// ============================================
// HANDLE FORM SUBMISSIONS
// ============================================

// Handle Add New Client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    try {
        // Collect form data
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
        
        // Validate required fields
        if (empty($client_name) || empty($client_type) || empty($category)) {
            $error_message = "Client Name, Client Type, and Category are required fields.";
        } else {
            // Generate client code if not provided
            if (empty($client_code)) {
                $client_code = generateClientCode($conn);
            } else {
                // Check if client code already exists
                $checkStmt = $conn->prepare("SELECT id FROM clients WHERE client_code = ? AND is_deleted = 0");
                $checkStmt->execute([$client_code]);
                
                if ($checkStmt->fetch()) {
                    $error_message = "Client Code already exists. Please use a different code.";
                }
            }
            
            if (!isset($error_message)) {
                // Insert new client
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
                
                // Refresh the page to show new data
                header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Handle Edit Client
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
            // Check if client code already exists (excluding current client)
            $checkStmt = $conn->prepare("SELECT id FROM clients WHERE client_code = ? AND id != ? AND is_deleted = 0");
            $checkStmt->execute([$client_code, $id]);
            
            if ($checkStmt->fetch()) {
                $error_message = "Client Code already exists. Please use a different code.";
            } else {
                // Update client
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
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Handle Soft Delete Client (traditional form submission - backup)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soft_delete_client'])) {
    try {
        $id = $_POST['client_id'] ?? 0;
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
            $success_message = "Client has been soft deleted successfully.";
            header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error_message = "Client not found or already deleted.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Handle Restore Client (for super admin - traditional form submission - backup)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_client'])) {
    try {
        $id = $_POST['client_id'] ?? 0;
        
        $stmt = $conn->prepare("
            UPDATE clients SET 
                is_deleted = 0,
                deleted_at = NULL,
                delete_reason = NULL,
                status = 'Active'
            WHERE id = ?
        ");
        
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $success_message = "Client has been restored successfully.";
            header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error_message = "Client not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// ============================================
// FETCH DATA (Only active, non-deleted clients)
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
    ORDER BY client_name ASC
");
$stmt->execute();
$clients = $stmt->fetchAll();
$total_clients = count($clients);

// Fetch deleted clients count (for super admin stats)
$deletedStmt = $conn->query("SELECT COUNT(*) as deleted_count FROM clients WHERE is_deleted = 1");
$deletedCount = $deletedStmt->fetch()['deleted_count'] ?? 0;

// Check if user is super admin (in real app, check from session)
$is_super_admin = true; // Set to true for testing, change based on your authentication system
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Master Data - GIBSYSNET</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #00bcd4;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            padding: 20px;
        }
        
        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 15px 25px;
            margin-bottom: 25px;
            border-radius: 10px;
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 14px;
        }
        
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color), #34495e);
            color: white;
            padding: 20px 25px;
            border-bottom: none;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid var(--secondary-color);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 22px;
        }
        
        .stats-icon.total { background: rgba(52, 152, 219, 0.1); color: var(--secondary-color); }
        .stats-icon.active { background: rgba(39, 174, 96, 0.1); color: var(--success-color); }
        .stats-icon.inactive { background: rgba(231, 76, 60, 0.1); color: var(--danger-color); }
        .stats-icon.deleted { background: rgba(149, 165, 166, 0.1); color: #7f8c8d; }
        
        .stats-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .data-table-wrapper {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }
        
        .table-custom {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table-custom thead {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }
        
        .table-custom th {
            border: none;
            padding: 15px;
            font-weight: 600;
            color: var(--primary-color);
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table-custom td {
            border: none;
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .table-custom tbody tr {
            transition: background-color 0.2s;
        }
        
        .table-custom tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .status-active {
            background-color: rgba(39, 174, 96, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .status-inactive {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .status-pending {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .type-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .type-individual { background: rgba(52, 152, 219, 0.15); color: #2980b9; }
        .type-corporate { background: rgba(155, 89, 182, 0.15); color: #8e44ad; }
        .type-government { background: rgba(241, 196, 15, 0.15); color: #f39c12; }
        .type-other { background: rgba(46, 204, 113, 0.15); color: #27ae60; }
        
        .category-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .category-client { background: rgba(52, 152, 219, 0.15); color: #2980b9; }
        .category-agent { background: rgba(155, 89, 182, 0.15); color: #8e44ad; }
        .category-marketing { background: rgba(241, 196, 15, 0.15); color: #f39c12; }
        .category-partner { background: rgba(46, 204, 113, 0.15); color: #27ae60; }
        .category-other { background: rgba(149, 165, 166, 0.15); color: #7f8c8d; }
        
        .action-buttons .btn {
            padding: 6px 12px;
            margin-right: 5px;
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .btn-view { background: rgba(52, 152, 219, 0.1); color: var(--secondary-color); border: 1px solid rgba(52, 152, 219, 0.2); }
        .btn-edit { background: rgba(243, 156, 18, 0.1); color: var(--warning-color); border: 1px solid rgba(243, 156, 18, 0.2); }
        .btn-delete { background: rgba(231, 76, 60, 0.1); color: var(--danger-color); border: 1px solid rgba(231, 76, 60, 0.2); }
        .btn-restore { background: rgba(46, 204, 113, 0.1); color: #27ae60; border: 1px solid rgba(46, 204, 113, 0.2); }
        
        .btn-view:hover { background: var(--secondary-color); color: white; }
        .btn-edit:hover { background: var(--warning-color); color: white; }
        .btn-delete:hover { background: var(--danger-color); color: white; }
        .btn-restore:hover { background: #27ae60; color: white; }
        
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 45px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            height: 45px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #2980b9, #21618c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 60px;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        
        .empty-state h4 {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #adb5bd;
            max-width: 400px;
            margin: 0 auto 20px;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .export-btn {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px 20px;
            color: #6c757d;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 500;
        }
        
        .export-btn:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            color: var(--primary-color);
        }
        
        /* Modal Styles */
        .modal-header-custom {
            background: linear-gradient(135deg, var(--primary-color), #34495e);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .form-label-custom {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        
        .form-control-custom, .form-select-custom {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control-custom:focus, .form-select-custom:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .alert-custom {
            border-radius: 8px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Toast notification */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .toast-custom {
            min-width: 300px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        /* Super Admin Badge */
        .super-admin-badge {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        /* Deleted row style */
        .deleted-row {
            background-color: rgba(236, 240, 241, 0.5);
            opacity: 0.7;
        }
        
        .deleted-row:hover {
            background-color: rgba(236, 240, 241, 0.8);
        }
        
        .deleted-badge {
            background-color: #95a5a6;
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        /* Bulk Action Toolbar */
        .bulk-action-toolbar {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Toast Notification Container -->
    <div class="toast-container">
        <?php if (isset($success_message)): ?>
            <div class="toast toast-custom align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i><?= safe_html($success_message) ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="toast toast-custom align-items-center text-bg-danger border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-exclamation-circle me-2"></i><?= safe_html($error_message) ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="container-fluid">
        <!-- Top Navigation -->
        <nav class="navbar-custom">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div>
                    <h4 class="page-title"><i class="fas fa-users me-2"></i>Client Master Data</h4>
                    <p class="page-subtitle">Manage active clients (soft deleted clients are hidden)</p>
                </div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-primary-custom me-3" data-bs-toggle="modal" data-bs-target="#addClientModal">
                        <i class="fas fa-plus-circle me-2"></i>Add New Client
                    </button>
                    <?php if ($is_super_admin): ?>
                        <!-- Di bagian tombol View Deleted, ganti dengan: -->
<a href="deleted-clients.php" class="btn btn-outline-warning me-3">
    <i class="fas fa-trash-restore me-2"></i>View Deleted (<?= $deletedCount ?>)
</a>
                    <?php endif; ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>Admin User
                            <?php if ($is_super_admin): ?>
                                <span class="super-admin-badge ms-2">Super Admin</span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Stats Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon total">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-number"><?= number_format($total_clients) ?></div>
                    <div class="stats-label">Active Clients</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon active">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number">
                        <?= number_format(getStatusCount($clients, 'Active')) ?>
                    </div>
                    <div class="stats-label">Status: Active</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon inactive">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stats-number">
                        <?= number_format(getStatusCount($clients, 'Inactive')) ?>
                    </div>
                    <div class="stats-label">Status: Inactive</div>
                </div>
            </div>
            <?php if ($is_super_admin): ?>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon deleted">
                        <i class="fas fa-trash"></i>
                    </div>
                    <div class="stats-number"><?= number_format($deletedCount) ?></div>
                    <div class="stats-label">Soft Deleted</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bulk Action Toolbar (for Super Admin) -->
        <?php if ($is_super_admin): ?>
        <div class="bulk-action-toolbar" id="bulkActionToolbar" style="display: none;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span id="selectedCount">0</span> client(s) selected
                </div>
                <div class="btn-group">
                    <button class="btn btn-sm btn-success" id="bulkRestoreBtn" disabled>
                        <i class="fas fa-trash-restore me-1"></i> Restore Selected
                    </button>
                    <button class="btn btn-sm btn-secondary" id="clearSelectionBtn">
                        <i class="fas fa-times me-1"></i> Clear Selection
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Search Client</label>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name, code, or email...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Client Type</label>
                    <select class="form-select select2" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="individual">Individual</option>
                        <option value="corporate">Corporate</option>
                        <option value="government">Government</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Category</label>
                    <select class="form-select select2" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="client">Client</option>
                        <option value="agent">Agent</option>
                        <option value="marketing">Marketing</option>
                        <option value="partner">Partner</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select select2" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary-custom flex-grow-1" id="applyFilterBtn">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <button class="btn btn-outline-secondary" id="resetFilterBtn">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <button class="export-btn" id="exportExcel">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </button>
            <button class="export-btn" id="exportPDF">
                <i class="fas fa-file-pdf me-2"></i>Export PDF
            </button>
            <button class="export-btn" id="exportCSV">
                <i class="fas fa-file-csv me-2"></i>Export CSV
            </button>
        </div>

        <!-- Data Table -->
        <div class="data-table-wrapper">
            <table id="clientsTable" class="table-custom">
                <thead>
                    <tr>
                        <?php if ($is_super_admin): ?>
                        <th width="50"><input type="checkbox" id="selectAllCheckbox"></th>
                        <?php else: ?>
                        <th>#</th>
                        <?php endif; ?>
                        <th>Client Code</th>
                        <th>Client Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>City</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($clients) > 0): ?>
                        <?php foreach ($clients as $index => $row): ?>
                            <?php 
                            // Gunakan fungsi helper untuk handle null values
                            $clientCode = safe_html($row['client_code'] ?? '');
                            $clientName = safe_html($row['client_name'] ?? '');
                            $clientType = safe_html($row['client_type'] ?? '');
                            $category = safe_html($row['category'] ?? '');
                            $city = safe_html($row['city'] ?? '');
                            $phone = safe_html($row['phone'] ?? '');
                            $email = safe_html($row['email'] ?? '');
                            $status = safe_html($row['status'] ?? '');
                            $contactPerson = safe_html($row['contact_person'] ?? '');
                            $isDeleted = $row['is_deleted'] ?? 0;
                            ?>
                            <tr class="<?= $isDeleted ? 'deleted-row' : '' ?>">
                                <?php if ($is_super_admin): ?>
                                <td>
                                    <input type="checkbox" class="client-checkbox" value="<?= $row['id'] ?>" data-client-name="<?= safe_html($clientName) ?>">
                                </td>
                                <?php else: ?>
                                <td><?= $index + 1 ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="fw-semibold text-primary"><?= $clientCode ?></span>
                                    <?php if ($isDeleted): ?>
                                        <span class="deleted-badge">Deleted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= $clientName ?></div>
                                    <small class="text-muted"><?= $contactPerson ?: 'No contact person' ?></small>
                                </td>
                                <td>
                                    <?php if ($clientType): ?>
                                        <span class="type-badge type-<?= strtolower($clientType) ?>">
                                            <?= ucfirst($clientType) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($category): ?>
                                        <span class="category-badge category-<?= strtolower($category) ?>">
                                            <?= ucfirst($category) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $city ?: 'N/A' ?></td>
                                <td>
                                    <div><?= $phone ?: 'N/A' ?></div>
                                    <?php if ($mobile = safe_html($row['mobile'] ?? '')): ?>
                                        <small class="text-muted">Mobile: <?= $mobile ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($email): ?>
                                        <a href="mailto:<?= $email ?>" class="text-decoration-none">
                                            <?= $email ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status): ?>
                                        <span class="status-badge status-<?= strtolower($status) ?>">
                                            <?= $status ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <?php if (!$isDeleted): ?>
                                        <button class="btn btn-sm btn-view" title="View Details" onclick="viewClient(<?= $row['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-edit" title="Edit Client" onclick="editClient(<?= $row['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-delete" title="Soft Delete" onclick="softDeleteClient(<?= $row['id'] ?>, '<?= addslashes($clientName) ?>')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php elseif ($is_super_admin): ?>
                                        <button class="btn btn-sm btn-restore" title="Restore Client" onclick="restoreClient(<?= $row['id'] ?>, '<?= addslashes($clientName) ?>')">
                                            <i class="fas fa-trash-restore"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $is_super_admin ? '11' : '10' ?>">
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h4>No Client Data Available</h4>
                                    <p>No client records found in the database. Start by adding your first client.</p>
                                    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addClientModal">
                                        <i class="fas fa-plus-circle me-2"></i>Add First Client
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Info Footer -->
        <div class="mt-4 text-center text-muted">
            <p class="mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Showing <?= number_format($total_clients) ?> active client records • 
                Soft deleted clients are hidden • 
                Last updated: <?= date('F j, Y, g:i a') ?>
            </p>
        </div>
    </div>

    <!-- Modal: Add New Client -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title" id="addClientModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Add New Client
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php 
                        $nextClientCode = generateClientCode($conn);
                        ?>
                        
                        <div class="row">
                            <!-- Column 1 -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label-custom">Client Code</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-custom" name="client_code" 
                                               value="<?= $nextClientCode ?>" readonly>
                                        <button type="button" class="btn btn-outline-secondary" onclick="generateNewCode()">
                                            <i class="fas fa-redo"></i> Generate New
                                        </button>
                                    </div>
                                    <small class="text-muted">Auto-generated client code</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label-custom required">Client Name</label>
                                    <input type="text" class="form-control form-control-custom" name="client_name" 
                                           placeholder="e.g., PT. Sejahtera Abadi" required maxlength="100">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label-custom required">Client Type</label>
                                    <select class="form-select form-select-custom" name="client_type" required>
                                        <option value="">Select Type</option>
                                        <option value="individual">Individual</option>
                                        <option value="corporate">Corporate</option>
                                        <option value="government">Government</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label-custom required">Category</label>
                                    <select class="form-select form-select-custom" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="client">Client</option>
                                        <option value="agent">Agent</option>
                                        <option value="marketing">Marketing</option>
                                        <option value="partner">Partner</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label-custom">Address</label>
                                    <textarea class="form-control form-control-custom" name="address" 
                                              rows="3" placeholder="Full address"></textarea>
                                </div>
                            </div>
                            
                            <!-- Column 2 -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label-custom">City</label>
                                    <input type="text" class="form-control form-control-custom" name="city" 
                                           placeholder="e.g., Jakarta" maxlength="50">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label-custom">Country</label>
                                    <input type="text" class="form-control form-control-custom" name="country" 
                                           value="Indonesia" placeholder="e.g., Indonesia" maxlength="50">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label-custom">Phone</label>
                                    <input type="text" class="form-control form-control-custom" name="phone" 
                                           placeholder="e.g., 021-12345678" maxlength="20">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label-custom">Mobile</label>
                                    <input type="text" class="form-control form-control-custom" name="mobile" 
                                           placeholder="e.g., 0812-34567890" maxlength="20">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label-custom">Email</label>
                                    <input type="email" class="form-control form-control-custom" name="email" 
                                           placeholder="e.g., info@company.com" maxlength="100">
                                </div>
                            </div>
                            
                            <!-- Column 3 -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label-custom">Contact Person</label>
                                    <input type="text" class="form-control form-control-custom" name="contact_person" 
                                           placeholder="e.g., John Doe" maxlength="100">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label-custom">NPWP</label>
                                    <input type="text" class="form-control form-control-custom" name="npwp" 
                                           placeholder="e.g., 01.234.567.8-912.000" maxlength="25">
                                    <small class="text-muted">Tax Identification Number</small>
                                </div>
                            </div>
                            
                            <!-- Column 4 -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label-custom required">Join Date</label>
                                    <input type="date" class="form-control form-control-custom" name="join_date" 
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label-custom required">Status</label>
                                    <select class="form-select form-select-custom" name="status" required>
                                        <option value="Active" selected>Active</option>
                                        <option value="Inactive">Inactive</option>
                                        <option value="Pending">Pending</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_client" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>Save Client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Client -->
    <div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title" id="editClientModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Client
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="editClientForm">
                    <input type="hidden" name="client_id" id="editClientId">
                    <div class="modal-body" id="editClientModalBody">
                        <!-- Content will be loaded via AJAX -->
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3">Loading client data...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_client" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>Update Client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Soft Delete Client -->
    <div class="modal fade" id="softDeleteModal" tabindex="-1" aria-labelledby="softDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title" id="softDeleteModalLabel">
                        <i class="fas fa-trash-alt me-2"></i>Soft Delete Client
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="softDeleteForm">
                    <input type="hidden" name="client_id" id="deleteClientId">
                    <div class="modal-body">
                        <div class="alert alert-warning alert-custom">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This action will soft delete the client. The data will be hidden but can be restored by Super Admin.
                        </div>
                        <p id="deleteClientName" class="fw-semibold mb-3"></p>
                        <div class="mb-3">
                            <label class="form-label-custom">Reason for deletion</label>
                            <textarea class="form-control form-control-custom" name="delete_reason" 
                                      rows="3" placeholder="Please provide a reason for deletion..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="soft_delete_client" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-2"></i>Soft Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select an option'
        });

        // Initialize DataTable
        const table = $('#clientsTable').DataTable({
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search in table...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            order: [[<?= $is_super_admin ? '2' : '1' ?>, 'asc']], // Sort by Client Name by default
            responsive: true,
            dom: '<"top"lf>rt<"bottom"ip><"clear">',
            columnDefs: [
                {
                    orderable: false,
                    targets: <?= $is_super_admin ? '[0, 9]' : '[0, 8]' ?> // Make checkbox and action columns non-sortable
                }
            ]
        });

        // Search functionality
        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Filter functionality
        $('#applyFilterBtn').click(function() {
            let typeFilter = $('#typeFilter').val();
            let categoryFilter = $('#categoryFilter').val();
            let statusFilter = $('#statusFilter').val();
            
            // Combine filters
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                // Adjust column indices based on super admin checkbox column
                let colOffset = <?= $is_super_admin ? '1' : '0' ?>;
                let type = data[2 + colOffset]; // Type column
                let category = data[3 + colOffset]; // Category column
                let status = data[7 + colOffset]; // Status column
                
                let typeMatch = typeFilter === '' || type.toLowerCase().includes(typeFilter);
                let categoryMatch = categoryFilter === '' || category.toLowerCase().includes(categoryFilter);
                let statusMatch = statusFilter === '' || status.includes(statusFilter);
                
                return typeMatch && categoryMatch && statusMatch;
            });
            
            table.draw();
            $.fn.dataTable.ext.search.pop(); // Remove filter function
        });

        // Reset filters
        $('#resetFilterBtn').click(function() {
            $('#searchInput').val('');
            $('#typeFilter').val('').trigger('change');
            $('#categoryFilter').val('').trigger('change');
            $('#statusFilter').val('').trigger('change');
            table.search('').columns().search('').draw();
        });

        // Generate new client code
        window.generateNewCode = function() {
            $.ajax({
                url: 'generate-client-code.php',
                method: 'GET',
                success: function(response) {
                    if (response.code) {
                        $('input[name="client_code"]').val(response.code);
                    }
                }
            });
        }

        // Load client data for editing
        window.editClient = function(id) {
            $('#editClientModal').modal('show');
            
            $.ajax({
                url: 'get-client-data.php',
                method: 'GET',
                data: { id: id },
                success: function(response) {
                    if (response.success) {
                        $('#editClientId').val(response.data.id);
                        $('#editClientModalBody').html(response.html);
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to load client data',
                            icon: 'error'
                        });
                        $('#editClientModal').modal('hide');
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to load client data',
                        icon: 'error'
                    });
                    $('#editClientModal').modal('hide');
                }
            });
        }

        // ============================================
        // INTEGRASI DELETE, RESTORE, DAN BULK OPERATIONS
        // ============================================

        // AJAX Soft Delete
        window.softDeleteClient = function(id, name) {
            $('#deleteClientId').val(id);
            $('#deleteClientName').text('Client: ' + name);
            $('#softDeleteModal').modal('show');
        }

        // Handle soft delete form submission via AJAX
        $('#softDeleteForm').on('submit', function(e) {
            e.preventDefault();
            
            const clientId = $('#deleteClientId').val();
            const reason = $('textarea[name="delete_reason"]').val();
            
            Swal.fire({
                title: 'Are you sure?',
                html: `This will soft delete client <strong>${$('#deleteClientName').text().replace('Client: ', '')}</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'delete-client.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            client_id: clientId,
                            reason: reason
                        }),
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                            $('#softDeleteModal').modal('hide');
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'An error occurred while deleting the client.',
                                'error'
                            );
                            $('#softDeleteModal').modal('hide');
                        }
                    });
                }
            });
        });

        // AJAX Restore Client
        window.restoreClient = function(id, name) {
            Swal.fire({
                title: 'Restore Client',
                html: `Are you sure you want to restore <strong>${name}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, restore it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'restore-delete.php',
                        method: 'POST',
                        data: { client_id: id },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Restored!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'There was an error restoring the client.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

        // View Deleted Client Details
        window.viewDeletedClientDetails = function(id) {
            $.ajax({
                url: 'get-delete-client-detail.php',
                method: 'GET',
                data: { id: id },
                success: function(response) {
                    if (response.success) {
                        const client = response.client;
                        
                        Swal.fire({
                            title: 'Deleted Client Details',
                            html: `
                                <div class="text-start">
                                    <p><strong>Client Code:</strong> ${client.client_code}</p>
                                    <p><strong>Client Name:</strong> ${client.client_name}</p>
                                    <p><strong>Deleted Date:</strong> ${client.deleted_at_formatted}</p>
                                    <p><strong>Reason for Deletion:</strong><br>${client.delete_reason || 'No reason provided'}</p>
                                    <hr>
                                    <p><strong>Contact:</strong> ${client.phone || 'N/A'} / ${client.email || 'N/A'}</p>
                                    <p><strong>Status:</strong> <span class="badge bg-danger">Deleted</span></p>
                                </div>
                            `,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'Restore Client',
                            cancelButtonText: 'Close',
                            showDenyButton: true,
                            denyButtonText: 'Permanent Delete',
                            confirmButtonColor: '#27ae60',
                            denyButtonColor: '#e74c3c'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Restore client
                                restoreClient(id, client.client_name);
                            } else if (result.isDenied) {
                                // Permanent delete confirmation
                                Swal.fire({
                                    title: 'Permanent Delete',
                                    html: `Are you sure you want to <strong>permanently delete</strong> ${client.client_name}?<br><small class="text-danger">This action cannot be undone!</small>`,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#e74c3c',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Yes, delete permanently'
                                }).then((permanentResult) => {
                                    if (permanentResult.isConfirmed) {
                                        // Implement permanent delete if needed
                                        Swal.fire(
                                            'Not Implemented',
                                            'Permanent delete functionality is not implemented in this demo.',
                                            'info'
                                        );
                                    }
                                });
                            }
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        'Error!',
                        'Failed to load client details.',
                        'error'
                    );
                }
            });
        }

        // Enhance existing viewClient function to include deleted client details
        window.viewClient = function(id) {
            $.ajax({
                url: 'get-client-details.php',
                method: 'GET',
                data: { id: id },
                success: function(response) {
                    if (response.success) {
                        const client = response.client;
                        
                        let html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Basic Information</h6>
                                    <p><strong>Client Code:</strong> ${client.client_code}</p>
                                    <p><strong>Client Name:</strong> ${client.client_name}</p>
                                    <p><strong>Client Type:</strong> <span class="badge bg-info">${client.client_type}</span></p>
                                    <p><strong>Category:</strong> <span class="badge bg-secondary">${client.category}</span></p>
                                    <p><strong>Status:</strong> <span class="badge ${client.status === 'Active' ? 'bg-success' : 'bg-danger'}">${client.status}</span></p>
                                    ${client.is_deleted ? `<p><strong>Deleted Status:</strong> <span class="badge bg-dark">Deleted</span></p>` : ''}
                                </div>
                                <div class="col-md-6">
                                    <h6>Contact Information</h6>
                                    <p><strong>Address:</strong> ${client.address || 'N/A'}</p>
                                    <p><strong>City:</strong> ${client.city || 'N/A'}</p>
                                    <p><strong>Phone:</strong> ${client.phone || 'N/A'}</p>
                                    <p><strong>Mobile:</strong> ${client.mobile || 'N/A'}</p>
                                    <p><strong>Email:</strong> <a href="mailto:${client.email}">${client.email || 'N/A'}</a></p>
                                    <p><strong>Contact Person:</strong> ${client.contact_person || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6>Additional Information</h6>
                                    <p><strong>NPWP:</strong> ${client.npwp || 'N/A'}</p>
                                    <p><strong>Join Date:</strong> ${client.join_date || 'N/A'}</p>
                                    <p><strong>Country:</strong> ${client.country || 'N/A'}</p>
                                </div>
                                ${client.is_deleted ? `
                                <div class="col-md-6">
                                    <h6 class="text-danger">Deletion Information</h6>
                                    <p><strong>Deleted At:</strong> ${client.deleted_at || 'N/A'}</p>
                                    <p><strong>Delete Reason:</strong> ${client.delete_reason || 'No reason provided'}</p>
                                </div>
                                ` : ''}
                            </div>
                        `;
                        
                        Swal.fire({
                            title: 'Client Details',
                            html: html,
                            width: 800,
                            showCloseButton: true,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        'Error!',
                        'Failed to load client details.',
                        'error'
                    );
                }
            });
        }

        // ============================================
        // BULK OPERATIONS (for Super Admin)
        // ============================================
        <?php if ($is_super_admin): ?>
        // Select all checkbox functionality
        $('#selectAllCheckbox').on('change', function() {
            $('.client-checkbox').prop('checked', this.checked);
            updateBulkActionToolbar();
        });

        // Individual checkbox functionality
        $(document).on('change', '.client-checkbox', function() {
            // Update select all checkbox state
            const allChecked = $('.client-checkbox:checked').length === $('.client-checkbox').length;
            $('#selectAllCheckbox').prop('checked', allChecked);
            
            updateBulkActionToolbar();
        });

        // Update bulk action toolbar
        function updateBulkActionToolbar() {
            const selectedCount = $('.client-checkbox:checked').length;
            $('#selectedCount').text(selectedCount);
            
            if (selectedCount > 0) {
                $('#bulkActionToolbar').show();
                $('#bulkRestoreBtn').prop('disabled', false);
            } else {
                $('#bulkActionToolbar').hide();
                $('#bulkRestoreBtn').prop('disabled', true);
            }
        }

        // Clear selection
        $('#clearSelectionBtn').click(function() {
            $('.client-checkbox').prop('checked', false);
            $('#selectAllCheckbox').prop('checked', false);
            updateBulkActionToolbar();
        });

        // Bulk restore functionality
        $('#bulkRestoreBtn').click(function() {
            const selectedIds = [];
            const selectedNames = [];
            
            $('.client-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
                selectedNames.push($(this).data('client-name'));
            });
            
            if (selectedIds.length === 0) return;
            
            Swal.fire({
                title: 'Restore Multiple Clients',
                html: `Are you sure you want to restore <strong>${selectedIds.length}</strong> client(s)?<br><small>${selectedNames.slice(0, 3).join(', ')}${selectedNames.length > 3 ? ' and ' + (selectedNames.length - 3) + ' more...' : ''}</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, restore ${selectedIds.length} client(s)`
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'bulk-restore.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ client_ids: selectedIds }),
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Restored!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'An error occurred during bulk restore.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
        <?php endif; ?>

        // Show modal if there's an error
        <?php if (isset($error_message) && !isset($_POST['edit_client']) && !isset($_POST['soft_delete_client'])): ?>
            $(document).ready(function() {
                $('#addClientModal').modal('show');
            });
        <?php endif; ?>

        // Auto-hide toast notifications
        setTimeout(function() {
            $('.toast').toast('hide');
        }, 5000);

        // Export buttons
        $('#exportExcel').click(function() {
            Swal.fire({
                title: 'Export to Excel',
                text: 'Preparing Excel file for download...',
                icon: 'info',
                showConfirmButton: false,
                timer: 2000
            });
        });

        $('#exportPDF').click(function() {
            Swal.fire({
                title: 'Export to PDF',
                text: 'Generating PDF document...',
                icon: 'info',
                showConfirmButton: false,
                timer: 2000
            });
        });

        $('#exportCSV').click(function() {
            // Simple CSV export
            let csv = [];
            let rows = document.querySelectorAll("#clientsTable tr");
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll("td, th");
                
                for (let j = 0; j < cols.length; j++) {
                    // Skip checkbox column for super admin
                    if (<?= $is_super_admin ? 'true' : 'false' ?> && j === 0 && i > 0) continue;
                    
                    let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").replace(/(\s\s)/gm, " ");
                    row.push(text);
                }
                csv.push(row.join(","));        
            }

            // Download CSV file
            let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "clients_<?= date('Y-m-d') ?>.csv");
            document.body.appendChild(link);
            link.click();
            
            Swal.fire({
                title: 'Success!',
                text: 'CSV file downloaded successfully.',
                icon: 'success',
                timer: 2000
            });
        });

        // Form validation for add client
        $('#addClientModal form').on('submit', function(e) {
            let isValid = true;
            let errorMessages = [];
            
            // Check required fields
            $(this).find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                    errorMessages.push($(this).prev('label').text().replace(' *', '') + ' is required');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                let errorHtml = '<div class="alert alert-danger alert-custom"><ul class="mb-0">';
                errorMessages.forEach(msg => {
                    errorHtml += '<li>' + msg + '</li>';
                });
                errorHtml += '</ul></div>';
                
                // Show error at the top of modal
                if ($('#addClientModal .alert-danger').length === 0) {
                    $('#addClientModal .modal-body').prepend(errorHtml);
                }
            }
        });
    });
    </script>
</body>
</html>