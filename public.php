<?php
require_once 'config.php';

// Get search query
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Get categories
$sql = "SELECT * FROM categories";
$categories = $conn->query($sql);

// Get websites based on search
if ($search) {
    $website_sql = "SELECT w.*, c.name_amharic as category_name 
                    FROM websites w 
                    JOIN categories c ON w.category_id = c.id 
                    WHERE w.name LIKE '%$search%' 
                    OR w.description LIKE '%$search%'
                    AND w.is_active = 1";
    $search_results = $conn->query($website_sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .header p {
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-section {
            background: white;
            padding: 40px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-container {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .search-box input {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-box button {
            padding: 12px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .search-box button:hover {
            background: #5a67d8;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .section-title h2 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .section-title p {
            color: #666;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .category-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
        }
        
        .category-icon {
            width: 80px;
            height: 80px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            font-weight: bold;
        }
        
        .category-card h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .category-card p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .login-link {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 25px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .login-link:hover {
            background: #5a67d8;
        }
        
        .search-results {
            margin-top: 40px;
        }
        
        .result-item {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        
        .result-item h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .result-item p {
            color: #666;
            margin-bottom: 10px;
        }
        
        .result-item .category {
            font-size: 14px;
            color: #667eea;
            font-weight: 600;
        }
        
        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 60px;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 32px;
            }
            
            .search-box {
                flex-direction: column;
            }
            
            .category-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Church Management System</h1>
        <p>Centralized access to all church department websites and resources</p>
    </div>
    
    <div class="search-section">
        <div class="search-container">
            <h2>Search Our Websites</h2>
            <p>Find specific church resources by keyword (e.g., "Atsedeteguhan")</p>
            
            <form method="GET" action="" class="search-box">
                <input type="text" name="search" placeholder="Search websites..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>
            
            <?php if ($search && isset($search_results)): ?>
                <div class="search-results">
                    <h3>Search Results for "<?php echo htmlspecialchars($search); ?>"</h3>
                    <?php if ($search_results->num_rows > 0): ?>
                        <?php while ($result = $search_results->fetch_assoc()): ?>
                            <div class="result-item">
                                <h4><?php echo $result['name']; ?></h4>
                                <p><?php echo $result['description']; ?></p>
                                <span class="category">Category: <?php echo $result['category_name']; ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666;">No websites found matching your search.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <div class="section-title">
            <h2>Our Departments</h2>
            <p>Explore our church departments and their resources</p>
        </div>
        
        <div class="category-grid">
            <?php while ($category = $categories->fetch_assoc()): ?>
                <div class="category-card">
                    <div class="category-icon">
                        <?php echo substr($category['name_english'], 0, 1); ?>
                    </div>
                    <h3><?php echo $category['name_amharic']; ?></h3>
                    <p><?php echo $category['description']; ?></p>
                    <a href="index.php" class="login-link">Access Resources</a>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: 600;">Staff Login →</a>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2024 Church Management System. All rights reserved.</p>
    </div>
</body>
</html>