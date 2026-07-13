<?php
/**
 * Login Portal for OXO Admin Panel
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Redirect to dashboard if already authenticated
redirect_if_logged_in();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both your username and password.';
    } else {
        $db = get_db_connection();
        if ($db) {
            try {
                $stmt = $db->prepare("SELECT * FROM `oxo_admins` WHERE `username` = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Start secure session and save identity
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $user['username'];
                    
                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } catch (\Exception $e) {
                $error = 'An error occurred while connecting to the database: ' . $e->getMessage();
            }
        } else {
            $error = 'Could not connect to the database. Make sure MySQL is running in XAMPP.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — OXO Admin Panel</title>
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;700;800&family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body class="login-body">

    <div class="login-card">
        <a href="../index.php">
            <img src="../assets/images/logo.png" alt="OXO Premium Furniture" class="login-logo">
        </a>
        <h1 class="login-title">OXO <span>Admin</span></h1>
        <p class="login-subtitle">Sign in to manage the premium creations</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert">
                <i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="input-control" required placeholder="Enter username" autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="input-control" required placeholder="Enter password" autocomplete="current-password">
            </div>
            
            <button type="submit" class="login-btn">Sign In to Dashboard</button>
        </form>
    </div>

</body>
</html>
