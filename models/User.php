<?php
require_once "../config.php";

class User {
    private UserRepository $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function register(string $username, string $password, string $role): bool {
        // Validate input
        if (empty($username) || empty($password) || empty($role)) {
            return false;
        }

        // Validate role
        if (!in_array($role, ['Admin', 'Reviewer', 'MP'])) {
            return false;
        }

        // Check if user already exists
        if ($this->userRepository->findByUsername($username)) {
            return false;
        }

        // Hash password and create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        return $this->userRepository->createUser($username, $hashedPassword, $role);
    }

    public function login(string $username, string $password): bool {
        $user = $this->userRepository->findByUsername($username);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        $_SESSION['user'] = [
            'username' => $username,
            'role' => $user['role']
        ];

        return true;
    }

    public function getRole(): ?string {
        return $_SESSION['user']['role'] ?? null;
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['user']);
    }

    public function hasRole(string $role): bool {
        return $this->isLoggedIn() && $_SESSION['user']['role'] === $role;
    }
}
?>