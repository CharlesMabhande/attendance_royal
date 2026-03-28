<?php
session_start();

// Check if already logged in
if (isset($_SESSION['uid'])) {
    header('location:admin/admindash.php');	
    exit();
}

// Initialize error message
$error_msg = '';

// Process login form
if (isset($_POST['submit'])) {
    include('dbcon.php');
    require_once __DIR__ . '/admin/role_helpers.php';

    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    $qry = "SELECT * FROM `admin` WHERE `username`=? AND `password`=?";
    $stmt = mysqli_prepare($con, $qry);
    mysqli_stmt_bind_param($stmt, "ss", $username, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $data = mysqli_fetch_assoc($result);
        $id = (int) $data['id'];

        session_regenerate_id(true);

        $_SESSION['uid'] = $id;
        $role = isset($data['role']) ? trim((string) $data['role']) : 'full';
        if ($role === '' || !rfjs_valid_admin_role($role)) {
            $role = 'full';
        }
        $_SESSION['admin_role'] = $role;
        $uname = isset($data['username']) ? trim((string) $data['username']) : '';
        $_SESSION['admin_username'] = $uname;
        $_SESSION['admin_class_scope'] = rfjs_class_scope_from_username($uname);
        header('location:admin/admindash.php');
        exit();
    } else {
        $error_msg = "Invalid username or password!";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
}
?>
<html>
<head>
    <title>Admin Login</title>
    <link rel="icon" href="image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="csss/rfjs-theme.css" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Flamenco" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Flamenco', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: cover;
            background-attachment: fixed;
        }

        /* Navigation */
        .main-nav {
            list-style: none;
            display: flex;
            justify-content: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            gap: 30px;
            flex-wrap: wrap;
        }

        .main-nav a {
            text-decoration: none;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
        }

        .main-nav a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        /* Login Card */
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .login-header i {
            color: #667eea;
            font-size: 36px;
        }

        .login-header p {
            color: #666;
            font-size: 16px;
        }

        /* Error Message */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 8px;
            text-align: left;
        }

        .error-message i {
            font-size: 18px;
        }

        /* Form Styles */
        .login-form {
            width: 100%;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 18px;
        }

        .form-input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-input.error {
            border-color: #dc3545;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2, #667eea);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 20px;
            color: white;
            background: rgba(0, 0, 0, 0.2);
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-nav {
                gap: 15px;
                padding: 15px;
            }
            
            .main-nav a {
                padding: 8px 15px;
                font-size: 14px;
            }
            
            .login-container {
                padding: 40px 30px;
                margin: 20px;
            }
            
            .login-header h1 {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .main-nav {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            
            .main-nav a {
                width: 200px;
                justify-content: center;
            }
            
            .login-container {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 24px;
                flex-direction: column;
                gap: 10px;
            }
            
            .form-input {
                padding: 12px 12px 12px 45px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <ul class="main-nav">
            <li><a href="index.php"><i class="fas fa-home"></i> HOME</a></li>
            <li><a href="admin/aboutus.php"><i class="fas fa-info-circle"></i> ABOUT</a></li>
            <li><a href="admin/contactus.php"><i class="fas fa-envelope"></i> CONTACT</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="login-container">
            <div style="text-align:center;margin-bottom:18px;">
                <img src="image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="120" height="120">
            </div>
            <div class="login-header">
                <h1><i class="fas fa-lock"></i> Admin Login</h1>
                <p>Enter your credentials to access the dashboard</p>
            </div>
            
            <?php if (!empty($error_msg)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" action="" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <button type="submit" name="submit" class="submit-btn">
                    <i class="fas fa-sign-in-alt"></i> LOGIN
                </button>
            </form>
        </div>
    </main>

    <footer>
        DESIGNED BY CharlzTech Web Developers
    </footer>
</body>
</html>