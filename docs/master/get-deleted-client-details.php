<?php
// get-client-details.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
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
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed']));
    }
}

function safe_html($value) {
    return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $isDeleted = isset($_GET['deleted']);
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                id, client_code, client_name, client_type, category,
                address, city, country, phone, mobile, email,
                contact_person, npwp, join_date, status, created_at,
                updated_at, deleted_at, delete_reason, is_deleted
            FROM clients 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        
        if ($client) {
            $html = '<div class="row">';
            
            // Basic Info
            $html .= '<div class="col-md-6">';
            $html .= '<h6>Basic Information</h6>';
            $html .= '<p><strong>Client Code:</strong> ' . safe_html($client['client_code']) . '</p>';
            $html .= '<p><strong>Client Name:</strong> ' . safe_html($client['client_name']) . '</p>';
            $html .= '<p><strong>Client Type:</strong> <span class="badge bg-info">' . safe_html($client['client_type']) . '</span></p>';
            $html .= '<p><strong>Category:</strong> <span class="badge bg-secondary">' . safe_html($client['category']) . '</span></p>';
            $html .= '<p><strong>Status:</strong> <span class="badge ' . ($client['status'] === 'Active' ? 'bg-success' : 'bg-danger') . '">' . safe_html($client['status']) . '</span></p>';
            
            if ($client['is_deleted']) {
                $html .= '<p><strong>Deleted Status:</strong> <span class="badge bg-dark">Deleted</span></p>';
            }
            $html .= '</div>';
            
            // Contact Info
            $html .= '<div class="col-md-6">';
            $html .= '<h6>Contact Information</h6>';
            $html .= '<p><strong>Address:</strong> ' . safe_html($client['address'] ?: 'N/A') . '</p>';
            $html .= '<p><strong>City:</strong> ' . safe_html($client['city'] ?: 'N/A') . '</p>';
            $html .= '<p><strong>Phone:</strong> ' . safe_html($client['phone'] ?: 'N/A') . '</p>';
            $html .= '<p><strong>Mobile:</strong> ' . safe_html($client['mobile'] ?: 'N/A') . '</p>';
            $html .= '<p><strong>Email:</strong> ' . ($client['email'] ? '<a href="mailto:' . safe_html($client['email']) . '">' . safe_html($client['email']) . '</a>' : 'N/A') . '</p>';
            $html .= '<p><strong>Contact Person:</strong> ' . safe_html($client['contact_person'] ?: 'N/A') . '</p>';
            $html .= '</div>';
            
            $html .= '</div>';
            
            // Additional Info
            $html .= '<div class="row mt-3">';
            $html .= '<div class="col-md-6">';
            $html .= '<h6>Additional Information</h6>';
            $html .= '<p><strong>NPWP:</strong> ' . safe_html($client['npwp'] ?: 'N/A') . '</p>';
            $html .= '<p><strong>Join Date:</strong> ' . safe_html($client['join_date'] ?: 'N/A') . '</p>';
            $html .= '<p><strong>Country:</strong> ' . safe_html($client['country'] ?: 'N/A') . '</p>';
            $html .= '</div>';
            
            // Deletion Info (if deleted)
            if ($client['is_deleted']) {
                $html .= '<div class="col-md-6">';
                $html .= '<h6 class="text-danger">Deletion Information</h6>';
                $html .= '<p><strong>Deleted At:</strong> ' . ($client['deleted_at'] ? date('d M Y, H:i', strtotime($client['deleted_at'])) : 'N/A') . '</p>';
                $html .= '<p><strong>Delete Reason:</strong> ' . safe_html($client['delete_reason'] ?: 'No reason provided') . '</p>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            
            echo json_encode([
                'success' => true,
                'html' => $html
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Client not found.'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No client ID provided.'
    ]);
}
?>