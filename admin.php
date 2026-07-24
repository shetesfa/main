<?php
require_once 'config.php';

// Check if user is admin or superadmin
if (!isLoggedIn() || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'superadmin')) {
    redirect('index.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $name_amharic = $conn->real_escape_string($_POST['name_amharic']);
        $name_english = $conn->real_escape_string($_POST['name_english']);
        $description = $conn->real_escape_string($_POST['description']);
        $password = md5($_POST['password']);
        
        $sql = "INSERT INTO categories (name_amharic, name_english, description, category_password) 
                VALUES ('$name_amharic', '$name_english', '$description', '$password')";
        $conn->query($sql);
    }
    
    if (isset($_POST['add_website'])) {
        $category_id = $_POST['category_id'];
        $name = $conn->real_escape_string($_POST['name']);
        $url = $conn->real_escape_string($_POST['url']);
        $description = $conn->real_escape_string($_POST['description']);
        
        $sql = "INSERT INTO websites (category_id, name, url, description) 
                VALUES ($category_id, '$name', '$url', '$description')";
        $conn->query($sql);
    }
}

// Get all data
$categories = $conn->query("SELECT * FROM categories");
$websites = $conn->query("SELECT w.*, c.name_amharic as category_name FROM websites w JOIN categories c ON w.category_id = c.id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        /* Add admin panel styles here */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        form {
            display: grid;
            gap: 10px;
            max-width: 500px;
            margin-bottom: 30px;
        }
        
        input, textarea, select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        button {
            padding: 10px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        button:hover {
            background: #5a67d8;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="section">
            <h2>Add New Category</h2>
            <form method="POST">
                <input type="text" name="name_amharic" placeholder="Amharic Name" required>
                <input type="text" name="name_english" placeholder="English Name" required>
                <textarea name="description" placeholder="Description" rows="3"></textarea>
                <input type="password" name="password" placeholder="Category Password" required>
                <button type="submit" name="add_category">Add Category</button>
            </form>
        </div>
        
        <div class="section">
            <h2>Add New Website</h2>
            <form method="POST">
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php 
                    $categories->data_seek(0);
                    while ($cat = $categories->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo $cat['name_amharic']; ?> (<?php echo $cat['name_english']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="name" placeholder="Website Name" required>
                <input type="url" name="url" placeholder="URL (e.g., http://example.com)" required>
                <textarea name="description" placeholder="Description" rows="2"></textarea>
                <button type="submit" name="add_website">Add Website</button>
            </form>
        </div>
        
        <div class="section">
            <h2>Existing Websites</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Website Name</th>
                        <th>URL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($site = $websites->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $site['category_name']; ?></td>
                        <td><?php echo $site['name']; ?></td>
                        <td><?php echo $site['url']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>