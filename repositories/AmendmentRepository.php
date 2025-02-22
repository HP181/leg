<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../models/Amendment.php";

class AmendmentRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(Amendment $amendment): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO amendments (
                    bill_id,
                    reviewer,
                    amendment_text,
                    comments,
                    created_at
                ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $amendment->getBillId(),
                $amendment->getReviewer(),
                $amendment->getAmendmentText(),
                $amendment->getComments()
            ]);

            $this->db->commit();
            error_log("Successfully created amendment for bill ID: " . $amendment->getBillId());
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating amendment: " . $e->getMessage());
            throw new Exception("Failed to create amendment: " . $e->getMessage());
        }
    }

    public function findById(string $id): ?Amendment {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM amendments
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            return $data ? Amendment::fromArray($data) : null;
        } catch (Exception $e) {
            error_log("Error finding amendment by ID: " . $e->getMessage());
            throw new Exception("Failed to find amendment: " . $e->getMessage());
        }
    }

    public function findByBillId(string $billId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM amendments 
                WHERE bill_id = ?
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$billId]);
            $amendments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => Amendment::fromArray($data), $amendments);
        } catch (Exception $e) {
            error_log("Error finding amendments by bill ID: " . $e->getMessage());
            throw new Exception("Failed to find amendments: " . $e->getMessage());
        }
    }

    public function update(Amendment $amendment): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE amendments 
                SET reviewer = ?,
                    amendment_text = ?,
                    comments = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $amendment->getReviewer(),
                $amendment->getAmendmentText(),
                $amendment->getComments(),
                $amendment->getId()
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Amendment not found or no changes made");
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating amendment: " . $e->getMessage());
            throw new Exception("Failed to update amendment: " . $e->getMessage());
        }
    }

    public function delete(string $id): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                DELETE FROM amendments
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Amendment not found");
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error deleting amendment: " . $e->getMessage());
            throw new Exception("Failed to delete amendment: " . $e->getMessage());
        }
    }

    public function findByReviewer(string $reviewer): array {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM amendments 
                WHERE reviewer = ?
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$reviewer]);
            $amendments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => Amendment::fromArray($data), $amendments);
        } catch (Exception $e) {
            error_log("Error finding amendments by reviewer: " . $e->getMessage());
            throw new Exception("Failed to find amendments: " . $e->getMessage());
        }
    }
}
