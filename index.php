<?php
// Start output buffering
ob_start();

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    // Check if password change is required
    if (isset($_SESSION['password_change_required']) && $_SESSION['password_change_required'] == 1) {
        header("Location: change_password.php");
        exit();
    } else {
        header("Location: dashboard.php");
        exit();
    }
}

$error = '';

// Get all users from database to display on login page
$users_list = [];
$users_query = "SELECT id, username, full_name, role, assigned_category_id, 
                (SELECT name_amharic FROM categories WHERE id = assigned_category_id) as category_name 
                FROM users ORDER BY role, username";
$users_result = $conn->query($users_query);
if ($users_result && $users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $users_list[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "እባክዎ የተጠቃሚ ስም እና ይለፍ ቃል ያስገቡ";
    } else {
        // Hash the entered password
        $password_hash = md5($password);
        
        // Check user in database
        $sql = "SELECT u.*, c.name_amharic as category_name 
                FROM users u 
                LEFT JOIN categories c ON u.assigned_category_id = c.id 
                WHERE u.username = '$username' AND u.password = '$password_hash'";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['assigned_category'] = $user['assigned_category_id'];
            $_SESSION['assigned_category_name'] = $user['category_name'];
            $_SESSION['password_change_required'] = $user['password_change_required'];
            
            // Clear any previous temporary access
            unset($_SESSION['temp_access']);
            
            // Check if using default password "123" AND password change is required
            if ($password == '123' && $user['password_change_required'] == 1) {
                // Redirect to change password page
                header("Location: change_password.php");
                exit();
            } 
            // If password change is required but not using "123" (shouldn't happen, but just in case)
            elseif ($user['password_change_required'] == 1) {
                header("Location: change_password.php");
                exit();
            }
            else {
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = "የተጠቃሚ ስም ወይም ይለፍ ቃል ትክክል አይደለም!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ግባ - የቤተክርስቲያን ማኔጅመንት ሲስተም</title>
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
            background: linear-gradient(135deg, #8B4513 0%, #A52A2A 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: var(--bg-cream);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
            border: 3px solid var(--gold-primary);
        }

        h2 {
            text-align: center;
            color: var(--brown-dark);
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: var(--brown-medium);
            margin-bottom: 30px;
            font-size: 14px;
        }

        .info-box {
            background: var(--gold-pale);
            color: var(--brown-dark);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid var(--gold-primary);
            font-size: 14px;
        }

        .default-password-note {
            background: #FEF3C7;
            color: #D97706;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            border: 1px dashed var(--gold-primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: var(--brown-dark);
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gold-pale);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
        }

        button {
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
            border: 2px solid var(--gold-primary);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139,69,19,0.3);
        }

        .error {
            background: #FEF3C7;
            color: var(--error-red);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid var(--error-red);
        }

        .users-list {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--gold-pale);
        }

        .users-list h4 {
            color: var(--brown-dark);
            margin-bottom: 15px;
            font-size: 18px;
            text-align: center;
            background: var(--gold-pale);
            padding: 10px;
            border-radius: 8px;
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            max-height: 300px;
            overflow-y: auto;
            padding: 5px;
        }

        .user-item {
            background: white;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            color: var(--brown-dark);
            border: 1px solid var(--gold-pale);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .user-item:hover {
            transform: translateY(-2px);
            border-color: var(--gold-primary);
        }

        .user-item strong {
            color: var(--brown-medium);
            font-size: 14px;
            display: block;
            margin-bottom: 3px;
        }

        .user-details {
            font-size: 11px;
            color: var(--brown-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 5px;
        }

        .role-badge {
            background: var(--gold-pale);
            color: var(--brown-dark);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }

        .category-badge {
            background: var(--brown-dark);
            color: var(--gold-primary);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
        }

        .default-badge {
            background: var(--gold-primary);
            color: var(--brown-dark);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            margin-left: 5px;
        }

        .logo-area {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: var(--gold-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--brown-dark);
            border: 3px solid var(--brown-dark);
        }

        .stats-badge {
            background: var(--brown-dark);
            color: var(--gold-primary);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-area">
            <div class="logo-icon">⛪</div>
        </div>
        
        <h2>የቤተክርስቲያን ማኔጅመንት ሲስተም</h2>
        <div class="subtitle">የተጠቃሚ ስም እና ይለፍ ቃልዎን ያስገቡ</div>
        
        <div class="stats-badge">
            በስርአቱ ውስጥ <?php echo count($users_list); ?> ተጠቃሚዎች አሉ
        </div>
        
        <div class="default-password-note">
            ⚠️ ነባሪ ይለፍ ቃል ለሁሉም አዲስ ተጠቃሚዎች: <strong>123</strong>
        </div>
        
        <div class="info-box">
            <strong>🔐 ማስታወሻ:</strong> አዲስ ተጠቃሚዎች በነባሪ ይለፍ ቃል (123) ሲገቡ ወዲያውኑ ይለፍ ቃላቸውን መቀየር ይኖርባቸዋል።
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>የተጠቃሚ ስም</label>
                <input type="text" name="username" required placeholder="ለምሳሌ: tesfa, dawit, admin" list="usernames">
                <datalist id="usernames">
                    <?php foreach ($users_list as $user): ?>
                        <option value="<?php echo $user['username']; ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="form-group">
                <label>ይለፍ ቃል</label>
                <input type="password" name="password" required placeholder="ነባሪ ቃል: 123">
            </div>
            
            <button type="submit">ግባ</button>
        </form>
        
        <div class="users-list">
            <h4>👥 በስርአቱ ውስጥ ያሉ ተጠቃሚዎች</h4>
            <div class="users-grid">
                <?php foreach ($users_list as $user): 
                    // Determine role in Amharic
                    $role_text = '';
                    if ($user['role'] == 'superadmin') $role_text = 'ሱፐር አድሚን';
                    elseif ($user['role'] == 'admin') $role_text = 'አድሚን';
                    else $role_text = 'የክፍል ባለቤት';
                ?>
                    <div class="user-item">
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        <div><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div class="user-details">
                            <span class="role-badge"><?php echo $role_text; ?></span>
                            <?php if ($user['category_name']): ?>
                                <span class="category-badge"><?php echo htmlspecialchars($user['category_name']); ?></span>
                            <?php endif; ?>
                            <span class="default-badge">123</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
ob_end_flush();
?>