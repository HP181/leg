<!-- models/User.php -->
<?php

class User {
    private string $username;
    private string $password;
    private string $role;
    private array $allowedRoles = ['Admin', 'Reviewer', 'MP'];

    public function __construct(
        string $username,
        string $password,
        string $role
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->role = $role;
    }

    // Getters
    public function getUsername(): string {
        return $this->username;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getRole(): string {
        return $this->role;
    }

    // Setters
    public function setPassword(string $password): void {
        $this->password = $password;
    }

    public function setRole(string $role): void {
        if (!in_array($role, $this->allowedRoles)) {
            throw new InvalidArgumentException("Invalid role provided");
        }
        $this->role = $role;
    }

    // Business Logic Methods
    public function validate(): array {
        $errors = [];

        if (empty($this->username)) {
            $errors['username'] = "Username is required";
        } elseif (strlen($this->username) < 3) {
            $errors['username'] = "Username must be at least 3 characters long";
        }

        if (empty($this->password)) {
            $errors['password'] = "Password is required";
        } elseif (strlen($this->password) < 6) {
            $errors['password'] = "Password must be at least 6 characters long";
        }

        if (empty($this->role)) {
            $errors['role'] = "Role is required";
        } elseif (!in_array($this->role, $this->allowedRoles)) {
            $errors['role'] = "Invalid role selected";
        }

        return $errors;
    }

    public function hashPassword(): void {
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password);
    }

    public function hasRole(string $role): bool {
        return $this->role === $role;
    }

    // Data conversion methods
    public function toArray(): array {
        return [
            'username' => $this->username,
            'password' => $this->password,
            'role' => $this->role
        ];
    }

    public function toSession(): array {
        return [
            'username' => $this->username,
            'role' => $this->role
        ];
    }

    // Factory method
    public static function fromArray(array $data): self {
        return new self(
            $data['username'],
            $data['password'],
            $data['role']
        );
    }
}