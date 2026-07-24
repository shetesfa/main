<?php
// Start output buffering
ob_start();

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Get current password from database
    $sql = "SELECT password FROM users WHERE id = $user_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $current_password_hash = md5($current_password);
        
        // Verify current password
        if ($user['password'] != $current_password_hash) {
            $error = "የአሁኑ ይለፍ ቃል ትክክል አይደለም!";
        } 
        // Check new password length
        elseif (strlen($new_password) < 6) {
            $error = "አዲሱ ይለፍ ቃል ቢያንስ 6 ቁምፊዎች መሆን አለበት!";
        }
        // Check if new password matches confirm
        elseif ($new_password != $confirm_password) {
            $error = "አዲሱ ይለፍ ቃል እና የተደገመው ይለፍ ቃል አይዛመዱም!";
        }
        // Check if new password is same as old
        elseif ($user['password'] == md5($new_password)) {
            $error = "አዲሱ ይለፍ ቃል ከአሁኑ ይለፍ ቃል ጋር ተመሳሳይ መሆን አይችልም!";
        }
        else {
            // Update password
            $new_password_hash = md5($new_password);
            $update_sql = "UPDATE users SET password = '$new_password_hash', password_change_required = 0, last_password_change = NOW() WHERE id = $user_id";
            
            if ($conn->query($update_sql)) {
                // Update session
                $_SESSION['password_change_required'] = 0;
                $success = "ይለፍ ቃልዎ በተሳካ ሁኔታ ተቀይሯል! ወደ ዳሽቦርድ ይመራሉ...";
                
                // Redirect after 3 seconds
                header("refresh:3; url=dashboard.php");
            } else {
                $error = "ይለፍ ቃል መቀየር አልተሳካም: " . $conn->error;
            }
        }
    } else {
        $error = "ተጠቃሚ አልተገኘም!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ይለፍ ቃል ቀይር - የቤተክርስቲያን ማኔጅመንት ሲስተም</title>
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

        .change-password-container {
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

        .warning-box {
            background: var(--gold-pale);
            color: var(--brown-dark);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid var(--gold-primary);
            font-weight: 500;
        }

        .user-info {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--brown-dark);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
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
            margin-top: 10px;
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

        .success {
            background: #D1FAE5;
            color: var(--success-green);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid var(--success-green);
        }

        .info-text {
            margin-top: 20px;
            text-align: center;
        }

        .info-text a {
            color: var(--brown-medium);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .info-text a:hover {
            color: var(--gold-dark);
            text-decoration: underline;
        }

        .password-requirements {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            border: 1px solid var(--gold-pale);
        }

        .password-requirements h4 {
            color: var(--brown-dark);
            margin-bottom: 10px;
            font-size: 16px;
        }

        .password-requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .password-requirements li {
            color: var(--brown-medium);
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }

        .password-requirements li:before {
            content: "•";
            color: var(--gold-primary);
            font-weight: bold;
            position: absolute;
            left: 5px;
        }

        .logo-area {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: var(--gold-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: var(--brown-dark);
            border: 2px solid var(--brown-dark);
        }
    </style>
</head>
<body>
    <div class="change-password-container">
        <div class="logo-area">
            <div class="logo-icon">🔐</div>
        </div>
        
        <h2>ይለፍ ቃል ቀይር</h2>
        <div class="subtitle">ነባሪ ይለፍ ቃልዎን ወደ አዲስ ይለፍ ቃል ይቀይሩ</div>
        
        <div class="user-info">
            <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['username']; ?>)
        </div>
        
        <div class="warning-box">
            <strong>⚠️ ማስጠንቀቂያ!</strong> ለደህንነት ሲባል ነባሪ ይለፍ ቃልዎን መቀየር ያስፈልጋል።
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="password-requirements">
            <h4>የይለፍ ቃል መስፈርቶች:</h4>
            <ul>
                <li>ቢያንስ 6 ቁምፊዎች መሆን አለበት</li>
                <li>ፊደላት እና ቁጥሮችን መያዝ ይችላል</li>
                <li>ቀላል ይለፍ ቃሎችን አይጠቀሙ</li>
            </ul>
        </div>
        
        <form method="POST" action="" onsubmit="return validateForm()">
            <div class="form-group">
                <label>የአሁኑ ይለፍ ቃል</label>
                <input type="password" name="current_password" required placeholder="ነባሪ ይለፍ ቃልዎን ያስገቡ">
            </div>
            
            <div class="form-group">
                <label>አዲስ ይለፍ ቃል</label>
                <input type="password" name="new_password" id="new_password" required placeholder="አዲስ ይለፍ ቃል ያስገቡ">
            </div>
            
            <div class="form-group">
                <label>አዲስ ይለፍ ቃል ድገም</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="አዲስ ይለፍ ቃል ድገሙት">
            </div>
            
            <button type="submit">ይለፍ ቃል ቀይር</button>
        </form>
        
        <div class="info-text">
            <a href="dashboard.php">← ወደ ዳሽቦርድ ተመለስ</a>
        </div>
    </div>
    
    <script>
        function validateForm() {
            var newPass = document.getElementById('new_password').value;
            var confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass.length < 6) {
                alert('ይለፍ ቃል ቢያንስ 6 ቁምፊዎች መሆን አለበት!');
                return false;
            }
            
            if (newPass !== confirmPass) {
                alert('ይለፍ ቃሎች አይዛመዱም!');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
<?php
ob_end_flush();
?>