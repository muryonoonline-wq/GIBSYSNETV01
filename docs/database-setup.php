<?php
// File ini hanya untuk setup database sekali saja
// Hapus atau rename setelah setup selesai

$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Setup for GIBSYSNET</h2>";
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS gibsysnet 
                 CHARACTER SET utf8mb4 
                 COLLATE utf8mb4_unicode_ci");
    
    echo "✓ Database 'gibsysnet' created/checked<br>";
    
    // Use the database
    $conn->exec("USE gibsysnet");
    
    // Drop existing tables if they exist
    $conn->exec("DROP TABLE IF EXISTS client_documents");
    $conn->exec("DROP TABLE IF EXISTS client_contacts");
    $conn->exec("DROP TABLE IF EXISTS clients");
    
    echo "✓ Old tables cleaned up<br>";
    
    // Create clients table
    $sql = "CREATE TABLE clients (
        id INT PRIMARY KEY AUTO_INCREMENT,
        client_code VARCHAR(20) UNIQUE NOT NULL,
        client_name VARCHAR(100) NOT NULL,
        client_type ENUM('Client', 'Agent', 'Marketing', 'Partner') NOT NULL,
        category ENUM('Corporate', 'Individual', 'Government', 'Other') DEFAULT 'Corporate',
        address TEXT,
        city VARCHAR(50),
        country VARCHAR(50) DEFAULT 'Indonesia',
        phone VARCHAR(20),
        mobile VARCHAR(20),
        email VARCHAR(100),
        contact_person VARCHAR(100),
        npwp VARCHAR(25),
        join_date DATE,
        status ENUM('Active', 'Inactive', 'Pending') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql);
    echo "✓ Table 'clients' created<br>";
    
    // Insert sample data
    $sql = "INSERT INTO clients (client_code, client_name, client_type, category, address, city, phone, email, contact_person, npwp, join_date, status) VALUES
        ('CL001', 'PT. Sejahtera Abadi', 'Client', 'Corporate', 'Jl. Sudirman No. 123, Jakarta', 'Jakarta', '021-55667788', 'info@sejahtera.com', 'Budi Santoso', '01.234.567.8-912.000', '2023-01-15', 'Active'),
        ('AG001', 'Global Insurance Agency', 'Agent', 'Corporate', 'Jl. Thamrin No. 45, Bandung', 'Bandung', '022-77889900', 'contact@globalins.com', 'Siti Aminah', '02.345.678.9-013.000', '2023-03-20', 'Active'),
        ('MK001', 'Premium Marketing Group', 'Marketing', 'Corporate', 'Jl. Melati No. 10, Surabaya', 'Surabaya', '031-11223344', 'sales@premium.com', 'Dian Purnama', '03.456.789.0-114.000', '2023-05-10', 'Active'),
        ('PT001', 'Strategic Business Partner', 'Partner', 'Corporate', 'Jl. Gatot Subroto No. 89, Medan', 'Medan', '061-44556677', 'partner@strategic.com', 'Rudi Hartono', '04.567.890.1-215.000', '2023-02-28', 'Active'),
        ('CL002', 'John Smith', 'Client', 'Individual', 'Jl. Merdeka No. 56, Bali', 'Bali', '0361-998877', 'john@email.com', 'John Smith', '05.678.901.2-316.000', '2023-06-15', 'Active'),
        ('AG002', 'Silver Star Agency', 'Agent', 'Corporate', 'Jl. Pahlawan No. 78, Yogyakarta', 'Yogyakarta', '0274-556677', 'info@silverstar.com', 'Linda Wijaya', '06.789.012.3-417.000', '2023-04-10', 'Inactive')";
    
    $conn->exec($sql);
    echo "✓ Sample data inserted<br>";
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ Database setup completed successfully!</h3>";
    echo "<p>Now you can access <a href='master-data-client.php'>master-data-client.php</a></p>";
    echo "<p><strong>Note:</strong> Delete or rename this file for security.</p>";
    
} catch(PDOException $e) {
    echo "<h3 style='color: red;'>Setup Failed</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure MySQL is running and credentials are correct.</p>";
}
?>