<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $client_id = $_GET['id'] ?? 0;

    if (empty($client_id)) {
        throw new Exception('Client ID is required');
    }

    // Get client details (including deleted clients)
    $stmt = $conn->prepare("
        SELECT 
            *,
            DATE_FORMAT(join_date, '%d %b %Y') as join_date_formatted,
            DATE_FORMAT(deleted_at, '%d %b %Y, %H:%i') as deleted_at_formatted
        FROM clients 
        WHERE id = ?
    ");
    
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client) {
        echo json_encode([
            'success' => true,
            'client' => $client
        ]);
    } else {
        throw new Exception('Client not found.');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>