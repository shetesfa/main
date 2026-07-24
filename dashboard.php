<?php
// Start output buffering
ob_start();

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Define the missing function at the top of the file
if (!function_exists('getWebsitesByCategory')) {
    function getWebsitesByCategory($category_id) {
        global $conn;
        $sql = "SELECT * FROM websites WHERE category_id = $category_id AND is_active = 1 ORDER BY created_at DESC";
        $result = $conn->query($sql);
        $websites = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $websites[] = $row;
            }
        }
        return $websites;
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if password change is required
if (isset($_SESSION['password_change_required']) && $_SESSION['password_change_required'] == 1) {
    header("Location: change_password.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';
$add_success = '';
$add_error = '';
$website_success = '';
$website_error = '';

// Handle category password verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_category'])) {
    $category_id = (int)$_POST['category_id'];
    $password = $_POST['category_password'];
    
    // Check category password
    $sql = "SELECT category_password FROM categories WHERE id = $category_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $category = $result->fetch_assoc();
        
        if (md5($password) == $category['category_password']) {
            $_SESSION['temp_access'][$category_id] = true;
            $_SESSION['temp_access_time'][$category_id] = time();
            $success = "✅ መዳረሻ ተሰጥቷል! አሁን ድረ ገጾቹን ማየት ይችላሉ።";
        } else {
            $error = "❌ የክፍሉ ይለፍ ቃል ትክክል አይደለም!";
        }
    } else {
        $error = "❌ ክፍል አልተገኘም!";
    }
}

// Handle add user form submission - MANUAL USERNAME ENTRY WITH DEFAULT PASSWORD "123"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $username = $conn->real_escape_string($_POST['username']);
    
    // Default password is always "123"
    $default_password = '123';
    $password = md5($default_password);
    
    // Check if username already exists
    $check_sql = "SELECT id FROM users WHERE username = '$username'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        $add_error = "❌ ይህ የተጠቃሚ ስም አስቀድሞ አለ! እባክዎ ሌላ ይምረጡ።";
    } else {
        $assigned_category = (int)$_SESSION['assigned_category'];
        $created_by = (int)$_SESSION['user_id'];
        
        // Insert new user with password_change_required = 1
        $sql = "INSERT INTO users (full_name, username, password, role, assigned_category_id, created_by, password_change_required) 
                VALUES ('$full_name', '$username', '$password', 'category_owner', $assigned_category, $created_by, 1)";
        
        if ($conn->query($sql)) {
            $add_success = "
                <div style='background: #D1FAE5; color: #10B981; padding: 15px; border-radius: 8px; border-left: 4px solid #10B981;'>
                    <strong>✅ ተጠቃሚ በተሳካ ሁኔታ ታክሏል!</strong><br><br>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; background: #FFF8DC;'><strong>ሙሉ ስም:</strong></td>
                            <td style='padding: 8px;'>$full_name</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; background: #FFF8DC;'><strong>የተጠቃሚ ስም:</strong></td>
                            <td style='padding: 8px;'><code>$username</code></td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; background: #FFF8DC;'><strong>ነባሪ ይለፍ ቃል:</strong></td>
                            <td style='padding: 8px;'><code>123</code></td>
                        </tr>
                    </table>
                    <br>
                    <small>📝 ማስታወሻ: ተጠቃሚው ለመጀመሪያ ጊዜ ሲገባ ይለፍ ቃሉን መቀየር ይኖርበታል።</small>
                </div>";
        } else {
            $add_error = "❌ ተጠቃሚ መጨመር አልተሳካም: " . $conn->error;
        }
    }
}

// Handle add website to DATABASE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_website'])) {
    $website_name = $conn->real_escape_string($_POST['website_name']);
    $website_url = $conn->real_escape_string($_POST['website_url']);
    $website_desc = $conn->real_escape_string($_POST['website_desc']);
    $category_id = (int)$_SESSION['assigned_category'];
    $added_by = $_SESSION['full_name'];
    
    // Check if created_by column exists
    $check_column = $conn->query("SHOW COLUMNS FROM websites LIKE 'created_by'");
    
    if ($check_column && $check_column->num_rows > 0) {
        // Column exists - use it
        $sql = "INSERT INTO websites (category_id, name, url, description, created_by, is_active) 
                VALUES ($category_id, '$website_name', '$website_url', '$website_desc', '$added_by', 1)";
    } else {
        // Column doesn't exist - insert without created_by
        $sql = "INSERT INTO websites (category_id, name, url, description, is_active) 
                VALUES ($category_id, '$website_name', '$website_url', '$website_desc', 1)";
    }
    
    if ($conn->query($sql)) {
        $website_success = "✅ ድረ ገጽ በተሳካ ሁኔታ ወደ ዳታቤዝ ታክሏል!";
    } else {
        $website_error = "❌ ድረ ገጽ መጨመር አልተሳካም: " . $conn->error;
    }
}

// Get user info
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];
$assigned_category = isset($_SESSION['assigned_category']) ? $_SESSION['assigned_category'] : null;
$assigned_category_name = isset($_SESSION['assigned_category_name']) ? $_SESSION['assigned_category_name'] : '';

// Get all categories
$categories = null;
$sql = "SELECT * FROM categories ORDER BY id";
$result = $conn->query($sql);
if ($result) {
    $categories = $result;
}

// Get users created by this category owner - ONLY PENDING USERS
$created_users = null;
if ($user_role == 'category_owner') {
    $created_users_sql = "SELECT * FROM users WHERE created_by = $user_id AND password_change_required = 1 ORDER BY created_at DESC";
    $created_users = $conn->query($created_users_sql);
}

// Get category passwords for superadmin
$category_passwords = [];
if ($user_role == 'superadmin') {
    $pass_sql = "SELECT id, name_amharic, name_english FROM categories";
    $pass_result = $conn->query($pass_sql);
    if ($pass_result) {
        while ($row = $pass_result->fetch_assoc()) {
            // Original passwords
            $original_pass = '';
            switch($row['id']) {
                case 1: $original_pass = 'edu@123'; break;
                case 2: $original_pass = 'music@123'; break;
                case 3: $original_pass = 'dev@123'; break;
                case 4: $original_pass = 'prop@123'; break;
                case 5: $original_pass = 'mem@123'; break;
                case 6: $original_pass = 'cong@123'; break;
            }
            $row['password'] = $original_pass;
            $category_passwords[] = $row;
        }
    }
}

// Get statistics
$total_categories = $categories ? $categories->num_rows : 0;
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_websites = $conn->query("SELECT COUNT(*) as count FROM websites WHERE is_active = 1")->fetch_assoc()['count'];
$pending_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE password_change_required = 1")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ዳሽቦርድ - የቤተክርስቲያን ማኔጅመንት ሲስተም</title>
    <style>
        :root {
            --brown-dark: #8B4513;
            --brown-medium: #A52A2A;
            --brown-light: #CD853F;
            --gold-primary: #FFD700;
            --gold-dark: #DAA520;
            --gold-light: #FBBF24;
            --gold-pale: #FFF8DC;
            --bg-cream: #FAF9F6;
            --success-green: #10B981;
            --error-red: #EF4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: var(--bg-cream);
        }

        .header {
            background: linear-gradient(135deg, #8B4513 0%, #A52A2A 100%);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--gold-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--brown-dark);
        }

        .title h1 {
            font-size: 20px;
            color: var(--gold-primary);
        }

        .title p {
            font-size: 14px;
            color: var(--gold-light);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-name {
            text-align: right;
            background: rgba(0,0,0,0.2);
            padding: 10px 20px;
            border-radius: 10px;
        }

        .user-name strong {
            color: var(--gold-primary);
        }

        .logout-btn {
            background: var(--gold-primary);
            color: var(--brown-dark);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: var(--gold-dark);
            transform: translateY(-2px);
        }

        .nav {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-links {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-link {
            padding: 10px 20px;
            color: var(--brown-dark);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--gold-pale);
            color: var(--gold-dark);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .warning-message {
            background: #FEF3C7;
            color: #D97706;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 5px solid #D97706;
        }

        .warning-message span {
            font-size: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid var(--gold-primary);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 30px;
            color: var(--gold-dark);
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--brown-dark);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gold-pale);
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-header h2 {
            color: var(--brown-dark);
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary) 0%, var(--gold-dark) 100%);
            color: var(--brown-dark);
            border: 2px solid var(--gold-primary);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139,69,19,0.3);
        }

        .btn-secondary {
            background: var(--brown-dark);
            color: var(--gold-primary);
            border: 2px solid var(--gold-primary);
        }

        .btn-secondary:hover {
            background: var(--brown-medium);
        }

        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;
            position: relative;
            animation: slideDown 0.3s ease;
            border: 3px solid var(--gold-primary);
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: var(--brown-light);
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: var(--brown-dark);
        }

        .modal h2 {
            color: var(--brown-dark);
            margin-bottom: 5px;
        }

        .modal .category-name {
            color: var(--gold-dark);
            font-weight: 600;
            margin-bottom: 25px;
            font-size: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gold-pale);
        }

        .info-box {
            background: var(--gold-pale);
            color: var(--brown-dark);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid var(--gold-primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--brown-dark);
            font-weight: 600;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gold-pale);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
        }

        .form-group input[readonly] {
            background: var(--gold-pale);
            color: var(--brown-dark);
            font-weight: 600;
            border: 2px dashed var(--gold-primary);
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: var(--brown-light);
            font-size: 12px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--gold-primary) 0%, var(--gold-dark) 100%);
            color: var(--brown-dark);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139,69,19,0.3);
        }

        /* Category Passwords Section */
        .passwords-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 2px solid var(--gold-primary);
        }

        .password-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .password-card {
            background: var(--gold-pale);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--gold-dark);
        }

        .password-card h4 {
            color: var(--brown-dark);
            margin-bottom: 10px;
            font-size: 16px;
        }

        .password-card p {
            color: var(--brown-medium);
            margin-bottom: 5px;
        }

        .password-card code {
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            color: var(--brown-dark);
            font-weight: bold;
            cursor: pointer;
            border: 1px solid var(--gold-primary);
        }

        .password-card code:hover {
            background: var(--gold-pale);
        }

        /* Users Table */
        .users-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--gold-pale);
            color: var(--brown-dark);
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gold-pale);
        }

        tr:hover {
            background: #FEF9E7;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: #FEF3C7;
            color: #D97706;
        }

        .default-password-badge {
            background: var(--gold-primary);
            color: var(--brown-dark);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        /* Category Grid */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 2px solid transparent;
            transition: transform 0.3s;
        }

        .category-card:hover {
            transform: translateY(-5px);
        }

        .category-card.assigned {
            border-color: var(--gold-primary);
        }

        .category-header {
            background: linear-gradient(135deg, #8B4513 0%, #A52A2A 100%);
            color: white;
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .category-header.assigned {
            background: linear-gradient(135deg, var(--gold-primary) 0%, var(--gold-dark) 100%);
            color: var(--brown-dark);
        }

        .category-header h3 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .assigned-tag {
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
        }

        .toggle-icon {
            font-size: 24px;
            font-weight: bold;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
        }

        .category-websites {
            padding: 20px;
            display: none;
            background: white;
        }

        .category-websites.active {
            display: block;
        }

        .website-link {
            display: block;
            background: var(--gold-pale);
            color: var(--brown-dark);
            text-decoration: none;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 10px;
            border-left: 4px solid var(--gold-primary);
            transition: all 0.3s;
        }

        .website-link:hover {
            background: #FEF9E7;
            transform: translateX(5px);
        }

        .website-name {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 16px;
            color: var(--brown-dark);
        }

        .website-desc {
            font-size: 14px;
            color: var(--brown-medium);
            margin-bottom: 5px;
        }

        .website-url {
            font-size: 12px;
            color: var(--gold-dark);
            word-break: break-all;
        }

        .website-meta {
            font-size: 11px;
            color: var(--brown-light);
            margin-top: 5px;
        }

        .fixed-badge {
            background: var(--gold-primary);
            color: var(--brown-dark);
            padding: 2px 8px;
            border-radius: 50px;
            font-size: 10px;
            margin-left: 8px;
            display: inline-block;
            font-weight: bold;
        }

        .access-form {
            padding: 20px;
            background: var(--gold-pale);
            border-radius: 10px;
            margin-top: 10px;
        }

        .access-form h4 {
            color: var(--brown-dark);
            margin-bottom: 15px;
            font-size: 14px;
        }

        .access-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 12px;
            border: 2px solid var(--gold-pale);
            border-radius: 8px;
            font-size: 14px;
        }

        .access-form button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--gold-primary) 0%, var(--gold-dark) 100%);
            color: var(--brown-dark);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .access-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139,69,19,0.3);
        }

        .granted-badge {
            background: var(--success-green);
            color: white;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 13px;
            display: inline-block;
            margin-bottom: 15px;
        }

        .no-websites {
            text-align: center;
            color: var(--brown-light);
            padding: 30px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .category-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo-area">
                <div class="logo-icon">⛪</div>
                <div class="title">
                    <h1>የቤተክርስቲያን ማኔጅመንት ሲስተም</h1>
                    <p>Church Management System</p>
                </div>
            </div>
            <div class="user-info">
                <div class="user-name">
                    <div><?php echo htmlspecialchars($full_name); ?></div>
                    <small><strong>
                        <?php 
                        if($user_role == 'superadmin') echo 'ሱፐር አድሚን';
                        elseif($user_role == 'admin') echo 'አድሚን';
                        else echo 'የክፍል ባለቤት';
                        ?>
                        <?php if ($assigned_category && $assigned_category_name): ?>
                            | <?php echo htmlspecialchars($assigned_category_name); ?>
                        <?php endif; ?>
                    </strong></small>
                </div>
                <a href="?logout=1" class="logout-btn">ውጣ</a>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="nav">
        <div class="nav-links">
            <a href="#" class="nav-link active">ዳሽቦርድ</a>
            <a href="#" class="nav-link">ክፍሎች</a>
            <?php if ($user_role == 'superadmin'): ?>
                <a href="#" class="nav-link">አስተዳደር</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="container">
        <!-- Password Change Warning -->
        <?php if (isset($_SESSION['password_change_required']) && $_SESSION['password_change_required'] == 1): ?>
            <div class="warning-message">
                <span>⚠️</span>
                <div>
                    <strong>ማስጠንቀቂያ!</strong> እባክዎ ነባሪ ይለፍ ቃልዎን (123) ይቀይሩ። 
                    <a href="change_password.php" style="color: #D97706; font-weight: bold; text-decoration: underline;">አሁን ቀይር</a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Alert Messages -->
        <?php if ($error): ?>
            <div style="background: #FEF3C7; color: #EF4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #EF4444;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div style="background: #D1FAE5; color: #10B981; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10B981;"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($add_success): ?>
            <div style="margin-bottom: 20px;"><?php echo $add_success; ?></div>
        <?php endif; ?>
        
        <?php if ($website_success): ?>
            <div style="background: #D1FAE5; color: #10B981; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10B981;"><?php echo $website_success; ?></div>
        <?php endif; ?>
        
        <?php if ($website_error): ?>
            <div style="background: #FEF3C7; color: #EF4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #EF4444;"><?php echo $website_error; ?></div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-value"><?php echo $total_categories; ?></div>
                <div class="stat-label">ክፍሎች</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">ተጠቃሚዎች</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🌐</div>
                <div class="stat-value"><?php echo $total_websites; ?></div>
                <div class="stat-label">ድረ ገጾች</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?php echo $pending_users; ?></div>
                <div class="stat-label">ይለፍ ቃል መቀየር ያለባቸው</div>
            </div>
        </div>
        
        <!-- Superadmin Category Passwords Section -->
        <?php if ($user_role == 'superadmin' && !empty($category_passwords)): ?>
            <div class="passwords-section">
                <div class="section-header">
                    <h2>🔑 የክፍሎች ይለፍ ቃላት</h2>
                    <span class="btn btn-primary" onclick="copyAllPasswords()">ሁሉንም ቅዳር</span>
                </div>
                <div class="password-grid">
                    <?php foreach ($category_passwords as $cat): ?>
                        <div class="password-card">
                            <h4><?php echo htmlspecialchars($cat['name_amharic']); ?></h4>
                            <p><?php echo htmlspecialchars($cat['name_english']); ?></p>
                            <code onclick="copyToClipboard('<?php echo $cat['password']; ?>')" title="ቅዳር ለመቅዳት ጠቅ ያድርጉ">
                                <?php echo $cat['password']; ?>
                            </code>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Category Owner Section -->
        <?php if ($user_role == 'category_owner'): ?>
            <!-- Action Buttons -->
            <div class="section">
                <div class="section-header">
                    <h2>⚡ ፈጣን ድርጊቶች</h2>
                    <div class="btn-group">
                        <button onclick="openAddUserModal()" class="btn btn-primary">➕ አዲስ ተጠቃሚ ጨምር</button>
                        <button onclick="openAddWebsiteModal()" class="btn btn-secondary">🌐 አዲስ ድረ ገጽ ጨምር</button>
                    </div>
                </div>
            </div>
            
            <!-- Add User Modal - MANUAL USERNAME ENTRY -->
            <div id="addUserModal" class="modal">
                <div class="modal-content">
                    <span onclick="closeAddUserModal()" class="close-btn">&times;</span>
                    <h2>አዲስ ተጠቃሚ ጨምር</h2>
                    <div class="category-name">ክፍል: <?php echo htmlspecialchars($assigned_category_name); ?></div>
                    
                    <div class="info-box">
                        <strong>ℹ️ ማስታወሻ:</strong> ሙሉ ስም እና የተጠቃሚ ስም በእጅ ያስገቡ። ነባሪ ይለፍ ቃል <strong>123</strong> ይሆናል።
                    </div>
                    
                    <form method="POST" onsubmit="return validateUserForm()">
                        <div class="form-group">
                            <label>ሙሉ ስም *</label>
                            <input type="text" name="full_name" required placeholder="ለምሳሌ፡ አበራ መኮንን" id="full_name" autocomplete="off">
                        </div>
                        
                        <div class="form-group">
                            <label>የተጠቃሚ ስም *</label>
                            <input type="text" name="username" required placeholder="ለምሳሌ፡ abera" id="username" autocomplete="off">
                            <small>የተጠቃሚ ስም ልዩ መሆን አለበት (ፊደላት፣ ቁጥሮች፣ መስመር ብቻ)</small>
                        </div>
                        
                        <div class="form-group">
                            <label>ነባሪ ይለፍ ቃል</label>
                            <input type="text" value="123" readonly disabled style="background: var(--gold-pale); font-weight: bold;">
                            <small>ተጠቃሚው ለመጀመሪያ ጊዜ ሲገባ ይህንን ይለፍ ቃል ይጠቀማል ከዚያ መቀየር አለበት</small>
                        </div>
                        
                        <button type="submit" name="add_user" class="submit-btn">ተጠቃሚ ጨምር</button>
                    </form>
                </div>
            </div>
            
            <!-- Add Website Modal -->
            <div id="addWebsiteModal" class="modal">
                <div class="modal-content">
                    <span onclick="closeAddWebsiteModal()" class="close-btn">&times;</span>
                    <h2>አዲስ ድረ ገጽ ጨምር</h2>
                    <div class="category-name">ክፍል: <?php echo htmlspecialchars($assigned_category_name); ?></div>
                    
                    <div class="info-box">
                        <strong>ℹ️ ማስታወሻ:</strong> ድረ ገጹ ወደ ዳታቤዝ ይቀመጣል።
                    </div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>የድረ ገጽ ስም *</label>
                            <input type="text" name="website_name" required placeholder="ለምሳሌ፡ የልማት ፕሮጀክት መከታተያ">
                        </div>
                        
                        <div class="form-group">
                            <label>ድረ ገጽ አድራሻ (URL) *</label>
                            <input type="url" name="website_url" required placeholder="https://example.com">
                        </div>
                        
                        <div class="form-group">
                            <label>መግለጫ</label>
                            <textarea name="website_desc" rows="3" placeholder="ስለ ድረ ገጹ ያስረዱ"></textarea>
                        </div>
                        
                        <button type="submit" name="add_website" class="submit-btn">ድረ ገጽ ጨምር</button>
                    </form>
                </div>
            </div>
            
            <!-- Form Validation Script -->
            <script>
            function validateUserForm() {
                var fullName = document.getElementById('full_name').value.trim();
                var username = document.getElementById('username').value.trim();
                
                if (fullName.length < 3) {
                    alert('ሙሉ ስም ቢያንስ 3 ፊደላት መሆን አለበት!');
                    return false;
                }
                
                if (username.length < 3) {
                    alert('የተጠቃሚ ስም ቢያንስ 3 ፊደላት መሆን አለበት!');
                    return false;
                }
                
                // Username validation (letters, numbers, underscore only)
                var usernameRegex = /^[a-zA-Z0-9_]+$/;
                if (!usernameRegex.test(username)) {
                    alert('የተጠቃሚ ስም ፊደላት፣ ቁጥሮች እና መስመር ብቻ መያዝ አለበት!');
                    return false;
                }
                
                return true;
            }
            </script>
            
            <!-- Users List - PENDING USERS ONLY -->
            <?php if ($created_users && $created_users->num_rows > 0): ?>
                <div class="users-section">
                    <div class="section-header">
                        <h2>👥 ይለፍ ቃል መቀየር ያለባቸው ተጠቃሚዎች</h2>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ሙሉ ስም</th>
                                    <th>የተጠቃሚ ስም</th>
                                    <th>ነባሪ ይለፍ ቃል</th>
                                    <th>ሁኔታ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $created_users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                        <td><span class="default-password-badge">123</span></td>
                                        <td><span class="status-badge status-pending">ይለፍ ቃል መቀየር ይጠበቅበታል</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Admin/Superadmin Info -->
        <?php if ($user_role == 'admin' || $user_role == 'superadmin'): ?>
            <div class="section" style="background: var(--gold-pale);">
                <div class="section-header">
                    <h2>👑 የአስተዳዳሪ መዳረሻ</h2>
                </div>
                <p style="color: var(--brown-dark);">ሁሉንም ክፍሎች ማየት ይችላሉ። ምንም ይለፍ ቃል አያስፈልግም።</p>
            </div>
        <?php endif; ?>
        
        <!-- Categories Section -->
        <div class="section">
            <div class="section-header">
                <h2>📂 ክፍሎች</h2>
            </div>
            
            <?php if (!$categories || $categories->num_rows == 0): ?>
                <div class="no-websites">ምንም ክፍሎች አልተገኙም።</div>
            <?php else: ?>
                <div class="category-grid">
                    <?php while ($category = $categories->fetch_assoc()): 
                        $is_assigned = ($assigned_category == $category['id']);
                        $has_access = ($user_role == 'superadmin' || $user_role == 'admin' || $is_assigned || isset($_SESSION['temp_access'][$category['id']]));
                        
                        $category_websites = getWebsitesByCategory($category['id']);
                    ?>
                        <div class="category-card <?php echo $is_assigned ? 'assigned' : ''; ?>">
                            <!-- Category Header -->
                            <div class="category-header <?php echo $is_assigned ? 'assigned' : ''; ?>" onclick="toggleWebsites(<?php echo $category['id']; ?>)">
                                <h3>
                                    <?php echo htmlspecialchars($category['name_amharic']); ?>
                                    <?php if ($is_assigned): ?>
                                        <span class="assigned-tag">የእርስዎ ክፍል</span>
                                    <?php endif; ?>
                                </h3>
                                <span class="toggle-icon" id="toggle-<?php echo $category['id']; ?>">+</span>
                            </div>
                            
                            <!-- Category Websites -->
                            <div class="category-websites" id="websites-<?php echo $category['id']; ?>">
                                <?php if ($has_access): ?>
                                    <?php if (!empty($category_websites)): ?>
                                        <?php foreach ($category_websites as $website): ?>
                                            <a href="<?php echo htmlspecialchars($website['url']); ?>" target="_blank" class="website-link">
                                                <div class="website-name">
                                                    <?php echo htmlspecialchars($website['name']); ?>
                                                    <?php if ($website['id'] == 13 || $website['id'] == 14 || $website['id'] == 15): ?>
                                                        <span class="fixed-badge">ቋሚ</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="website-desc"><?php echo htmlspecialchars($website['description']); ?></div>
                                                <div class="website-url"><?php echo htmlspecialchars($website['url']); ?></div>
                                                <?php if (isset($website['created_by']) && !empty($website['created_by'])): ?>
                                                    <div class="website-meta">ታክሏል: <?php echo htmlspecialchars($website['created_by']); ?></div>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="no-websites">
                                            በዚህ ክፍል ውስጥ ምንም ድረ ገጽ የለም<br>
                                            <?php if ($is_assigned && $user_role == 'category_owner'): ?>
                                                <small style="color: var(--gold-dark);">☝️ ከላይ ያለውን ቁልፍ ተጠቅመው ድረ ገጽ ያክሉ</small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Access Form for Locked Categories -->
                                    <div class="access-form">
                                        <h4>🔒 ይህ ክፍል ይለፍ ቃል ይፈልጋል</h4>
                                        <?php if (isset($_SESSION['temp_access'][$category['id']])): ?>
                                            <div class="granted-badge">✓ መዳረሻ ተሰጥቷል</div>
                                            <?php 
                                            $websites_after_access = getWebsitesByCategory($category['id']);
                                            if (!empty($websites_after_access)) {
                                                foreach ($websites_after_access as $website) {
                                                    echo '<a href="' . htmlspecialchars($website['url']) . '" target="_blank" class="website-link">';
                                                    echo '<div class="website-name">' . htmlspecialchars($website['name']);
                                                    if ($website['id'] == 13 || $website['id'] == 14 || $website['id'] == 15) {
                                                        echo ' <span class="fixed-badge">ቋሚ</span>';
                                                    }
                                                    echo '</div>';
                                                    echo '<div class="website-desc">' . htmlspecialchars($website['description']) . '</div>';
                                                    echo '<div class="website-url">' . htmlspecialchars($website['url']) . '</div>';
                                                    echo '</a>';
                                                }
                                            } else {
                                                echo '<div class="no-websites">በዚህ ክፍል ውስጥ ምንም ድረ ገጽ የለም</div>';
                                            }
                                            ?>
                                        <?php else: ?>
                                            <form method="POST">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <input type="password" name="category_password" placeholder="የክፍሉን ይለፍ ቃል ያስገቡ" required>
                                                <button type="submit" name="verify_category">አረጋግጥ እና ክፈት</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('ይለፍ ቃል ተቀድቷል: ' + text);
            }, function() {
                alert('ቅዳር አልተሳካም');
            });
        }
        
        function copyAllPasswords() {
            let passwords = '';
            <?php foreach ($category_passwords as $cat): ?>
                passwords += '<?php echo $cat['name_amharic']; ?>: <?php echo $cat['password']; ?>\n';
            <?php endforeach; ?>
            
            navigator.clipboard.writeText(passwords).then(function() {
                alert('ሁሉም ይለፍ ቃላት ተቀድተዋል!');
            });
        }
        
        // Modal functions
        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function openAddWebsiteModal() {
            document.getElementById('addWebsiteModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddWebsiteModal() {
            document.getElementById('addWebsiteModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var userModal = document.getElementById('addUserModal');
            var websiteModal = document.getElementById('addWebsiteModal');
            
            if (event.target == userModal) {
                closeAddUserModal();
            }
            if (event.target == websiteModal) {
                closeAddWebsiteModal();
            }
        }
        
        // Toggle websites function
        function toggleWebsites(categoryId) {
            var websitesDiv = document.getElementById('websites-' + categoryId);
            var toggleIcon = document.getElementById('toggle-' + categoryId);
            
            if (websitesDiv.classList.contains('active')) {
                websitesDiv.classList.remove('active');
                toggleIcon.textContent = '+';
            } else {
                websitesDiv.classList.add('active');
                toggleIcon.textContent = '-';
            }
        }
        
        // Auto-expand assigned category for category owners
        <?php if ($user_role == 'category_owner' && $assigned_category): ?>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    toggleWebsites(<?php echo $assigned_category; ?>);
                }, 500);
            });
        <?php endif; ?>
    </script>
</body>
</html>
<?php
ob_end_flush();
?>