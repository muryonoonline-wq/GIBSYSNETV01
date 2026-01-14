<?php
// restoran-client.php
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

// Handle AJAX restore request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id'])) {
    try {
        $id = $_POST['client_id'];
        
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
            echo json_encode([
                'success' => true,
                'message' => 'Client has been restored successfully.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Client not found or already restored.'
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
        'message' => 'Invalid request.'
    ]);
}
?>