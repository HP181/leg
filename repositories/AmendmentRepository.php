<?php
require_once __DIR__ . "/../config.php";

class AmendmentRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAmendmentsByBillId(string $billId): array {
        try {
            // Add debug logging
            error_log("Fetching amendments for bill ID: " . $billId);
            
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    bill_id,
                    reviewer,
                    amendment_text,
                    comments,
                    created_at
                FROM amendments 
                WHERE bill_id = ?
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$billId]);
            $amendments = $stmt->fetchAll();
            
            // Log the number of amendments found
            error_log("Found " . count($amendments) . " amendments for bill ID: " . $billId);
            
            return $amendments;
        } catch (PDOException $e) {
            error_log("Error fetching amendments: " . $e->getMessage());
            return [];
        }
    }

    public function addAmendment(array $amendmentData): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO amendments (
                    bill_id,
                    reviewer,
                    amendment_text,
                    comments,
                    created_at
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $amendmentData['bill_id'],
                $amendmentData['reviewer'],
                $amendmentData['amendment_text'],
                $amendmentData['comments'],
                $amendmentData['created_at']
            ]);
            
            error_log("Successfully added amendment for bill ID: " . $amendmentData['bill_id']);
        } catch (PDOException $e) {
            error_log("Error adding amendment: " . $e->getMessage());
            throw $e;
        }
    }
}