<?php
// master-data-client.php - MODERNIZED VERSION (FOR DELETED/INACTIVE CLIENTS)
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
// AJAX HANDLERS (UNTUK DELETED CLIENTS)
// ============================================

// Get Client Details AJAX (untuk View dan Edit) - UNTUK DELETED CLIENTS
if (isset($_GET['get_client']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    try {
        $id = intval($_GET['id']);
        
        // MODIFIKASI: Ambil data termasuk yang deleted
        $stmt = $conn->prepare("
            SELECT * FROM clients 
            WHERE id = ?
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
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// PERBAIKAN: Restore Client AJAX 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_restore'])) {
    header('Content-Type: application/json');
    
    try {
        $id = intval($_POST['client_id']);
        $restore_reason = $_POST['restore_reason'] ?? 'Restored by admin';
        
        // PERBAIKAN 1: Cek apakah client ada dan deleted
        $checkStmt = $conn->prepare("SELECT id, is_deleted FROM clients WHERE id = ?");
        $checkStmt->execute([$id]);
        $client = $checkStmt->fetch();
        
        if (!$client) {
            echo json_encode([
                'success' => false,
                'message' => 'Client not found'
            ]);
            exit();
        }
        
        if ($client['is_deleted'] == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Client is not deleted'
            ]);
            exit();
        }
        
        // PERBAIKAN 2: Update tanpa kolom restored_at jika tidak ada
        // Cek apakah kolom restored_at dan restore_reason ada di tabel
        $checkColumns = $conn->query("SHOW COLUMNS FROM clients LIKE 'restored_at'");
        $hasRestoredAt = $checkColumns->rowCount() > 0;
        
        $checkColumns2 = $conn->query("SHOW COLUMNS FROM clients LIKE 'restore_reason'");
        $hasRestoreReason = $checkColumns2->rowCount() > 0;
        
        if ($hasRestoredAt && $hasRestoreReason) {
            // Jika kolom ada, gunakan query dengan kolom tersebut
            $stmt = $conn->prepare("
                UPDATE clients SET 
                    is_deleted = 0,
                    restored_at = NOW(),
                    restore_reason = ?,
                    deleted_at = NULL,
                    delete_reason = NULL,
                    status = 'Active'
                WHERE id = ? AND is_deleted = 1
            ");
        } else {
            // Jika kolom tidak ada, gunakan query tanpa kolom tersebut
            $stmt = $conn->prepare("
                UPDATE clients SET 
                    is_deleted = 0,
                    deleted_at = NULL,
                    delete_reason = NULL,
                    status = 'Active'
                WHERE id = ? AND is_deleted = 1
            ");
            // Untuk kasus ini, kita akan execute dengan parameter yang berbeda
            $stmt->execute([$id]);
            $affectedRows = $stmt->rowCount();
            
            if ($affectedRows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Client restored successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Client not found or already restored'
                ]);
            }
            exit();
        }
        
        $stmt->execute([$restore_reason, $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Client restored successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Client not found or already restored'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Permanent Delete AJAX (FITUR BARU UNTUK DELETED CLIENTS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_permanent_delete'])) {
    header('Content-Type: application/json');
    
    try {
        $id = intval($_POST['client_id']);
        $permanent_delete_reason = $_POST['permanent_delete_reason'] ?? 'Permanently deleted by admin';
        
        // Cek apakah client ada dan deleted
        $checkStmt = $conn->prepare("SELECT id, is_deleted FROM clients WHERE id = ?");
        $checkStmt->execute([$id]);
        $client = $checkStmt->fetch();
        
        if (!$client) {
            echo json_encode([
                'success' => false,
                'message' => 'Client not found'
            ]);
            exit();
        }
        
        if ($client['is_deleted'] == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Client is not deleted. Cannot permanently delete.'
            ]);
            exit();
        }
        
        // Delete permanen dari database
        $deleteStmt = $conn->prepare("DELETE FROM clients WHERE id = ? AND is_deleted = 1");
        $deleteStmt->execute([$id]);
        
        if ($deleteStmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Client permanently deleted'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Client not found or not in deleted state'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
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

function getDeletedCount($conn) {
    $stmt = $conn->query("SELECT COUNT(*) as deleted_count FROM clients WHERE is_deleted = 1");
    return $stmt->fetch()['deleted_count'] ?? 0;
}

function getActiveCount($conn) {
    $stmt = $conn->query("SELECT COUNT(*) as active_count FROM clients WHERE is_deleted = 0");
    return $stmt->fetch()['active_count'] ?? 0;
}

function formatDate($date) {
    if (!$date) return 'N/A';
    return date('F j, Y', strtotime($date));
}

// ============================================
// FETCH DATA - AMBIL DATA DELETED CLIENTS
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
        deleted_at,
        delete_reason,
        is_deleted
    FROM clients 
    WHERE is_deleted = 1 
    ORDER BY deleted_at DESC, client_name ASC
");
$stmt->execute();
$clients = $stmt->fetchAll();
$total_deleted_clients = count($clients);

// Active clients count
$activeCount = getActiveCount($conn);

// Total deleted count (sama dengan $total_deleted_clients)
$deletedCount = $total_deleted_clients;

// Set default for super admin
$is_super_admin = true;

// Check if there are success/error messages
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleted Clients Management | GIBSYSNET</title>
    
    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Glassmorphism & Modern Styling - SAMA PERSIS dengan master-data-client.php -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 25px 50px rgba(0, 0, 0, 0.25);
        }
        
        /* ============================================
           CONTAINER & MARGIN SAMA PERSIS dengan master-data-client.php
           ============================================ */
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #2c3e50;
            overflow-x: hidden;
            padding: 0;
            margin: 0;
        }
        
        /* Main Container - SAMA PERSIS */
        .main-container {
            padding: 25px 30px;
            max-width: 100%;
            margin: 0 auto;
        }
        
        /* Top Navigation Bar - SAMA PERSIS */
        .top-nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        /* Glass Card - UKURAN SAMA PERSIS */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 18px;
            padding: 22px 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
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
        
        /* Glass Card Solid - SAMA PERSIS */
        .glass-card-solid {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 22px 25px;
            margin-bottom: 25px;
        }
        
        /* Nav Glass - SAMA PERSIS */
        .nav-glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 18px;
            padding: 22px 25px;
            margin-bottom: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        /* Filter Section - UKURAN SAMA */
        .filter-section {
            background: white;
            border-radius: 18px;
            padding: 22px 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }
        
        /* ============================================
           STYLE KHUSUS UNTUK DELETED CLIENTS
           ============================================ */
        
        /* Stats Cards - Modified for deleted clients */
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
            margin-bottom: 25px;
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
        
        /* Status Badge for Deleted Clients */
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
        
        .status-deleted {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.15) 0%, rgba(192, 57, 43, 0.15) 100%);
            color: #e74c3c;
            border: 2px solid rgba(231, 76, 60, 0.3);
        }
        
        .status-active {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.15) 0%, rgba(39, 174, 96, 0.15) 100%);
            color: #27ae60;
            border: 2px solid rgba(39, 174, 96, 0.3);
        }
        
        .status-inactive {
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.15) 0%, rgba(230, 126, 34, 0.15) 100%);
            color: #f39c12;
            border: 2px solid rgba(243, 156, 18, 0.3);
        }
        
        /* Buttons - Modified for deleted clients */
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
        
        /* Restore Button */
        .btn-restore {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-restore:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(67, 233, 123, 0.4);
            color: white;
        }
        
        /* Permanent Delete Button */
        .btn-permanent-delete {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-permanent-delete:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.4);
            color: white;
        }
        
        /* Table - SAMA dengan master-data-client.php */
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
        
        /* Action Buttons - Modified for deleted clients */
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
        
        .btn-restore-small {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1) 0%, rgba(39, 174, 96, 0.1) 100%);
            color: #27ae60;
            border-color: rgba(46, 204, 113, 0.2);
        }
        
        .btn-restore-small:hover {
            background: #27ae60;
            color: white;
        }
        
        .btn-permanent-delete-small {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1) 0%, rgba(192, 57, 43, 0.1) 100%);
            color: #e74c3c;
            border-color: rgba(231, 76, 60, 0.2);
        }
        
        .btn-permanent-delete-small:hover {
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
        
        /* Modal - SAMA dengan master-data-client.php */
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
        
        /* Responsive - SAMA dengan master-data-client.php */
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            
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
                padding: 18px;
                margin-bottom: 20px;
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
            
            .glass-card,
            .glass-card-solid,
            .nav-glass,
            .filter-section {
                padding: 18px;
                margin-bottom: 20px;
                border-radius: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .main-container {
                padding: 10px;
            }
            
            .top-nav-bar {
                padding: 15px;
                margin-bottom: 15px;
                border-radius: 15px;
            }
            
            .glass-card,
            .glass-card-solid,
            .nav-glass,
            .filter-section {
                padding: 15px;
                margin-bottom: 15px;
                border-radius: 12px;
            }
        }
        
        /* Dark Mode - SAMA dengan master-data-client.php */
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
        
        [data-bs-theme="dark"] .top-nav-bar {
            background: rgba(30, 30, 46, 0.95);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-bs-theme="dark"] .nav-glass {
            background: rgba(30, 30, 46, 0.85);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Success/Error Messages */
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }
        
        .alert-animate {
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="animate-fade-in">
    <!-- Alert Messages Container -->
    <div class="alert-container">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show alert-animate" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show alert-animate" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Container dengan padding yang sama persis -->
    <div class="main-container">
        <!-- Top Navigation Bar - SAMA dengan master-data-client.php -->
        <div class="top-nav-bar">
            <div class="nav-left">
                 <a href="../master/master-data-client.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Active Clients
                </a>
                <div>
                    <h2 class="page-title">Deleted Clients Management</h2>
                    <p class="page-subtitle">Manage and restore deleted client records from recycle bin</p>
                </div>
            </div>
            <div class="nav-right">
                <button class="export-button" onclick="exportClientReport()">
                    <i class="fas fa-file-export"></i> Export Report
                </button>
            </div>
        </div>

        <!-- Header (Original) -->
        <nav class="nav-glass">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <div>
                    <h1 class="h2 fw-bold mb-1 d-flex align-items-center gap-3">
                        <div class="p-3 rounded-4" style="background: var(--danger-gradient);">
                            <i class="bi bi-trash-fill text-white fs-4"></i>
                        </div>
                        <div>
                            <span class="text-gradient">Deleted Clients Database</span>
                            <small class="d-block text-muted fs-6 fw-normal mt-1">
                                <i class="bi bi-database me-1"></i> Total <?= number_format($total_deleted_clients) ?> deleted clients
                            </small>
                        </div>
                    </h1>
                </div>
                
                <div class="d-flex flex-wrap gap-3">
                    <?php if ($is_super_admin): ?>
                    <a href="../master/master-data-client.php" class="btn btn-outline-primary d-flex align-items-center gap-2">
                        <i class="bi bi-people"></i>
                        Active Clients
                        <span class="badge bg-primary ms-1"><?= $activeCount ?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Stats Cards - MODIFIED FOR DELETED CLIENTS -->
        <div class="row g-4 mb-4 animate-slide-up" style="animation-delay: 0.1s">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card deleted h-100">
                    <div class="stats-icon deleted">
                        <i class="bi bi-trash"></i>
                    </div>
                    <div class="stats-number"><?= number_format($total_deleted_clients) ?></div>
                    <div class="stats-label">Deleted Clients</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-archive me-1"></i>
                        In recycle bin
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card active h-100">
                    <div class="stats-icon active">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stats-number"><?= number_format($activeCount) ?></div>
                    <div class="stats-label">Active Clients</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-check-lg text-success me-1"></i>
                        Currently active
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card total h-100">
                    <div class="stats-icon total">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-number"><?= number_format($activeCount + $total_deleted_clients) ?></div>
                    <div class="stats-label">Total Records</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-database me-1"></i>
                        All clients in system
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="stats-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stats-number" style="color: white; -webkit-text-fill-color: white;">
                        <?= date('H:i') ?>
                    </div>
                    <div class="stats-label" style="color: rgba(255,255,255,0.9);">Last Updated</div>
                    <div class="text-light mt-2 fs-7">
                        <i class="bi bi-calendar me-1"></i>
                        <?= date('F j, Y') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section animate-slide-up" style="animation-delay: 0.2s">
            <div class="row g-3 align-items-end">
                <div class="col-xl-4 col-lg-6">
                    <label class="form-label fw-semibold mb-2">
                        <i class="bi bi-search me-2"></i>Search Deleted Clients
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
                    <label class="form-label fw-semibold mb-2">Deleted Date</label>
                    <select class="form-select form-select-modern" id="dateFilter">
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
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
        <div class="glass-card-solid animate-slide-up" style="animation-delay: 0.3s">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-0">Deleted Clients List</h5>
                    <p class="text-muted mb-0">Showing <?= number_format($total_deleted_clients) ?> deleted records</p>
                </div>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary d-flex align-items-center gap-2" id="exportExcel">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                    <button class="btn btn-outline-danger d-flex align-items-center gap-2" id="exportPDF">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                    <button class="btn btn-outline-success d-flex align-items-center gap-2" id="exportCSV">
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
                            <th>DELETED DATE</th>
                            <th>DELETE REASON</th>
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
                                $deleteDate = $row['deleted_at'] ? date('M d, Y H:i', strtotime($row['deleted_at'])) : 'N/A';
                                $deleteReason = safe_html($row['delete_reason'] ?? 'No reason provided');
                                $status = safe_html($row['status'] ?? '');
                                ?>
                                <tr data-id="<?= $row['id'] ?>">
                                    <td class="fw-bold"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-danger"><?= $clientCode ?></div>
                                        <small class="text-muted">ID: <?= $row['id'] ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= $clientName ?></div>
                                        <small class="text-muted">Status: 
                                            <?php if ($status === 'Active'): ?>
                                                <span class="text-success">Active</span>
                                            <?php elseif ($status === 'Inactive'): ?>
                                                <span class="text-warning">Inactive</span>
                                            <?php else: ?>
                                                <span class="text-secondary"><?= $status ?></span>
                                            <?php endif; ?>
                                        </small>
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
                                        <div class="text-danger"><?= $deleteDate ?></div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= $deleteReason ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn btn-view" 
                                                    onclick="viewClient(<?= $row['id'] ?>)"
                                                    title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="action-btn btn-restore-small" 
                                                    onclick="restoreClientPrompt(<?= $row['id'] ?>, '<?= addslashes($clientName) ?>')"
                                                    title="Restore Client">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                            <button class="action-btn btn-permanent-delete-small" 
                                                    onclick="permanentDeletePrompt(<?= $row['id'] ?>, '<?= addslashes($clientName) ?>')"
                                                    title="Permanent Delete">
                                                <i class="bi bi-trash-fill"></i>
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
                                            <i class="bi bi-trash"></i>
                                        </div>
                                        <h4 class="fw-bold mb-3">No Deleted Clients Found</h4>
                                        <p class="text-muted mb-4">Recycle bin is empty. No deleted client records available.</p>
                                        <a href="../master/master-data-client.php" class="btn-gradient">
                                            <i class="bi bi-arrow-left me-2"></i>Back to Active Clients
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
                Showing deleted clients only • 
                Last updated: <?= date('F j, Y, g:i a') ?> • 
                System: GIBSYSNET v2.0
            </p>
        </div>
    </div>

    <!-- ============================================
    MODALS UNTUK DELETED CLIENTS
    ============================================ -->

    <!-- View Client Modal -->
    <div class="modal fade" id="viewClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header modal-header-gradient">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-badge me-2"></i>Deleted Client Details
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
                    <button type="button" class="btn-restore" id="restoreFromViewBtn">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Restore Client
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div class="modal fade" id="restoreClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-success">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Restore Client
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="restoreClientForm">
                    <input type="hidden" id="restoreClientId">
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="p-4 rounded-circle bg-success bg-opacity-10 d-inline-block mb-3">
                                <i class="bi bi-arrow-counterclockwise text-success" style="font-size: 2.5rem;"></i>
                            </div>
                            <h5 class="fw-bold mb-3" id="restoreClientName">Client Name</h5>
                            <p class="text-muted">Are you sure you want to restore this client? The client will be moved back to active clients list.</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Restoration Reason (Optional)</label>
                            <textarea class="form-control form-control-modern" 
                                      id="restoreReason" rows="3" 
                                      placeholder="Optional: Reason for restoration..."></textarea>
                        </div>
                        
                        <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25">
                            <i class="bi bi-info-circle me-2"></i>
                            <small>Note: Restored clients will be moved to active clients list and can be managed normally.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Restore Client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Permanent Delete Confirmation Modal -->
    <div class="modal fade" id="permanentDeleteClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-trash3-fill me-2"></i>Permanent Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="permanentDeleteClientForm">
                    <input type="hidden" id="permanentDeleteClientId">
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="p-4 rounded-circle bg-danger bg-opacity-10 d-inline-block mb-3">
                                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 2.5rem;"></i>
                            </div>
                            <h5 class="fw-bold mb-3" id="permanentDeleteClientName">Client Name</h5>
                            <p class="text-danger fw-bold">⚠️ WARNING: This action cannot be undone!</p>
                            <p class="text-muted">This client will be permanently deleted from the database. All associated data will be lost.</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason for permanent deletion *</label>
                            <textarea class="form-control form-control-modern" 
                                      id="permanentDeleteReason" rows="3" 
                                      placeholder="Required: Reason for permanent deletion..." required></textarea>
                        </div>
                        
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <small><strong>Critical:</strong> This action is irreversible. Please ensure you have backups if needed.</small>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmPermanentDelete" required>
                            <label class="form-check-label text-danger fw-bold" for="confirmPermanentDelete">
                                I understand this action cannot be undone and I take full responsibility
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash3-fill me-2"></i>Permanently Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================
    JAVASCRIPT UNTUK DELETED CLIENTS - DIPERBAIKI
    ============================================ -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#clientsTable').DataTable({
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
            language: {
                search: "",
                searchPlaceholder: "Search deleted clients...",
                lengthMenu: "_MENU_ per page",
                info: "Showing _START_ to _END_ of _TOTAL_ deleted clients",
                infoEmpty: "No deleted clients found",
                infoFiltered: "(filtered from _MAX_ total deleted clients)",
                zeroRecords: "No matching deleted clients found",
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>'
                }
            },
            order: [[5, 'desc']], // Sort by deleted date descending
            responsive: true,
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
            columnDefs: [
                { orderable: false, targets: [7] },
                { className: "align-middle", targets: "_all" }
            ]
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
            const dateFilter = $('#dateFilter').val();
            
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const type = data[3];
                const category = data[4];
                const deletedDate = data[5];
                
                const typeMatch = !typeFilter || type.toLowerCase().includes(typeFilter.toLowerCase());
                const categoryMatch = !categoryFilter || category.toLowerCase().includes(categoryFilter.toLowerCase());
                
                // Date filter logic
                let dateMatch = true;
                if (dateFilter) {
                    const now = new Date();
                    const deleted = new Date(deletedDate);
                    
                    if (dateFilter === 'today') {
                        dateMatch = deleted.toDateString() === now.toDateString();
                    } else if (dateFilter === 'week') {
                        const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                        dateMatch = deleted >= weekAgo;
                    } else if (dateFilter === 'month') {
                        const monthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
                        dateMatch = deleted >= monthAgo;
                    }
                }
                
                return typeMatch && categoryMatch && dateMatch;
            });
            
            table.draw();
            $.fn.dataTable.ext.search.pop();
        });

        $('#resetFilterBtn').click(function() {
            $('#typeFilter').val('');
            $('#categoryFilter').val('');
            $('#dateFilter').val('');
            $('#searchInput').val('');
            table.search('').columns().search('').draw();
        });

        // ============================================
        // VIEW DELETED CLIENT FUNCTION
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
                        
                        // Format dates
                        const joinDate = new Date(client.join_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        
                        const deletedDate = client.deleted_at ? 
                            new Date(client.deleted_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            }) : 'N/A';
                        
                        // Determine status
                        let statusText = client.status || 'Unknown';
                        let statusClass = 'badge bg-secondary';
                        if (statusText === 'Active') statusClass = 'badge bg-success';
                        else if (statusText === 'Inactive') statusClass = 'badge bg-warning';
                        
                        // Build HTML for deleted client
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
                                                    <span class="${statusClass}">${statusText}</span>
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
                                                <i class="bi bi-trash me-2"></i>Deletion Information
                                            </h6>
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted d-block">Deleted Date</small>
                                                    <strong class="text-danger">${deletedDate}</strong>
                                                </div>
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted d-block">Delete Reason</small>
                                                    <strong>${client.delete_reason || 'No reason provided'}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Deleted Status</small>
                                                    <span class="badge bg-danger">Deleted</span>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Can Be Restored</small>
                                                    <span class="badge bg-success">Yes</span>
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
                        
                        // Set restore button to work from view
                        $('#restoreFromViewBtn').off('click').click(function() {
                            $('#viewClientModal').modal('hide');
                            setTimeout(() => {
                                restoreClientPrompt(id, client.client_name);
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
        // RESTORE CLIENT FUNCTION - DIPERBAIKI
        // ============================================
        window.restoreClientPrompt = function(id, name) {
            $('#restoreClientId').val(id);
            $('#restoreClientName').text(name);
            $('#restoreReason').val('');
            $('#restoreClientModal').modal('show');
        };

        // PERBAIKAN: Handle restore form submission
        $('#restoreClientForm').submit(function(e) {
            e.preventDefault();
            
            const id = $('#restoreClientId').val();
            const reason = $('#restoreReason').val() || 'Restored by admin';
            
            // Tampilkan konfirmasi
            Swal.fire({
                title: 'Restore Client?',
                html: `Restore <strong>${$('#restoreClientName').text()}</strong> to active clients?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, restore it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan loading
                    const restoreSwal = Swal.fire({
                        title: 'Restoring...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Kirim AJAX request
                    $.ajax({
                        url: '<?= $_SERVER['PHP_SELF'] ?>',
                        method: 'POST',
                        data: {
                            ajax_restore: 'true',
                            client_id: id,
                            restore_reason: reason
                        },
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            
                            if (response.success) {
                                // Tampilkan success message
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Restored Successfully!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Refresh halaman setelah 2 detik
                                    setTimeout(() => {
                                        location.reload();
                                    }, 500);
                                });
                            } else {
                                // Tampilkan error message
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Restore Failed',
                                    text: response.message || 'Unknown error occurred',
                                    confirmButtonText: 'OK'
                                });
                            }
                            
                            // Tutup modal
                            $('#restoreClientModal').modal('hide');
                        },
                        error: function(xhr, status, error) {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'Restore Failed',
                                text: 'AJAX Error: ' + error,
                                confirmButtonText: 'OK'
                            });
                            $('#restoreClientModal').modal('hide');
                        }
                    });
                }
            });
        });

        // ============================================
        // PERMANENT DELETE CLIENT FUNCTION
        // ============================================
        window.permanentDeletePrompt = function(id, name) {
            $('#permanentDeleteClientId').val(id);
            $('#permanentDeleteClientName').text(name);
            $('#permanentDeleteReason').val('');
            $('#confirmPermanentDelete').prop('checked', false);
            $('#permanentDeleteClientModal').modal('show');
        };

        // Handle permanent delete form submission
        $('#permanentDeleteClientForm').submit(function(e) {
            e.preventDefault();
            
            const id = $('#permanentDeleteClientId').val();
            const reason = $('#permanentDeleteReason').val();
            
            if (!reason.trim()) {
                Swal.fire('Warning', 'Please provide a reason for permanent deletion', 'warning');
                return;
            }
            
            if (!$('#confirmPermanentDelete').is(':checked')) {
                Swal.fire('Warning', 'Please confirm that you understand this action is irreversible', 'warning');
                return;
            }
            
            Swal.fire({
                title: '⚠️ FINAL WARNING!',
                html: `<strong>Permanently delete ${$('#permanentDeleteClientName').text()}?</strong><br><br>
                      <small class="text-danger">This action cannot be undone!</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete permanently!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting Permanently...',
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
                            ajax_permanent_delete: 'true',
                            client_id: id,
                            permanent_delete_reason: reason
                        },
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Permanently Deleted!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                            $('#permanentDeleteClientModal').modal('hide');
                        },
                        error: function() {
                            Swal.close();
                            Swal.fire('Error', 'Failed to permanently delete client', 'error');
                            $('#permanentDeleteClientModal').modal('hide');
                        }
                    });
                }
            });
        });

        // Export Client Report Function
        function exportClientReport() {
            Swal.fire({
                title: 'Export Deleted Clients Report',
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
                    $('#exportExcel').click();
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Export to PDF
                    $('#exportPDF').click();
                } else if (result.isDenied) {
                    // Export to CSV
                    $('#exportCSV').click();
                }
            });
        }

        // Make function available globally
        window.exportClientReport = exportClientReport;

        // Export buttons
        $('#exportExcel').click(function() {
            Swal.fire({
                title: 'Exporting to Excel',
                text: 'Please wait while we prepare the file...',
                icon: 'info',
                showConfirmButton: false,
                timer: 2000
            });
        });

        $('#exportPDF').click(function() {
            Swal.fire({
                title: 'Exporting to PDF',
                text: 'Generating PDF document...',
                icon: 'info',
                showConfirmButton: false,
                timer: 2000
            });
        });

        $('#exportCSV').click(function() {
            // Simple CSV export
            let csv = [];
            const headers = ['Client Code', 'Client Name', 'Type', 'Category', 'Deleted Date', 'Delete Reason'];
            csv.push(headers.join(','));
            
            $('#clientsTable tbody tr').each(function() {
                const cols = $(this).find('td');
                if (cols.length > 1) {
                    const row = [
                        $(cols[1]).find('.fw-bold').text().trim(),
                        $(cols[2]).find('.fw-bold').text().trim(),
                        $(cols[3]).text().trim(),
                        $(cols[4]).text().trim(),
                        $(cols[5]).text().trim(),
                        $(cols[6]).text().trim()
                    ];
                    csv.push(row.join(','));
                }
            });
            
            const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `deleted_clients_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            
            Swal.fire({
                title: 'Success!',
                text: 'CSV file downloaded',
                icon: 'success',
                timer: 1500
            });
        });

        // Auto-hide alerts setelah 5 detik
        setTimeout(function() {
            $('.alert').fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);

        // Close alert ketika di-klik
        $('.alert .btn-close').click(function() {
            $(this).closest('.alert').fadeOut(300, function() {
                $(this).remove();
            });
        });

        // Initialize tooltips
        $('[title]').tooltip({
            trigger: 'hover',
            placement: 'top'
        });
    });
    </script>
</body>
</html>