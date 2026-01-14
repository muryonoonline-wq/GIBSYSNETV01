<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $client_ids = $data['client_ids'] ?? [];

    if (empty($client_ids)) {
        throw new Exception('No clients selected for restore');
    }

    // Convert array to comma-separated string for IN clause
    $placeholders = str_repeat('?,', count($client_ids) - 1) . '?';
    
    // Restore multiple clients
    $stmt = $conn->prepare("
        UPDATE clients SET 
            is_deleted = 0,
            deleted_at = NULL,
            delete_reason = NULL,
            status = 'Active'
        WHERE id IN ($placeholders)
    ");
    
    $stmt->execute($client_ids);
    $restored_count = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully restored $restored_count client(s).",
        'restored_count' => $restored_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>