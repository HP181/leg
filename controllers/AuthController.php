<?php
require_once "../config.php";

class AuthController {
    private UserRepository $userRepository;
    private array $errors = [];

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function register(): array {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';

            // Validate input
            if (empty($username)) {
                $this->errors['username'] = "Username is required";
            } elseif (strlen($username) < 3) {
                $this->errors['username'] = "Username must be at least 3 characters long";
            }

            if (empty($password)) {
                $this->errors['password'] = "Password is required";
            } elseif (strlen($password) < 6) {
                $this->errors['password'] = "Password must be at least 6 characters long";
            }

            if (empty($role)) {
                $this->errors['role'] = "Role is required";
            }

            // Check if username already exists
            if (empty($this->errors['username'])) {
                $existingUser = $this->userRepository->findByUsername($username);
                if ($existingUser) {
                    $this->errors['username'] = "Username already exists";
                }
            }

            // If no errors, proceed with registration
            if (empty($this->errors)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    if ($this->userRepository->createUser($username, $hashedPassword, $role)) {
                        header("Location: login.php?registered=1");
                        exit;
                    } else {
                        $this->errors['general'] = "Registration failed. Please try again.";
                    }
                } catch (Exception $e) {
                    $this->errors['general'] = "An error occurred during registration.";
                    error_log("Registration error: " . $e->getMessage());
                }
            }
        }
        return $this->errors;
    }

    public function login(): array {
        $errors = [];
        
        try {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === 'on';
    
            // Validate input
            if (empty($username)) {
                $errors['username'] = "Username is required";
            }
    
            if (empty($password)) {
                $errors['password'] = "Password is required";
            }
    
            if (!empty($errors)) {
                return $errors;
            }
    
            // Check user credentials
            $user = $this->userRepository->findByUsername($username);
    
            if (!$user) {
                $errors['general'] = "Invalid username or password";
                return $errors;
            }
    
            if (!password_verify($password, $user['password'])) {
                $errors['general'] = "Invalid username or password";
                return $errors;
            }
    
            // Login successful
            $_SESSION['user'] = [
                'username' => $username,
                'role' => $user['role']
            ];
    
            if ($rememberMe) {
                $this->setRememberMeCookie($username, $password);
            } else {
                $this->deleteRememberMeCookie();
            }
    
            $this->redirectBasedOnRole($user['role']);
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $errors['general'] = "An error occurred during login. Please try again.";
        }
    
        return $errors;
    }

    private function redirectBasedOnRole(string $role): void {
        $redirectMap = [
            'Admin' => '/legislation_system_latest/views/dashboard_admin.php',
            'Reviewer' => '/legislation_system_latest/views/dashboard_reviewer.php',
            'MP' => '/legislation_system_latest/views/dashboard_mp.php'
        ];

        $redirect = $redirectMap[$role] ?? '/legislation_system_latest/views/login.php';
        header("Location: $redirect");
        exit;
    }

    private function setRememberMeCookie(string $username, string $password): void {
        if ($username && $password) {
            $cookieValue = base64_encode(json_encode([
                'username' => $username,
                'password' => $password
            ]));
            setcookie(
                'remember_me',
                $cookieValue,
                [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );
        }
    }

    private function deleteRememberMeCookie(): void {
        setcookie('remember_me', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    public function logout(): void {
        session_destroy();
        $this->deleteRememberMeCookie();
        header("Location: index.php");
        exit;
    }
}