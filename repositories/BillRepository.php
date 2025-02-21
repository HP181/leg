<?php

require_once __DIR__ . "/../config.php"; 

class BillRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createBill(array $billData): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO bills (id, title, description, author, draft, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $billId = uniqid('bill_', true);
            $stmt->execute([
                $billId,
                $billData['title'],
                $billData['description'],
                $billData['author'],
                $billData['draft'],
                $billData['status']
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating bill: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateBill(string $billId, array $updatedData): void {
        $this->db->beginTransaction();
        try {
            // First store the current version in history
            $stmt = $this->db->prepare("
                INSERT INTO bill_history (bill_id, title, description, draft, status)
                SELECT id, title, description, draft, status FROM bills WHERE id = ?
            ");
            $stmt->execute([$billId]);

            // Then update the bill
            $updateFields = [];
            $params = [];
            foreach ($updatedData as $key => $value) {
                if ($value !== null) {  // Only include non-null values
                    $updateFields[] = "$key = ?";
                    $params[] = $value;
                }
            }
            $params[] = $billId;

            $stmt = $this->db->prepare("
                UPDATE bills 
                SET " . implode(", ", $updateFields) . "
                WHERE id = ?
            ");
            
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                // If no rows were updated, exit without error
                return;
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating bill: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAllBills(): array {
        $stmt = $this->db->query("SELECT * FROM bills");
        return $stmt->fetchAll();
    }

    public function getBillById(string $billId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM bills WHERE id = ?");
        $stmt->execute([$billId]);
        return $stmt->fetch() ?: null;
    }

    public function startVoting(string $billId): void {
        try {
            error_log("Starting voting for bill: " . $billId); // Debug log
            
            // First verify the bill exists and has correct status
            $bill = $this->getBillById($billId);
            if (!$bill) {
                throw new Exception("Bill not found");
            }
            
            if ($bill['status'] !== 'Review Complete') {
                throw new Exception("Bill must be in 'Review Complete' status to start voting");
            }
    
            $this->updateBill($billId, ['status' => 'Voting Started']);
            error_log("Successfully started voting for bill: " . $billId); // Debug log
        } catch (Exception $e) {
            error_log("Error in startVoting: " . $e->getMessage());
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

    public function getBillsByStatus(string $status): array {
        $stmt = $this->db->prepare("SELECT * FROM bills WHERE status = ?");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function getBillsByAuthor(string $author): array {
        $stmt = $this->db->prepare("SELECT * FROM bills WHERE author = ? ORDER BY created_at DESC");
        $stmt->execute([$author]);
        return $stmt->fetchAll();
    }

    public function isVotingStarted(string $billId): bool {
        $stmt = $this->db->prepare("SELECT status FROM bills WHERE id = ?");
        $stmt->execute([$billId]);
        $result = $stmt->fetch();
        return $result && $result['status'] === 'Voting Started';
    }

    public function finalizeBillVoting(string $billId): void {
        $this->db->beginTransaction();
        try {
            // Check if bill exists and is in voting state
            $bill = $this->getBillById($billId);
            if (!$bill) {
                throw new Exception("Bill not found");
            }
    
            if ($bill['status'] !== 'Voting Started') {
                throw new Exception("Bill must be in 'Voting Started' status to finalize voting");
            }
    
            // Calculate results
            $voteCounts = $this->getVoteCounts($billId);
            $validVotes = $voteCounts['For'] + $voteCounts['Against'];
            
            if ($validVotes > 0) {
                $forPercentage = ($voteCounts['For'] / $validVotes) * 100;
                $newStatus = $forPercentage > 50 ? 'Passed' : 'Rejected';
                
                // Update bill status
                $stmt = $this->db->prepare("
                    UPDATE bills 
                    SET status = ?, 
                        voting_finalized_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([$newStatus, $billId]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Error finalizing bill voting");
                }
            }
    
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error finalizing bill voting: " . $e->getMessage());
            throw $e;
        }
    }

    public function getVoteCounts(string $billId): array {
        $stmt = $this->db->prepare("
            SELECT vote, COUNT(*) as count
            FROM votes
            WHERE bill_id = ?
            GROUP BY vote
        ");
        $stmt->execute([$billId]);
        $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = ['For' => 0, 'Against' => 0, 'Abstain' => 0];
        
        foreach ($votes as $vote) {
            $counts[$vote['vote']] = (int) $vote['count'];
        }
        
        return $counts;
    }
}
?>
