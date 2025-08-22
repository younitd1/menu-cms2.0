<?php
/**
 * CMS Installation Script
 * Creates database tables and initial configuration
 */

// Prevent running if already installed
if (file_exists('config/database_credentials.php')) {
    $config = require 'config/database_credentials.php';
    if (!empty($config['dbname'])) {
        die('System appears to be already installed. Delete config/database_credentials.php to reinstall.');
    }
}

$step = $_GET['step'] ?? 1;
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            $step = handleDatabaseStep();
            break;
        case 2:
            $step = handleUserStep();
            break;
        case 3:
            $step = handleSiteStep();
            break;
        case 4:
            $step = handleContentStep();
            break;
    }
}

function handleDatabaseStep() {
    global $errors;
    
    $host = trim($_POST['db_host'] ?? '');
    $dbname = trim($_POST['db_name'] ?? '');
    $username = trim($_POST['db_username'] ?? '');
    $password = $_POST['db_password'] ?? '';
    
    if (empty($host) || empty($dbname) || empty($username)) {
        $errors[] = "All database fields are required except password.";
        return 1;
    }
    
    try {
        // Test connection
        $dsn = "mysql:host={$host};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbname}`");
        
        // Create all tables
        createTables($pdo);
        
        // Save credentials
        $credentialsContent = "<?php\nreturn [\n";
        $credentialsContent .= "    'host' => '{$host}',\n";
        $credentialsContent .= "    'dbname' => '{$dbname}',\n";
        $credentialsContent .= "    'username' => '{$username}',\n";
        $credentialsContent .= "    'password' => '{$password}'\n";
        $credentialsContent .= "];\n?>";
        
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        
        file_put_contents('config/database_credentials.php', $credentialsContent);
        
        // Store database connection for next steps
        session_start();
        $_SESSION['install_pdo'] = serialize([
            'host' => $host,
            'dbname' => $dbname,
            'username' => $username,
            'password' => $password
        ]);
        
        return 2;
        
    } catch (Exception $e) {
        $errors[] = "Database connection failed: " . $e->getMessage();
        return 1;
    }
}

function handleUserStep() {
    global $errors;
    
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
        return 2;
    }
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters.";
        return 2;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
        return 2;
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
        return 2;
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
        return 2;
    }
    
    try {
        $pdo = getInstallPDO();
        
        // Create admin user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $email, $hashedPassword, $fullName]);
        
        return 3;
        
    } catch (Exception $e) {
        $errors[] = "Failed to create user: " . $e->getMessage();
        return 2;
    }
}

function handleSiteStep() {
    global $errors;
    
    $siteName = trim($_POST['site_name'] ?? '');
    $siteDescription = trim($_POST['site_description'] ?? '');
    $siteAddress = trim($_POST['site_address'] ?? '');
    $sitePhone = trim($_POST['site_phone'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    
    if (empty($siteName)) {
        $errors[] = "Site name is required.";
        return 3;
    }
    
    try {
        $pdo = getInstallPDO();
        
        // Insert site settings
        $settings = [
            ['site_name', $siteName, 'site'],
            ['site_description', $siteDescription, 'site'],
            ['site_address', $siteAddress, 'site'],
            ['site_phone', $sitePhone, 'site'],
            ['company_name', $companyName, 'site']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_group) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
        
        // Handle logo uploads
        handleLogoUploads($pdo);
        
        return 4;
        
    } catch (Exception $e) {
        $errors[] = "Failed to save site settings: " . $e->getMessage();
        return 3;
    }
}

function handleContentStep() {
    global $errors, $success;
    
    try {
        $pdo = getInstallPDO();
        
        // Create sample category
        createSampleCategory($pdo);
        
        // Create sample product
        createSampleProduct($pdo);
        
        // Create sample modules
        createSampleModules($pdo);
        
        // Initialize dashboard modules
        initializeDashboardModules($pdo);
        
        // Initialize theme settings
        initializeThemeSettings($pdo);
        
        // Initialize security settings
        initializeSecuritySettings($pdo);
        
        // Initialize email settings
        initializeEmailSettings($pdo);
        
        $success = "Installation completed successfully! You can now login to the admin panel.";
        
        // Clear installation session
        session_start();
        unset($_SESSION['install_pdo']);
        
        return 5;
        
    } catch (Exception $e) {
        $errors[] = "Failed to create sample content: " . $e->getMessage();
        return 4;
    }
}

function getInstallPDO() {
    session_start();
    if (!isset($_SESSION['install_pdo'])) {
        throw new Exception("Database connection lost. Please restart installation.");
    }
    
    $config = unserialize($_SESSION['install_pdo']);
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    
    return new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

function createTables($pdo) {
    $tables = [
        "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            status ENUM('active', 'inactive') DEFAULT 'active'
        )",
        
        "CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(50) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            description TEXT,
            background_image VARCHAR(255) NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_status (status)
        )",
        
        "CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            title VARCHAR(50) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            short_description TEXT,
            full_description LONGTEXT,
            price DECIMAL(10,2) DEFAULT 0.00,
            image VARCHAR(255) NULL,
            image_alignment ENUM('none', 'left', 'right') DEFAULT 'none',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            INDEX idx_category (category_id),
            INDEX idx_slug (slug),
            INDEX idx_status (status)
        )",
        
        "CREATE TABLE modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(50) NOT NULL,
            description TEXT,
            module_type ENUM('rich_text', 'custom_html', 'image', 'menu') NOT NULL,
            content LONGTEXT,
            position VARCHAR(50) NOT NULL,
            background_image VARCHAR(255) NULL,
            background_color VARCHAR(7) NULL,
            slideshow_enabled ENUM('yes', 'no') DEFAULT 'no',
            click_tracking ENUM('yes', 'no') DEFAULT 'no',
            display_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_position (position),
            INDEX idx_status (status),
            INDEX idx_order (display_order)
        )",
        
        "CREATE TABLE module_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            image_order INT DEFAULT 0,
            alt_text VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
            INDEX idx_module (module_id),
            INDEX idx_order (image_order)
        )",
        
        "CREATE TABLE menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_id INT NOT NULL,
            category_id INT NOT NULL,
            link_text VARCHAR(100) NOT NULL,
            link_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            INDEX idx_module (module_id),
            INDEX idx_category (category_id),
            INDEX idx_order (link_order)
        )",
        
        "CREATE TABLE settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value LONGTEXT,
            setting_group VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (setting_key),
            INDEX idx_group (setting_group)
        )",
        
        "CREATE TABLE theme_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_bg_image VARCHAR(255) NULL,
            full_bg_color VARCHAR(7) DEFAULT '#1a269b',
            store_logo VARCHAR(255) NULL,
            login_logo VARCHAR(255) NULL,
            backend_logo VARCHAR(255) NULL,
            main_font VARCHAR(100) DEFAULT 'Arial, sans-serif',
            h1_font VARCHAR(100) DEFAULT 'Arial, sans-serif',
            h1_color VARCHAR(7) DEFAULT '#ffcc3f',
            h1_size INT DEFAULT 32,
            h2_font VARCHAR(100) DEFAULT 'Arial, sans-serif',
            h2_color VARCHAR(7) DEFAULT '#ffcc3f',
            h2_size INT DEFAULT 28,
            h3_font VARCHAR(100) DEFAULT 'Arial, sans-serif',
            h3_color VARCHAR(7) DEFAULT '#ffffff',
            h3_size INT DEFAULT 24,
            h4_font VARCHAR(100) DEFAULT 'Arial, sans-serif',
            h4_color VARCHAR(7) DEFAULT '#ffffff',
            h4_size INT DEFAULT 20,
            h5_font VARCHAR(100) DEFAULT 'Arial, sans-serif',
            h5_color VARCHAR(7) DEFAULT '#ffffff',
            h5_size INT DEFAULT 16,
            links_color VARCHAR(7) DEFAULT '#0066cc',
            product_margin INT DEFAULT 20,
            product_padding INT DEFAULT 15,
            category_margin INT DEFAULT 20,
            category_padding INT DEFAULT 15,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE visitor_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            page_visited VARCHAR(255),
            country VARCHAR(100),
            state VARCHAR(100),
            visit_date DATE NOT NULL,
            visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_date (visit_date),
            INDEX idx_ip (ip_address)
        )",
        
        "CREATE TABLE click_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            click_type ENUM('order_now', 'phone_number') NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            click_date DATE NOT NULL,
            click_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_type (click_type),
            INDEX idx_date (click_date)
        )",
        
        "CREATE TABLE dashboard_modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_name VARCHAR(100) NOT NULL,
            module_title VARCHAR(255) NOT NULL,
            module_description TEXT,
            module_query LONGTEXT NOT NULL,
            display_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order (display_order),
            INDEX idx_status (status)
        )",
        
        "CREATE TABLE security_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            captcha_site_key VARCHAR(255),
            captcha_secret_key VARCHAR(255),
            under_construction ENUM('yes', 'no') DEFAULT 'no',
            max_login_attempts INT DEFAULT 5,
            lockout_duration INT DEFAULT 15,
            session_timeout INT DEFAULT 1440,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE email_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            smtp_host VARCHAR(255),
            smtp_port INT DEFAULT 587,
            smtp_username VARCHAR(255),
            smtp_password VARCHAR(255),
            smtp_encryption ENUM('tls', 'ssl', 'none') DEFAULT 'tls',
            from_email VARCHAR(255),
            from_name VARCHAR(255),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_time (attempt_time)
        )",
        
        "CREATE TABLE password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) UNIQUE NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        )"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
}

function createSampleCategory($pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO categories (title, slug, description, status) 
        VALUES (?, ?, ?, 'active')
    ");
    $stmt->execute([
        'First Category Product',
        'first-category-product', 
        'This is the description of the First Category product'
    ]);
    
    return $pdo->lastInsertId();
}

function createSampleProduct($pdo) {
    // Get the category ID
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = 'first-category-product'");
    $stmt->execute();
    $category = $stmt->fetch();
    
    if ($category) {
        $stmt = $pdo->prepare("
            INSERT INTO products (category_id, title, slug, short_description, full_description, price, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $category['id'],
            'My first product',
            'my-first-product',
            'This is my short description of this product.',
            'The price of this product is…………….$25.00',
            25.00
        ]);
    }
}

function createSampleModules($pdo) {
    $modules = [
        [
            'title' => 'Welcome Header',
            'description' => 'Main welcome message',
            'module_type' => 'rich_text',
            'content' => '<h1>Welcome to Our Store</h1><p>Discover amazing products at great prices!</p>',
            'position' => 'top-module-1'
        ],
        [
            'title' => 'Navigation Menu',
            'description' => 'Main site navigation',
            'module_type' => 'menu',
            'content' => '',
            'position' => 'top-module-2'
        ],
        [
            'title' => 'Featured Banner',
            'description' => 'Featured products banner',
            'module_type' => 'image',
            'content' => json_encode(['width' => '100%', 'as_background' => false]),
            'position' => 'top-module-3'
        ],
        [
            'title' => 'About Us',
            'description' => 'About company information',
            'module_type' => 'rich_text',
            'content' => '<h2>About Our Company</h2><p>We are dedicated to providing the best products and services to our customers.</p>',
            'position' => 'bottom-module-1'
        ],
        [
            'title' => 'Footer Info',
            'description' => 'Footer information',
            'module_type' => 'custom_html',
            'content' => '<div style="text-align: center; color: white;"><p>&copy; 2025 Your Company Name. All rights reserved.</p></div>',
            'position' => 'footer-module-1'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO modules (title, description, module_type, content, position, status) 
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    
    foreach ($modules as $module) {
        $stmt->execute([
            $module['title'],
            $module['description'],
            $module['module_type'],
            $module['content'],
            $module['position']
        ]);
    }
}

function initializeDashboardModules($pdo) {
    $dashboardModules = [
        [
            'module_name' => 'total_products',
            'module_title' => 'Total Products',
            'module_description' => 'Total number of products in the system',
            'module_query' => 'SELECT COUNT(*) as count FROM products WHERE status = "active"',
            'display_order' => 1
        ],
        [
            'module_name' => 'active_modules',
            'module_title' => 'Active Modules',
            'module_description' => 'Number of active modules',
            'module_query' => 'SELECT COUNT(*) as count FROM modules WHERE status = "active"',
            'display_order' => 2
        ],
        [
            'module_name' => 'total_categories',
            'module_title' => 'Total Categories',
            'module_description' => 'Number of categories available',
            'module_query' => 'SELECT COUNT(*) as count FROM categories WHERE status = "active"',
            'display_order' => 3
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO dashboard_modules (module_name, module_title, module_description, module_query, display_order, status) 
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    
    foreach ($dashboardModules as $module) {
        $stmt->execute([
            $module['module_name'],
            $module['module_title'],
            $module['module_description'],
            $module['module_query'],
            $module['display_order']
        ]);
    }
}

function initializeThemeSettings($pdo) {
    $stmt = $pdo->prepare("INSERT INTO theme_settings (id) VALUES (1)");
    $stmt->execute();
}

function initializeSecuritySettings($pdo) {
    $stmt = $pdo->prepare("INSERT INTO security_settings (id) VALUES (1)");
    $stmt->execute();
}

function initializeEmailSettings($pdo) {
    $stmt = $pdo->prepare("INSERT INTO email_settings (id) VALUES (1)");
    $stmt->execute();
}

function handleLogoUploads($pdo) {
    $uploadDir = 'uploads/logos';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Handle each logo upload
    $logoFields = ['store_logo', 'login_logo', 'backend_logo'];
    $uploads = [];
    
    foreach ($logoFields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $filename = uniqid() . '_' . basename($_FILES[$field]['name']);
            $filepath = $uploadDir . '/' . $filename;
            
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $filepath)) {
                $uploads[$field] = $filename;
            }
        }
    }
    
    // Update theme settings with uploaded logos
    if (!empty($uploads)) {
        $setClause = [];
        $params = [];
        
        foreach ($uploads as $field => $filename) {
            $setClause[] = "{$field} = ?";
            $params[] = $filename;
        }
        
        $sql = "UPDATE theme_settings SET " . implode(', ', $setClause) . " WHERE id = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a269b, #2d3db4);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .header {
            background: #1a269b;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            font-weight: bold;
        }
        
        .step.active {
            background: #ffcc3f;
            color: #1a269b;
        }
        
        .step.completed {
            background: #00c851;
        }
        
        .content {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1a269b;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #1a269b;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2d3db4;
            transform: translateY(-2px);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        
        .success {
            background: #efe;
            color: #363;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #363;
        }
        
        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }
        
        .file-upload:hover {
            border-color: #1a269b;
        }
        
        .file-upload input {
            display: none;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CMS Installation</h1>
            <div class="step-indicator">
                <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">1</div>
                <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2</div>
                <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">3</div>
                <div class="step <?= $step >= 4 ? ($step > 4 ? 'completed' : 'active') : '' ?>">4</div>
                <div class="step <?= $step >= 5 ? 'completed' : '' ?>">✓</div>
            </div>
        </div>
        
        <div class="content">
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success">
                    <p><?= htmlspecialchars($success) ?></p>
                    <p><strong><a href="admin/login.php">Click here to login</a></strong></p>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <h2>Database Configuration</h2>
                <p>Please enter your database connection details:</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" class="form-control" value="cms_database" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_username">Database Username</label>
                        <input type="text" id="db_username" name="db_username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_password">Database Password</label>
                        <input type="password" id="db_password" name="db_password" class="form-control">
                        <small style="color: #666;">Leave blank if no password is required</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Next Step</button>
                </form>
            
            <?php elseif ($step == 2): ?>
                <h2>Admin User Setup</h2>
                <p>Create your administrator account:</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Next Step</button>
                </form>
            
            <?php elseif ($step == 3): ?>
                <h2>Site Information</h2>
                <p>Configure your website settings:</p>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name" class="form-control" required maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">Site Description</label>
                        <textarea id="site_description" name="site_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_address">Site Address</label>
                        <textarea id="site_address" name="site_address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_phone">Phone Number</label>
                        <input type="text" id="site_phone" name="site_phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="company_name">Company Name</label>
                        <input type="text" id="company_name" name="company_name" class="form-control" value="YD Multimedia">
                    </div>
                    
                    <h3>Logo Uploads (Optional)</h3>
                    
                    <div class="form-group">
                        <label for="store_logo">Store Logo (Front-end)</label>
                        <div class="file-upload" onclick="document.getElementById('store_logo').click()">
                            <input type="file" id="store_logo" name="store_logo" accept="image/jpeg">
                            <p>Click to upload store logo (JPG, max 300KB)</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="login_logo">Login Logo</label>
                        <div class="file-upload" onclick="document.getElementById('login_logo').click()">
                            <input type="file" id="login_logo" name="login_logo" accept="image/jpeg">
                            <p>Click to upload login logo (JPG, max 300KB)</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="backend_logo">Backend Logo</label>
                        <div class="file-upload" onclick="document.getElementById('backend_logo').click()">
                            <input type="file" id="backend_logo" name="backend_logo" accept="image/jpeg">
                            <p>Click to upload backend logo (JPG, max 300KB)</p>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Next Step</button>
                </form>
            
            <?php elseif ($step == 4): ?>
                <h2>Sample Content</h2>
                <p>We'll create sample content to help you get started:</p>
                
                <ul style="margin: 20px 0; padding-left: 20px;">
                    <li>Sample category: "First Category Product"</li>
                    <li>Sample product: "My first product" ($25.00)</li>
                    <li>5 sample modules (header, navigation, banner, about, footer)</li>
                    <li>Dashboard metrics configuration</li>
                    <li>Default theme settings</li>
                </ul>
                
                <form method="POST">
                    <button type="submit" class="btn btn-primary">Create Sample Content & Finish</button>
                </form>
            
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // File upload feedback
        document.addEventListener('DOMContentLoaded', function() {
            const fileInputs = document.querySelectorAll('input[type="file"]');
            
            fileInputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    const fileUpload = this.parentNode;
                    const fileName = this.files[0] ? this.files[0].name : 'Click to upload';
                    fileUpload.querySelector('p').textContent = fileName;
                });
            });
            
            // Password confirmation validation
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (confirmPassword) {
                confirmPassword.addEventListener('input', function() {
                    if (this.value !== password.value) {
                        this.setCustomValidity('Passwords do not match');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        });
    </script>
</body>
</html>db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="
