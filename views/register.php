<?php
session_start();
require_once "../controllers/AuthController.php";

$auth = new AuthController();
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errors = $auth->register();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
        input[type="password"],
        select {
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 6px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        /* Add these new styles to your existing CSS */
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

        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 4px;
        }

        .form-group.error input,
        .form-group.error select {
            border-color: #dc2626;
        }

        .form-group.error input:focus,
        .form-group.error select:focus {
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
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
        <?php if (!empty($errors['general'])): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group <?php echo !empty($errors['username']) ? 'error' : ''; ?>">
                <label for="username">Username</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       placeholder="Enter your username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
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
                       required>
                <?php if (!empty($errors['password'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group <?php echo !empty($errors['role']) ? 'error' : ''; ?>">
                <label for="role">Role</label>
                <select id="role" 
                        name="role" 
                        required>
                    <option value="">Select a role</option>
                    <option value="MP" <?php echo (isset($_POST['role']) && $_POST['role'] === 'MP') ? 'selected' : ''; ?>>
                        Member of Parliament
                    </option>
                    <option value="Reviewer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Reviewer') ? 'selected' : ''; ?>>
                        Reviewer
                    </option>
                    <option value="Admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Admin') ? 'selected' : ''; ?>>
                        Administrator
                    </option>
                </select>
                <?php if (!empty($errors['role'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['role']); ?></div>
                <?php endif; ?>
            </div>

            <button type="submit">Register</button>
        </form>
        <a href="login.php">Already have an account? Login</a>
    </div>
</body>
</html>
