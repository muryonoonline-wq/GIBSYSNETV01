<?php
// Test database connection
$host = 'localhost';
$dbname = 'gibsysnet';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'clients'");
    
    if ($stmt->rowCount() > 0) {
        echo "✓ Database connected successfully\n";
        echo "✓ Table 'clients' exists\n";
        
        // Count records
        $count = $conn->query("SELECT COUNT(*) as total FROM clients")->fetch();
        echo "✓ Total records: " . $count['total'] . "\n";
        
        // Show column names
        $columns = $conn->query("DESCRIBE clients")->fetchAll();
        echo "✓ Columns: ";
        $colNames = [];
        foreach ($columns as $col) {
            $colNames[] = $col['Field'];
        }
        echo implode(', ', $colNames);
        
    } else {
        echo "✓ Database connected successfully\n";
        echo "✗ Table 'clients' doesn't exist\n";
        echo "Please run database-setup.php first";
    }
    
} catch(PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage();
}
?>