<?php
// master-cob.php - COB Insurance Product Management System
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
date_default_timezone_set('Asia/Jakarta');

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
        die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
    }
}

// ============================================
// AJAX HANDLERS
// ============================================

// Generate Product Code AJAX
if (isset($_GET['generate_code']) && $_GET['generate_code'] === 'true') {
    header('Content-Type: application/json');
    
    try {
        $type = isset($_GET['type']) ? trim($_GET['type']) : 'general';
        $category = isset($_GET['category']) ? trim($_GET['category']) : 'property';
        $sub_category = isset($_GET['sub_category']) ? trim($_GET['sub_category']) : 'fire';
        
        // Mapping untuk prefix
        $typePrefix = $type === 'general' ? 'GEN' : 'LIFE';
        
        // Format: GEN-PRO-FIR-001
        $prefix = $typePrefix . '-' . 
                 strtoupper(substr($category, 0, 3)) . '-' . 
                 strtoupper(substr($sub_category, 0, 3));
        
        // Cari nomor terakhir dengan lebih aman
        $stmt = $conn->prepare("SELECT MAX(product_code) as max_code FROM cob_products 
                               WHERE product_code LIKE ? AND is_deleted = 0");
        $likePattern = $prefix . '%';
        $stmt->execute([$likePattern]);
        $result = $stmt->fetch();
        
        if ($result && $result['max_code']) {
            $lastCode = $result['max_code'];
            // Ekstrak angka terakhir dengan regex
            preg_match('/-(\d+)$/', $lastCode, $matches);
            $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
            $nextNumber = $lastNumber + 1;
            $productCode = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } else {
            $productCode = $prefix . '-001';
        }
        
        echo json_encode([
            'success' => true,
            'code' => $productCode
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error generating code: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Get Product Details AJAX
if (isset($_GET['get_product']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    try {
        $id = $_GET['id'];
        
        $stmt = $conn->prepare("
            SELECT * FROM cob_products 
            WHERE id = ? AND is_deleted = 0
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        if ($product) {
            echo json_encode([
                'success' => true,
                'product' => $product
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found'
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

// Get Categories by Type AJAX
if (isset($_GET['get_categories']) && isset($_GET['type'])) {
    header('Content-Type: application/json');
    
    try {
        $type = $_GET['type'];
        
        // Static product structure
        $productStructure = [
            'general' => [
                'name' => 'General Insurance',
                'categories' => [
                    'property' => [
                        'name' => 'Property Insurance',
                        'description' => 'Covers property damage and loss'
                    ],
                    'motor' => [
                        'name' => 'Motor Insurance',
                        'description' => 'Covers vehicles against damage and theft'
                    ],
                    'marine' => [
                        'name' => 'Marine Insurance',
                        'description' => 'Covers ships, cargo, and maritime liabilities'
                    ],
                    'miscellaneous' => [
                        'name' => 'Miscellaneous Insurance',
                        'description' => 'Various specialized insurance products'
                    ]
                ]
            ],
            'life' => [
                'name' => 'Life Insurance',
                'categories' => [
                    'traditional' => [
                        'name' => 'Traditional Life Insurance',
                        'description' => 'Basic life insurance with fixed benefits'
                    ],
                    'investment' => [
                        'name' => 'Investment Linked Insurance',
                        'description' => 'Life insurance with investment components'
                    ],
                    'health' => [
                        'name' => 'Health & Medical Insurance',
                        'description' => 'Covers medical expenses and health treatments'
                    ],
                    'pension' => [
                        'name' => 'Pension & Annuity',
                        'description' => 'Retirement and pension plans'
                    ]
                ]
            ]
        ];
        
        if (isset($productStructure[$type])) {
            echo json_encode([
                'success' => true,
                'categories' => $productStructure[$type]['categories']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid insurance type'
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

// Get Sub Categories by Type and Category AJAX
if (isset($_GET['get_sub_categories']) && isset($_GET['type']) && isset($_GET['category'])) {
    header('Content-Type: application/json');
    
    try {
        $type = $_GET['type'];
        $category = $_GET['category'];
        
        // Static product structure
        $productStructure = [
            'general' => [
                'property' => [
                    'fire' => [
                        'name' => 'Fire Insurance',
                        'description' => 'Covers damage caused by fire, lightning, and explosion'
                    ],
                    'burglary' => [
                        'name' => 'Burglary Insurance',
                        'description' => 'Covers theft and burglary of property'
                    ],
                    'earthquake' => [
                        'name' => 'Earthquake Insurance',
                        'description' => 'Covers damage caused by earthquakes'
                    ],
                    'flood' => [
                        'name' => 'Flood Insurance',
                        'description' => 'Covers damage caused by floods and water damage'
                    ],
                    'machinery' => [
                        'name' => 'Machinery Breakdown Insurance',
                        'description' => 'Covers breakdown of machinery and equipment'
                    ]
                ],
                'motor' => [
                    'comprehensive' => [
                        'name' => 'Comprehensive Motor Insurance',
                        'description' => 'Full coverage for vehicles including third party liability'
                    ],
                    'tlo' => [
                        'name' => 'TLO (Total Loss Only)',
                        'description' => 'Covers only total loss of vehicle'
                    ],
                    'third_party' => [
                        'name' => 'Third Party Liability',
                        'description' => 'Covers liability to third parties only'
                    ]
                ],
                'marine' => [
                    'cargo' => [
                        'name' => 'Marine Cargo Insurance',
                        'description' => 'Covers goods during transportation by sea'
                    ],
                    'hull' => [
                        'name' => 'Hull Insurance',
                        'description' => 'Covers physical damage to ships and vessels'
                    ],
                    'liability' => [
                        'name' => 'Marine Liability Insurance',
                        'description' => 'Covers maritime liabilities and claims'
                    ]
                ],
                'miscellaneous' => [
                    'engineering' => [
                        'name' => 'Engineering Insurance',
                        'description' => 'Covers engineering projects and constructions'
                    ],
                    'aviation' => [
                        'name' => 'Aviation Insurance',
                        'description' => 'Covers aircraft and aviation risks'
                    ],
                    'bond' => [
                        'name' => 'Bond Insurance',
                        'description' => 'Covers financial guarantees and bonds'
                    ],
                    'credit' => [
                        'name' => 'Credit Insurance',
                        'description' => 'Covers credit risks and defaults'
                    ]
                ]
            ],
            'life' => [
                'traditional' => [
                    'whole_life' => [
                        'name' => 'Whole Life Insurance',
                        'description' => 'Life insurance coverage for entire lifetime'
                    ],
                    'endowment' => [
                        'name' => 'Endowment Insurance',
                        'description' => 'Combines savings with life insurance coverage'
                    ],
                    'term' => [
                        'name' => 'Term Life Insurance',
                        'description' => 'Temporary life insurance for specific period'
                    ]
                ],
                'investment' => [
                    'unit_link' => [
                        'name' => 'Unit Link Insurance',
                        'description' => 'Investment-linked life insurance products'
                    ],
                    'variable' => [
                        'name' => 'Variable Life Insurance',
                        'description' => 'Life insurance with variable investment returns'
                    ]
                ],
                'health' => [
                    'health' => [
                        'name' => 'Health Insurance',
                        'description' => 'Covers medical and hospitalization expenses'
                    ],
                    'critical_illness' => [
                        'name' => 'Critical Illness Insurance',
                        'description' => 'Covers specific critical illnesses'
                    ],
                    'hospital' => [
                        'name' => 'Hospital Cash Insurance',
                        'description' => 'Provides daily cash during hospitalization'
                    ]
                ],
                'pension' => [
                    'annuity' => [
                        'name' => 'Annuity Insurance',
                        'description' => 'Provides regular income after retirement'
                    ],
                    'pension' => [
                        'name' => 'Pension Plan',
                        'description' => 'Retirement savings and pension scheme'
                    ]
                ]
            ]
        ];
        
        if (isset($productStructure[$type][$category])) {
            echo json_encode([
                'success' => true,
                'sub_categories' => $productStructure[$type][$category]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid category'
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

// Check Product Name Duplicate AJAX
if (isset($_GET['check_duplicate']) && isset($_GET['product_name'])) {
    header('Content-Type: application/json');
    
    try {
        $product_name = $_GET['product_name'];
        $product_id = $_GET['product_id'] ?? null;
        
        $sql = "SELECT COUNT(*) as count FROM cob_products 
                WHERE product_name = ? AND is_deleted = 0";
        $params = [$product_name];
        
        if ($product_id) {
            $sql .= " AND id != ?";
            $params[] = $product_id;
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            echo json_encode([
                'success' => true,
                'is_duplicate' => true,
                'message' => 'Product name already exists'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'is_duplicate' => false,
                'message' => 'Product name is available'
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

// Delete Product AJAX (Soft Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_delete'])) {
    header('Content-Type: application/json');
    
    try {
        $id = $_POST['product_id'];
        $reason = $_POST['delete_reason'] ?? 'No reason provided';
        $current_user = $_SESSION['user_id'] ?? 'admin';
        
        $stmt = $conn->prepare("
            UPDATE cob_products SET 
                is_deleted = 1,
                deleted_at = NOW(),
                delete_reason = ?,
                deleted_by = ?,
                is_active = 0
            WHERE id = ? AND is_deleted = 0
        ");
        
        $stmt->execute([$reason, $current_user, $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Product moved to trash successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found or already deleted'
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

// Restore Product AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_restore'])) {
    header('Content-Type: application/json');
    
    try {
        $id = $_POST['product_id'];
        $current_user = $_SESSION['user_id'] ?? 'admin';
        
        $stmt = $conn->prepare("
            UPDATE cob_products SET 
                is_deleted = 0,
                restored_at = NOW(),
                restored_by = ?,
                delete_reason = NULL,
                deleted_at = NULL,
                deleted_by = NULL,
                is_active = 1
            WHERE id = ? AND is_deleted = 1
        ");
        
        $stmt->execute([$current_user, $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Product restored successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found or not in trash'
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

// Permanent Delete AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_permanent_delete'])) {
    header('Content-Type: application/json');
    
    try {
        $id = $_POST['product_id'];
        $current_user = $_SESSION['user_id'] ?? 'admin';
        
        // Check if product is already soft deleted
        $checkStmt = $conn->prepare("SELECT is_deleted FROM cob_products WHERE id = ?");
        $checkStmt->execute([$id]);
        $product = $checkStmt->fetch();
        
        if (!$product) {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found'
            ]);
            exit();
        }
        
        if ($product['is_deleted'] == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Product must be soft deleted first before permanent deletion'
            ]);
            exit();
        }
        
        // Delete permanently
        $stmt = $conn->prepare("DELETE FROM cob_products WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Product permanently deleted'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete product'
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

// Toggle Product Status AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    header('Content-Type: application/json');
    
    try {
        $id = $_POST['product_id'];
        $current_user = $_SESSION['user_id'] ?? 'admin';
        
        $stmt = $conn->prepare("
            UPDATE cob_products SET 
                is_active = NOT is_active,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ? AND is_deleted = 0
        ");
        
        $stmt->execute([$current_user, $id]);
        
        if ($stmt->rowCount() > 0) {
            // Get new status
            $statusStmt = $conn->prepare("SELECT is_active FROM cob_products WHERE id = ?");
            $statusStmt->execute([$id]);
            $status = $statusStmt->fetch()['is_active'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Product status updated successfully',
                'is_active' => $status
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found'
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

// Export to CSV AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv'])) {
    try {
        // Fetch all products
        $stmt = $conn->prepare("
            SELECT 
                p.product_code,
                p.product_name,
                p.type,
                p.category,
                p.sub_category,
                p.description,
                p.is_active,
                p.created_at,
                p.created_by,
                p.updated_at,
                p.updated_by
            FROM cob_products p
            WHERE p.is_deleted = 0
            ORDER BY p.type, p.category, p.sub_category, p.product_code
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=COB_Products_' . date('Y-m-d_H-i-s') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        // Add headers
        fputcsv($output, [
            'Product Code',
            'Product Name',
            'Type',
            'Category',
            'Sub-category',
            'Description',
            'Status',
            'Created Date',
            'Created By',
            'Updated Date',
            'Updated By'
        ]);
        
        // Add data
        foreach ($products as $product) {
            $status = $product['is_active'] ? 'Active' : 'Inactive';
            $type = $product['type'] === 'general' ? 'General Insurance' : 'Life Insurance';
            
            fputcsv($output, [
                $product['product_code'],
                $product['product_name'],
                $type,
                $product['category'],
                $product['sub_category'],
                $product['description'] ?? '',
                $status,
                $product['created_at'],
                $product['created_by'] ?? 'System',
                $product['updated_at'] ?? '',
                $product['updated_by'] ?? ''
            ]);
        }
        
        fclose($output);
        exit();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Export error: ' . $e->getMessage()
        ]);
        exit();
    }
}

// Export to Excel AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_excel'])) {
    try {
        // Fetch all products
        $stmt = $conn->prepare("
            SELECT 
                p.product_code,
                p.product_name,
                p.type,
                p.category,
                p.sub_category,
                p.description,
                p.is_active,
                p.created_at,
                p.created_by,
                p.updated_at,
                p.updated_by
            FROM cob_products p
            WHERE p.is_deleted = 0
            ORDER BY p.type, p.category, p.sub_category, p.product_code
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        // Create HTML table for Excel
        $html = '<table border="1">';
        $html .= '<tr>';
        $html .= '<th>No</th>';
        $html .= '<th>Product Code</th>';
        $html .= '<th>Product Name</th>';
        $html .= '<th>Type</th>';
        $html .= '<th>Category</th>';
        $html .= '<th>Sub-category</th>';
        $html .= '<th>Description</th>';
        $html .= '<th>Status</th>';
        $html .= '<th>Created Date</th>';
        $html .= '<th>Created By</th>';
        $html .= '<th>Updated Date</th>';
        $html .= '<th>Updated By</th>';
        $html .= '</tr>';
        
        $counter = 1;
        foreach ($products as $product) {
            $status = $product['is_active'] ? 'Active' : 'Inactive';
            $type = $product['type'] === 'general' ? 'General Insurance' : 'Life Insurance';
            
            $html .= '<tr>';
            $html .= '<td>' . $counter++ . '</td>';
            $html .= '<td>' . htmlspecialchars($product['product_code']) . '</td>';
            $html .= '<td>' . htmlspecialchars($product['product_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($type) . '</td>';
            $html .= '<td>' . htmlspecialchars($product['category']) . '</td>';
            $html .= '<td>' . htmlspecialchars($product['sub_category']) . '</td>';
            $html .= '<td>' . htmlspecialchars($product['description'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($status) . '</td>';
            $html .= '<td>' . htmlspecialchars($product['created_at']) . '</td>';
            $html .= '<td>' . htmlspecialchars($product['created_by'] ?? 'System') . '</td>';
            $html .= '<td>' . htmlspecialchars($product['updated_at'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($product['updated_by'] ?? '') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="COB_Products_' . date('Y-m-d_H-i-s') . '.xls"');
        
        echo $html;
        exit();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Export error: ' . $e->getMessage()
        ]);
        exit();
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function safe_html($value) {
    return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}

function formatDate($date) {
    if (empty($date) || $date == '0000-00-00 00:00:00') return 'N/A';
    return date('d M Y, H:i', strtotime($date));
}

// Insurance Product Structure
$productStructure = [
    'general' => [
        'name' => 'General Insurance',
        'categories' => [
            'property' => [
                'name' => 'Property Insurance',
                'sub_categories' => [
                    'fire' => [
                        'name' => 'Fire Insurance',
                        'description' => 'Covers damage caused by fire, lightning, and explosion'
                    ],
                    'burglary' => [
                        'name' => 'Burglary Insurance',
                        'description' => 'Covers theft and burglary of property'
                    ],
                    'earthquake' => [
                        'name' => 'Earthquake Insurance',
                        'description' => 'Covers damage caused by earthquakes'
                    ],
                    'flood' => [
                        'name' => 'Flood Insurance',
                        'description' => 'Covers damage caused by floods and water damage'
                    ],
                    'machinery' => [
                        'name' => 'Machinery Breakdown Insurance',
                        'description' => 'Covers breakdown of machinery and equipment'
                    ]
                ]
            ],
            'motor' => [
                'name' => 'Motor Insurance',
                'sub_categories' => [
                    'comprehensive' => [
                        'name' => 'Comprehensive Motor Insurance',
                        'description' => 'Full coverage for vehicles including third party liability'
                    ],
                    'tlo' => [
                        'name' => 'TLO (Total Loss Only)',
                        'description' => 'Covers only total loss of vehicle'
                    ],
                    'third_party' => [
                        'name' => 'Third Party Liability',
                        'description' => 'Covers liability to third parties only'
                    ]
                ]
            ],
            'marine' => [
                'name' => 'Marine Insurance',
                'sub_categories' => [
                    'cargo' => [
                        'name' => 'Marine Cargo Insurance',
                        'description' => 'Covers goods during transportation by sea'
                    ],
                    'hull' => [
                        'name' => 'Hull Insurance',
                        'description' => 'Covers physical damage to ships and vessels'
                    ],
                    'liability' => [
                        'name' => 'Marine Liability Insurance',
                        'description' => 'Covers maritime liabilities and claims'
                    ]
                ]
            ],
            'miscellaneous' => [
                'name' => 'Miscellaneous Insurance',
                'sub_categories' => [
                    'engineering' => [
                        'name' => 'Engineering Insurance',
                        'description' => 'Covers engineering projects and constructions'
                    ],
                    'aviation' => [
                        'name' => 'Aviation Insurance',
                        'description' => 'Covers aircraft and aviation risks'
                    ],
                    'bond' => [
                        'name' => 'Bond Insurance',
                        'description' => 'Covers financial guarantees and bonds'
                    ],
                    'credit' => [
                        'name' => 'Credit Insurance',
                        'description' => 'Covers credit risks and defaults'
                    ]
                ]
            ]
        ]
    ],
    'life' => [
        'name' => 'Life Insurance',
        'categories' => [
            'traditional' => [
                'name' => 'Traditional Life Insurance',
                'sub_categories' => [
                    'whole_life' => [
                        'name' => 'Whole Life Insurance',
                        'description' => 'Life insurance coverage for entire lifetime'
                    ],
                    'endowment' => [
                        'name' => 'Endowment Insurance',
                        'description' => 'Combines savings with life insurance coverage'
                    ],
                    'term' => [
                        'name' => 'Term Life Insurance',
                        'description' => 'Temporary life insurance for specific period'
                    ]
                ]
            ],
            'investment' => [
                'name' => 'Investment Linked Insurance',
                'sub_categories' => [
                    'unit_link' => [
                        'name' => 'Unit Link Insurance',
                        'description' => 'Investment-linked life insurance products'
                    ],
                    'variable' => [
                        'name' => 'Variable Life Insurance',
                        'description' => 'Life insurance with variable investment returns'
                    ]
                ]
            ],
            'health' => [
                'name' => 'Health & Medical Insurance',
                'sub_categories' => [
                    'health' => [
                        'name' => 'Health Insurance',
                        'description' => 'Covers medical and hospitalization expenses'
                    ],
                    'critical_illness' => [
                        'name' => 'Critical Illness Insurance',
                        'description' => 'Covers specific critical illnesses'
                    ],
                    'hospital' => [
                        'name' => 'Hospital Cash Insurance',
                        'description' => 'Provides daily cash during hospitalization'
                    ]
                ]
            ],
            'pension' => [
                'name' => 'Pension & Annuity',
                'sub_categories' => [
                    'annuity' => [
                        'name' => 'Annuity Insurance',
                        'description' => 'Provides regular income after retirement'
                    ],
                    'pension' => [
                        'name' => 'Pension Plan',
                        'description' => 'Retirement savings and pension scheme'
                    ]
                ]
            ]
        ]
    ]
];

function getProductTypeName($type, $structure) {
    return isset($structure[$type]['name']) ? $structure[$type]['name'] : ucfirst($type);
}

function getCategoryName($type, $category, $structure) {
    return isset($structure[$type]['categories'][$category]['name']) ? 
           $structure[$type]['categories'][$category]['name'] : ucfirst(str_replace('_', ' ', $category));
}

function getSubCategoryName($type, $category, $sub_category, $structure) {
    return isset($structure[$type]['categories'][$category]['sub_categories'][$sub_category]['name']) ? 
           $structure[$type]['categories'][$category]['sub_categories'][$sub_category]['name'] : ucfirst(str_replace('_', ' ', $sub_category));
}

function getSubCategoryDescription($type, $category, $sub_category, $structure) {
    return isset($structure[$type]['categories'][$category]['sub_categories'][$sub_category]['description']) ? 
           $structure[$type]['categories'][$category]['sub_categories'][$sub_category]['description'] : '';
}

function generateProductName($type, $category, $sub_category, $structure) {
    $category_name = isset($structure[$type]['categories'][$category]['name']) 
        ? $structure[$type]['categories'][$category]['name'] 
        : ucfirst(str_replace('_', ' ', $category));
    
    $sub_category_name = isset($structure[$type]['categories'][$category]['sub_categories'][$sub_category]['name'])
        ? $structure[$type]['categories'][$category]['sub_categories'][$sub_category]['name']
        : ucfirst(str_replace('_', ' ', $sub_category));
    
    return $category_name . ' - ' . $sub_category_name;
}

// ============================================
// FORM SUBMISSIONS
// ============================================

// Add New Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    try {
        $type = $_POST['type'] ?? '';
        $category = $_POST['category'] ?? '';
        $sub_category = $_POST['sub_category'] ?? '';
        $description = $_POST['description'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $current_user = $_SESSION['user_id'] ?? 'admin';
        
        if (empty($type) || empty($category) || empty($sub_category)) {
            $error_message = "Type, Category, and Sub-category are required fields.";
        } else {
            // Generate product code
            $typePrefix = $type === 'general' ? 'GEN' : 'LIFE';
            $prefix = $typePrefix . '-' . 
                     strtoupper(substr($category, 0, 3)) . '-' . 
                     strtoupper(substr($sub_category, 0, 3));
            
            $stmt = $conn->prepare("SELECT MAX(product_code) as max_code FROM cob_products 
                                   WHERE product_code LIKE ? AND is_deleted = 0");
            $likePattern = $prefix . '%';
            $stmt->execute([$likePattern]);
            $result = $stmt->fetch();
            
            if ($result && $result['max_code']) {
                $lastCode = $result['max_code'];
                preg_match('/-(\d+)$/', $lastCode, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
                $product_code = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            } else {
                $product_code = $prefix . '-001';
            }
            
            $product_name = generateProductName($type, $category, $sub_category, $productStructure);
            
            // Check if product name already exists
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM cob_products 
                                        WHERE product_name = ? AND is_deleted = 0");
            $checkStmt->execute([$product_name]);
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                $error_message = "Product name '" . $product_name . "' already exists! Please choose a different combination.";
            } else {
                // Generate unique ID
                $id = uniqid('cob_');
                
                $insertStmt = $conn->prepare("
                    INSERT INTO cob_products (
                        id, type, category, sub_category, product_name, 
                        product_code, description, is_active, created_by, created_at, is_deleted
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)
                ");
                
                $insertStmt->execute([
                    $id, $type, $category, $sub_category, $product_name,
                    $product_code, $description, $is_active, $current_user
                ]);
                
                $success_message = "Product successfully added with code: $product_code!";
                // Redirect to refresh the page and show new data
                echo "<script>window.location.href = window.location.href;</script>";
                exit();
            }
        }
    } catch (PDOException $e) {
        // Check for duplicate entry error
        if ($e->getCode() == 23000) {
            if (strpos($e->getMessage(), 'product_name') !== false) {
                $error_message = "Product name already exists!";
            } elseif (strpos($e->getMessage(), 'product_code') !== false) {
                $error_message = "Product code already exists!";
            } else {
                $error_message = "Duplicate entry error!";
            }
        } else {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    try {
        $id = $_POST['product_id'] ?? '';
        $type = $_POST['type'] ?? '';
        $category = $_POST['category'] ?? '';
        $sub_category = $_POST['sub_category'] ?? '';
        $description = $_POST['description'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $current_user = $_SESSION['user_id'] ?? 'admin';
        
        if (empty($id) || empty($type) || empty($category) || empty($sub_category)) {
            $error_message = "Required fields are missing.";
        } else {
            // Check if the new combination already exists for other product
            $new_product_name = generateProductName($type, $category, $sub_category, $productStructure);
            
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM cob_products 
                                        WHERE product_name = ? AND id != ? AND is_deleted = 0");
            $checkStmt->execute([$new_product_name, $id]);
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                $error_message = "Product name '" . $new_product_name . "' already exists for another product!";
            } else {
                $updateStmt = $conn->prepare("
                    UPDATE cob_products SET 
                        type = ?,
                        category = ?,
                        sub_category = ?,
                        product_name = ?,
                        description = ?,
                        is_active = ?,
                        updated_by = ?,
                        updated_at = NOW()
                    WHERE id = ? AND is_deleted = 0
                ");
                
                $updateStmt->execute([
                    $type, $category, $sub_category, $new_product_name,
                    $description, $is_active, $current_user, $id
                ]);
                
                if ($updateStmt->rowCount() > 0) {
                    $success_message = "Product successfully updated!";
                    echo "<script>window.location.href = window.location.href;</script>";
                    exit();
                } else {
                    $error_message = "Product not found or already deleted.";
                }
            }
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// ============================================
// DATABASE SETUP & DATA FETCHING
// ============================================

// Check if table exists, create if not with additional columns for soft delete
try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'cob_products'");
    if ($checkTable->rowCount() == 0) {
        $createTableSQL = "CREATE TABLE cob_products (
            id VARCHAR(50) PRIMARY KEY,
            type ENUM('general', 'life') NOT NULL,
            category VARCHAR(50) NOT NULL,
            sub_category VARCHAR(50) NOT NULL,
            product_name VARCHAR(200) NOT NULL UNIQUE,
            product_code VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            is_deleted BOOLEAN DEFAULT FALSE,
            delete_reason TEXT,
            deleted_at TIMESTAMP NULL,
            deleted_by VARCHAR(50),
            restored_at TIMESTAMP NULL,
            restored_by VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by VARCHAR(50),
            updated_by VARCHAR(50),
            INDEX idx_type (type),
            INDEX idx_category (category),
            INDEX idx_sub_category (sub_category),
            INDEX idx_active (is_active),
            INDEX idx_deleted (is_deleted),
            INDEX idx_deleted_at (deleted_at),
            INDEX idx_product_name (product_name),
            INDEX idx_product_code (product_code)
        )";
        $conn->exec($createTableSQL);
    } else {
        // Check if new columns exist, add if not
        $checkColumns = $conn->query("SHOW COLUMNS FROM cob_products");
        $existingColumns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
        
        $columnsToAdd = [
            'restored_at' => "ALTER TABLE cob_products ADD COLUMN restored_at TIMESTAMP NULL AFTER deleted_by",
            'restored_by' => "ALTER TABLE cob_products ADD COLUMN restored_by VARCHAR(50) AFTER restored_at",
            'product_name_unique' => "ALTER TABLE cob_products ADD UNIQUE INDEX idx_product_name_unique (product_name)"
        ];
        
        foreach ($columnsToAdd as $column => $sql) {
            if (!in_array(str_replace('_unique', '', $column), $existingColumns) && $column !== 'product_name_unique') {
                $conn->exec($sql);
            }
        }
    }
} catch (Exception $e) {
    error_log("Table creation error: " . $e->getMessage());
}

// Fetch all active products (non-deleted)
$stmt = $conn->prepare("
    SELECT 
        id,
        type,
        category,
        sub_category,
        product_name,
        product_code,
        description,
        is_active,
        is_deleted,
        created_at,
        updated_at,
        created_by,
        updated_by
    FROM cob_products 
    WHERE is_deleted = 0 
    ORDER BY type, category, sub_category, product_code ASC
");
$stmt->execute();
$products = $stmt->fetchAll();
$total_products = count($products);

// Get counts
$active_count = $conn->query("SELECT COUNT(*) as count FROM cob_products WHERE is_active = 1 AND is_deleted = 0")->fetch()['count'] ?? 0;
$inactive_count = $conn->query("SELECT COUNT(*) as count FROM cob_products WHERE is_active = 0 AND is_deleted = 0")->fetch()['count'] ?? 0;
$general_count = $conn->query("SELECT COUNT(*) as count FROM cob_products WHERE type = 'general' AND is_deleted = 0")->fetch()['count'] ?? 0;
$life_count = $conn->query("SELECT COUNT(*) as count FROM cob_products WHERE type = 'life' AND is_deleted = 0")->fetch()['count'] ?? 0;
$deleted_count = $conn->query("SELECT COUNT(*) as count FROM cob_products WHERE is_deleted = 1")->fetch()['count'] ?? 0;

// Set default for super admin
$is_super_admin = isset($_SESSION['is_super_admin']) ? $_SESSION['is_super_admin'] : true;
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COB Insurance Product Management | GIBSYSNET</title>
    
    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Glassmorphism & Modern Styling -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #ff5858 0%, #f09819 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 25px 50px rgba(0, 0, 0, 0.25);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #2c3e50;
            overflow-x: hidden;
        }
        
        /* Top Navigation Bar */
        .top-nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 25px 30px;
            box-shadow: var(--shadow-lg);
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
        
        .glass-card-solid {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
        }
        
        .nav-glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 16px 0 0 16px;
        }
        
        .stats-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }
        
        .stats-card.total::before { background: var(--primary-gradient); }
        .stats-card.active::before { background: var(--success-gradient); }
        .stats-card.inactive::before { background: var(--warning-gradient); }
        .stats-card.general::before { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
        .stats-card.life::before { background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); }
        .stats-card.deleted::before { background: var(--danger-gradient); }
        
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
        
        .stats-icon.total { background: var(--primary-gradient); }
        .stats-icon.active { background: var(--success-gradient); }
        .stats-icon.inactive { background: var(--warning-gradient); }
        .stats-icon.general { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
        .stats-icon.life { background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); }
        .stats-icon.deleted { background: var(--danger-gradient); }
        
        .stats-number {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        
        /* Badges */
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
        
        .status-active {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.15) 0%, rgba(39, 174, 96, 0.15) 100%);
            color: #27ae60;
            border: 2px solid rgba(39, 174, 96, 0.3);
        }
        
        .status-inactive {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.15) 0%, rgba(192, 57, 43, 0.15) 100%);
            color: #e74c3c;
            border: 2px solid rgba(231, 76, 60, 0.3);
        }
        
        .status-deleted {
            background: linear-gradient(135deg, rgba(149, 165, 166, 0.15) 0%, rgba(127, 140, 141, 0.15) 100%);
            color: #7f8c8d;
            border: 2px solid rgba(149, 165, 166, 0.3);
        }
        
        .type-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .type-general {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, rgba(41, 128, 185, 0.1) 100%);
            color: #2980b9;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .type-life {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1) 0%, rgba(39, 174, 96, 0.1) 100%);
            color: #27ae60;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        /* Buttons */
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
        
        .btn-restore {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-restore:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(46, 204, 113, 0.4);
        }
        
        /* Table */
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
        
        /* Action Buttons */
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
            text-decoration: none;
            cursor: pointer;
        }
        
        .action-btn:hover {
            transform: translateY(-3px) scale(1.1);
            text-decoration: none;
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
        
        .btn-edit {
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.1) 0%, rgba(230, 126, 34, 0.1) 100%);
            color: #f39c12;
            border-color: rgba(243, 156, 18, 0.2);
        }
        
        .btn-edit:hover {
            background: #f39c12;
            color: white;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1) 0%, rgba(192, 57, 43, 0.1) 100%);
            color: #e74c3c;
            border-color: rgba(231, 76, 60, 0.2);
        }
        
        .btn-delete:hover {
            background: #e74c3c;
            color: white;
        }
        
        .btn-restore-sm {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1) 0%, rgba(39, 174, 96, 0.1) 100%);
            color: #27ae60;
            border-color: rgba(46, 204, 113, 0.2);
        }
        
        .btn-restore-sm:hover {
            background: #27ae60;
            color: white;
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
        
        /* Section Headers */
        .section-header {
            padding: 12px 20px;
            background: rgba(102, 126, 234, 0.08);
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .section-header h6 {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
        }
        
        /* Modal */
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
        
        /* Product Preview */
        .product-preview {
            background: rgba(102, 126, 234, 0.05);
            border: 2px dashed rgba(102, 126, 234, 0.3);
            border-radius: 16px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        
        .product-preview h5 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .product-preview p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        /* Loader */
        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Animation Classes */
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        .animate-slide-up {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Toast Notification */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
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
        }
    </style>
</head>
<body class="animate-fade-in">
    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Main Container -->
    <div class="container-fluid py-4 px-4 px-lg-5">
        <!-- Top Navigation Bar -->
        <div class="top-nav-bar glass-card">
            <div class="nav-left">
                <a href="../../docs/dashboard/dashboard-admin.html" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <div>
                    <h2 class="page-title">COB Insurance Product Management</h2>
                    <p class="page-subtitle">Comprehensive management of General and Life insurance products</p>
                </div>
            </div>
            <div class="nav-right">
                <button class="export-button" onclick="exportProductReport()">
                    <i class="fas fa-file-export"></i> Export Report
                </button>
            </div>
        </div>

        <!-- Header -->
        <nav class="nav-glass rounded-4 p-4 mb-5 animate-slide-up">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <div>
                    <h1 class="h2 fw-bold mb-1 d-flex align-items-center gap-3">
                        <div class="p-3 rounded-4" style="background: var(--primary-gradient);">
                            <i class="bi bi-box-seam text-white fs-4"></i>
                        </div>
                        <div>
                            <span class="text-gradient">COB Product Database</span>
                            <small class="d-block text-muted fs-6 fw-normal mt-1">
                                <i class="bi bi-boxes me-1"></i> Total <?= number_format($total_products) ?> active products
                            </small>
                        </div>
                    </h1>
                </div>
                
                <div class="d-flex flex-wrap gap-3">
                    <?php if ($is_super_admin): ?>
                    <a href="deleted-cob.php" class="btn btn-outline-warning d-flex align-items-center gap-2">
                        <i class="bi bi-trash"></i>
                        Deleted COB
                        <span class="badge bg-warning text-dark ms-1"><?= $deleted_count ?></span>
                    </a>
                    <?php endif; ?>
                    
                    <button type="button" class="btn-gradient" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-circle me-2"></i>Add New COB
                    </button>
                </div>
            </div>
        </nav>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5 animate-slide-up" style="animation-delay: 0.1s">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card total h-100">
                    <div class="stats-icon total">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div class="stats-number"><?= number_format($total_products) ?></div>
                    <div class="stats-label">Total Products</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-arrow-up text-success me-1"></i>
                        Managed in system
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card active h-100">
                    <div class="stats-icon active">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stats-number"><?= number_format($active_count) ?></div>
                    <div class="stats-label">Active COB</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-check-lg text-success me-1"></i>
                        Available for policies
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card general h-100">
                    <div class="stats-icon general">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="stats-number"><?= number_format($general_count) ?></div>
                    <div class="stats-label">General Insurance</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-shield-check me-1"></i>
                        Property, Motor, Marine
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card life h-100">
                    <div class="stats-icon life">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <div class="stats-number"><?= number_format($life_count) ?></div>
                    <div class="stats-label">Life Insurance</div>
                    <div class="text-muted mt-2 fs-7">
                        <i class="bi bi-heart me-1"></i>
                        Health, Investment, Pension
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section mb-4 animate-slide-up" style="animation-delay: 0.2s">
            <div class="row g-3 align-items-end">
                <div class="col-xl-4 col-lg-6">
                    <label class="form-label fw-semibold mb-2">
                        <i class="bi bi-search me-2"></i>Search Products
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control form-control-modern border-start-0" 
                               id="searchInput" placeholder="Search by name, code, description...">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label fw-semibold mb-2">Product Type</label>
                    <select class="form-select form-select-modern" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="general">General Insurance</option>
                        <option value="life">Life Insurance</option>
                    </select>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label fw-semibold mb-2">Category</label>
                    <select class="form-select form-select-modern" id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($productStructure as $type => $typeData): ?>
                            <?php foreach ($typeData['categories'] as $catKey => $catData): ?>
                                <option value="<?= $catKey ?>"><?= $catData['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label fw-semibold mb-2">Status</label>
                    <select class="form-select form-select-modern" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
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

        <!-- Messages -->
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= safe_html($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= safe_html($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Main Table Card -->
        <div class="glass-card-solid p-4 mb-4 animate-slide-up" style="animation-delay: 0.3s">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-0">Product List</h5>
                    <p class="text-muted mb-0">Showing <?= number_format($total_products) ?> active records</p>
                </div>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary d-flex align-items-center gap-2" id="exportExcelBtn">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                    <button class="btn btn-outline-danger d-flex align-items-center gap-2" id="exportPDFBtn">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                    <button class="btn btn-outline-success d-flex align-items-center gap-2" id="exportCSVBtn">
                        <i class="bi bi-file-earmark-text"></i> CSV
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table id="productsTable" class="table modern-table">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>PRODUCT CODE</th>
                            <th>PRODUCT NAME</th>
                            <th>TYPE</th>
                            <th>CATEGORY</th>
                            <th>SUB-CATEGORY</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $index => $row): ?>
                                <?php 
                                $productCode = safe_html($row['product_code'] ?? '');
                                $productName = safe_html($row['product_name'] ?? '');
                                $type = safe_html($row['type'] ?? '');
                                $category = safe_html($row['category'] ?? '');
                                $subCategory = safe_html($row['sub_category'] ?? '');
                                $description = safe_html($row['description'] ?? '');
                                $isActive = $row['is_active'];
                                ?>
                                <tr data-id="<?= $row['id'] ?>">
                                    <td class="fw-bold text-center"><?= $index + 1 ?></td>
                                    
                                    <td>
                                        <div class="fw-bold text-primary"><?= $productCode ?></div>
                                        <small class="text-muted">ID: <?= $row['id'] ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= $productName ?></div>
                                        <?php if ($description): ?>
                                            <small class="text-muted"><?= substr($description, 0, 60) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="type-badge type-<?= $type ?>">
                                            <?= getProductTypeName($type, $productStructure) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= getCategoryName($type, $category, $productStructure) ?>
                                    </td>
                                    <td>
                                        <?= getSubCategoryName($type, $category, $subCategory, $productStructure) ?>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="status-badge status-active">
                                                <i class="bi bi-check-circle-fill"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">
                                                <i class="bi bi-x-circle-fill"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn btn-view" 
                                                    onclick="viewProduct('<?= $row['id'] ?>')"
                                                    title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="action-btn btn-edit" 
                                                    onclick="editProduct('<?= $row['id'] ?>')"
                                                    title="Edit Product">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="action-btn <?= $isActive ? 'btn-delete' : 'btn-view' ?>" 
                                                    onclick="<?= $isActive ? 'toggleProductStatus(\'' . $row['id'] . '\', \'' . addslashes($productName) . '\', true)' : 'toggleProductStatus(\'' . $row['id'] . '\', \'' . addslashes($productName) . '\', false)' ?>"
                                                    title="<?= $isActive ? 'Deactivate Product' : 'Activate Product' ?>">
                                                <i class="bi <?= $isActive ? 'bi-pause' : 'bi-play' ?>"></i>
                                            </button>
                                            <button class="action-btn btn-delete" 
                                                    onclick="deleteProductPrompt('<?= $row['id'] ?>', '<?= addslashes($productName) ?>')"
                                                    title="Move to Trash">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state py-5 text-center">
                                        <div class="empty-state-icon mb-4">
                                            <i class="bi bi-box" style="font-size: 64px; opacity: 0.3;"></i>
                                        </div>
                                        <h4 class="fw-bold mb-3">No Products Found</h4>
                                        <p class="text-muted mb-4">No product records available. Start by adding your first insurance product.</p>
                                        <button type="button" class="btn-gradient" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                            <i class="bi bi-plus-circle me-2"></i>Add First Product
                                        </button>
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
                Showing active products only • 
                Last updated: <?= date('F j, Y, g:i a') ?> • 
                System: GIBSYSNET COB v2.0
            </p>
        </div>
    </div>

    <!-- ============================================
    MODALS
    ============================================ -->

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header modal-header-gradient">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Add New Product
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="addProductForm" onsubmit="return validateAddForm()">
                    <div class="modal-body p-4">
                        <!-- Insurance Type Section -->
                        <div class="section-header">
                            <h6><i class="bi bi-shield me-2"></i>Insurance Type *</h6>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <select class="form-select form-control-modern" 
                                        id="typeSelect" name="type" required>
                                    <option value="">Select Insurance Type</option>
                                    <option value="general">General Insurance</option>
                                    <option value="life">Life Insurance</option>
                                </select>
                            </div>
                        </div>

                        <!-- Category Section -->
                        <div class="section-header">
                            <h6><i class="bi bi-grid me-2"></i>Category *</h6>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <select class="form-select form-control-modern" 
                                        id="categorySelect" name="category" required disabled>
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                        </div>

                        <!-- Sub-category Section -->
                        <div class="section-header">
                            <h6><i class="bi bi-diagram-3 me-2"></i>Sub-category *</h6>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <select class="form-select form-control-modern" 
                                        id="subCategorySelect" name="sub_category" required disabled>
                                    <option value="">Select Sub-category</option>
                                </select>
                            </div>
                        </div>

                        <!-- Product Code Section -->
                        <div class="section-header">
                            <h6><i class="bi bi-hash me-2"></i>Product Code</h6>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-modern" 
                                       id="productCodeInput" name="product_code" readonly
                                       placeholder="Auto-generated">
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>Auto-generated based on selections
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Product Name Preview -->
                        <div class="product-preview" id="productPreview">
                            <div class="mb-3">
                                <i class="bi bi-box-seam fs-1 text-primary"></i>
                            </div>
                            <h5 id="productNameDisplay">No product name yet</h5>
                            <p class="mb-0" id="productDescriptionPreview">Select type, category and sub-category to preview</p>
                        </div>

                        <!-- Description Section -->
                        <div class="section-header">
                            <h6><i class="bi bi-card-text me-2"></i>Description</h6>
                        </div>
                        <div class="mb-4">
                            <textarea class="form-control form-control-modern" 
                                      name="description" id="description" rows="5" 
                                      placeholder="Enter product description..."></textarea>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-lightbulb me-1"></i>Describe the coverage, benefits, and key features of this insurance product
                                </small>
                            </div>
                        </div>

                        <!-- Active Product Toggle -->
                        <div class="section-header">
                            <h6><i class="bi bi-toggle-on me-2"></i>Product Status</h6>
                        </div>
                        <div class="mb-4">
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                <label class="form-check-label fw-bold ms-3" for="is_active">
                                    <span class="d-block">Active Product</span>
                                    <small class="text-muted d-block">Active products will be available for policy creation</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" name="add_product" class="btn-gradient px-4">
                            <i class="bi bi-save me-2"></i>Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header modal-header-gradient">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i>Edit Product
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="editProductForm">
                    <input type="hidden" name="product_id" id="editProductId">
                    <div class="modal-body p-4" id="editProductModalBody">
                        <!-- Loaded via AJAX -->
                        <div class="text-center py-5">
                            <div class="loader mx-auto mb-3"></div>
                            <p>Loading product data...</p>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" name="edit_product" class="btn-gradient">
                            <i class="bi bi-save me-2"></i>Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Product Modal -->
    <div class="modal fade" id="viewProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header modal-header-gradient">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-box-seam me-2"></i>Product Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="viewProductModalBody">
                    <!-- Loaded via AJAX -->
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn-gradient" id="editFromViewBtn">
                        <i class="bi bi-pencil me-2"></i>Edit Product
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-trash3 me-2"></i>Move to Trash
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="deleteProductForm">
                    <input type="hidden" id="deleteProductId">
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="p-4 rounded-circle bg-danger bg-opacity-10 d-inline-block mb-3">
                                <i class="bi bi-trash3 text-danger" style="font-size: 2.5rem;"></i>
                            </div>
                            <h5 class="fw-bold mb-3" id="deleteProductName">Product Name</h5>
                            <p class="text-muted">This action will move the product to the recycle bin. You can restore it later if needed.</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason for deletion *</label>
                            <textarea class="form-control form-control-modern" 
                                      id="deleteReason" rows="3" 
                                      placeholder="Please provide a reason for deletion..." required></textarea>
                        </div>
                        
                        <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <small>Note: This is a soft delete. The product data will be archived and can be restored by Super Admin.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash3 me-2"></i>Move to Trash
                        </button>
                    </div>
                </form>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    // Toast notification function
    function showToast(message, type = 'success') {
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${type === 'success' ? 'bi-check-circle' : type === 'error' ? 'bi-exclamation-circle' : 'bi-info-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        $('.toast-container').append(toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Remove toast after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function () {
            this.remove();
        });
    }

    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#productsTable').DataTable({
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
            language: {
                search: "",
                searchPlaceholder: "Search products...",
                lengthMenu: "_MENU_ per page",
                info: "Showing _START_ to _END_ of _TOTAL_ products",
                infoEmpty: "No products found",
                infoFiltered: "(filtered from _MAX_ total products)",
                zeroRecords: "No matching products found",
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>'
                }
            },
            order: [[1, 'asc']],
            responsive: true,
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
            columnDefs: [
                { 
                    orderable: false, 
                    targets: [0, 7]
                },
                { 
                    className: "align-middle", 
                    targets: "_all" 
                }
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
            const statusFilter = $('#statusFilter').val();
            
            // Custom filtering
            $.fn.dataTable.ext.search = [];
            
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const type = $(data[3]).find('.type-badge').text().toLowerCase();
                const category = data[4].toLowerCase();
                const status = $(data[6]).find('.status-badge').hasClass('status-active') ? '1' : '0';
                
                const typeMatch = !typeFilter || type.includes(typeFilter.toLowerCase());
                const categoryMatch = !categoryFilter || category.includes(categoryFilter.toLowerCase());
                const statusMatch = !statusFilter || status === statusFilter;
                
                return typeMatch && categoryMatch && statusMatch;
            });
            
            table.draw();
        });

        $('#resetFilterBtn').click(function() {
            $('#typeFilter').val('');
            $('#categoryFilter').val('');
            $('#statusFilter').val('');
            $('#searchInput').val('');
            
            $.fn.dataTable.ext.search = [];
            table.search('').draw();
        });

        // ============================================
        // ADD PRODUCT FORM FUNCTIONALITY
        // ============================================

        // Product Type change handler for Add Modal
        $('#typeSelect').change(function() {
            const type = $(this).val();
            const $category = $('#categorySelect');
            const $subCategory = $('#subCategorySelect');
            
            // Reset category and sub-category
            $category.val('').prop('disabled', true);
            $subCategory.val('').prop('disabled', true);
            $('#productNameDisplay').text('No product name yet');
            $('#productDescriptionPreview').text('Select type, category and sub-category to preview');
            $('#productCodeInput').val('');
            
            if (type) {
                // Load categories via AJAX
                $.ajax({
                    url: '<?= $_SERVER['PHP_SELF'] ?>',
                    method: 'GET',
                    data: { 
                        get_categories: 'true',
                        type: type
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        $category.html('<option value="">Loading categories...</option>');
                    },
                    success: function(response) {
                        if (response.success) {
                            $category.html('<option value="">Select Category</option>');
                            $.each(response.categories, function(key, value) {
                                $category.append($('<option>', {
                                    value: key,
                                    text: value.name
                                }));
                            });
                            $category.prop('disabled', false);
                        } else {
                            $category.html('<option value="">Error loading categories</option>');
                        }
                    },
                    error: function() {
                        $category.html('<option value="">Error loading categories</option>');
                    }
                });
            }
        });

        // Category change handler for Add Modal
        $('#categorySelect').change(function() {
            const type = $('#typeSelect').val();
            const category = $(this).val();
            const $subCategory = $('#subCategorySelect');
            
            // Reset sub-category
            $subCategory.val('').prop('disabled', true);
            $('#productNameDisplay').text('No product name yet');
            $('#productDescriptionPreview').text('Select sub-category to preview');
            $('#productCodeInput').val('');
            
            if (type && category) {
                // Load sub-categories via AJAX
                $.ajax({
                    url: '<?= $_SERVER['PHP_SELF'] ?>',
                    method: 'GET',
                    data: { 
                        get_sub_categories: 'true',
                        type: type,
                        category: category
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        $subCategory.html('<option value="">Loading sub-categories...</option>');
                    },
                    success: function(response) {
                        if (response.success) {
                            $subCategory.html('<option value="">Select Sub-category</option>');
                            $.each(response.sub_categories, function(key, value) {
                                $subCategory.append($('<option>', {
                                    value: key,
                                    text: value.name
                                }));
                            });
                            $subCategory.prop('disabled', false);
                        } else {
                            $subCategory.html('<option value="">Error loading sub-categories</option>');
                        }
                    },
                    error: function() {
                        $subCategory.html('<option value="">Error loading sub-categories</option>');
                    }
                });
                
                // Update category preview
                const categoryText = $(this).find('option:selected').text();
                $('#productNameDisplay').text(categoryText);
            }
        });

        // Sub-category change handler for Add Modal
        $('#subCategorySelect').change(function() {
            const type = $('#typeSelect').val();
            const category = $('#categorySelect').val();
            const subCategory = $(this).val();
            
            if (type && category && subCategory) {
                // Get category and sub-category names
                const categoryText = $('#categorySelect option:selected').text();
                const subCategoryText = $(this).find('option:selected').text();
                const productName = categoryText + ' - ' + subCategoryText;
                
                // Update product preview
                $('#productNameDisplay').text(productName);
                
                // Get description for the sub-category
                $.ajax({
                    url: '<?= $_SERVER['PHP_SELF'] ?>',
                    method: 'GET',
                    data: { 
                        get_sub_categories: 'true',
                        type: type,
                        category: category
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.sub_categories[subCategory]) {
                            $('#productDescriptionPreview').text(response.sub_categories[subCategory].description || 'No description available');
                        }
                    }
                });
                
                // Generate product code
                $.ajax({
                    url: '<?= $_SERVER['PHP_SELF'] ?>',
                    method: 'GET',
                    data: { 
                        generate_code: 'true',
                        type: type,
                        category: category,
                        sub_category: subCategory
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#productCodeInput').val(response.code);
                        }
                    }
                });
            }
        });

        // Check for duplicate product name
        function checkProductNameDuplicate(productName, productId = null) {
            return new Promise((resolve) => {
                $.ajax({
                    url: '<?= $_SERVER['PHP_SELF'] ?>',
                    method: 'GET',
                    data: { 
                        check_duplicate: 'true',
                        product_name: productName,
                        product_id: productId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            resolve(response.is_duplicate);
                        } else {
                            resolve(false);
                        }
                    },
                    error: function() {
                        resolve(false);
                    }
                });
            });
        }

        // Validate Add Form
        function validateAddForm() {
            const type = $('#typeSelect').val();
            const category = $('#categorySelect').val();
            const subCategory = $('#subCategorySelect').val();
            const productName = $('#productNameDisplay').text();
            
            if (!type || !category || !subCategory) {
                showToast('Please select type, category and sub-category', 'warning');
                return false;
            }
            
            if (productName === 'No product name yet') {
                showToast('Please complete all selections to generate product name', 'warning');
                return false;
            }
            
            // Check for duplicate
            checkProductNameDuplicate(productName).then((isDuplicate) => {
                if (isDuplicate) {
                    showToast('This product name already exists! Please choose a different combination.', 'error');
                    return false;
                }
            });
            
            return true;
        }

        // Export Product Report Function
        function exportProductReport() {
            Swal.fire({
                title: 'Export Product Report',
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
                    exportToExcel();
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    exportToPDF();
                } else if (result.isDenied) {
                    exportToCSV();
                }
            });
        }

        window.exportProductReport = exportProductReport;

        // Export Functions
        function exportToExcel() {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= $_SERVER['PHP_SELF'] ?>';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'export_excel';
            input.value = 'true';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            Swal.fire({
                icon: 'success',
                title: 'Excel Export Started',
                text: 'Your Excel file is being generated...',
                timer: 2000,
                showConfirmButton: false
            });
        }

        function exportToPDF() {
            // Simple PDF generation using window.print()
            window.print();
            
            Swal.fire({
                icon: 'success',
                title: 'PDF Print Dialog',
                text: 'Use the print dialog to save as PDF',
                timer: 3000,
                showConfirmButton: false
            });
        }

        function exportToCSV() {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= $_SERVER['PHP_SELF'] ?>';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'export_csv';
            input.value = 'true';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            Swal.fire({
                icon: 'success',
                title: 'CSV Export Started',
                text: 'Your CSV file is being generated...',
                timer: 2000,
                showConfirmButton: false
            });
        }

        // Button event handlers
        $('#exportExcelBtn').click(exportToExcel);
        $('#exportPDFBtn').click(exportToPDF);
        $('#exportCSVBtn').click(exportToCSV);

        // ============================================
        // PRODUCT FUNCTIONS
        // ============================================

        // View Product
        window.viewProduct = function(id) {
            $.ajax({
                url: '<?= $_SERVER['PHP_SELF'] ?>',
                method: 'GET',
                data: { get_product: 'true', id: id },
                dataType: 'json',
                beforeSend: function() {
                    $('#viewProductModalBody').html(`
                        <div class="text-center py-5">
                            <div class="loader mx-auto mb-3"></div>
                            <p>Loading product details...</p>
                        </div>
                    `);
                },
                success: function(response) {
                    if (response.success) {
                        const product = response.product;
                        const typeName = product.type === 'general' ? 'General Insurance' : 'Life Insurance';
                        const createdDate = new Date(product.created_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
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
                                                    <small class="text-muted d-block">Product Code</small>
                                                    <strong class="text-primary">${product.product_code}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Status</small>
                                                    ${product.is_active ? 
                                                        '<span class="badge bg-success">Active</span>' : 
                                                        '<span class="badge bg-danger">Inactive</span>'}
                                                </div>
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted d-block">Product Name</small>
                                                    <strong>${product.product_name}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Type</small>
                                                    <span class="badge ${product.type === 'general' ? 'bg-info' : 'bg-success'}">${typeName}</span>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Category</small>
                                                    <span class="badge bg-primary">${product.category}</span>
                                                </div>
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted d-block">Sub-category</small>
                                                    <strong>${product.sub_category}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light mb-4">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted mb-3">
                                                <i class="bi bi-file-text me-2"></i>Description & Details
                                            </h6>
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted d-block">Description</small>
                                                    <p class="mb-0">${product.description || 'No description provided'}</p>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Created</small>
                                                    <strong>${createdDate}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Created By</small>
                                                    <strong>${product.created_by || 'System'}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Last Updated</small>
                                                    <strong>${product.updated_at ? new Date(product.updated_at).toLocaleDateString() : 'Never'}</strong>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <small class="text-muted d-block">Updated By</small>
                                                    <strong>${product.updated_by || 'N/A'}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        $('#viewProductModalBody').html(html);
                        $('#viewProductModal').modal('show');
                        
                        $('#editFromViewBtn').off('click').click(function() {
                            $('#viewProductModal').modal('hide');
                            setTimeout(() => {
                                editProduct(id);
                            }, 300);
                        });
                        
                    } else {
                        showToast(response.message || 'Product not found', 'error');
                    }
                },
                error: function() {
                    showToast('Failed to load product details', 'error');
                }
            });
        };

        // Edit Product
        window.editProduct = function(id) {
            $.ajax({
                url: '<?= $_SERVER['PHP_SELF'] ?>',
                method: 'GET',
                data: { get_product: 'true', id: id },
                dataType: 'json',
                beforeSend: function() {
                    $('#editProductModalBody').html(`
                        <div class="text-center py-5">
                            <div class="loader mx-auto mb-3"></div>
                            <p>Loading product data...</p>
                        </div>
                    `);
                },
                success: function(response) {
                    if (response.success) {
                        const product = response.product;
                        
                        // Fetch categories for this type
                        $.ajax({
                            url: '<?= $_SERVER['PHP_SELF'] ?>',
                            method: 'GET',
                            data: { 
                                get_categories: 'true',
                                type: product.type
                            },
                            dataType: 'json',
                            success: function(catResponse) {
                                if (catResponse.success) {
                                    // Fetch sub-categories for this category
                                    $.ajax({
                                        url: '<?= $_SERVER['PHP_SELF'] ?>',
                                        method: 'GET',
                                        data: { 
                                            get_sub_categories: 'true',
                                            type: product.type,
                                            category: product.category
                                        },
                                        dataType: 'json',
                                        success: function(subResponse) {
                                            if (subResponse.success) {
                                                const html = `
                                                    <!-- Insurance Type -->
                                                    <div class="section-header">
                                                        <h6><i class="bi bi-shield me-2"></i>Insurance Type *</h6>
                                                    </div>
                                                    <div class="row mb-4">
                                                        <div class="col-md-6">
                                                            <select class="form-select form-control-modern" 
                                                                    name="type" id="editTypeSelect" required>
                                                                <option value="general" ${product.type === 'general' ? 'selected' : ''}>General Insurance</option>
                                                                <option value="life" ${product.type === 'life' ? 'selected' : ''}>Life Insurance</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Category -->
                                                    <div class="section-header">
                                                        <h6><i class="bi bi-grid me-2"></i>Category *</h6>
                                                    </div>
                                                    <div class="row mb-4">
                                                        <div class="col-md-6">
                                                            <select class="form-select form-control-modern" 
                                                                    name="category" id="editCategorySelect" required>
                                                                <option value="">Select Category</option>
                                                                ${Object.entries(catResponse.categories).map(([key, value]) => 
                                                                    `<option value="${key}" ${key === product.category ? 'selected' : ''}>${value.name}</option>`
                                                                ).join('')}
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Sub-category -->
                                                    <div class="section-header">
                                                        <h6><i class="bi bi-diagram-3 me-2"></i>Sub-category *</h6>
                                                    </div>
                                                    <div class="row mb-4">
                                                        <div class="col-md-6">
                                                            <select class="form-select form-control-modern" 
                                                                    name="sub_category" id="editSubCategorySelect" required>
                                                                <option value="">Select Sub-category</option>
                                                                ${Object.entries(subResponse.sub_categories).map(([key, value]) => 
                                                                    `<option value="${key}" ${key === product.sub_category ? 'selected' : ''}>${value.name}</option>`
                                                                ).join('')}
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Product Code -->
                                                    <div class="section-header">
                                                        <h6><i class="bi bi-hash me-2"></i>Product Code</h6>
                                                    </div>
                                                    <div class="row mb-4">
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control form-control-modern" 
                                                                   value="${product.product_code}" readonly>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Product Name Preview -->
                                                    <div class="product-preview">
                                                        <div class="mb-3">
                                                            <i class="bi bi-box-seam fs-1 text-primary"></i>
                                                        </div>
                                                        <h5 id="editProductNameDisplay">${product.product_name}</h5>
                                                        <p class="mb-0" id="editProductDescriptionPreview">${subResponse.sub_categories[product.sub_category]?.description || 'No description available'}</p>
                                                    </div>
                                                    
                                                    <!-- Description -->
                                                    <div class="section-header">
                                                        <h6><i class="bi bi-card-text me-2"></i>Description</h6>
                                                    </div>
                                                    <div class="mb-4">
                                                        <textarea class="form-control form-control-modern" 
                                                                  name="description" id="editDescription" rows="5"
                                                                  placeholder="Enter product description...">${product.description || ''}</textarea>
                                                    </div>
                                                    
                                                    <!-- Active Product Toggle -->
                                                    <div class="section-header">
                                                        <h6><i class="bi bi-toggle-on me-2"></i>Product Status</h6>
                                                    </div>
                                                    <div class="mb-4">
                                                        <div class="form-check form-switch form-switch-lg">
                                                            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" ${product.is_active ? 'checked' : ''}>
                                                            <label class="form-check-label fw-bold ms-3" for="editIsActive">
                                                                <span class="d-block">Active Product</span>
                                                                <small class="text-muted d-block">Active products will be available for policy creation</small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                `;
                                                
                                                $('#editProductId').val(id);
                                                $('#editProductModalBody').html(html);
                                                $('#editProductModal').modal('show');
                                                
                                                // Update product name preview when selections change
                                                function updateEditProductNamePreview() {
                                                    const type = $('#editTypeSelect').val();
                                                    const category = $('#editCategorySelect').val();
                                                    const subCategory = $('#editSubCategorySelect').val();
                                                    
                                                    if (type && category && subCategory) {
                                                        const categoryText = $('#editCategorySelect option:selected').text();
                                                        const subCategoryText = $('#editSubCategorySelect option:selected').text();
                                                        $('#editProductNameDisplay').text(categoryText + ' - ' + subCategoryText);
                                                    }
                                                }
                                                
                                                // Update description preview
                                                function updateEditDescriptionPreview() {
                                                    const type = $('#editTypeSelect').val();
                                                    const category = $('#editCategorySelect').val();
                                                    const subCategory = $('#editSubCategorySelect').val();
                                                    
                                                    if (type && category && subCategory) {
                                                        $.ajax({
                                                            url: '<?= $_SERVER['PHP_SELF'] ?>',
                                                            method: 'GET',
                                                            data: { 
                                                                get_sub_categories: 'true',
                                                                type: type,
                                                                category: category
                                                            },
                                                            dataType: 'json',
                                                            success: function(response) {
                                                                if (response.success && response.sub_categories[subCategory]) {
                                                                    $('#editProductDescriptionPreview').text(response.sub_categories[subCategory].description || 'No description available');
                                                                }
                                                            }
                                                        });
                                                    }
                                                }
                                                
                                                $('#editTypeSelect, #editCategorySelect, #editSubCategorySelect').change(function() {
                                                    updateEditProductNamePreview();
                                                    updateEditDescriptionPreview();
                                                    
                                                    // If type changes, update categories
                                                    if ($(this).attr('id') === 'editTypeSelect') {
                                                        const newType = $(this).val();
                                                        $.ajax({
                                                            url: '<?= $_SERVER['PHP_SELF'] ?>',
                                                            method: 'GET',
                                                            data: { 
                                                                get_categories: 'true',
                                                                type: newType
                                                            },
                                                            dataType: 'json',
                                                            success: function(response) {
                                                                if (response.success) {
                                                                    $('#editCategorySelect').html('<option value="">Select Category</option>');
                                                                    $.each(response.categories, function(key, value) {
                                                                        $('#editCategorySelect').append($('<option>', {
                                                                            value: key,
                                                                            text: value.name
                                                                        }));
                                                                    });
                                                                    $('#editSubCategorySelect').html('<option value="">Select Sub-category</option>');
                                                                    $('#editProductNameDisplay').text('No product name yet');
                                                                    $('#editProductDescriptionPreview').text('Select sub-category to preview');
                                                                }
                                                            }
                                                        });
                                                    }
                                                    
                                                    // If category changes, update sub-categories
                                                    if ($(this).attr('id') === 'editCategorySelect') {
                                                        const type = $('#editTypeSelect').val();
                                                        const category = $(this).val();
                                                        if (type && category) {
                                                            $.ajax({
                                                                url: '<?= $_SERVER['PHP_SELF'] ?>',
                                                                method: 'GET',
                                                                data: { 
                                                                    get_sub_categories: 'true',
                                                                    type: type,
                                                                    category: category
                                                                },
                                                                dataType: 'json',
                                                                success: function(response) {
                                                                    if (response.success) {
                                                                        $('#editSubCategorySelect').html('<option value="">Select Sub-category</option>');
                                                                        $.each(response.sub_categories, function(key, value) {
                                                                            $('#editSubCategorySelect').append($('<option>', {
                                                                                value: key,
                                                                                text: value.name
                                                                            }));
                                                                        });
                                                                        updateEditProductNamePreview();
                                                                    }
                                                                }
                                                            });
                                                        }
                                                    }
                                                });
                                            }
                                        }
                                    });
                                }
                            }
                        });
                        
                    } else {
                        showToast(response.message || 'Product not found', 'error');
                    }
                },
                error: function() {
                    showToast('Failed to load product data', 'error');
                }
            });
        };

        // Toggle Product Status
        window.toggleProductStatus = function(id, name, isActive) {
            const action = isActive ? 'deactivate' : 'activate';
            const actionText = isActive ? 'Deactivate' : 'Activate';
            
            Swal.fire({
                title: `${actionText} Product?`,
                html: `Are you sure you want to ${action} <strong>${name}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: isActive ? '#f39c12' : '#27ae60',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${actionText}!`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= $_SERVER['PHP_SELF'] ?>',
                        method: 'POST',
                        data: {
                            toggle_status: 'true',
                            product_id: id
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            Swal.fire({
                                title: 'Updating...',
                                text: 'Please wait',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function(response) {
                            Swal.close();
                            if (response.success) {
                                showToast(response.message, 'success');
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                showToast(response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.close();
                            showToast('Failed to update product status', 'error');
                        }
                    });
                }
            });
        };

        // Delete Product (Soft Delete)
        window.deleteProductPrompt = function(id, name) {
            $('#deleteProductId').val(id);
            $('#deleteProductName').text(name);
            $('#deleteReason').val('');
            $('#deleteProductModal').modal('show');
        };

        // Handle delete form submission
        $('#deleteProductForm').submit(function(e) {
            e.preventDefault();
            
            const id = $('#deleteProductId').val();
            const reason = $('#deleteReason').val();
            
            if (!reason.trim()) {
                showToast('Please provide a reason for deletion', 'warning');
                return;
            }
            
            Swal.fire({
                title: 'Move to Trash?',
                html: `Are you sure you want to move <strong>${$('#deleteProductName').text()}</strong> to trash?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, move to trash!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= $_SERVER['PHP_SELF'] ?>',
                        method: 'POST',
                        data: {
                            ajax_delete: 'true',
                            product_id: id,
                            delete_reason: reason
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            Swal.fire({
                                title: 'Moving to Trash...',
                                text: 'Please wait',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function(response) {
                            Swal.close();
                            if (response.success) {
                                showToast(response.message, 'success');
                                $('#deleteProductModal').modal('hide');
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                showToast(response.message, 'error');
                                $('#deleteProductModal').modal('hide');
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.close();
                            showToast('Failed to delete product: ' + error, 'error');
                            $('#deleteProductModal').modal('hide');
                        }
                    });
                }
            });
        });

        // Form validation
        $('#editProductForm').on('submit', function(e) {
            let isValid = true;
            $(this).find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showToast('Please fill all required fields', 'warning');
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
        
        // Close modal and reset form
        $('#addProductModal').on('hidden.bs.modal', function () {
            $('#addProductForm')[0].reset();
            $('#categorySelect').prop('disabled', true).html('<option value="">Select Type First</option>');
            $('#subCategorySelect').prop('disabled', true).html('<option value="">Select Category First</option>');
            $('#productCodeInput').val('');
            $('#productNameDisplay').text('No product name yet');
            $('#productDescriptionPreview').text('Select type, category and sub-category to preview');
        });
        
        // Initialize select2 for better UX
        $('#typeSelect, #categorySelect, #subCategorySelect').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#addProductModal')
        });
    });
    </script>
</body>
</html>