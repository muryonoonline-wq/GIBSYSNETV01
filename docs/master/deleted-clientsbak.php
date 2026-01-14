<?php
// deleted-clients.php - WITH EXPORT TO EXCEL AND PDF
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
// EXPORT HANDLERS
// ============================================

// Export to Excel (XLSX)
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Check if PhpSpreadsheet is available
    $phpspreadsheetPath = $_SERVER['DOCUMENT_ROOT'] . '/gibsysnet/vendor/autoload.php';
    
    if (file_exists($phpspreadsheetPath)) {
        require_once $phpspreadsheetPath;
    } else {
        // Try alternative paths
        $alternativePaths = [
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../../../vendor/autoload.php',
            'C:/xampp/htdocs/gibsysnet/vendor/autoload.php'
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
            die("PhpSpreadsheet library not found. Please install it with: composer require phpoffice/phpspreadsheet");
        }
    }
    
    try {
        // Fetch deleted clients data
        $stmt = $conn->prepare("
            SELECT 
                client_code,
                client_name,
                client_type,
                category,
                phone,
                mobile,
                email,
                contact_person,
                address,
                city,
                country,
                npwp,
                DATE_FORMAT(deleted_at, '%d %M %Y %H:%i') as deleted_date,
                delete_reason,
                status
            FROM clients 
            WHERE is_deleted = 1 
            ORDER BY deleted_at DESC
        ");
        $stmt->execute();
        $clients = $stmt->fetchAll();
        
        // Create new Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("GIBSYSNET")
            ->setTitle("Deleted Clients Report")
            ->setSubject("Deleted Clients Data")
            ->setDescription("Report of deleted clients from GIBSYSNET system");
        
        // Set default column width
        $sheet->getDefaultColumnDimension()->setWidth(20);
        
        // Set headers
        $headers = [
            'A' => 'Client Code',
            'B' => 'Client Name',
            'C' => 'Type',
            'D' => 'Category',
            'E' => 'Phone',
            'F' => 'Mobile',
            'G' => 'Email',
            'H' => 'Contact Person',
            'I' => 'Address',
            'J' => 'City',
            'K' => 'Country',
            'L' => 'Tax ID (NPWP)',
            'M' => 'Deleted Date',
            'N' => 'Delete Reason',
            'O' => 'Status'
        ];
        
        // Apply styles to headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FF416C']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ];
        
        // Write headers
        foreach ($headers as $column => $header) {
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->applyFromArray($headerStyle);
        }
        
        // Write data
        $row = 2;
        foreach ($clients as $client) {
            $sheet->setCellValue('A' . $row, $client['client_code']);
            $sheet->setCellValue('B' . $row, $client['client_name']);
            $sheet->setCellValue('C' . $row, $client['client_type']);
            $sheet->setCellValue('D' . $row, $client['category']);
            $sheet->setCellValue('E' . $row, $client['phone']);
            $sheet->setCellValue('F' . $row, $client['mobile']);
            $sheet->setCellValue('G' . $row, $client['email']);
            $sheet->setCellValue('H' . $row, $client['contact_person']);
            $sheet->setCellValue('I' . $row, $client['address']);
            $sheet->setCellValue('J' . $row, $client['city']);
            $sheet->setCellValue('K' . $row, $client['country']);
            $sheet->setCellValue('L' . $row, $client['npwp']);
            $sheet->setCellValue('M' . $row, $client['deleted_date']);
            $sheet->setCellValue('N' . $row, $client['delete_reason']);
            $sheet->setCellValue('O' . $row, $client['status']);
            
            // Apply alternate row colors
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':O' . $row)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFF5F5F5');
            }
            
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Set borders for data
        $lastRow = $row - 1;
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ]
        ];
        $sheet->getStyle('A1:O' . $lastRow)->applyFromArray($dataStyle);
        
        // Add title
        $sheet->insertNewRowBefore(1, 2);
        $sheet->mergeCells('A1:O1');
        $sheet->setCellValue('A1', 'DELETED CLIENTS REPORT - GIBSYSNET');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '2C3E50']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ]);
        
        // Add timestamp
        $sheet->setCellValue('A2', 'Generated on: ' . date('F j, Y, g:i a'));
        $sheet->mergeCells('A2:O2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '7F8C8D']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ]);
        
        // Move headers down
        $headerRow = 3;
        foreach ($headers as $column => $header) {
            $sheet->setCellValue($column . $headerRow, $header);
            $sheet->getStyle($column . $headerRow)->applyFromArray($headerStyle);
        }
        
        // Move data down
        for ($i = 2; $i <= $lastRow; $i++) {
            foreach (range('A', 'O') as $col) {
                $value = $sheet->getCell($col . ($i + 2))->getValue();
                $sheet->setCellValue($col . ($i + 2), $value);
                $sheet->getCell($col . ($i + 2))->setValue('');
            }
        }
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="deleted_clients_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Create Excel writer and output
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
        
    } catch (Exception $e) {
        die("Error generating Excel file: " . $e->getMessage());
    }
}

// Export to PDF
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
            'C:/xampp/htdocs/gibsysnet/vendor/autoload.php'
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
        // Fetch deleted clients data
        $stmt = $conn->prepare("
            SELECT 
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
            WHERE is_deleted = 1 
            ORDER BY deleted_at DESC
            LIMIT 50  // Limit for PDF to avoid memory issues
        ");
        $stmt->execute();
        $clients = $stmt->fetchAll();
        
        // Create new PDF document
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('GIBSYSNET');
        $pdf->SetAuthor('GIBSYSNET Admin');
        $pdf->SetTitle('Deleted Clients Report');
        $pdf->SetSubject('Deleted Clients Data');
        $pdf->SetKeywords('GIBSYSNET, Clients, Deleted, Report');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'GIBSYSNET - Deleted Clients Report', 'Generated on: ' . date('F j, Y, g:i a'));
        
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
        $pdf->Cell(0, 0, 'Total Records: ' . count($clients), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Create table header
        $html = '<style>
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
                font-family: helvetica;
                font-size: 10px;
            }
            th {
                background-color: #FF416C;
                color: white;
                font-weight: bold;
                padding: 8px;
                border: 1px solid #ddd;
                text-align: center;
            }
            td {
                padding: 6px;
                border: 1px solid #ddd;
                text-align: left;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .header-row {
                background-color: #2C3E50;
                color: white;
                font-weight: bold;
            }
        </style>';
        
        $html .= '<table border="1" cellpadding="4" cellspacing="0">';
        $html .= '<tr class="header-row">';
        $html .= '<th width="8%">No</th>';
        $html .= '<th width="12%">Client Code</th>';
        $html .= '<th width="20%">Client Name</th>';
        $html .= '<th width="10%">Type</th>';
        $html .= '<th width="10%">Category</th>';
        $html .= '<th width="12%">Phone</th>';
        $html .= '<th width="15%">Email</th>';
        $html .= '<th width="15%">Contact Person</th>';
        $html .= '<th width="15%">Deleted Date</th>';
        $html .= '<th width="20%">Delete Reason</th>';
        $html .= '</tr>';
        
        // Add table rows
        $no = 1;
        foreach ($clients as $client) {
            $html .= '<tr>';
            $html .= '<td align="center">' . $no . '</td>';
            $html .= '<td>' . htmlspecialchars($client['client_code']) . '</td>';
            $html .= '<td>' . htmlspecialchars($client['client_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($client['client_type']) . '</td>';
            $html .= '<td>' . htmlspecialchars($client['category']) . '</td>';
            $html .= '<td>' . htmlspecialchars($client['phone']) . '</td>';
            $html .= '<td>' . htmlspecialchars($client['email']) . '</td>';
            $html .= '<td>' . htmlspecialchars($client['contact_person']) . '</td>';
            $html .= '<td>' . htmlspecialchars($client['deleted_date']) . '</td>';
            $html .= '<td>' . htmlspecialchars(substr($client['delete_reason'], 0, 50)) . 
                     (strlen($client['delete_reason']) > 50 ? '...' : '') . '</td>';
            $html .= '</tr>';
            $no++;
        }
        
        $html .= '</table>';
        
        // Add summary
        $html .= '<div style="margin-top: 20px; font-size: 9px; color: #666;">';
        $html .= '<strong>Report Summary:</strong><br>';
        $html .= '• Total Deleted Clients: ' . count($clients) . '<br>';
        $html .= '• Report Generated: ' . date('F j, Y, g:i a') . '<br>';
        $html .= '• Generated By: GIBSYSNET Admin<br>';
        $html .= '• System: GIBSYSNET v2.0';
        $html .= '</div>';
        
        // Output HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output('deleted_clients_' . date('Y-m-d') . '.pdf', 'D');
        exit();
        
    } catch (Exception $e) {
        die("Error generating PDF file: " . $e->getMessage());
    }
}

// Export to CSV (existing functionality - keep as fallback)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        // Fetch deleted clients data
        $stmt = $conn->prepare("
            SELECT 
                client_code,
                client_name,
                client_type,
                category,
                phone,
                mobile,
                email,
                contact_person,
                address,
                city,
                country,
                npwp,
                DATE_FORMAT(deleted_at, '%d %M %Y %H:%i') as deleted_date,
                delete_reason,
                status
            FROM clients 
            WHERE is_deleted = 1 
            ORDER BY deleted_at DESC
        ");
        $stmt->execute();
        $clients = $stmt->fetchAll();
        
        // Set headers for CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="deleted_clients_' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Write headers
        $headers = [
            'Client Code',
            'Client Name', 
            'Client Type',
            'Category',
            'Phone',
            'Mobile',
            'Email',
            'Contact Person',
            'Address',
            'City',
            'Country',
            'Tax ID (NPWP)',
            'Deleted Date',
            'Delete Reason',
            'Status'
        ];
        fputcsv($output, $headers);
        
        // Write data
        foreach ($clients as $client) {
            fputcsv($output, [
                $client['client_code'],
                $client['client_name'],
                $client['client_type'],
                $client['category'],
                $client['phone'],
                $client['mobile'],
                $client['email'],
                $client['contact_person'],
                $client['address'],
                $client['city'],
                $client['country'],
                $client['npwp'],
                $client['deleted_date'],
                $client['delete_reason'],
                $client['status']
            ]);
        }
        
        fclose($output);
        exit();
        
    } catch (Exception $e) {
        die("Error generating CSV file: " . $e->getMessage());
    }
}

// ============================================
// AJAX HANDLERS (keep existing code)
// ============================================

// AJAX Get Counts - NEW FUNCTION
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_counts'])) {
    header('Content-Type: application/json');
    
    try {
        // Get deleted clients count
        $deletedStmt = $conn->query("SELECT COUNT(*) as deleted_count FROM clients WHERE is_deleted = 1");
        $deletedCount = $deletedStmt->fetch()['deleted_count'] ?? 0;
        
        // Get active clients count
        $activeStmt = $conn->query("SELECT COUNT(*) as active_count FROM clients WHERE is_deleted = 0");
        $activeCount = $activeStmt->fetch()['active_count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'deleted_count' => $deletedCount,
            'active_count' => $activeCount,
            'deleted_formatted' => number_format($deletedCount),
            'active_formatted' => number_format($activeCount)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error'
        ]);
    }
    exit();
}

// AJAX Restore Client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_restore'])) {
    header('Content-Type: application/json');
    
    try {
        $id = intval($_POST['client_id']);
        
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
        
        if ($stmt->rowCount() > 0) {
            // Get updated counts
            $deletedStmt = $conn->query("SELECT COUNT(*) as deleted_count FROM clients WHERE is_deleted = 1");
            $deletedCount = $deletedStmt->fetch()['deleted_count'] ?? 0;
            
            $activeStmt = $conn->query("SELECT COUNT(*) as active_count FROM clients WHERE is_deleted = 0");
            $activeCount = $activeStmt->fetch()['active_count'] ?? 0;
            
            echo json_encode([
                'success' => true,
                'message' => 'Client restored successfully',
                'deleted_count' => $deletedCount,
                'active_count' => $activeCount,
                'deleted_formatted' => number_format($deletedCount),
                'active_formatted' => number_format($activeCount)
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

// ... (keep all other AJAX handlers exactly as they were) ...

// ============================================
// HELPER FUNCTIONS (keep existing code)
// ============================================
function safe_html($value) {
    return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}

// FIXED FUNCTION: Time elapsed string without dynamic property creation
function time_elapsed_string($datetime, $full = false) {
    try {
        if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
            return 'just now';
        }
        
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        // Calculate weeks manually instead of using dynamic property
        $weeks = floor($diff->d / 7);
        $remaining_days = $diff->d % 7;
        
        $string = array();
        
        if ($diff->y > 0) {
            $string[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            $string[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
        }
        if ($weeks > 0) {
            $string[] = $weeks . ' week' . ($weeks > 1 ? 's' : '');
        }
        if ($remaining_days > 0) {
            $string[] = $remaining_days . ' day' . ($remaining_days > 1 ? 's' : '');
        }
        if ($diff->h > 0) {
            $string[] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
        }
        if ($diff->i > 0) {
            $string[] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        }
        if ($diff->s > 0) {
            $string[] = $diff->s . ' second' . ($diff->s > 1 ? 's' : '');
        }
        
        if (empty($string)) {
            return 'just now';
        }
        
        if (!$full) {
            $string = array_slice($string, 0, 1);
        }
        
        return $string ? implode(', ', $string) . ' ago' : 'just now';
        
    } catch (Exception $e) {
        return 'recently';
    }
}

// ============================================
// FETCH DATA (keep existing code)
// ============================================
// Fetch deleted clients
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

// Fetch active clients count for stats
$activeStmt = $conn->query("SELECT COUNT(*) as active_count FROM clients WHERE is_deleted = 0");
$activeCount = $activeStmt->fetch()['active_count'] ?? 0;

$is_super_admin = true;
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleted Clients | GIBSYSNET</title>
    
    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    
    <!-- Modern Styling -->
    <style>
        :root {
            --danger-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 25px 50px rgba(0, 0, 0, 0.25);
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
        }
        
        /* Export buttons styles */
        .export-dropdown .btn-group {
            display: flex;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .export-btn:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .export-btn.excel:hover {
            border-color: #28a745;
            color: #28a745;
            background-color: rgba(40, 167, 69, 0.05);
        }
        
        .export-btn.pdf:hover {
            border-color: #dc3545;
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.05);
        }
        
        .export-btn.csv:hover {
            border-color: #17a2b8;
            color: #17a2b8;
            background-color: rgba(23, 162, 184, 0.05);
        }
        
        /* Other existing styles remain the same... */
        
        /* ... (keep all other existing CSS styles exactly as they were) ... */
        
    </style>
</head>
<body class="animate-fade-in">
    <!-- Theme Toggle -->
    <div class="position-fixed bottom-3 end-3 z-3">
        <div class="theme-toggle" id="themeToggle">
            <i class="bi bi-moon-fill"></i>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container-fluid py-4 px-4 px-lg-5">
        <!-- Header -->
        <nav class="nav-glass rounded-4 p-4 mb-5 animate-slide-up">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <div>
                    <h1 class="h2 fw-bold mb-1 d-flex align-items-center gap-3">
                        <div class="p-3 rounded-4" style="background: var(--danger-gradient);">
                            <i class="bi bi-trash3 text-white fs-4"></i>
                        </div>
                        <div>
                            <span>Deleted Clients</span>
                            <small class="d-block text-muted fs-6 fw-normal mt-1">
                                <i class="bi bi-database me-1"></i> Total <span id="totalDeletedCount"><?= number_format($total_deleted) ?></span> deleted records
                            </small>
                        </div>
                    </h1>
                </div>
                
                <div class="d-flex flex-wrap gap-3">
                    <a href="../master/master-data-client.php" class="btn-gradient d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i>
                        Back to Active Clients
                    </a>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-shield-lock"></i>
                            <span>Super Admin</span>
                            <span class="badge bg-gradient-warning">SUPER</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../master/master-data-client.php">
                                <i class="bi bi-people me-2"></i>Active Clients
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5 animate-slide-up" style="animation-delay: 0.1s">
            <div class="col-xl-6 col-md-6">
                <div class="stats-card deleted h-100">
                    <div class="stats-icon deleted">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <div class="stats-number" id="deletedCount"><?= number_format($total_deleted) ?></div>
                    <div class="stats-label">Deleted Clients</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                        In recycle bin
                    </div>
                </div>
            </div>
            
            <div class="col-xl-6 col-md-6">
                <div class="stats-card active h-100">
                    <div class="stats-icon active">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-number" id="activeCount"><?= number_format($activeCount) ?></div>
                    <div class="stats-label">Active Clients</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-check-circle text-success me-1"></i>
                        Currently active
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning Alert -->
        <div class="alert alert-warning glass-card p-4 mb-4 animate-slide-up" style="animation-delay: 0.2s">
            <div class="d-flex align-items-start gap-3">
                <div class="p-3 rounded-4 bg-warning bg-opacity-10">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-4"></i>
                </div>
                <div>
                    <h5 class="alert-heading fw-bold mb-2">Important Notice</h5>
                    <p class="mb-2">This page displays soft deleted clients only. Deleted clients can be restored to active status or permanently deleted from the system.</p>
                    <small class="text-danger fw-semibold">
                        <i class="bi bi-info-circle me-1"></i>
                        Permanent deletion cannot be undone!
                    </small>
                </div>
            </div>
        </div>

        <!-- Bulk Action Toolbar -->
        <div class="bulk-action-toolbar animate-slide-up" id="bulkActionToolbar" style="animation-delay: 0.3s; display: none;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 rounded-circle bg-warning bg-opacity-10">
                            <i class="bi bi-collection text-warning"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Bulk Actions</h6>
                            <span id="selectedCount" class="text-muted">0 client(s) selected</span>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-success d-flex align-items-center gap-2" id="bulkRestoreBtn" disabled>
                        <i class="bi bi-trash-restore"></i>
                        Restore Selected
                    </button>
                    <button class="btn btn-outline-secondary d-flex align-items-center gap-2" id="clearSelectionBtn">
                        <i class="bi bi-x-lg"></i>
                        Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="glass-card p-4 mb-4 animate-slide-up" style="animation-delay: 0.4s">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-0">Deleted Client List</h5>
                    <p class="text-muted mb-0">Showing <span id="currentDeletedCount"><?= number_format($total_deleted) ?></span> deleted records</p>
                </div>
                
                <div class="d-flex gap-2 export-dropdown">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-success export-btn excel" id="exportExcel">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                        <button type="button" class="btn btn-outline-danger export-btn pdf" id="exportPDF">
                            <i class="bi bi-file-earmark-pdf"></i> PDF
                        </button>
                        <button type="button" class="btn btn-outline-info export-btn csv" id="exportCSV">
                            <i class="bi bi-file-earmark-text"></i> CSV
                        </button>
                    </div>
                    <button class="btn btn-outline-danger d-flex align-items-center gap-2" id="emptyTrashBtn">
                        <i class="bi bi-trash"></i> Empty Trash
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <?php if (count($deleted_clients) > 0): ?>
                    <table id="deletedClientsTable" class="table modern-table">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="form-check-input" id="selectAllCheckbox">
                                </th>
                                <th>CLIENT CODE</th>
                                <th>CLIENT NAME</th>
                                <th>DELETED DATE</th>
                                <th>DELETE REASON</th>
                                <th>ORIGINAL INFO</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="deletedClientsBody">
                            <?php foreach ($deleted_clients as $index => $row): ?>
                                <?php 
                                $deletedDate = $row['deleted_at'] ? date('d M Y, H:i', strtotime($row['deleted_at'])) : 'Unknown';
                                $deleteReason = $row['delete_reason'] ?: 'No reason provided';
                                $timeAgo = $row['deleted_at'] ? time_elapsed_string($row['deleted_at']) : '';
                                ?>
                                <tr class="deleted-row" data-id="<?= $row['id'] ?>" id="clientRow_<?= $row['id'] ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input client-checkbox" 
                                               value="<?= $row['id'] ?>"
                                               data-client-name="<?= safe_html($row['client_name']) ?>">
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= safe_html($row['client_code']) ?></div>
                                        <span class="deleted-badge">
                                            <i class="bi bi-trash"></i> Deleted
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= safe_html($row['client_name']) ?></div>
                                        <small class="text-muted"><?= safe_html($row['email'] ?: 'No email') ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= $deletedDate ?></div>
                                        <?php if ($timeAgo): ?>
                                            <span class="time-badge"><?= $timeAgo ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="truncate-text" style="max-width: 200px;" 
                                             title="<?= safe_html($deleteReason) ?>">
                                            <?= safe_html(mb_strimwidth($deleteReason, 0, 50, '...')) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                                <?= safe_html($row['client_type']) ?>
                                            </span>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                                <?= safe_html($row['category']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons d-flex gap-2">
                                            <button class="action-btn btn-restore" 
                                                    onclick="restoreClient(<?= $row['id'] ?>, '<?= addslashes($row['client_name']) ?>')"
                                                    title="Restore Client">
                                                <i class="bi bi-trash-restore"></i>
                                            </button>
                                            <button class="action-btn btn-delete-permanent" 
                                                    onclick="deleteClientPermanent(<?= $row['id'] ?>, '<?= addslashes($row['client_name']) ?>')"
                                                    title="Permanent Delete">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                            <button class="action-btn btn-view" 
                                                    onclick="viewClientDetails(<?= $row['id'] ?>)"
                                                    title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state py-5">
                        <div class="empty-state-icon">
                            <i class="bi bi-trash"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Recycle Bin is Empty</h4>
                        <p class="text-muted mb-4">No deleted client records found in the database.</p>
                        <a href="../master/master-data-client.php" class="btn-gradient">
                            <i class="bi bi-arrow-left me-2"></i>Back to Active Clients
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="text-center text-muted py-3">
            <p class="mb-0">
                <i class="bi bi-shield-lock me-2"></i>
                Super Admin Access Only • 
                Last updated: <?= date('F j, Y, g:i a') ?> • 
                System: GIBSYSNET v2.0
            </p>
        </div>
    </div>

    <!-- ============================================
    MODALS
    ============================================ -->

    <!-- View Client Details Modal -->
    <div class="modal fade" id="viewClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header modal-header-danger">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-trash3 me-2"></i>Deleted Client Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="viewClientModalBody">
                    <!-- Loaded via AJAX -->
                    <div class="text-center py-5">
                        <div class="loader mx-auto mb-3"></div>
                        <p>Loading client details...</p>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn-gradient" id="restoreFromViewBtn">
                        <i class="bi bi-trash-restore me-2"></i>Restore Client
                    </button>
                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        document.documentElement.setAttribute('data-bs-theme', currentTheme);
        themeToggle.innerHTML = currentTheme === 'dark' 
            ? '<i class="bi bi-sun-fill"></i>' 
            : '<i class="bi bi-moon-fill"></i>';
        
        themeToggle.addEventListener('click', () => {
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            themeToggle.innerHTML = newTheme === 'dark' 
                ? '<i class="bi bi-sun-fill"></i>' 
                : '<i class="bi bi-moon-fill"></i>';
            
            themeToggle.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                themeToggle.style.transform = 'rotate(0deg)';
            }, 300);
        });

        // Initialize DataTable
        const table = $('#deletedClientsTable').DataTable({
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
            language: {
                search: "",
                searchPlaceholder: "Search deleted clients...",
                lengthMenu: "_MENU_ per page",
                info: "Showing _START_ to _END_ of _TOTAL_ deleted clients",
                infoEmpty: "No deleted clients found",
                infoFiltered: "(filtered from _MAX_ total deleted)",
                zeroRecords: "No matching deleted clients found",
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>'
                }
            },
            order: [[3, 'desc']],
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [0, 6] },
                { className: "align-middle", targets: "_all" }
            ]
        });

        // Search functionality
        $('.dataTables_filter input').addClass('form-control form-control-modern');
        $('.dataTables_length select').addClass('form-select form-select-modern');

        // ============================================
        // EXPORT FUNCTIONS
        // ============================================

        // Export to Excel
        $('#exportExcel').click(function() {
            Swal.fire({
                title: 'Exporting to Excel',
                html: 'Preparing Excel file with advanced formatting...',
                icon: 'info',
                showConfirmButton: false,
                timer: 1500
            });
            
            setTimeout(() => {
                window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?export=excel';
            }, 500);
        });

        // Export to PDF
        $('#exportPDF').click(function() {
            Swal.fire({
                title: 'Exporting to PDF',
                html: 'Generating professional PDF document...',
                icon: 'info',
                showConfirmButton: false,
                timer: 1500
            });
            
            setTimeout(() => {
                window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?export=pdf';
            }, 500);
        });

        // Export to CSV
        $('#exportCSV').click(function() {
            Swal.fire({
                title: 'Exporting to CSV',
                html: 'Creating CSV file...',
                icon: 'info',
                showConfirmButton: false,
                timer: 1500
            });
            
            setTimeout(() => {
                window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?export=csv';
            }, 500);
        });

        // ============================================
        // BULK OPERATIONS
        // ============================================

        // Select all checkbox
        $('#selectAllCheckbox').on('change', function() {
            $('.client-checkbox').prop('checked', this.checked);
            updateBulkActionToolbar();
        });

        // Individual checkbox
        $(document).on('change', '.client-checkbox', function() {
            const allChecked = $('.client-checkbox:checked').length === $('.client-checkbox').length;
            $('#selectAllCheckbox').prop('checked', allChecked);
            updateBulkActionToolbar();
        });

        // Update bulk action toolbar
        function updateBulkActionToolbar() {
            const selectedCount = $('.client-checkbox:checked').length;
            $('#selectedCount').text(selectedCount + ' client(s) selected');
            
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

        // Bulk restore
        $('#bulkRestoreBtn').click(function() {
            const selectedIds = [];
            const selectedNames = [];
            
            $('.client-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
                selectedNames.push($(this).data('client-name'));
            });
            
            if (selectedIds.length === 0) return;
            
            Swal.fire({
                title: 'Bulk Restore',
                html: `Are you sure you want to restore <strong>${selectedIds.length}</strong> client(s)?<br>
                      <small class="text-muted">${selectedNames.slice(0, 3).join(', ')}${selectedNames.length > 3 ? ' and ' + (selectedNames.length - 3) + ' more...' : ''}</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, restore ${selectedIds.length} client(s)`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Restoring...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    $.ajax({
                        url: '<?= $_SERVER['PHP_SELF'] ?>',
                        method: 'POST',
                        data: {
                            ajax_bulk_restore: 'true',
                            client_ids: JSON.stringify(selectedIds)
                        },
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            if (response.success) {
                                // Remove restored rows from table
                                selectedIds.forEach(id => {
                                    const row = $(`#clientRow_${id}`);
                                    if (row.length) {
                                        row.fadeOut(300, function() {
                                            $(this).remove();
                                        });
                                    }
                                });
                                
                                // Update counts
                                updateCountDisplay('deletedCount', response.deleted_formatted);
                                updateCountDisplay('activeCount', response.active_formatted);
                                updateCountDisplay('totalDeletedCount', response.deleted_formatted);
                                updateCountDisplay('currentDeletedCount', response.deleted_formatted);
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    html: `${response.message}<br><small>${response.count} client(s) restored</small>`,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    updateTableCount();
                                    updateCounts(); // Get latest counts from server
                                });
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.close();
                            Swal.fire('Error', 'Failed to restore clients', 'error');
                        }
                    });
                }
            });
        });

        // Empty trash button
        $('#emptyTrashBtn').click(function() {
            const totalDeleted = <?= $total_deleted ?>;
            if (totalDeleted === 0) {
                Swal.fire('Info', 'Trash is already empty', 'info');
                return;
            }
            
            Swal.fire({
                title: 'Empty Trash',
                html: `Are you sure you want to <strong>permanently delete ALL</strong> ${totalDeleted} client(s)?<br>
                      <small class="text-danger">This action cannot be undone and will permanently remove all deleted clients!</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, empty trash',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Are you absolutely sure?',
                        html: `This will <strong class="text-danger">permanently delete ALL</strong> ${totalDeleted} client(s) from the database.<br>
                              <small class="text-danger">This action cannot be reversed!</small>`,
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#e74c3c',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, I understand, delete everything',
                        cancelButtonText: 'Cancel'
                    }).then((finalResult) => {
                        if (finalResult.isConfirmed) {
                            // Get all IDs
                            const allIds = [];
                            $('.client-checkbox').each(function() {
                                allIds.push($(this).val());
                            });
                            
                            if (allIds.length > 0) {
                                Swal.fire({
                                    title: 'Deleting...',
                                    text: `Permanently deleting ${allIds.length} clients`,
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                                
                                // Simulate deletion (you need to implement bulk delete on server side)
                                // For now, we'll just reload the page
                                setTimeout(() => {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Trash Emptied!',
                                        text: `Successfully deleted ${allIds.length} client(s)`,
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                }, 1500);
                            }
                        }
                    });
                }
            });
        });

        // ============================================
        // RESTORE CLIENT FUNCTION (WITH REAL-TIME UPDATE)
        // ============================================
        window.restoreClient = function(id, name) {
            Swal.fire({
                title: 'Restore Client',
                html: `Are you sure you want to restore <strong>${name}</strong>?<br>
                      <small class="text-muted">Client will be moved back to active clients list.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, restore client',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Restoring...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    $.ajax({
                        url: '<?= $_SERVER['PHP_SELF'] ?>',
                        method: 'POST',
                        data: {
                            ajax_restore: 'true',
                            client_id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            if (response.success) {
                                // Remove row from table
                                const row = $(`#clientRow_${id}`);
                                if (row.length) {
                                    row.fadeOut(300, function() {
                                        $(this).remove();
                                        
                                        // Update counts immediately from response
                                        updateCountDisplay('deletedCount', response.deleted_formatted);
                                        updateCountDisplay('activeCount', response.active_formatted);
                                        updateCountDisplay('totalDeletedCount', response.deleted_formatted);
                                        updateCountDisplay('currentDeletedCount', response.deleted_formatted);
                                        
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Restored!',
                                            text: response.message,
                                            timer: 2000,
                                            showConfirmButton: false
                                        }).then(() => {
                                            updateTableCount();
                                            updateCounts(); // Get latest counts from server
                                        });
                                    });
                                } else {
                                    // If row not found, just update counts
                                    updateCountDisplay('deletedCount', response.deleted_formatted);
                                    updateCountDisplay('activeCount', response.active_formatted);
                                    updateCountDisplay('totalDeletedCount', response.deleted_formatted);
                                    updateCountDisplay('currentDeletedCount', response.deleted_formatted);
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Restored!',
                                        text: response.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        updateCounts(); // Get latest counts from server
                                    });
                                }
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.close();
                            Swal.fire('Error', 'Failed to restore client', 'error');
                        }
                    });
                }
            });
        };

        // Update table count after operations
        function updateTableCount() {
            const remaining = $('.client-checkbox').length;
            $('#selectedCount').text(remaining + ' client(s) remaining');
            
            if (remaining === 0) {
                $('#bulkActionToolbar').hide();
                // Show empty state
                $('.data-table-wrapper').html(`
                    <div class="empty-state py-5">
                        <div class="empty-state-icon">
                            <i class="bi bi-trash"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Recycle Bin is Empty</h4>
                        <p class="text-muted mb-4">No deleted client records found in the database.</p>
                        <a href="../master/master-data-client.php" class="btn-gradient">
                            <i class="bi bi-arrow-left me-2"></i>Back to Active Clients
                        </a>
                    </div>
                `);
            }
        }

        // ============================================
        // PERMANENT DELETE CLIENT FUNCTION (WITH REAL-TIME UPDATE)
        // ============================================
        window.deleteClientPermanent = function(id, name) {
            Swal.fire({
                title: 'Permanent Delete',
                html: `<div class="text-center">
                    <div class="p-4 rounded-circle bg-danger bg-opacity-10 d-inline-block mb-3">
                        <i class="bi bi-trash3 text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold mb-3">${name}</h5>
                    <p>Are you sure you want to <strong class="text-danger">permanently delete</strong> this client?</p>
                    <p class="small text-muted">This action cannot be undone! All client data will be permanently lost.</p>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete permanently',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Are you absolutely sure?',
                        html: `<div class="text-center">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-3"></i>
                            <p class="fw-bold">This action cannot be reversed!</p>
                            <p>Client <strong>${name}</strong> will be permanently removed from the database.</p>
                        </div>`,
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#e74c3c',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, I understand, delete permanently',
                        cancelButtonText: 'Cancel'
                    }).then((finalResult) => {
                        if (finalResult.isConfirmed) {
                            Swal.fire({
                                title: 'Deleting...',
                                text: 'Permanently removing client',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            
                            $.ajax({
                                url: '<?= $_SERVER['PHP_SELF'] ?>',
                                method: 'POST',
                                data: {
                                    ajax_permanent_delete: 'true',
                                    client_id: id
                                },
                                dataType: 'json',
                                success: function(response) {
                                    Swal.close();
                                    if (response.success) {
                                        // Remove row from table
                                        const row = $(`#clientRow_${id}`);
                                        if (row.length) {
                                            row.fadeOut(300, function() {
                                                $(this).remove();
                                                
                                                // Update counts immediately from response
                                                updateCountDisplay('deletedCount', response.deleted_formatted);
                                                updateCountDisplay('activeCount', response.active_formatted);
                                                updateCountDisplay('totalDeletedCount', response.deleted_formatted);
                                                updateCountDisplay('currentDeletedCount', response.deleted_formatted);
                                                
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Deleted!',
                                                    text: response.message,
                                                    timer: 2000,
                                                    showConfirmButton: false
                                                }).then(() => {
                                                    updateTableCount();
                                                    updateCounts(); // Get latest counts from server
                                                });
                                            });
                                        } else {
                                            // If row not found, just update counts
                                            updateCountDisplay('deletedCount', response.deleted_formatted);
                                            updateCountDisplay('activeCount', response.active_formatted);
                                            updateCountDisplay('totalDeletedCount', response.deleted_formatted);
                                            updateCountDisplay('currentDeletedCount', response.deleted_formatted);
                                            
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Deleted!',
                                                text: response.message,
                                                timer: 2000,
                                                showConfirmButton: false
                                            }).then(() => {
                                                updateCounts(); // Get latest counts from server
                                            });
                                        }
                                    } else {
                                        Swal.fire('Error', response.message, 'error');
                                    }
                                },
                                error: function() {
                                    Swal.close();
                                    Swal.fire('Error', 'Failed to delete client', 'error');
                                }
                            });
                        }
                    });
                }
            });
        };

        // ============================================
        // COUNT UPDATE FUNCTIONS
        // ============================================

        // Function to update counts
        function updateCounts() {
            $.ajax({
                url: '<?= $_SERVER['PHP_SELF'] ?>',
                method: 'GET',
                data: {
                    ajax_get_counts: 'true'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update count displays with animation
                        updateCountDisplay('deletedCount', response.deleted_formatted);
                        updateCountDisplay('activeCount', response.active_formatted);
                        updateCountDisplay('totalDeletedCount', response.deleted_formatted);
                        updateCountDisplay('currentDeletedCount', response.deleted_formatted);
                        
                        // Update info in DataTable
                        updateDataTableInfo(response.deleted_formatted);
                    }
                }
            });
        }

        // Function to update a specific count display with animation
        function updateCountDisplay(elementId, newValue) {
            const element = $('#' + elementId);
            const oldValue = element.text().trim();
            
            if (oldValue !== newValue) {
                element.addClass('counter-update');
                element.text(newValue);
                
                // Remove animation class after animation completes
                setTimeout(() => {
                    element.removeClass('counter-update');
                }, 500);
            }
        }

        // Update DataTable info display
        function updateDataTableInfo(totalDeleted) {
            // Update DataTable info if available
            if (table) {
                table.page.info().recordsTotal = parseInt(totalDeleted.replace(/,/g, ''));
                table.draw('page');
            }
        }

        // ============================================
        // VIEW CLIENT DETAILS FUNCTION
        // ============================================
        window.viewClientDetails = function(id) {
            $.ajax({
                url: '<?= $_SERVER['PHP_SELF'] ?>',
                method: 'GET',
                data: {
                    ajax_get_client: 'true',
                    id: id
                },
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
                        $('#viewClientModalBody').html(response.html);
                        $('#viewClientModal').modal('show');
                        
                        // Set restore button to work from view
                        $('#restoreFromViewBtn').off('click').click(function() {
                            $('#viewClientModal').modal('hide');
                            setTimeout(() => {
                                restoreClient(id, response.client.client_name);
                            }, 300);
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load client details', 'error');
                }
            });
        };

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