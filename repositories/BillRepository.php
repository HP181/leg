<?php
// repositories/BillRepository.php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../models/Bill.php";

class BillRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createBill(Bill $bill): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO bills (
                    id, title, description, author, draft, status, 
                    created_at, review_completed_at, reviewed_by, voting_finalized_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 
                    CURRENT_TIMESTAMP, ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $bill->getId(),
                $bill->getTitle(),
                $bill->getDescription(),
                $bill->getAuthor(),
                $bill->getDraft(),
                $bill->getStatus(),
                $bill->getReviewCompletedAt(),
                $bill->getReviewedBy(),
                $bill->getVotingFinalizedAt()
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error creating bill: " . $e->getMessage());
        }
    }

    public function updateBill(Bill $bill): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE bills 
                SET title = ?,
                    description = ?,
                    author = ?,
                    draft = ?,
                    status = ?,
                    review_completed_at = ?,
                    reviewed_by = ?,
                    voting_finalized_at = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $bill->getTitle(),
                $bill->getDescription(),
                $bill->getAuthor(),
                $bill->getDraft(),
                $bill->getStatus(),
                $bill->getReviewCompletedAt(),
                $bill->getReviewedBy(),
                $bill->getVotingFinalizedAt(),
                $bill->getId()
            ]);
    
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error updating bill: " . $e->getMessage());
        }
    }

    public function findById(string $id): ?Bill {
        try {
            $stmt = $this->db->prepare("SELECT * FROM bills WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $data ? Bill::fromArray($data) : null;
        } catch (Exception $e) {
            throw new Exception("Error finding bill: " . $e->getMessage());
        }
    }

    public function findByStatus(string $status): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM bills WHERE status = ? ORDER BY created_at DESC");
            $stmt->execute([$status]);
            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => Bill::fromArray($data), $bills);
        } catch (Exception $e) {
            throw new Exception("Error finding bills by status: " . $e->getMessage());
        }
    }


    public function findByAuthor(string $author): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM bills WHERE author = ? ORDER BY created_at DESC");
            $stmt->execute([$author]);
            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => Bill::fromArray($data), $bills);
        } catch (Exception $e) {
            throw new Exception("Error finding bills by author: " . $e->getMessage());
        }
    }

    public function findAll(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM bills ORDER BY created_at DESC");
            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => Bill::fromArray($data), $bills);
        } catch (Exception $e) {
            throw new Exception("Error finding all bills: " . $e->getMessage());
        }
    }

    // public function startVoting(Bill $bill): void {
    //     try {
    //         if (!$bill->canStartVoting()) {
    //             throw new Exception("Bill must be in 'Review Complete' status to start voting");
    //         }

    //         $bill->setStatus('Voting Started');
    //         $this->updateBill($bill);
    //     } catch (Exception $e) {
    //         throw new Exception("Error starting voting: " . $e->getMessage());
    //     }
    // }

   
   
    // public function finalizeBillVoting(Bill $bill): void {
    //     $this->db->beginTransaction();
    //     try {
    //         if (!$bill->isVotingStarted()) {
    //             throw new Exception("Bill must be in 'Voting Started' status to finalize voting");
    //         }

    //         $voteCounts = $this->getVoteCounts($bill->getId());
    //         $newStatus = $bill->calculateVotingResult($voteCounts);
            
    //         $bill->setStatus($newStatus);
    //         $bill->setVotingFinalizedAt(date('Y-m-d H:i:s'));
            
    //         $this->updateBill($bill);
    //         $this->db->commit();
    //     } catch (Exception $e) {
    //         $this->db->rollBack();
    //         throw new Exception("Error finalizing bill voting: " . $e->getMessage());
    //     }
    // }

   
   
    // public function getVoteByUser(string $billId, string $username): ?string {
    //     try {
    //         $stmt = $this->db->prepare("
    //             SELECT vote 
    //             FROM votes 
    //             WHERE bill_id = ? AND username = ?
    //         ");
    //         $stmt->execute([$billId, $username]);
    //         return $stmt->fetchColumn() ?: null;
    //     } catch (Exception $e) {
    //         throw new Exception("Error getting user vote: " . $e->getMessage());
    //     }
    // }

  
    // public function getVoteCounts(string $billId): array {
    //     try {
    //         $stmt = $this->db->prepare("
    //             SELECT vote, COUNT(*) as count
    //             FROM votes
    //             WHERE bill_id = ?
    //             GROUP BY vote
    //         ");
    //         $stmt->execute([$billId]);
    //         $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
    //         $counts = ['For' => 0, 'Against' => 0, 'Abstain' => 0];
    //         foreach ($votes as $vote) {
    //             $counts[$vote['vote']] = (int)$vote['count'];
    //         }
            
    //         return $counts;
    //     } catch (Exception $e) {
    //         throw new Exception("Error getting vote counts: " . $e->getMessage());
    //     }
    // }

    
    
    // private function storeBillHistory(string $billId): void {
    //     $stmt = $this->db->prepare("
    //         INSERT INTO bill_history (
    //             bill_id, title, description, draft, status,
    //             review_completed_at, reviewed_by, voting_finalized_at
    //         )
    //         SELECT 
    //             id, title, description, draft, status,
    //             review_completed_at, reviewed_by, voting_finalized_at
    //         FROM bills 
    //         WHERE id = ?
    //     ");
    //     $stmt->execute([$billId]);
    // }
}