<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../models/Vote.php";

class VoteRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(Vote $vote): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO votes (bill_id, username, vote, voted_at)
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE 
                    vote = VALUES(vote),
                    voted_at = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                $vote->getBillId(),
                $vote->getUsername(),
                $vote->getVote()
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating vote: " . $e->getMessage());
            throw new Exception("Failed to record vote: " . $e->getMessage());
        }
    }

    // public function findByBillId(string $billId): array {
    //     try {
    //         $stmt = $this->db->prepare("
    //             SELECT * 
    //             FROM votes 
    //             WHERE bill_id = ?
    //             ORDER BY voted_at DESC
    //         ");
    //         $stmt->execute([$billId]);
    //         $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //         return array_map(fn($data) => Vote::fromArray($data), $votes);
    //     } catch (Exception $e) {
    //         error_log("Error finding votes by bill ID: " . $e->getMessage());
    //         throw new Exception("Failed to retrieve votes");
    //     }
    // }

   
   
    public function findByUser(string $billId, string $username): ?Vote {
        try {
            $stmt = $this->db->prepare("
                SELECT * 
                FROM votes 
                WHERE bill_id = ? AND username = ?
            ");
            $stmt->execute([$billId, $username]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            return $data ? Vote::fromArray($data) : null;
        } catch (Exception $e) {
            error_log("Error finding vote by user: " . $e->getMessage());
            throw new Exception("Failed to retrieve vote");
        }
    }

    public function getVoteCounts(string $billId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT vote, COUNT(*) as count
                FROM votes
                WHERE bill_id = ?
                GROUP BY vote
            ");
            $stmt->execute([$billId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $counts = ['For' => 0, 'Against' => 0, 'Abstain' => 0];
            foreach ($results as $result) {
                $counts[$result['vote']] = (int)$result['count'];
            }

            return $counts;
        } catch (Exception $e) {
            error_log("Error getting vote counts: " . $e->getMessage());
            throw new Exception("Failed to get vote counts");
        }
    }

    public function findAll(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * 
                FROM votes 
                ORDER BY voted_at DESC
            ");
            $stmt->execute();
            $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => Vote::fromArray($data), $votes);
        } catch (Exception $e) {
            error_log("Error finding all votes: " . $e->getMessage());
            throw new Exception("Failed to retrieve votes");
        }
    }

    // public function getVotesByType(string $billId, string $voteType): array {
    //     try {
    //         $stmt = $this->db->prepare("
    //             SELECT * 
    //             FROM votes 
    //             WHERE bill_id = ? AND vote = ?
    //             ORDER BY voted_at DESC
    //         ");
    //         $stmt->execute([$billId, $voteType]);
    //         $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //         return array_map(fn($data) => Vote::fromArray($data), $votes);
    //     } catch (Exception $e) {
    //         error_log("Error getting votes by type: " . $e->getMessage());
    //         throw new Exception("Failed to retrieve votes");
    //     }
    // }

    public function deleteVote(string $billId, string $username): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                DELETE FROM votes 
                WHERE bill_id = ? AND username = ?
            ");
            $stmt->execute([$billId, $username]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Vote not found");
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error deleting vote: " . $e->getMessage());
            throw new Exception("Failed to delete vote");
        }
    }
}