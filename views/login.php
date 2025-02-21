<?php 
session_start(); 
require_once "../controllers/AuthController.php";

$auth = new AuthController();
$errors = [];
$success = '';

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Registration successful! Please login.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $auth->login();
}

// Check if remember_me cookie exists
$rememberMeData = isset($_COOKIE['remember_me']) ? json_decode(base64_decode($_COOKIE['remember_me']), true) : null;
$username = $rememberMeData['username'] ?? '';
$password = $rememberMeData['password'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="password"] {
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 6px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        button {
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        button:active {
            transform: translateY(1px);
        }

        a {
            color: #667eea;
            text-decoration: none;
            text-align: center;
            margin-top: 20px;
            display: inline-block;
            width: 100%;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }

        .notification.error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .notification.success {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 4px;
        }

        .form-group.error input {
            border-color: #dc2626;
        }

        .form-group.error input:focus {
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        /* Add animation for notifications */
        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .notification {
            animation: slideDown 0.3s ease-out;
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($success)): ?>
            <div class="notification success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="form-group <?php echo !empty($errors['username']) ? 'error' : ''; ?>">
                <label for="username">Username</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       placeholder="Enter your username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? $username); ?>" 
                       required>
                <?php if (!empty($errors['username'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['username']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group <?php echo !empty($errors['password']) ? 'error' : ''; ?>">
                <label for="password">Password</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="Enter your password" 
                       value="<?php echo htmlspecialchars($password); ?>" 
                       required>
                <?php if (!empty($errors['password'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
                <?php endif; ?>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" 
                       id="remember_me" 
                       name="remember_me" 
                       <?php echo $rememberMeData ? 'checked' : ''; ?>>
                <label for="remember_me">Remember Me</label>
            </div>

            <button type="submit">Login</button>
        </form>
        <a href="./register.php">Register</a>
    </div>
</body>
</html>