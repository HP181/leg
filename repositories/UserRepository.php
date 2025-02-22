<!-- repositories/UserRepository.php -->
<?php

require_once "../config.php";
require_once "../models/User.php";

class UserRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findByUsername(string $username): ?User {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            return $userData ? User::fromArray($userData) : null;
        } catch (PDOException $e) {
            error_log("Error finding user: " . $e->getMessage());
            throw new Exception("Database error while finding user");
        }
    }

    public function create(User $user): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (username, password, role) 
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $user->getUsername(),
                $user->getPassword(),
                $user->getRole()
            ]);

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating user: " . $e->getMessage());
            throw new Exception("Failed to create user");
        }
    }

    // public function update(User $user): void {
    //     $this->db->beginTransaction();
    //     try {
    //         $stmt = $this->db->prepare("
    //             UPDATE users 
    //             SET password = ?, role = ?
    //             WHERE username = ?
    //         ");

    //         $stmt->execute([
    //             $user->getPassword(),
    //             $user->getRole(),
    //             $user->getUsername()
    //         ]);

    //         if ($stmt->rowCount() === 0) {
    //             throw new Exception("User not found");
    //         }

    //         $this->db->commit();
    //     } catch (PDOException $e) {
    //         $this->db->rollBack();
    //         error_log("Error updating user: " . $e->getMessage());
    //         throw new Exception("Failed to update user");
    //     }
    // }

    // public function delete(string $username): void {
    //     $this->db->beginTransaction();
    //     try {
    //         $stmt = $this->db->prepare("DELETE FROM users WHERE username = ?");
    //         $stmt->execute([$username]);

    //         if ($stmt->rowCount() === 0) {
    //             throw new Exception("User not found");
    //         }

    //         $this->db->commit();
    //     } catch (PDOException $e) {
    //         $this->db->rollBack();
    //         error_log("Error deleting user: " . $e->getMessage());
    //         throw new Exception("Failed to delete user");
    //     }
    // }

   
    // public function findAll(): array {
    //     try {
    //         $stmt = $this->db->query("SELECT * FROM users ORDER BY username");
    //         $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //         return array_map(fn($userData) => User::fromArray($userData), $users);
    //     } catch (PDOException $e) {
    //         error_log("Error finding all users: " . $e->getMessage());
    //         throw new Exception("Database error while finding users");
    //     }
    // }
}