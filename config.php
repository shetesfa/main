<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ===== XAMPP DATABASE CONFIGURATION =====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'church_management_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("<div style='background: #FEF3C7; color: #8B4513; padding: 20px; margin: 20px; border-left: 5px solid #DAA520; font-family: Arial;'>
            <h2>❌ Database Connection Error!</h2>
            <p><strong>Error:</strong> " . $conn->connect_error . "</p>
            <p><strong>Make sure XAMPP is running!</strong></p>
          </div>");
}

// Set charset to support Amharic
$conn->set_charset("utf8mb4");

// Site configuration
define('SITE_NAME', 'የቤተክርስቲያን ማኔጅመንት ሲስተም');
define('SITE_URL', 'http://localhost/main');

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to safely redirect
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit();
    }
}

// Function to get websites by category
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

// Function to add website to database
function addWebsite($category_id, $name, $url, $description, $added_by) {
    global $conn;
    
    $name = $conn->real_escape_string($name);
    $url = $conn->real_escape_string($url);
    $description = $conn->real_escape_string($description);
    $added_by = $conn->real_escape_string($added_by);
    
    $sql = "INSERT INTO websites (category_id, name, url, description, created_by, is_active) 
            VALUES ($category_id, '$name', '$url', '$description', '$added_by', 1)";
    
    return $conn->query($sql);
}
?>