<?php

require_once "../config.php";
require_once "../models/User.php";
require_once "../repositories/UserRepository.php";

class AuthController {
    private UserRepository $userRepository;
    private array $errors = [];

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function register(): array {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return $this->errors;
        }

        try {
            // Create and validate user
            $user = new User(
                trim($_POST['username'] ?? ''),
                $_POST['password'] ?? '',
                $_POST['role'] ?? ''
            );

            // Validate user data
            $this->errors = $user->validate();
            if (!empty($this->errors)) {
                return $this->errors;
            }

            // Check if username exists
            if ($this->userRepository->findByUsername($user->getUsername())) {
                $this->errors['username'] = "Username already exists";
                return $this->errors;
            }

            // Hash password and create user
            $user->hashPassword();
            $this->userRepository->create($user);

            // Redirect to login
            $this->redirectTo('/legislation_system_latest/views/login.php?registered=1');
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $this->errors['general'] = "Registration failed. Please try again.";
        }

        return $this->errors;
    }

    public function login(): array {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return [];
        }

        try {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === 'on';

            // Basic validation
            if (empty($username) || empty($password)) {
                return ['general' => "Username and password are required"];
            }

            // Find user
            $user = $this->userRepository->findByUsername($username);
            if (!$user || !$user->verifyPassword($password)) {
                return ['general' => "Invalid username or password"];
            }

            // Set session
            $_SESSION['user'] = $user->toSession();

            // Handle remember me
            if ($rememberMe) {
                $this->setRememberMeCookie($username, $password);
            } else {
                $this->deleteRememberMeCookie();
            }

            // Redirect based on role
            $this->redirectBasedOnRole($user->getRole());
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['general' => "An error occurred during login"];
        }

        return [];
    }

    // public function logout(): void {
    //     session_destroy();
    //     $this->deleteRememberMeCookie();
    //     $this->redirectTo('/legislation_system_latest/views/index.php');
    // }

    private function redirectBasedOnRole(string $role): void {
        $redirectMap = [
            'Admin' => '/legislation_system_latest/views/dashboard_admin.php',
            'Reviewer' => '/legislation_system_latest/views/dashboard_reviewer.php',
            'MP' => '/legislation_system_latest/views/dashboard_mp.php'
        ];

        $redirect = $redirectMap[$role] ?? '/legislation_system_latest/views/login.php';
        $this->redirectTo($redirect);
    }

    private function setRememberMeCookie(string $username, string $password): void {
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

    private function deleteRememberMeCookie(): void {
        setcookie('remember_me', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    private function redirectTo(string $url): void {
        header("Location: $url");
        exit;
    }

    // Additional utility methods
    // public function isLoggedIn(): bool {
    //     return isset($_SESSION['user']);
    // }

    // public function getCurrentUser(): ?User {
    //     if (!$this->isLoggedIn()) {
    //         return null;
    //     }

    //     return $this->userRepository->findByUsername($_SESSION['user']['username']);
    // }

    // public function requireLogin(): void {
    //     if (!$this->isLoggedIn()) {
    //         $this->redirectTo('/legislation_system_latest/views/login.php');
    //     }
    // }

    // public function requireRole(string $role): void {
    //     $this->requireLogin();
        
    //     $user = $this->getCurrentUser();
    //     if (!$user || !$user->hasRole($role)) {
    //         $this->redirectTo('/legislation_system_latest/views/unauthorized.php');
    //     }
    // }
}