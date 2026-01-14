<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Product Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-bg: #f5f7fa;
            --sidebar-width: 250px;
            --header-height: 60px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light-bg);
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            transition: all 0.3s;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .sidebar-menu {
            padding: 15px 0;
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li {
            position: relative;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar-menu li a:hover, .sidebar-menu li a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--secondary-color);
        }

        .sidebar-menu li a i {
            width: 25px;
            font-size: 1.1rem;
            margin-right: 10px;
        }

        .submenu {
            display: none;
            background-color: rgba(0,0,0,0.2);
        }

        .submenu.active {
            display: block;
        }

        .submenu li a {
            padding-left: 50px;
            font-size: 0.9rem;
        }

        .has-submenu > a::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 20px;
            transition: transform 0.3s;
        }

        .has-submenu.active > a::after {
            transform: rotate(180deg);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #219653;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #e67e22;
        }

        .btn-danger {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        /* Card Layout */
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.3rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        /* Tree View */
        .tree-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            max-height: 500px;
            overflow-y: auto;
            margin-bottom: 30px;
        }

        .tree {
            list-style: none;
            padding-left: 0;
        }

        .tree, .tree ul {
            margin: 0;
            padding-left: 20px;
            position: relative;
        }

        .tree ul {
            margin-left: 20px;
        }

        .tree li {
            margin: 10px 0;
            position: relative;
            padding-left: 20px;
        }

        .tree li:before {
            content: "";
            position: absolute;
            left: 0;
            top: 10px;
            width: 10px;
            height: 1px;
            background-color: #ccc;
        }

        .tree li:after {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 1px;
            background-color: #ccc;
        }

        .tree li:last-child:after {
            height: 10px;
        }

        .tree-node {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #eee;
            transition: all 0.3s;
        }

        .tree-node:hover {
            background-color: #e9f5ff;
            border-color: var(--secondary-color);
        }

        .tree-node-icon {
            margin-right: 10px;
            color: var(--secondary-color);
        }

        .tree-node-actions {
            margin-left: auto;
            display: flex;
            gap: 5px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tree-node:hover .tree-node-actions {
            opacity: 1;
        }

        .tree-node-action {
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            transition: all 0.3s;
        }

        .tree-node-action:hover {
            color: white;
            background-color: var(--secondary-color);
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border 0.3s;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        /* Table Styling */
        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid #eee;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .action-btn-edit {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
        }

        .action-btn-edit:hover {
            background-color: var(--secondary-color);
            color: white;
        }

        .action-btn-delete {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--accent-color);
        }

        .action-btn-delete:hover {
            background-color: var(--accent-color);
            color: white;
        }

        /* Search and Filter */
        .search-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }

        .filter-dropdown {
            min-width: 200px;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.life {
            background-color: var(--secondary-color);
        }

        .stat-icon.general {
            background-color: var(--success-color);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #777;
            font-size: 0.9rem;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #777;
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .tab {
            padding: 12px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }

        .tab.active {
            border-bottom-color: var(--secondary-color);
            color: var(--secondary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2 span, 
            .sidebar-menu li a span {
                display: none;
            }
            
            .sidebar-menu li a i {
                margin-right: 0;
                font-size: 1.3rem;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .has-submenu > a::after {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-alt"></i> <span>InsureMaster</span></h2>
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li class="has-submenu">
                    <a href="#"><i class="fas fa-sitemap"></i> <span>Product Hierarchy</span></a>
                    <ul class="submenu">
                        <li><a href="#" data-tab="life"><i class="fas fa-heartbeat"></i> Life Insurance</a></li>
                        <li><a href="#" data-tab="general"><i class="fas fa-car"></i> General Insurance</a></li>
                    </ul>
                </li>
                <li><a href="#" data-tab="all-products"><i class="fas fa-list"></i> <span>All Products</span></a></li>
                <li><a href="#" data-tab="reports"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
                <li><a href="#" data-tab="comparison"><i class="fas fa-balance-scale"></i> <span>Comparison</span></a></li>
                <li><a href="#" data-tab="import-export"><i class="fas fa-file-export"></i> <span>Import/Export</span></a></li>
                <li><a href="#" data-tab="settings"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>Insurance Product Management</h1>
            <div class="header-actions">
                <button class="btn btn-primary" id="btnAddProduct">
                    <i class="fas fa-plus-circle"></i> Add New Product
                </button>
                <button class="btn btn-success" id="btnImport">
                    <i class="fas fa-file-import"></i> Import Products
                </button>
                <button class="btn btn-warning" id="btnExport">
                    <i class="fas fa-file-export"></i> Export Products
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon life">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="stat-info">
                    <h3 id="lifeCount">0</h3>
                    <p>Life Insurance Products</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon general">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-info">
                    <h3 id="generalCount">0</h3>
                    <p>General Insurance Products</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: var(--warning-color);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-info">
                    <h3 id="categoryCount">0</h3>
                    <p>Product Categories</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: var(--accent-color);">
                    <i class="fas fa-code-branch"></i>
                </div>
                <div class="stat-info">
                    <h3 id="subproductCount">0</h3>
                    <p>Sub-Products</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" data-tab="tree-view">Hierarchy View</div>
            <div class="tab" data-tab="table-view">Table View</div>
            <div class="tab" data-tab="bulk-actions">Bulk Actions</div>
        </div>

        <!-- Tree View Tab Content -->
        <div class="tab-content active" id="tree-view">
            <div class="tree-container">
                <div class="card-header">
                    <h3>Insurance Product Hierarchy</h3>
                    <div>
                        <button class="btn btn-primary" id="btnExpandAll">
                            <i class="fas fa-expand-alt"></i> Expand All
                        </button>
                        <button class="btn btn-primary" id="btnCollapseAll">
                            <i class="fas fa-compress-alt"></i> Collapse All
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="tree" id="insuranceTree">
                        <!-- Tree will be generated dynamically by JavaScript -->
                    </ul>
                </div>
            </div>
        </div>

        <!-- Table View Tab Content -->
        <div class="tab-content" id="table-view">
            <div class="card">
                <div class="card-header">
                    <h3>All Insurance Products</h3>
                    <div class="search-filter">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search products...">
                        </div>
                        <select class="form-control filter-dropdown" id="categoryFilter">
                            <option value="">All Categories</option>
                            <option value="life">Life Insurance</option>
                            <option value="general">General Insurance</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Parent Product</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <!-- Table rows will be generated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Tab Content -->
        <div class="tab-content" id="bulk-actions">
            <div class="card">
                <div class="card-header">
                    <h3>Bulk Product Management</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Select Action</label>
                        <select class="form-control" id="bulkAction">
                            <option value="">Select an action...</option>
                            <option value="activate">Activate Selected Products</option>
                            <option value="deactivate">Deactivate Selected Products</option>
                            <option value="move">Move to Different Category</option>
                            <option value="delete">Delete Selected Products</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="moveCategoryGroup" style="display:none;">
                        <label>Select Target Category</label>
                        <select class="form-control" id="targetCategory">
                            <option value="">Select category...</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Select Products</label>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; padding: 10px;">
                            <div id="bulkProductList">
                                <!-- Product checkboxes will be populated dynamically -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button class="btn btn-primary" id="btnApplyBulkAction">Apply Action</button>
                        <button class="btn btn-warning" id="btnSelectAll">Select All</button>
                        <button class="btn btn-warning" id="btnDeselectAll">Deselect All</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Comparison Card -->
        <div class="card" id="comparisonCard" style="display:none;">
            <div class="card-header">
                <h3>Product Comparison</h3>
                <button class="btn btn-danger" id="btnClearComparison">
                    <i class="fas fa-times"></i> Clear Comparison
                </button>
            </div>
            <div class="card-body">
                <div id="comparisonContent">
                    <!-- Comparison content will be generated dynamically -->
                </div>
            </div>
        </div>
    </main>

    <!-- Product Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Product</h3>
                <button class="close-modal" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" id="productId">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="productName">Product Name *</label>
                            <input type="text" class="form-control" id="productName" required>
                        </div>
                        <div class="form-group">
                            <label for="productCode">Product Code *</label>
                            <input type="text" class="form-control" id="productCode" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="productType">Product Type *</label>
                            <select class="form-control" id="productType" required>
                                <option value="">Select Type</option>
                                <option value="life">Life Insurance</option>
                                <option value="general">General Insurance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="productCategory">Category *</label>
                            <select class="form-control" id="productCategory" required>
                                <option value="">Select Category</option>
                                <!-- Categories will be populated based on type -->
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="parentProduct">Parent Product</label>
                            <select class="form-control" id="parentProduct">
                                <option value="">None (Top Level)</option>
                                <!-- Parent products will be populated dynamically -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="productStatus">Status</label>
                            <select class="form-control" id="productStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="productDescription">Description</label>
                        <textarea class="form-control" id="productDescription" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="premiumMin">Minimum Premium</label>
                            <input type="number" class="form-control" id="premiumMin" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="premiumMax">Maximum Premium</label>
                            <input type="number" class="form-control" id="premiumMax" step="0.01">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="coverageDetails">Coverage Details (JSON)</label>
                        <textarea class="form-control" id="coverageDetails" rows="3" placeholder='{"key": "value"}'></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="btnSaveProduct">Save Product</button>
                <button class="btn btn-warning" id="btnCancel">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal" id="importModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Import Products</h3>
                <button class="close-modal" id="closeImportModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Select File (CSV or JSON)</label>
                    <input type="file" class="form-control" id="importFile" accept=".csv,.json">
                </div>
                <div class="form-group">
                    <label>Import Mode</label>
                    <select class="form-control" id="importMode">
                        <option value="add">Add New Products Only</option>
                        <option value="update">Update Existing Products</option>
                        <option value="replace">Replace All Products</option>
                    </select>
                </div>
                <div id="importPreview" style="display:none;">
                    <h4>Preview</h4>
                    <div class="table-container">
                        <table class="table" id="importPreviewTable">
                            <!-- Preview will be shown here -->
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" id="btnProcessImport">Process Import</button>
                <button class="btn btn-warning" id="btnCancelImport">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Insurance product data structure
        const insuranceData = {
            lifeInsurance: {
                name: "Life Insurance",
                children: {
                    individualLife: {
                        name: "Individual Life",
                        children: {
                            termLife: {
                                name: "Term Life",
                                children: {
                                    termLife5Y: { name: "TermLife 5Y" },
                                    termLife10Y: { name: "TermLife 10Y" },
                                    termLife15Y: { name: "TermLife 15Y" },
                                    termLife20Y: { name: "TermLife 20Y" },
                                    renewableTermLife: { name: "Renewable Term Life" },
                                    convertibleTermLife: { name: "Convertible Term Life" }
                                }
                            },
                            wholeLife: {
                                name: "WholeLife",
                                children: {
                                    traditionalWholeLife: { name: "Traditional Whole Life" },
                                    limitedPayWholeLife: { name: "Limited Pay Whole Life" },
                                    participatingWholeLife: { name: "Participating Whole Life" },
                                    nonParticipatingWholeLife: { name: "Non Participating Whole Life" }
                                }
                            },
                            endowment: {
                                name: "Endowment",
                                children: {
                                    educationPlan: { name: "Education Plan" },
                                    savingPlan: { name: "Saving Plan" },
                                    pensionPlan: { name: "Pension Plan" }
                                }
                            },
                            unitLinked: {
                                name: "UnitLinked",
                                children: {
                                    unitLinkEquityFund: { name: "UnitLink Equity Fund" },
                                    unitLinkBalancedFund: { name: "UnitLink Balanced Fund" },
                                    unitLinkFixedIncomeFund: { name: "UnitLink Fixed Income Fund" },
                                    unitLinkMoneyMarketFund: { name: "UnitLink Money Market Fund" },
                                    unitLinkSyariahFund: { name: "UnitLink Syariah Fund" }
                                }
                            }
                        }
                    },
                    groupLife: {
                        name: "GroupLife",
                        children: {
                            groupTermLife: {
                                name: "Group Term Life",
                                children: {
                                    employeeLife: { name: "Employee Life" },
                                    creditLife: { name: "Credit Life" },
                                    microLife: { name: "Micro Life" }
                                }
                            },
                            groupHealthLife: {
                                name: "Group Health Life",
                                children: {
                                    corporateHealthLife: { name: "Corporate Health Life" },
                                    smeHealthLife: { name: "SME Health Life" },
                                    microHealthLife: { name: "Micro Health Life" }
                                }
                            },
                            groupPersonalAccident: {
                                name: "Group Personal Accident",
                                children: {
                                    paEmployee: { name: "PA Employee" },
                                    paMass: { name: "PA Mass" },
                                    paEvent: { name: "PA Event" }
                                }
                            }
                        }
                    },
                    rider: {
                        name: "Rider",
                        children: {
                            healthRider: {
                                name: "HealthRider",
                                children: {
                                    inpatient: { name: "Inpatient" },
                                    outpatient: { name: "Outpatient" },
                                    maternity: { name: "Maternity" },
                                    dental: { name: "Dental" },
                                    optical: { name: "Optical" }
                                }
                            },
                            criticalIllnessRider: {
                                name: "CriticalIllnessRider",
                                children: {
                                    ciEarlyStage: { name: "CI EarlyStage" },
                                    ciMajor: { name: "CI Major" },
                                    ciAdvanced: { name: "CI Advanced" }
                                }
                            },
                            accidentalRider: {
                                name: "Accidental Rider",
                                children: {
                                    accidentalDeath: { name: "Accidental Death" },
                                    accidentalDisability: { name: "Accidental Disability" },
                                    accidentalMedical: { name: "Accidental Medical" }
                                }
                            },
                            waiverRider: {
                                name: "WaiverRider",
                                children: {
                                    waiverOfPremium: { name: "Waiver Of Premium" },
                                    payorBenefit: { name: "Payor Benefit" }
                                }
                            }
                        }
                    },
                    lifeSharia: {
                        name: "LifeSharia",
                        children: {
                            individualShariaLife: { name: "Individual Sharia Life" },
                            groupShariaLife: { name: "Group Sharia Life" },
                            unitLinkSharia: { name: "UnitLink Sharia" },
                            microSharia: { name: "Micro Sharia" }
                        }
                    }
                }
            },
            generalInsurance: {
                name: "General Insurance",
                children: {
                    property: {
                        name: "Property",
                        children: {
                            fire: {
                                name: "Fire",
                                children: {
                                    fireStandard: { name: "Fire Standard" },
                                    industrialAllRisk: { name: "Industrial All Risk" },
                                    propertyAllRisk: { name: "Property All Risk" }
                                }
                            },
                            naturalPerils: {
                                name: "NaturalPerils",
                                children: {
                                    earthquake: { name: "Earthquake" },
                                    flood: { name: "Flood" },
                                    tsunami: { name: "Tsunami" },
                                    volcano: { name: "Volcano" }
                                }
                            },
                            propertyExtension: {
                                name: "Property Extension",
                                children: {
                                    terrorism: { name: "Terrorism" },
                                    rscc: { name: "RSCC" },
                                    maliciousDamage: { name: "Malicious Damage" }
                                }
                            }
                        }
                    },
                    motor: {
                        name: "Motor",
                        children: {
                            motorComprehensive: {
                                name: "Motor Comprehensive",
                                children: {
                                    allRisk: { name: "AllRisk" },
                                    totalLossOnly: { name: "Total Loss Only" }
                                }
                            },
                            motorTLO: {
                                name: "Motor TLO",
                                children: {
                                    totalLossOnlyMotor: { name: "Total Loss Only" }
                                }
                            },
                            motorExtension: {
                                name: "MotorExtension",
                                children: {
                                    floodMotor: { name: "Flood" },
                                    earthquakeMotor: { name: "Earthquake" },
                                    riotStrike: { name: "RiotStrike" },
                                    terrorismMotor: { name: "Terrorism" }
                                }
                            }
                        }
                    },
                    engineering: {
                        name: "Engineering",
                        children: {
                            construction: {
                                name: "Construction",
                                children: {
                                    contractorAllRisk: { name: "ContractorAllRisk" },
                                    erectionAllRisk: { name: "ErectionAllRisk" }
                                }
                            },
                            machinery: {
                                name: "Machinery",
                                children: {
                                    machineryBreakdown: { name: "Machinery Breakdown" },
                                    boilerPressureVessel: { name: "Boiler Pressure Vessel" }
                                }
                            },
                            electronic: {
                                name: "Electronic",
                                children: {
                                    electronicEquipmentInsurance: { name: "Electronic Equipment Insurance" }
                                }
                            }
                        }
                    },
                    marine: {
                        name: "Marine",
                        children: {
                            marineCargo: {
                                name: "Marine Cargo",
                                children: {
                                    iccA: { name: 'ICC "A"' },
                                    iccB: { name: 'ICC "B"' },
                                    iccC: { name: 'ICC "C"' }
                                }
                            },
                            marineHull: {
                                name: "MarineHull",
                                children: {
                                    hullMachinery: { name: "Hull Machinery" },
                                    protectionIndemnity: { name: "Protection Indemnity" }
                                }
                            },
                            marineLiability: {
                                name: "Marine Liability",
                                children: {
                                    chartererLiability: { name: "Charterer Liability" },
                                    terminalOperatorLiability: { name: "Terminal Operator Liability" }
                                }
                            }
                        }
                    },
                    liability: {
                        name: "Liability",
                        children: {
                            generalLiability: {
                                name: "General Liability",
                                children: {
                                    publicLiability: { name: "Public Liability" },
                                    productLiability: { name: "Product Liability" }
                                }
                            },
                            professionalLiability: {
                                name: "ProfessionalLiability",
                                children: {
                                    professionalIndemnity: { name: "Professional Indemnity" },
                                    directorsOfficers: { name: "Directors Officers" },
                                    medicalMalpractice: { name: "Medical Malpractice" }
                                }
                            },
                            employerLiability: {
                                name: "Employer Liability",
                                children: {
                                    workmenCompensation: { name: "Workmen Compensation" },
                                    employerLiabilityChild: { name: "Employer Liability" }
                                }
                            }
                        }
                    },
                    healthInsurance: {
                        name: "Health Insurance",
                        children: {
                            individualHealth: {
                                name: "Individual Health",
                                children: {
                                    inpatientOnly: { name: "Inpatient Only" },
                                    inOutPatient: { name: "In Out Patient" },
                                    majorMedical: { name: "Major Medical" },
                                    hospitalCashPlan: { name: "Hospital Cash Plan" }
                                }
                            },
                            groupHealth: {
                                name: "GroupHealth",
                                children: {
                                    corporateHealth: { name: "Corporate Health" },
                                    smeHealth: { name: "SME Health" },
                                    microHealth: { name: "Micro Health" }
                                }
                            },
                            healthExtension: {
                                name: "HealthExtension",
                                children: {
                                    maternityHealth: { name: "Maternity" },
                                    dentalHealth: { name: "Dental" },
                                    opticalHealth: { name: "Optical" },
                                    medicalCheckup: { name: "Medical Checkup" }
                                }
                            }
                        }
                    },
                    personalAccident: {
                        name: "Personal Accident",
                        children: {
                            individualPA: { name: "Individual PA" },
                            groupPA: { name: "Group PA" },
                            studentPA: { name: "Student PA" },
                            travelPA: { name: "Travel PA" }
                        }
                    },
                    travel: {
                        name: "Travel",
                        children: {
                            domesticTravel: { name: "Domestic Travel" },
                            internationalTravel: { name: "International Travel" },
                            umrahHaji: { name: "Umrah Haji" },
                            annualMultiTrip: { name: "Annual Multi Trip" }
                        }
                    },
                    credit: {
                        name: "Credit",
                        children: {
                            tradeCreditInsurance: { name: "Trade Credit Insurance" },
                            suretyBond: { name: "Surety Bond" },
                            customsBond: { name: "Customs Bond" }
                        }
                    },
                    financial: {
                        name: "Financial",
                        children: {
                            fidelityGuarantee: { name: "Fidelity Guarantee" },
                            crimeInsurance: { name: "Crime Insurance" },
                            cyberInsurance: { name: "Cyber Insurance" },
                            bankersBlanketBond: { name: "Bankers Blanket Bond" }
                        }
                    },
                    miscellaneous: {
                        name: "Miscellaneous",
                        children: {
                            cashInSafe: { name: "Cash In Safe" },
                            cashInTransit: { name: "Cash In Transit" },
                            glassInsurance: { name: "Glass Insurance" },
                            plateGlass: { name: "Plate Glass" },
                            liveStockInsurance: { name: "Live stock Insurance" },
                            cropInsurance: { name: "Crop Insurance" }
                        }
                    }
                }
            }
        };

        // Application state
        const appState = {
            products: [],
            currentProductId: null,
            comparisonProducts: [],
            expandedNodes: new Set()
        };

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            initApp();
        });

        function initApp() {
            // Load products from localStorage or initialize with sample data
            loadProducts();
            
            // Setup event listeners
            setupEventListeners();
            
            // Render initial views
            renderTreeView();
            renderTableView();
            updateStats();
            setupBulkActions();
        }

        function loadProducts() {
            const savedProducts = localStorage.getItem('insuranceProducts');
            if (savedProducts) {
                appState.products = JSON.parse(savedProducts);
            } else {
                // Initialize with sample data from the structure
                appState.products = generateSampleProducts();
                saveProducts();
            }
        }

        function generateSampleProducts() {
            const products = [];
            let idCounter = 1;
            
            // Helper function to add products recursively
            function addProducts(parentId, data, type, path = []) {
                for (const key in data) {
                    const item = data[key];
                    const product = {
                        id: idCounter++,
                        name: item.name,
                        code: generateProductCode(item.name, type),
                        type: type,
                        category: path[0] || type,
                        parentId: parentId,
                        status: 'active',
                        description: `${item.name} insurance product`,
                        premiumMin: Math.floor(Math.random() * 100) + 50,
                        premiumMax: Math.floor(Math.random() * 500) + 200,
                        coverageDetails: JSON.stringify({ coverage: "Standard" }),
                        createdAt: new Date().toISOString(),
                        updatedAt: new Date().toISOString()
                    };
                    
                    products.push(product);
                    
                    if (item.children) {
                        const newPath = [...path, item.name];
                        addProducts(product.id, item.children, type, newPath);
                    }
                }
            }
            
            // Add life insurance products
            addProducts(null, insuranceData.lifeInsurance.children, 'life', ['Life Insurance']);
            
            // Add general insurance products
            addProducts(null, insuranceData.generalInsurance.children, 'general', ['General Insurance']);
            
            return products;
        }

        function generateProductCode(name, type) {
            const prefix = type === 'life' ? 'LIF' : 'GEN';
            const nameCode = name.replace(/\s+/g, '').substring(0, 5).toUpperCase();
            const randomNum = Math.floor(Math.random() * 900) + 100;
            return `${prefix}-${nameCode}-${randomNum}`;
        }

        function saveProducts() {
            localStorage.setItem('insuranceProducts', JSON.stringify(appState.products));
        }

        function setupEventListeners() {
            // Sidebar menu
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links
                    document.querySelectorAll('.sidebar-menu a').forEach(a => {
                        a.classList.remove('active');
                    });
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Handle submenu toggle
                    if (this.parentElement.classList.contains('has-submenu')) {
                        this.parentElement.classList.toggle('active');
                        const submenu = this.nextElementSibling;
                        if (submenu) {
                            submenu.classList.toggle('active');
                        }
                    }
                    
                    // Handle tab switching
                    const tab = this.getAttribute('data-tab');
                    if (tab) {
                        switchTab(tab);
                    }
                });
            });
            
            // Tab switching
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    switchTab(tabId);
                });
            });
            
            // Modal buttons
            document.getElementById('btnAddProduct').addEventListener('click', () => openProductModal());
            document.getElementById('btnSaveProduct').addEventListener('click', saveProduct);
            document.getElementById('btnCancel').addEventListener('click', closeModal);
            document.getElementById('closeModal').addEventListener('click', closeModal);
            
            // Import/Export buttons
            document.getElementById('btnImport').addEventListener('click', () => openImportModal());
            document.getElementById('btnExport').addEventListener('click', exportProducts);
            document.getElementById('closeImportModal').addEventListener('click', closeImportModal);
            document.getElementById('btnCancelImport').addEventListener('click', closeImportModal);
            document.getElementById('btnProcessImport').addEventListener('click', processImport);
            
            // Tree view controls
            document.getElementById('btnExpandAll').addEventListener('click', expandAllNodes);
            document.getElementById('btnCollapseAll').addEventListener('click', collapseAllNodes);
            
            // Product type change
            document.getElementById('productType').addEventListener('change', updateCategoryOptions);
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', filterProducts);
            document.getElementById('categoryFilter').addEventListener('change', filterProducts);
            
            // Bulk actions
            document.getElementById('bulkAction').addEventListener('change', toggleBulkActionOptions);
            document.getElementById('btnApplyBulkAction').addEventListener('click', applyBulkAction);
            document.getElementById('btnSelectAll').addEventListener('click', selectAllProducts);
            document.getElementById('btnDeselectAll').addEventListener('click', deselectAllProducts);
            
            // Comparison
            document.getElementById('btnClearComparison').addEventListener('click', clearComparison);
        }

        function switchTab(tabId) {
            // Update active tab
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.toggle('active', tab.getAttribute('data-tab') === tabId);
            });
            
            // Update active tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.toggle('active', content.id === tabId);
            });
            
            // Refresh the content if needed
            if (tabId === 'table-view') {
                renderTableView();
            } else if (tabId === 'bulk-actions') {
                setupBulkActions();
            }
        }

        function renderTreeView() {
            const treeContainer = document.getElementById('insuranceTree');
            treeContainer.innerHTML = '';
            
            // Create tree for life insurance
            const lifeInsuranceTree = createTreeItem('Life Insurance', 'life', 'fas fa-heartbeat', true);
            const lifeChildren = buildTree('life', null);
            lifeInsuranceTree.appendChild(lifeChildren);
            treeContainer.appendChild(lifeInsuranceTree);
            
            // Create tree for general insurance
            const generalInsuranceTree = createTreeItem('General Insurance', 'general', 'fas fa-car', true);
            const generalChildren = buildTree('general', null);
            generalInsuranceTree.appendChild(generalChildren);
            treeContainer.appendChild(generalInsuranceTree);
            
            // Add event listeners for tree nodes
            document.querySelectorAll('.tree-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const node = this.closest('.tree-node');
                    const children = node.nextElementSibling;
                    if (children && children.classList.contains('tree-children')) {
                        children.classList.toggle('collapsed');
                        this.classList.toggle('fa-chevron-right');
                        this.classList.toggle('fa-chevron-down');
                        
                        // Store expanded state
                        const nodeId = node.getAttribute('data-node-id');
                        if (children.classList.contains('collapsed')) {
                            appState.expandedNodes.delete(nodeId);
                        } else {
                            appState.expandedNodes.add(nodeId);
                        }
                    }
                });
            });
            
            // Add event listeners for node actions
            document.querySelectorAll('.tree-node-action').forEach(action => {
                action.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const node = this.closest('.tree-node');
                    const nodeId = node.getAttribute('data-node-id');
                    
                    if (this.classList.contains('edit-action')) {
                        const product = appState.products.find(p => p.id == nodeId);
                        if (product) openProductModal(product);
                    } else if (this.classList.contains('delete-action')) {
                        deleteProduct(nodeId);
                    } else if (this.classList.contains('compare-action')) {
                        addToComparison(nodeId);
                    }
                });
            });
        }

        function createTreeItem(name, id, icon, hasChildren = false) {
            const li = document.createElement('li');
            li.innerHTML = `
                <div class="tree-node" data-node-id="${id}">
                    <div class="tree-node-content">
                        ${hasChildren ? `<i class="tree-toggle fas fa-chevron-down"></i>` : ''}
                        <i class="tree-node-icon ${icon}"></i>
                        <span class="tree-node-text">${name}</span>
                    </div>
                    <div class="tree-node-actions">
                        <button class="tree-node-action compare-action" title="Add to Comparison">
                            <i class="fas fa-balance-scale"></i>
                        </button>
                        <button class="tree-node-action edit-action" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="tree-node-action delete-action" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            if (hasChildren) {
                const ul = document.createElement('ul');
                ul.className = 'tree-children';
                li.appendChild(ul);
            }
            
            return li;
        }

        function buildTree(type, parentId) {
            const ul = document.createElement('ul');
            ul.className = 'tree-children';
            
            const children = appState.products.filter(product => 
                product.type === type && product.parentId === parentId
            );
            
            children.forEach(child => {
                const hasChildren = appState.products.some(p => p.parentId === child.id);
                const icon = getIconForProduct(child);
                const li = createTreeItem(child.name, child.id, icon, hasChildren);
                
                if (hasChildren) {
                    const childUl = buildTree(type, child.id);
                    li.querySelector('.tree-children').appendChild(childUl);
                }
                
                ul.appendChild(li);
            });
            
            return ul;
        }

        function getIconForProduct(product) {
            const type = product.type;
            const category = product.category.toLowerCase();
            
            if (type === 'life') {
                if (category.includes('individual')) return 'fas fa-user';
                if (category.includes('group')) return 'fas fa-users';
                if (category.includes('rider')) return 'fas fa-plus-circle';
                if (category.includes('sharia')) return 'fas fa-star-and-crescent';
                return 'fas fa-heartbeat';
            } else {
                if (category.includes('property')) return 'fas fa-home';
                if (category.includes('motor')) return 'fas fa-car';
                if (category.includes('engineering')) return 'fas fa-cogs';
                if (category.includes('marine')) return 'fas fa-ship';
                if (category.includes('liability')) return 'fas fa-balance-scale';
                if (category.includes('health')) return 'fas fa-heartbeat';
                if (category.includes('accident')) return 'fas fa-ambulance';
                if (category.includes('travel')) return 'fas fa-plane';
                if (category.includes('credit')) return 'fas fa-credit-card';
                if (category.includes('financial')) return 'fas fa-chart-line';
                return 'fas fa-box';
            }
        }

        function renderTableView() {
            const tbody = document.getElementById('productsTableBody');
            tbody.innerHTML = '';
            
            appState.products.forEach(product => {
                const parentProduct = product.parentId ? 
                    appState.products.find(p => p.id === product.parentId) : 
                    { name: 'None' };
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${product.name}</td>
                    <td><span class="badge ${product.type === 'life' ? 'badge-life' : 'badge-general'}">${product.type}</span></td>
                    <td>${product.category}</td>
                    <td>${parentProduct.name}</td>
                    <td><span class="status-badge status-${product.status}">${product.status}</span></td>
                    <td>
                        <div class="table-actions">
                            <button class="action-btn action-btn-edit" data-id="${product.id}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn action-btn-delete" data-id="${product.id}">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Add event listeners for table actions
            document.querySelectorAll('.action-btn-edit').forEach(btn => {
                btn.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const product = appState.products.find(p => p.id == productId);
                    if (product) openProductModal(product);
                });
            });
            
            document.querySelectorAll('.action-btn-delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    deleteProduct(productId);
                });
            });
        }

        function openProductModal(product = null) {
            const modal = document.getElementById('productModal');
            const title = document.getElementById('modalTitle');
            const form = document.getElementById('productForm');
            
            if (product) {
                // Edit mode
                title.textContent = 'Edit Product';
                document.getElementById('productId').value = product.id;
                document.getElementById('productName').value = product.name;
                document.getElementById('productCode').value = product.code;
                document.getElementById('productType').value = product.type;
                document.getElementById('productCategory').value = product.category;
                document.getElementById('parentProduct').value = product.parentId || '';
                document.getElementById('productStatus').value = product.status;
                document.getElementById('productDescription').value = product.description || '';
                document.getElementById('premiumMin').value = product.premiumMin || '';
                document.getElementById('premiumMax').value = product.premiumMax || '';
                document.getElementById('coverageDetails').value = product.coverageDetails || '';
                
                // Update category options based on type
                updateCategoryOptions();
            } else {
                // Add mode
                title.textContent = 'Add New Product';
                form.reset();
                document.getElementById('productId').value = '';
                document.getElementById('productStatus').value = 'active';
                
                // Generate a default product code
                const type = document.getElementById('productType').value;
                if (type) {
                    const name = document.getElementById('productName').value || 'NEWPROD';
                    document.getElementById('productCode').value = generateProductCode(name, type);
                }
            }
            
            // Update parent product options
            updateParentProductOptions(product ? product.id : null);
            
            modal.classList.add('active');
        }

        function updateCategoryOptions() {
            const type = document.getElementById('productType').value;
            const categorySelect = document.getElementById('productCategory');
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            
            if (type === 'life') {
                const categories = ['Individual Life', 'GroupLife', 'Rider', 'LifeSharia'];
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category;
                    option.textContent = category;
                    categorySelect.appendChild(option);
                });
            } else if (type === 'general') {
                const categories = [
                    'Property', 'Motor', 'Engineering', 'Marine', 'Liability',
                    'Health Insurance', 'Personal Accident', 'Travel', 'Credit',
                    'Financial', 'Miscellaneous'
                ];
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category;
                    option.textContent = category;
                    categorySelect.appendChild(option);
                });
            }
        }

        function updateParentProductOptions(excludeId = null) {
            const type = document.getElementById('productType').value;
            const parentSelect = document.getElementById('parentProduct');
            
            // Keep the first option
            const firstOption = parentSelect.options[0];
            parentSelect.innerHTML = '';
            parentSelect.appendChild(firstOption);
            
            if (type) {
                // Get products of the same type (excluding the current product if editing)
                const parentCandidates = appState.products.filter(p => 
                    p.type === type && p.id != excludeId
                );
                
                parentCandidates.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = product.name;
                    parentSelect.appendChild(option);
                });
            }
        }

        function saveProduct() {
            const form = document.getElementById('productForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const productId = document.getElementById('productId').value;
            const productData = {
                name: document.getElementById('productName').value,
                code: document.getElementById('productCode').value,
                type: document.getElementById('productType').value,
                category: document.getElementById('productCategory').value,
                parentId: document.getElementById('parentProduct').value || null,
                status: document.getElementById('productStatus').value,
                description: document.getElementById('productDescription').value,
                premiumMin: parseFloat(document.getElementById('premiumMin').value) || null,
                premiumMax: parseFloat(document.getElementById('premiumMax').value) || null,
                coverageDetails: document.getElementById('coverageDetails').value,
                updatedAt: new Date().toISOString()
            };
            
            if (productId) {
                // Update existing product
                const index = appState.products.findIndex(p => p.id == productId);
                if (index !== -1) {
                    appState.products[index] = {
                        ...appState.products[index],
                        ...productData
                    };
                }
            } else {
                // Add new product
                productData.id = generateNewId();
                productData.createdAt = new Date().toISOString();
                appState.products.push(productData);
            }
            
            saveProducts();
            closeModal();
            
            // Refresh views
            renderTreeView();
            renderTableView();
            updateStats();
            setupBulkActions();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: `Product ${productId ? 'updated' : 'added'} successfully`,
                timer: 2000,
                showConfirmButton: false
            });
        }

        function generateNewId() {
            const maxId = appState.products.reduce((max, p) => Math.max(max, p.id), 0);
            return maxId + 1;
        }

        function deleteProduct(productId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Check if product has children
                    const hasChildren = appState.products.some(p => p.parentId == productId);
                    
                    if (hasChildren) {
                        Swal.fire({
                            title: 'Cannot Delete',
                            text: 'This product has child products. Please delete or reassign them first.',
                            icon: 'error'
                        });
                        return;
                    }
                    
                    // Remove product
                    appState.products = appState.products.filter(p => p.id != productId);
                    saveProducts();
                    
                    // Refresh views
                    renderTreeView();
                    renderTableView();
                    updateStats();
                    setupBulkActions();
                    
                    Swal.fire(
                        'Deleted!',
                        'Product has been deleted.',
                        'success'
                    );
                }
            });
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
        }

        function openImportModal() {
            document.getElementById('importModal').classList.add('active');
            document.getElementById('importPreview').style.display = 'none';
            document.getElementById('importFile').value = '';
        }

        function closeImportModal() {
            document.getElementById('importModal').classList.remove('active');
        }

        function processImport() {
            const fileInput = document.getElementById('importFile');
            const file = fileInput.files[0];
            
            if (!file) {
                Swal.fire('Error', 'Please select a file', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    let importedProducts = [];
                    const fileType = file.name.split('.').pop().toLowerCase();
                    
                    if (fileType === 'json') {
                        importedProducts = JSON.parse(e.target.result);
                    } else if (fileType === 'csv') {
                        importedProducts = parseCSV(e.target.result);
                    } else {
                        throw new Error('Unsupported file format');
                    }
                    
                    // Validate imported products
                    if (!Array.isArray(importedProducts)) {
                        throw new Error('Invalid data format');
                    }
                    
                    const importMode = document.getElementById('importMode').value;
                    
                    if (importMode === 'replace') {
                        appState.products = importedProducts;
                    } else if (importMode === 'update') {
                        importedProducts.forEach(importedProduct => {
                            const index = appState.products.findIndex(p => p.id === importedProduct.id);
                            if (index !== -1) {
                                appState.products[index] = importedProduct;
                            } else {
                                appState.products.push(importedProduct);
                            }
                        });
                    } else if (importMode === 'add') {
                        // Generate new IDs for imported products to avoid conflicts
                        importedProducts.forEach(product => {
                            product.id = generateNewId();
                            appState.products.push(product);
                        });
                    }
                    
                    saveProducts();
                    closeImportModal();
                    
                    // Refresh views
                    renderTreeView();
                    renderTableView();
                    updateStats();
                    setupBulkActions();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Import Successful',
                        text: `${importedProducts.length} products imported`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                } catch (error) {
                    Swal.fire('Error', `Failed to import: ${error.message}`, 'error');
                }
            };
            
            reader.readAsText(file);
        }

        function parseCSV(csvText) {
            const lines = csvText.split('\n');
            const headers = lines[0].split(',').map(h => h.trim());
            const products = [];
            
            for (let i = 1; i < lines.length; i++) {
                if (lines[i].trim() === '') continue;
                
                const values = lines[i].split(',').map(v => v.trim());
                const product = {};
                
                headers.forEach((header, index) => {
                    if (values[index]) {
                        // Try to parse JSON fields
                        if (header === 'coverageDetails' || header === 'metadata') {
                            try {
                                product[header] = JSON.parse(values[index]);
                            } catch {
                                product[header] = values[index];
                            }
                        } else {
                            product[header] = values[index];
                        }
                    }
                });
                
                products.push(product);
            }
            
            return products;
        }

        function exportProducts() {
            const dataStr = JSON.stringify(appState.products, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = `insurance-products-${new Date().toISOString().split('T')[0]}.json`;
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
            
            Swal.fire({
                icon: 'success',
                title: 'Export Successful',
                text: `${appState.products.length} products exported`,
                timer: 2000,
                showConfirmButton: false
            });
        }

        function expandAllNodes() {
            document.querySelectorAll('.tree-children').forEach(children => {
                children.classList.remove('collapsed');
            });
            document.querySelectorAll('.tree-toggle').forEach(toggle => {
                toggle.classList.remove('fa-chevron-right');
                toggle.classList.add('fa-chevron-down');
            });
        }

        function collapseAllNodes() {
            document.querySelectorAll('.tree-children').forEach(children => {
                children.classList.add('collapsed');
            });
            document.querySelectorAll('.tree-toggle').forEach(toggle => {
                toggle.classList.remove('fa-chevron-down');
                toggle.classList.add('fa-chevron-right');
            });
        }

        function filterProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            
            const filteredProducts = appState.products.filter(product => {
                const matchesSearch = product.name.toLowerCase().includes(searchTerm) ||
                                     product.code.toLowerCase().includes(searchTerm) ||
                                     product.description.toLowerCase().includes(searchTerm);
                
                const matchesCategory = !categoryFilter || product.type === categoryFilter;
                
                return matchesSearch && matchesCategory;
            });
            
            // Update table view
            const tbody = document.getElementById('productsTableBody');
            tbody.innerHTML = '';
            
            filteredProducts.forEach(product => {
                const parentProduct = product.parentId ? 
                    appState.products.find(p => p.id === product.parentId) : 
                    { name: 'None' };
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${product.name}</td>
                    <td><span class="badge ${product.type === 'life' ? 'badge-life' : 'badge-general'}">${product.type}</span></td>
                    <td>${product.category}</td>
                    <td>${parentProduct.name}</td>
                    <td><span class="status-badge status-${product.status}">${product.status}</span></td>
                    <td>
                        <div class="table-actions">
                            <button class="action-btn action-btn-edit" data-id="${product.id}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn action-btn-delete" data-id="${product.id}">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        function updateStats() {
            const lifeCount = appState.products.filter(p => p.type === 'life').length;
            const generalCount = appState.products.filter(p => p.type === 'general').length;
            
            // Count unique categories
            const categories = new Set(appState.products.map(p => p.category));
            const categoryCount = categories.size;
            
            // Count sub-products (products with parent)
            const subproductCount = appState.products.filter(p => p.parentId !== null).length;
            
            document.getElementById('lifeCount').textContent = lifeCount;
            document.getElementById('generalCount').textContent = generalCount;
            document.getElementById('categoryCount').textContent = categoryCount;
            document.getElementById('subproductCount').textContent = subproductCount;
        }

        function setupBulkActions() {
            const bulkProductList = document.getElementById('bulkProductList');
            bulkProductList.innerHTML = '';
            
            // Populate target category dropdown
            const targetCategory = document.getElementById('targetCategory');
            targetCategory.innerHTML = '<option value="">Select category...</option>';
            
            const categories = [...new Set(appState.products.map(p => p.category))];
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                targetCategory.appendChild(option);
            });
            
            // Populate product checkboxes
            appState.products.forEach(product => {
                const div = document.createElement('div');
                div.className = 'form-check';
                div.innerHTML = `
                    <input type="checkbox" class="form-check-input bulk-product-checkbox" value="${product.id}" id="bulk-${product.id}">
                    <label class="form-check-label" for="bulk-${product.id}">
                        ${product.name} (${product.code})
                    </label>
                `;
                bulkProductList.appendChild(div);
            });
        }

        function toggleBulkActionOptions() {
            const action = document.getElementById('bulkAction').value;
            const moveCategoryGroup = document.getElementById('moveCategoryGroup');
            
            if (action === 'move') {
                moveCategoryGroup.style.display = 'block';
            } else {
                moveCategoryGroup.style.display = 'none';
            }
        }

        function selectAllProducts() {
            document.querySelectorAll('.bulk-product-checkbox').forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        function deselectAllProducts() {
            document.querySelectorAll('.bulk-product-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        function applyBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const selectedProducts = Array.from(document.querySelectorAll('.bulk-product-checkbox:checked'))
                .map(cb => parseInt(cb.value));
            
            if (selectedProducts.length === 0) {
                Swal.fire('Warning', 'Please select at least one product', 'warning');
                return;
            }
            
            if (action === 'activate' || action === 'deactivate') {
                const newStatus = action === 'activate' ? 'active' : 'inactive';
                
                appState.products.forEach(product => {
                    if (selectedProducts.includes(product.id)) {
                        product.status = newStatus;
                        product.updatedAt = new Date().toISOString();
                    }
                });
                
                saveProducts();
                renderTableView();
                renderTreeView();
                
                Swal.fire('Success', `${selectedProducts.length} products ${action}d`, 'success');
                
            } else if (action === 'move') {
                const targetCategory = document.getElementById('targetCategory').value;
                
                if (!targetCategory) {
                    Swal.fire('Warning', 'Please select a target category', 'warning');
                    return;
                }
                
                appState.products.forEach(product => {
                    if (selectedProducts.includes(product.id)) {
                        product.category = targetCategory;
                        product.updatedAt = new Date().toISOString();
                    }
                });
                
                saveProducts();
                renderTableView();
                renderTreeView();
                
                Swal.fire('Success', `${selectedProducts.length} products moved to ${targetCategory}`, 'success');
                
            } else if (action === 'delete') {
                Swal.fire({
                    title: 'Confirm Deletion',
                    text: `Are you sure you want to delete ${selectedProducts.length} products?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Check for child products
                        const productsWithChildren = [];
                        selectedProducts.forEach(productId => {
                            const hasChildren = appState.products.some(p => p.parentId == productId);
                            if (hasChildren) {
                                const product = appState.products.find(p => p.id == productId);
                                productsWithChildren.push(product.name);
                            }
                        });
                        
                        if (productsWithChildren.length > 0) {
                            Swal.fire({
                                title: 'Cannot Delete',
                                html: `The following products have child products and cannot be deleted:<br><strong>${productsWithChildren.join(', ')}</strong>`,
                                icon: 'error'
                            });
                            return;
                        }
                        
                        // Delete products
                        appState.products = appState.products.filter(p => !selectedProducts.includes(p.id));
                        saveProducts();
                        
                        // Refresh views
                        renderTreeView();
                        renderTableView();
                        updateStats();
                        setupBulkActions();
                        
                        Swal.fire(
                            'Deleted!',
                            `${selectedProducts.length} products have been deleted.`,
                            'success'
                        );
                    }
                });
            }
        }

        function addToComparison(productId) {
            const product = appState.products.find(p => p.id == productId);
            if (!product) return;
            
            if (!appState.comparisonProducts.some(p => p.id == productId)) {
                appState.comparisonProducts.push(product);
                
                // Show comparison card if hidden
                document.getElementById('comparisonCard').style.display = 'block';
                updateComparisonView();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Comparison',
                    text: `${product.name} added to comparison`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }

        function updateComparisonView() {
            const comparisonContent = document.getElementById('comparisonContent');
            
            if (appState.comparisonProducts.length === 0) {
                comparisonContent.innerHTML = '<p>No products selected for comparison</p>';
                return;
            }
            
            let html = '<div class="table-container"><table class="table"><thead><tr><th>Feature</th>';
            
            // Add product headers
            appState.comparisonProducts.forEach(product => {
                html += `<th>${product.name}<br><small>${product.code}</small></th>`;
            });
            
            html += '</tr></thead><tbody>';
            
            // Add comparison rows
            const features = [
                { name: 'Type', key: 'type' },
                { name: 'Category', key: 'category' },
                { name: 'Status', key: 'status' },
                { name: 'Min Premium', key: 'premiumMin', format: v => v ? `$${v}` : 'N/A' },
                { name: 'Max Premium', key: 'premiumMax', format: v => v ? `$${v}` : 'N/A' }
            ];
            
            features.forEach(feature => {
                html += `<tr><td><strong>${feature.name}</strong></td>`;
                
                appState.comparisonProducts.forEach(product => {
                    let value = product[feature.key];
                    if (feature.format) {
                        value = feature.format(value);
                    }
                    html += `<td>${value || 'N/A'}</td>`;
                });
                
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            
            comparisonContent.innerHTML = html;
        }

        function clearComparison() {
            appState.comparisonProducts = [];
            document.getElementById('comparisonCard').style.display = 'none';
        }

        // Add some CSS for badges
        const style = document.createElement('style');
        style.textContent = `
            .badge {
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 0.8rem;
                font-weight: 600;
                text-transform: uppercase;
            }
            .badge-life {
                background-color: rgba(52, 152, 219, 0.1);
                color: var(--secondary-color);
            }
            .badge-general {
                background-color: rgba(39, 174, 96, 0.1);
                color: var(--success-color);
            }
            .status-badge {
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 0.8rem;
                font-weight: 600;
            }
            .status-active {
                background-color: rgba(39, 174, 96, 0.1);
                color: var(--success-color);
            }
            .status-inactive {
                background-color: rgba(149, 165, 166, 0.1);
                color: #95a5a6;
            }
            .status-draft {
                background-color: rgba(243, 156, 18, 0.1);
                color: var(--warning-color);
            }
            .tree-children.collapsed {
                display: none;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>