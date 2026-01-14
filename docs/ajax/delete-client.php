<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ============================================
// DATABASE CONNECTION
// ============================================

// Coba beberapa path yang mungkin
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

// ============================================
// EXPORT TO PDF FUNCTIONALITY - FIXED VERSION
// ============================================
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Check if TCPDF is available
    $tcpdfPath = $_SERVER['DOCUMENT_ROOT'] . '/gibsysnet/vendor/autoload.php';
    
    if (file_exists($tcpdfPath)) {
        require_once $tcpdfPath;
    } else {
        // Try alternative paths
        $alternativePaths = [
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../../../vendor/autoload.php',
            'C:/xampp/htdocs/gibsysnet/vendor/autoload.php',
            'C:/xampp/htdocs/vendor/autoload.php',
            'vendor/autoload.php'
        ];
        
        $found = false;
        foreach ($alternativePaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            die("TCPDF library not found. Please install it with: composer require tecnickcom/tcpdf");
        }
    }
    
    try {
        // Build query based on filters
        $sql = "SELECT 
                    client_code,
                    client_name,
                    client_type,
                    category,
                    phone,
                    email,
                    contact_person,
                    address,
                    city,
                    DATE_FORMAT(deleted_at, '%d %M %Y %H:%i') as deleted_date,
                    delete_reason
                FROM clients 
                WHERE is_deleted = 1 ";
        
        $params = [];
        
        // Apply search filter if exists
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $sql .= " AND (client_code LIKE ? OR client_name LIKE ? OR email LIKE ? OR phone LIKE ?) ";
            $params = array_fill(0, 4, $search);
        }
        
        $sql .= " ORDER BY deleted_at DESC";
        
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $pdf_clients = $stmt->fetchAll();
        
        // Create new PDF document
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('GIBSYSNET');
        $pdf->SetAuthor('GIBSYSNET Admin');
        $pdf->SetTitle('Deleted Clients Report');
        $pdf->SetSubject('Deleted Clients Data');
        $pdf->SetKeywords('GIBSYSNET, Clients, Deleted, Report');
        
        // Set default header data
        $header_string = 'Generated on: ' . date('F j, Y, g:i a');
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $header_string .= ' | Search: ' . $_GET['search'];
        }
        
        $pdf->SetHeaderData('', 0, 'GIBSYSNET - Deleted Clients Report', $header_string);
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $pdf->SetMargins(15, 25, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'DELETED CLIENTS REPORT', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        
        $subtitle = 'Total Records: ' . count($pdf_clients);
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $subtitle .= ' (Filtered by: "' . $_GET['search'] . '")';
        }
        
        $pdf->Cell(0, 0, $subtitle, 0, 1, 'C');
        $pdf->Ln(10);
        
        // Create table header
        $html = '<style>
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
                font-family: helvetica;
                font-size: 9px;
            }
            th {
                background-color: #FF416C;
                color: white;
                font-weight: bold;
                padding: 6px;
                border: 1px solid #ddd;
                text-align: center;
            }
            td {
                padding: 5px;
                border: 1px solid #ddd;
                text-align: left;
                vertical-align: top;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
        </style>';
        
        $html .= '<table border="1" cellpadding="3" cellspacing="0">';
        $html .= '<tr>';
        $html .= '<th width="4%">No</th>';
        $html .= '<th width="9%">Client Code</th>';
        $html .= '<th width="15%">Client Name</th>';
        $html .= '<th width="7%">Type</th>';
        $html .= '<th width="8%">Category</th>';
        $html .= '<th width="10%">Phone</th>';
        $html .= '<th width="13%">Email</th>';
        $html .= '<th width="12%">Contact Person</th>';
        $html .= '<th width="11%">City</th>';
        $html .= '<th width="11%">Deleted Date</th>';
        $html .= '<th width="16%">Delete Reason</th>';
        $html .= '</tr>';
        
        // Add table rows
        if (count($pdf_clients) > 0) {
            $no = 1;
            foreach ($pdf_clients as $client) {
                $html .= '<tr>';
                $html .= '<td align="center">' . $no . '</td>';
                $html .= '<td>' . htmlspecialchars($client['client_code']) . '</td>';
                $html .= '<td>' . htmlspecialchars($client['client_name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($client['client_type']) . '</td>';
                $html .= '<td>' . htmlspecialchars($client['category']) . '</td>';
                $html .= '<td>' . htmlspecialchars($client['phone']) . '</td>';
                $html .= '<td>' . htmlspecialchars($client['email']) . '</td>';
                $html .= '<td>' . htmlspecialchars($client['contact_person']) . '</td>';
                $html .= '<td>' . htmlspecialchars($client['city']) . '</td>';
                $html .= '<td>' . htmlspecialchars($client['deleted_date']) . '</td>';
                $html .= '<td>' . htmlspecialchars($client['delete_reason'] ?: 'No reason provided') . '</td>';
                $html .= '</tr>';
                $no++;
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="11" align="center" style="padding: 20px;">No deleted clients found</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // Add summary
        $html .= '<div style="margin-top: 20px; font-size: 8px; color: #666; line-height: 1.5;">';
        $html .= '<strong>Report Summary:</strong><br>';
        $html .= '• Total Deleted Clients: ' . count($pdf_clients) . '<br>';
        $html .= '• Report Generated: ' . date('F j, Y, g:i a') . '<br>';
        $html .= '• Generated By: GIBSYSNET Admin<br>';
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $html .= '• Search Filter: "' . htmlspecialchars($_GET['search']) . '"<br>';
        }
        
        $html .= '• System: GIBSYSNET v2.0<br>';
        $html .= '• Note: This report contains all clients marked as deleted in the system.';
        $html .= '</div>';
        
        // Output HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Set filename
        $filename = 'deleted_clients_' . date('Y-m-d_H-i-s');
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_slug = preg_replace('/[^a-zA-Z0-9]/', '_', substr($_GET['search'], 0, 20));
            $filename .= '_search_' . $search_slug;
        }
        $filename .= '.pdf';
        
        // Close and output PDF document
        $pdf->Output($filename, 'D');
        exit();
        
    } catch (Exception $e) {
        // Log error
        error_log("PDF Generation Error in deleted-clients.php: " . $e->getMessage());
        
        // Show user-friendly message
        die("Error generating PDF file: " . htmlspecialchars($e->getMessage()));
    }
}

// ============================================
// HANDLE FORM SUBMISSIONS
// ============================================

// Handle Restore Client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_client'])) {
    try {
        $id = $_POST['client_id'] ?? 0;
        
        $stmt = $conn->prepare("
            UPDATE clients SET 
                is_deleted = 0,
                deleted_at = NULL,
                delete_reason = NULL,
                status = 'Active',
                updated_at = NOW()
            WHERE id = ? AND is_deleted = 1
        ");
        
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Client has been restored successfully.";
            header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error_message'] = "Client not found or not deleted.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }
}

// Handle Permanent Delete (for super admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permanent_delete'])) {
    try {
        $id = $_POST['client_id'] ?? 0;
        $reason = $_POST['delete_reason'] ?? 'No reason provided';
        
        // First, get client info for logging
        $getStmt = $conn->prepare("SELECT client_code, client_name FROM clients WHERE id = ?");
        $getStmt->execute([$id]);
        $client = $getStmt->fetch();
        
        if ($client) {
            // Insert to audit log before deletion
            $auditStmt = $conn->prepare("
                INSERT INTO client_audit_log 
                (client_id, client_code, client_name, action, reason, performed_by, performed_at)
                VALUES (?, ?, ?, 'PERMANENT_DELETE', ?, 'Admin', NOW())
            ");
            $auditStmt->execute([$id, $client['client_code'], $client['client_name'], $reason]);
            
            // Permanent delete
            $deleteStmt = $conn->prepare("DELETE FROM clients WHERE id = ? AND is_deleted = 1");
            $deleteStmt->execute([$id]);
            
            if ($deleteStmt->rowCount() > 0) {
                $_SESSION['success_message'] = "Client has been permanently deleted from database.";
                header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['error_message'] = "Client not found or not deleted.";
            }
        } else {
            $_SESSION['error_message'] = "Client not found.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }
}

// Handle Bulk Restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_restore'])) {
    try {
        $selected_ids = $_POST['selected_clients'] ?? [];
        
        if (empty($selected_ids)) {
            $_SESSION['error_message'] = "No clients selected for restoration.";
        } else {
            $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
            
            $stmt = $conn->prepare("
                UPDATE clients SET 
                    is_deleted = 0,
                    deleted_at = NULL,
                    delete_reason = NULL,
                    status = 'Active',
                    updated_at = NOW()
                WHERE id IN ($placeholders) AND is_deleted = 1
            ");
            
            $stmt->execute($selected_ids);
            
            $restored_count = $stmt->rowCount();
            $_SESSION['success_message'] = "Successfully restored $restored_count client(s).";
            header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }
}

// Handle Bulk Permanent Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_permanent_delete'])) {
    try {
        $selected_ids = $_POST['selected_clients'] ?? [];
        $reason = $_POST['bulk_delete_reason'] ?? 'Bulk permanent deletion';
        
        if (empty($selected_ids)) {
            $_SESSION['error_message'] = "No clients selected for permanent deletion.";
        } else {
            // Get client info for audit log
            $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
            $getStmt = $conn->prepare("SELECT id, client_code, client_name FROM clients WHERE id IN ($placeholders)");
            $getStmt->execute($selected_ids);
            $clients = $getStmt->fetchAll();
            
            // Insert to audit log for each client
            $auditStmt = $conn->prepare("
                INSERT INTO client_audit_log 
                (client_id, client_code, client_name, action, reason, performed_by, performed_at)
                VALUES (?, ?, ?, 'PERMANENT_DELETE', ?, 'Admin', NOW())
            ");
            
            foreach ($clients as $client) {
                $auditStmt->execute([$client['id'], $client['client_code'], $client['client_name'], $reason]);
            }
            
            // Permanent delete
            $deleteStmt = $conn->prepare("DELETE FROM clients WHERE id IN ($placeholders) AND is_deleted = 1");
            $deleteStmt->execute($selected_ids);
            
            $deleted_count = $deleteStmt->rowCount();
            $_SESSION['success_message'] = "Successfully permanently deleted $deleted_count client(s).";
            header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }
}

// ============================================
// FETCH DELETED DATA
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
$deleted_clients = $stmt->fetchAll();
$total_deleted = count($deleted_clients);

// Fetch active clients count for comparison
$activeStmt = $conn->query("SELECT COUNT(*) as active_count FROM clients WHERE is_deleted = 0");
$activeCount = $activeStmt->fetch()['active_count'] ?? 0;

// Check if user is super admin (in real app, check from session)
$is_super_admin = true;

// Get search parameter if exists
$search = isset($_GET['search']) ? $_GET['search'] : '';
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleted Clients - GIBSYSNET</title>
    
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
            
            /* Glassmorphism Variables */
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --glass-blur: blur(10px);
            
            /* Gradient Variables */
            --gradient-danger: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
            --gradient-success: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            --gradient-warning: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            --gradient-primary: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            --gradient-dark: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            
            /* Animation Variables */
            --transition-fast: 0.3s ease;
            --transition-medium: 0.5s ease;
            --transition-slow: 0.8s ease;
        }
        
        /* Dark Mode Variables */
        [data-theme="dark"] {
            --primary-color: #ecf0f1;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f1c40f;
            --info-color: #1abc9c;
            --light-color: #2c3e50;
            --dark-color: #ecf0f1;
            
            --glass-bg: rgba(44, 62, 80, 0.2);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            
            background-color: #1a252f;
            color: #ecf0f1;
        }
        
        * {
            transition: background-color var(--transition-medium), 
                       color var(--transition-medium),
                       border-color var(--transition-medium),
                       box-shadow var(--transition-medium);
        }
        
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            animation: fadeIn 1s ease;
        }
        
        [data-theme="dark"] body {
            background: linear-gradient(135deg, #1a252f 0%, #2c3e50 100%);
        }
        
        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            animation: slideUp 0.8s ease;
        }
        
        /* Glassmorphism Design */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: var(--glass-shadow);
            padding: 25px;
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
            position: relative;
            overflow: hidden;
        }
        
        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-danger);
            border-radius: 16px 16px 0 0;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .navbar-custom {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            padding: 20px 30px;
            margin-bottom: 30px;
            border-radius: 16px;
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: 800;
            margin-bottom: 5px;
            background: var(--gradient-danger);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 3s ease infinite;
        }
        
        .page-subtitle {
            color: var(--primary-color);
            opacity: 0.8;
            font-size: 14px;
        }
        
        /* Stats Cards */
        .stats-card {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease;
        }
        
        .stats-card:nth-child(1) { animation-delay: 0.1s; }
        .stats-card:nth-child(2) { animation-delay: 0.2s; }
        .stats-card:nth-child(3) { animation-delay: 0.3s; }
        .stats-card:nth-child(4) { animation-delay: 0.4s; }
        
        .stats-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
        }
        
        .stats-icon.deleted { 
            background: rgba(255, 65, 108, 0.2); 
            color: #FF416C; 
            border: 1px solid rgba(255, 65, 108, 0.3);
        }
        
        .stats-icon.active { 
            background: rgba(39, 174, 96, 0.2); 
            color: #27ae60; 
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .stats-icon.warning { 
            background: rgba(243, 156, 18, 0.2); 
            color: var(--warning-color); 
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .stats-icon.history { 
            background: rgba(52, 152, 219, 0.2); 
            color: var(--secondary-color); 
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .stats-number {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-label {
            color: var(--primary-color);
            opacity: 0.8;
            font-size: 14px;
            margin-top: 8px;
        }
        
        /* Table Styling */
        .data-table-wrapper {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
            animation: fadeIn 1s ease;
        }
        
        .table-custom {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background: transparent;
        }
        
        .table-custom thead {
            background: linear-gradient(135deg, rgba(255, 65, 108, 0.2), rgba(255, 75, 43, 0.2));
            backdrop-filter: var(--glass-blur);
        }
        
        .table-custom th {
            border: none;
            padding: 18px;
            font-weight: 700;
            color: var(--primary-color);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
            border-bottom: 2px solid rgba(255, 65, 108, 0.3);
        }
        
        .table-custom td {
            border: none;
            padding: 18px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--primary-color);
        }
        
        .table-custom tbody tr {
            transition: all var(--transition-fast);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: var(--glass-blur);
            animation: fadeInRow 0.5s ease;
        }
        
        @keyframes fadeInRow {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .table-custom tbody tr:hover {
            background: rgba(255, 65, 108, 0.1);
            transform: translateX(5px);
            border-left: 3px solid #FF416C;
        }
        
        /* Badges */
        .deleted-badge {
            background: var(--gradient-danger);
            color: white;
            font-size: 10px;
            padding: 4px 10px;
            border-radius: 12px;
            margin-left: 8px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(255, 65, 108, 0.3);
        }
        
        .type-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
        }
        
        .type-individual { 
            background: rgba(52, 152, 219, 0.2); 
            color: #2980b9; 
            border-color: rgba(52, 152, 219, 0.3);
        }
        
        .type-corporate { 
            background: rgba(155, 89, 182, 0.2); 
            color: #8e44ad; 
            border-color: rgba(155, 89, 182, 0.3);
        }
        
        .type-government { 
            background: rgba(241, 196, 15, 0.2); 
            color: #f39c12; 
            border-color: rgba(241, 196, 15, 0.3);
        }
        
        .type-other { 
            background: rgba(46, 204, 113, 0.2); 
            color: #27ae60; 
            border-color: rgba(46, 204, 113, 0.3);
        }
        
        /* Buttons */
        .btn-glass {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            color: var(--primary-color);
            padding: 10px 25px;
            border-radius: 12px;
            font-weight: 600;
            transition: all var(--transition-fast);
            box-shadow: var(--glass-shadow);
        }
        
        .btn-glass:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn-danger-gradient {
            background: var(--gradient-danger);
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 700;
            transition: all var(--transition-fast);
            box-shadow: 0 4px 15px rgba(255, 65, 108, 0.3);
        }
        
        .btn-danger-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 65, 108, 0.4);
            background: linear-gradient(135deg, #FF4B2B 0%, #FF416C 100%);
        }
        
        .btn-success-gradient {
            background: var(--gradient-success);
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 700;
            transition: all var(--transition-fast);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-success-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }
        
        .btn-primary-gradient {
            background: var(--gradient-primary);
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 700;
            transition: all var(--transition-fast);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        
        /* Action Buttons */
        .action-buttons .btn {
            padding: 8px 16px;
            margin-right: 8px;
            border-radius: 10px;
            font-size: 13px;
            transition: all var(--transition-fast);
            border: 1px solid var(--glass-border);
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
        }
        
        .btn-restore { 
            color: #27ae60; 
            border-color: rgba(46, 204, 113, 0.3);
        }
        
        .btn-restore:hover { 
            background: rgba(46, 204, 113, 0.2); 
            transform: translateY(-2px);
        }
        
        .btn-permanent-delete { 
            color: #FF416C; 
            border-color: rgba(255, 65, 108, 0.3);
        }
        
        .btn-permanent-delete:hover { 
            background: rgba(255, 65, 108, 0.2); 
            transform: translateY(-2px);
        }
        
        .btn-view { 
            color: var(--secondary-color); 
            border-color: rgba(52, 152, 219, 0.3);
        }
        
        .btn-view:hover { 
            background: rgba(52, 152, 219, 0.2); 
            transform: translateY(-2px);
        }
        
        /* Search and Filter */
        .filter-section {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--glass-shadow);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 50px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            height: 50px;
            transition: all var(--transition-fast);
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            color: var(--primary-color);
        }
        
        .search-box input:focus {
            border-color: #FF416C;
            box-shadow: 0 0 0 0.3rem rgba(255, 65, 108, 0.25);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .search-box i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            opacity: 0.7;
            z-index: 10;
        }
        
        /* Dark Mode Toggle */
        .theme-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        
        .theme-toggle-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            transition: all var(--transition-fast);
            box-shadow: var(--glass-shadow);
        }
        
        .theme-toggle-btn:hover {
            transform: rotate(30deg) scale(1.1);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 80px;
            background: var(--gradient-danger);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Bulk Actions */
        .bulk-actions {
            background: rgba(255, 65, 108, 0.1);
            backdrop-filter: var(--glass-blur);
            border: 1px solid rgba(255, 65, 108, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .bulk-actions.show {
            display: block;
        }
        
        /* Modal Styles */
        .modal-content {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            color: var(--primary-color);
        }
        
        .modal-header-custom {
            background: linear-gradient(135deg, rgba(255, 65, 108, 0.8), rgba(255, 75, 43, 0.8));
            backdrop-filter: var(--glass-blur);
            color: white;
            border-radius: 20px 20px 0 0;
            border-bottom: 1px solid var(--glass-border);
        }
        
        /* Toast Notification */
        .toast-container {
            position: fixed;
            top: 30px;
            right: 30px;
            z-index: 9999;
        }
        
        .toast-custom {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            box-shadow: var(--glass-shadow);
            color: var(--primary-color);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container-fluid {
                padding: 0 10px;
            }
            
            .navbar-custom {
                padding: 15px;
            }
            
            .page-title {
                font-size: 24px;
            }
            
            .stats-card {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .data-table-wrapper {
                padding: 15px;
                overflow-x: auto;
            }
            
            .filter-section {
                padding: 15px;
            }
            
            .theme-toggle {
                bottom: 20px;
                right: 20px;
            }
            
            .theme-toggle-btn {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .table-custom th,
            .table-custom td {
                padding: 10px;
                font-size: 12px;
            }
            
            .btn-primary-gradient,
            .btn-success-gradient,
            .btn-danger-gradient {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
        
        /* Print Styles */
        @media print {
            .theme-toggle,
            .navbar-custom .btn,
            .action-buttons,
            .bulk-actions,
            .filter-section {
                display: none !important;
            }
            
            body {
                background: white !important;
                color: black !important;
            }
            
            .glass-card,
            .stats-card,
            .data-table-wrapper {
                background: white !important;
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Theme Toggle Button -->
    <div class="theme-toggle">
        <div class="theme-toggle-btn" id="themeToggle">
            <i class="fas fa-moon"></i>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="toast toast-custom align-items-center border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2 text-success"></i><?= safe_html($_SESSION['success_message']) ?>
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div class="toast toast-custom align-items-center border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-exclamation-circle me-2 text-danger"></i><?= safe_html($_SESSION['error_message']) ?>
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </div>

    <div class="container-fluid">
        <!-- Top Navigation -->
        <nav class="navbar-custom glass-card">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div>
                    <h4 class="page-title"><i class="fas fa-trash-restore me-2"></i>Deleted Clients</h4>
                    <p class="page-subtitle">Manage soft deleted clients - Restore or permanently delete</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <?php
                    $export_url = "?export=pdf";
                    if (!empty($search)) {
                        $export_url .= "&search=" . urlencode($search);
                    }
                    ?>
                    <a href="<?= $export_url ?>" class="btn btn-danger-gradient">
                        <i class="fas fa-file-pdf me-2"></i>Export to PDF
                    </a>
                    <a href="master-data-client.php" class="btn btn-primary-gradient">
                        <i class="fas fa-arrow-left me-2"></i>Back to Active Clients
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-glass dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>Super Admin
                            <span class="badge bg-danger ms-2">Super Admin</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
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
        <div class="row mb-4 g-4">
            <div class="col-xl-3 col-lg-6">
                <div class="stats-card">
                    <div class="stats-icon deleted">
                        <i class="fas fa-trash"></i>
                    </div>
                    <div class="stats-number"><?= number_format($total_deleted) ?></div>
                    <div class="stats-label">Soft Deleted Clients</div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="stats-card">
                    <div class="stats-icon active">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number"><?= number_format($activeCount) ?></div>
                    <div class="stats-label">Active Clients</div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="stats-card">
                    <div class="stats-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stats-number"><?= number_format($total_deleted + $activeCount) ?></div>
                    <div class="stats-label">Total Records</div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="stats-card">
                    <div class="stats-icon history">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stats-number">
                        <?php if ($total_deleted > 0): ?>
                            <?php 
                            $oldest = $deleted_clients[count($deleted_clients)-1]['deleted_at'] ?? 'N/A';
                            echo $oldest !== 'N/A' ? date('M d, Y', strtotime($oldest)) : 'N/A';
                            ?>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </div>
                    <div class="stats-label">Oldest Deletion</div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Section -->
        <div class="bulk-actions glass-card" id="bulkActionsSection">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger text-white px-3 py-2 rounded-pill">
                        <i class="fas fa-layer-group me-2"></i>
                        <span id="selectedCount">0</span> client(s) selected
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="" id="bulkRestoreForm" class="d-inline">
                        <input type="hidden" name="selected_clients[]" id="bulkRestoreIds">
                        <button type="button" class="btn btn-success-gradient btn-sm" onclick="confirmBulkRestore()">
                            <i class="fas fa-trash-restore me-1"></i>Restore Selected
                        </button>
                    </form>
                    <form method="POST" action="" id="bulkDeleteForm" class="d-inline">
                        <input type="hidden" name="selected_clients[]" id="bulkDeleteIds">
                        <input type="hidden" name="bulk_delete_reason" id="bulkDeleteReason" value="Bulk permanent deletion">
                        <button type="button" class="btn btn-danger-gradient btn-sm" onclick="confirmBulkDelete()">
                            <i class="fas fa-trash-alt me-1"></i>Permanently Delete
                        </button>
                    </form>
                    <button type="button" class="btn btn-glass btn-sm" onclick="clearSelections()">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row g-3 align-items-end">
                <div class="col-xl-4 col-lg-6">
                    <label class="form-label fw-semibold">Search Deleted Client</label>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="Search by name, code, or deletion reason..."
                               value="<?= safe_html($search) ?>">
                    </div>
                </div>
                <div class="col-xl-2 col-lg-6">
                    <label class="form-label fw-semibold">Client Type</label>
                    <select class="form-select select2 glass-card" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="individual">Individual</option>
                        <option value="corporate">Corporate</option>
                        <option value="government">Government</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-6">
                    <label class="form-label fw-semibold">Category</label>
                    <select class="form-select select2 glass-card" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="client">Client</option>
                        <option value="agent">Agent</option>
                        <option value="marketing">Marketing</option>
                        <option value="partner">Partner</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-6">
                    <label class="form-label fw-semibold">Deleted Period</label>
                    <select class="form-select select2 glass-card" id="periodFilter">
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">Last 7 Days</option>
                        <option value="month">Last 30 Days</option>
                        <option value="quarter">Last 90 Days</option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-6">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary-gradient flex-grow-1" id="applyFilterBtn">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <button class="btn btn-glass" id="resetFilterBtn">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="data-table-wrapper">
            <table id="deletedClientsTable" class="table-custom">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" class="form-check-input select-all-checkbox" id="selectAll">
                        </th>
                        <th>#</th>
                        <th>Client Code</th>
                        <th>Client Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Deleted Date</th>
                        <th>Delete Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($deleted_clients) > 0): ?>
                        <?php foreach ($deleted_clients as $index => $row): ?>
                            <?php 
                            $clientCode = safe_html($row['client_code'] ?? '');
                            $clientName = safe_html($row['client_name'] ?? '');
                            $clientType = safe_html($row['client_type'] ?? '');
                            $category = safe_html($row['category'] ?? '');
                            $deletedAt = safe_html($row['deleted_at'] ?? '');
                            $deleteReason = safe_html($row['delete_reason'] ?? '');
                            $contactPerson = safe_html($row['contact_person'] ?? '');
                            $email = safe_html($row['email'] ?? '');
                            $phone = safe_html($row['phone'] ?? '');
                            ?>
                            <tr data-id="<?= $row['id'] ?>" style="animation-delay: <?= $index * 0.05 ?>s">
                                <td>
                                    <input type="checkbox" class="form-check-input client-checkbox" name="selected_clients[]" value="<?= $row['id'] ?>">
                                </td>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <span class="fw-bold text-primary"><?= $clientCode ?></span>
                                    <span class="deleted-badge">Deleted</span>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= $clientName ?></div>
                                    <small class="text-muted"><?= $contactPerson ?: 'No contact person' ?></small>
                                    <div class="text-muted small">
                                        <i class="fas fa-envelope me-1"></i><?= $email ?: 'N/A' ?><br>
                                        <i class="fas fa-phone me-1"></i><?= $phone ?: 'N/A' ?>
                                    </div>
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
                                        <span class="type-badge">
                                            <?= ucfirst($category) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($deletedAt): ?>
                                        <div class="fw-semibold"><?= date('M d, Y', strtotime($deletedAt)) ?></div>
                                        <small class="text-muted"><?= date('h:i A', strtotime($deletedAt)) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($deleteReason): ?>
                                        <div class="p-3 rounded glass-card">
                                            <small><i class="fas fa-comment me-2"></i><?= $deleteReason ?></small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No reason provided</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-view" title="View Details" onclick="viewClientDetails(<?= $row['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-restore" title="Restore Client" onclick="restoreClient(<?= $row['id'] ?>, '<?= addslashes($clientName) ?>')">
                                        <i class="fas fa-trash-restore"></i>
                                    </button>
                                    <button class="btn btn-sm btn-permanent-delete" title="Permanently Delete" onclick="permanentDeleteClient(<?= $row['id'] ?>, '<?= addslashes($clientName) ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="fas fa-trash"></i>
                                    <h4>No Deleted Clients Found</h4>
                                    <p>There are no soft deleted clients in the database. All clients are currently active.</p>
                                    <a href="master-data-client.php" class="btn btn-primary-gradient mt-3">
                                        <i class="fas fa-users me-2"></i>View Active Clients
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Info Footer -->
        <div class="mt-4 text-center text-muted glass-card p-3">
            <p class="mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Showing <?= number_format($total_deleted) ?> deleted client records • 
                Restored clients will appear in active clients list • 
                Permanent deletion cannot be undone • 
                Last checked: <?= date('F j, Y, g:i a') ?>
            </p>
        </div>
    </div>

    <!-- Modal: View Client Details -->
    <div class="modal fade" id="viewClientModal" tabindex="-1" aria-labelledby="viewClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title" id="viewClientModalLabel">
                        <i class="fas fa-eye me-2"></i>Client Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewClientModalBody">
                    <!-- Content will be loaded via AJAX -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Loading client details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-glass" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Permanent Delete Client -->
    <div class="modal fade" id="permanentDeleteModal" tabindex="-1" aria-labelledby="permanentDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title" id="permanentDeleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Permanent Delete Client
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="permanentDeleteForm">
                    <input type="hidden" name="client_id" id="permanentDeleteClientId">
                    <div class="modal-body">
                        <div class="alert alert-danger alert-glass glass-card">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning: This action cannot be undone!</strong><br>
                            Client will be permanently removed from the database.
                        </div>
                        <p id="permanentDeleteClientName" class="fw-semibold mb-3"></p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason for permanent deletion</label>
                            <textarea class="form-control glass-card" name="delete_reason" 
                                      rows="3" placeholder="Please provide a reason for permanent deletion..." required></textarea>
                            <small class="text-muted">This will be recorded in the audit log.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="permanent_delete" class="btn btn-danger-gradient">
                            <i class="fas fa-trash-alt me-2"></i>Permanently Delete
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
        const table = $('#deletedClientsTable').DataTable({
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
            order: [[6, 'desc']],
            responsive: true,
            dom: '<"top"lf>rt<"bottom"ip><"clear">',
            columnDefs: [
                { orderable: false, targets: [0, 8] }
            ],
            initComplete: function() {
                // Add animation to rows
                $('tbody tr').addClass('fade-in-row');
            }
        });

        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
            
            // Add animation
            this.style.transform = 'rotate(180deg) scale(1.1)';
            setTimeout(() => {
                this.style.transform = '';
            }, 300);
        });
        
        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }

        // Search functionality
        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Filter functionality
        $('#applyFilterBtn').click(function() {
            let typeFilter = $('#typeFilter').val();
            let categoryFilter = $('#categoryFilter').val();
            let periodFilter = $('#periodFilter').val();
            
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                let type = data[4];
                let category = data[5];
                let deletedDate = data[6];
                
                let typeMatch = typeFilter === '' || type.toLowerCase().includes(typeFilter);
                let categoryMatch = categoryFilter === '' || category.toLowerCase().includes(categoryFilter);
                
                let periodMatch = true;
                if (periodFilter !== '' && deletedDate !== 'N/A') {
                    let date = new Date(deletedDate.split('<')[0].trim());
                    let now = new Date();
                    
                    switch(periodFilter) {
                        case 'today':
                            periodMatch = date.toDateString() === now.toDateString();
                            break;
                        case 'week':
                            let weekAgo = new Date();
                            weekAgo.setDate(weekAgo.getDate() - 7);
                            periodMatch = date >= weekAgo;
                            break;
                        case 'month':
                            let monthAgo = new Date();
                            monthAgo.setMonth(monthAgo.getMonth() - 1);
                            periodMatch = date >= monthAgo;
                            break;
                        case 'quarter':
                            let quarterAgo = new Date();
                            quarterAgo.setMonth(quarterAgo.getMonth() - 3);
                            periodMatch = date >= quarterAgo;
                            break;
                    }
                }
                
                return typeMatch && categoryMatch && periodMatch;
            });
            
            table.draw();
            $.fn.dataTable.ext.search.pop();
        });

        // Reset filters
        $('#resetFilterBtn').click(function() {
            $('#searchInput').val('');
            $('#typeFilter').val('').trigger('change');
            $('#categoryFilter').val('').trigger('change');
            $('#periodFilter').val('').trigger('change');
            table.search('').columns().search('').draw();
        });

        // Checkbox functionality
        $('#selectAll').change(function() {
            $('.client-checkbox').prop('checked', this.checked);
            updateBulkActions();
        });

        $('.client-checkbox').change(function() {
            if (!this.checked) {
                $('#selectAll').prop('checked', false);
            }
            updateBulkActions();
        });

        // Update bulk actions
        function updateBulkActions() {
            let selectedCount = $('.client-checkbox:checked').length;
            $('#selectedCount').text(selectedCount);
            
            if (selectedCount > 0) {
                $('#bulkActionsSection').addClass('show');
                
                let selectedIds = [];
                $('.client-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                $('#bulkRestoreIds').val(selectedIds);
                $('#bulkDeleteIds').val(selectedIds);
            } else {
                $('#bulkActionsSection').removeClass('show');
                $('#selectAll').prop('checked', false);
            }
        }

        // Clear all selections
        window.clearSelections = function() {
            $('.client-checkbox').prop('checked', false);
            $('#selectAll').prop('checked', false);
            updateBulkActions();
        }

        // View client details
        window.viewClientDetails = function(id) {
            $('#viewClientModal').modal('show');
            
            // Simulate AJAX call
            setTimeout(() => {
                $('#viewClientModalBody').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-id-card me-2"></i>Client Information</h6>
                            <div class="glass-card p-3 mb-3">
                                <p><strong>Client Code:</strong> CL-${id}</p>
                                <p><strong>Client Name:</strong> Client ${id}</p>
                                <p><strong>Contact Person:</strong> John Doe</p>
                                <p><strong>Email:</strong> client${id}@example.com</p>
                                <p><strong>Phone:</strong> +62 812-3456-7890</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle me-2"></i>Deletion Information</h6>
                            <div class="glass-card p-3 mb-3">
                                <p><strong>Deleted Date:</strong> ${new Date().toLocaleDateString()}</p>
                                <p><strong>Delete Reason:</strong> Client requested deletion</p>
                                <p><strong>Deleted By:</strong> Admin User</p>
                                <p><strong>Status:</strong> <span class="badge bg-danger">Deleted</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6><i class="fas fa-history me-2"></i>Additional Information</h6>
                        <div class="glass-card p-3">
                            <p>This client was created on January 1, 2023 and deleted on ${new Date().toLocaleDateString()}.</p>
                            <p class="mb-0">Note: This is a sample view. In production, real data would be loaded from the database.</p>
                        </div>
                    </div>
                `);
            }, 500);
        }

        // Restore client
        window.restoreClient = function(id, name) {
            Swal.fire({
                title: 'Restore Client',
                html: `Are you sure you want to restore <strong>${name}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, restore it!',
                cancelButtonText: 'Cancel',
                background: 'var(--glass-bg)',
                backdrop: 'var(--glass-blur)',
                color: 'var(--primary-color)'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'client_id';
                    inputId.value = id;
                    
                    const inputAction = document.createElement('input');
                    inputAction.type = 'hidden';
                    inputAction.name = 'restore_client';
                    inputAction.value = '1';
                    
                    form.appendChild(inputId);
                    form.appendChild(inputAction);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Permanent delete client
        window.permanentDeleteClient = function(id, name) {
            $('#permanentDeleteClientId').val(id);
            $('#permanentDeleteClientName').text('Client: ' + name);
            $('#permanentDeleteModal').modal('show');
        }

        // Bulk restore confirmation
        window.confirmBulkRestore = function() {
            let count = $('.client-checkbox:checked').length;
            
            Swal.fire({
                title: 'Restore Multiple Clients',
                html: `Are you sure you want to restore <strong>${count} client(s)</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, restore ${count} client(s)`,
                cancelButtonText: 'Cancel',
                background: 'var(--glass-bg)',
                backdrop: 'var(--glass-blur)',
                color: 'var(--primary-color)'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#bulkRestoreForm').submit();
                }
            });
        }

        // Bulk delete confirmation
        window.confirmBulkDelete = function() {
            let count = $('.client-checkbox:checked').length;
            
            Swal.fire({
                title: 'Permanently Delete Multiple Clients',
                html: `<div class="text-danger">
                        <i class="fas fa-exclamation-triangle mb-3" style="font-size: 48px;"></i>
                        <p><strong>This action cannot be undone!</strong></p>
                        <p>Are you sure you want to permanently delete <strong>${count} client(s)</strong> from the database?</p>
                      </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, delete ${count} client(s) permanently`,
                cancelButtonText: 'Cancel',
                background: 'var(--glass-bg)',
                backdrop: 'var(--glass-blur)',
                color: 'var(--primary-color)'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#bulkDeleteForm').submit();
                }
            });
        }

        // Auto-hide toast notifications
        setTimeout(function() {
            $('.toast').toast('hide');
        }, 5000);

        // Add hover effects to all interactive elements
        $('.btn, .stats-card, .glass-card').on('mouseenter', function() {
            $(this).css('transform', 'translateY(-3px)');
        }).on('mouseleave', function() {
            $(this).css('transform', 'translateY(0)');
        });

        // Add loading animation to table rows
        $('tbody tr').each(function(index) {
            $(this).css('animation-delay', (index * 0.05) + 's');
        });
    });
    </script>
</body>
</html>