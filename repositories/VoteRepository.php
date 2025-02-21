<?php
class VoteRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

   

    public function getVotesByBillId(string $billId): array {
        $stmt = $this->db->prepare("
            SELECT username, vote 
            FROM votes 
            WHERE bill_id = ?
        ");
        $stmt->execute([$billId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function recordVote(string $billId, string $username, string $vote): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO votes (bill_id, username, vote)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE vote = ?
            ");
            $stmt->execute([$billId, $username, $vote, $vote]);
        } catch (PDOException $e) {
            error_log("Error recording vote: " . $e->getMessage());
            throw $e;
        }
    }

    public function getVoteByUser(string $billId, string $username): ?string {
        try {
            $stmt = $this->db->prepare("
                SELECT vote 
                FROM votes 
                WHERE bill_id = ? AND username = ?
            ");
            $stmt->execute([$billId, $username]);
            return $stmt->fetchColumn() ?: null;
        } catch (PDOException $e) {
            error_log("Error getting user vote: " . $e->getMessage());
            return null;
        }
    }
    
    public function getVoteCount(string $billId, string $voteType): int {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM votes 
                WHERE bill_id = ? AND vote = ?
            ");
            $stmt->execute([$billId, $voteType]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting vote count: " . $e->getMessage());
            return 0;
        }
    }
}