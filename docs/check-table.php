<?php
// Check table structure
$host = 'localhost';
$dbname = 'gibsysnet';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    $result = $conn->query("DESCRIBE clients");
    $output = "Table Structure:\n\n";
    
    foreach ($result as $row) {
        $output .= "Field: " . $row['Field'] . "\n";
        $output .= "Type: " . $row['Type'] . "\n";
        $output .= "Null: " . $row['Null'] . "\n";
        $output .= "Key: " . $row['Key'] . "\n";
        $output .= "Default: " . $row['Default'] . "\n";
        $output .= "Extra: " . $row['Extra'] . "\n";
        $output .= "-----------------\n";
    }
    
    echo $output;
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>