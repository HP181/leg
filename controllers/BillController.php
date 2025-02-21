//<?php
//require_once "../config.php";
//
//class BillController {
//    private BillRepository $billRepository;
//    private VoteRepository $voteRepository;
//    private AmendmentRepository $amendmentRepository;
//
//    public function __construct() {
//        $this->billRepository = new BillRepository();
//        $this->voteRepository = new VoteRepository();
//        $this->amendmentRepository = new AmendmentRepository();
//    }
//
//    public function createBill(string $title, string $description, string $author, string $draft): void {
//        try {
//            // Create the bill data array
//            $billData = [
//                'title' => $title,
//                'description' => $description,
//                'author' => $author,
//                'draft' => $draft,
//                'status' => 'Draft'
//            ];
//
//            // Validate required fields
//            foreach ($billData as $field => $value) {
//                if (empty($value)) {
//                    throw new InvalidArgumentException("Missing required field: $field");
//                }
//            }
//
//            $this->billRepository->createBill($billData);
//            
//            header("Location: /hit/views/dashboard_mp.php");
//            exit;
//        } catch (Exception $e) {
//            error_log("Error creating bill: " . $e->getMessage());
//            echo "Error creating bill: " . $e->getMessage();
//        }
//    }
//
//    public function updateBill(string $billId, array $updatedData): void {
//        try {
//            // Debug logging
//            error_log("Attempting to update bill ID: " . $billId);
//            error_log("Update data: " . print_r($updatedData, true));
//    
//            $bill = $this->billRepository->getBillById($billId);
//            if (!$bill) {
//                throw new Exception("Bill not found");
//            }
//    
//            // Debug logging
//            error_log("Current bill status: " . $bill['status']);
//    
//            // Verify user authorization
//            if (!$this->isAuthorizedToEdit($billId)) {
//                throw new Exception("Unauthorized to edit this bill");
//            }
//    
//            $this->billRepository->updateBill($billId, $updatedData);
//            
//            // Remove the automatic redirect to allow seeing any errors
//            // header("Location: /hit/views/dashboard_admin.php");
//            // exit;
//        } catch (Exception $e) {
//            error_log("Error updating bill: " . $e->getMessage());
//            throw $e; // Re-throw the exception to be caught by the caller
//        }
//    }
//
//    public function startVoting(string $billId): void {
//        try {
//            if (!$this->isAuthorizedToStartVoting($billId)) {
//                throw new Exception("Unauthorized to start voting");
//            }
//
//            $this->billRepository->startVoting($billId);
//            header("Location: /hit/views/bills.php");
//            exit;
//        } catch (Exception $e) {
//            error_log("Error starting voting: " . $e->getMessage());
//            echo "Error starting voting: " . $e->getMessage();
//        }
//    }
//
//    public function recordVote(string $billId, string $username, string $vote): void {
//        try {
//            // Verify bill exists and is in voting state
//            $bill = $this->billRepository->getBillById($billId);
//            if (!$bill) {
//                throw new Exception("Bill not found");
//            }
//    
//            if ($bill['status'] !== 'Voting Started') {
//                throw new Exception("Voting is not currently active for this bill");
//            }
//    
//            // Verify valid vote type
//            if (!in_array($vote, ['For', 'Against', 'Abstain'])) {
//                throw new Exception("Invalid vote type");
//            }
//    
//            // Record the vote
//            $this->voteRepository->recordVote($billId, $username, $vote);
//    
//        } catch (Exception $e) {
//            error_log("Error recording vote: " . $e->getMessage());
//            throw $e;
//        }
//    }
//
//    public function addAmendment(string $billId, string $content): void {
//        try {
//            if (!$this->isAuthorizedToAmend($billId)) {
//                throw new Exception("Unauthorized to add amendments");
//            }
//
//            $amendmentData = [
//                'bill_id' => $billId,
//                'content' => $content
//            ];
//
//            $this->amendmentRepository->addAmendment($amendmentData);
//            
//            header("Location: /hit/views/bill_details.php?id=" . $billId);
//            exit;
//        } catch (Exception $e) {
//            error_log("Error adding amendment: " . $e->getMessage());
//            echo "Error adding amendment: " . $e->getMessage();
//        }
//    }
//
//    private function isAuthorizedToEdit(string $billId): bool {
//        if (!isset($_SESSION['user'])) {
//            return false;
//        }
//
//        $bill = $this->billRepository->getBillById($billId);
//        $userRole = $_SESSION['user']['role'];
//        $username = $_SESSION['user']['username'];
//
//        return $userRole === 'Admin' || 
//               ($userRole === 'MP' && $bill['author'] === $username);
//    }
//
//    private function isAuthorizedToStartVoting(string $billId): bool {
//        return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'Admin';
//    }
//
//    private function isAuthorizedToAmend(string $billId): bool {
//        return isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['Admin', 'MP']);
//    }
//
//    private function checkAndFinalizeBill(string $billId): void {
//        // Add any logic for when to finalize voting
//        // For example, if all MPs have voted or time limit reached
//        $this->billRepository->finalizeBillVoting($billId);
//    }
//
//    public function getBillById(string $billId): ?array {
//        return $this->billRepository->getBillById($billId);
//    }
//
//    public function getAllBills(): array {
//        return $this->billRepository->getAllBills();
//    }
//
//    public function getBillsByStatus(string $status): array {
//        return $this->billRepository->getBillsByStatus($status);
//    }
//
//    public function getAmendmentsByBillId(string $billId): array {
//        return $this->amendmentRepository->getAmendmentsByBillId($billId);
//    }
//
//    public function getVotesByBillId(string $billId): array {
//        return $this->voteRepository->getVotesByBillId($billId);
//    }
//
//    public function getBillsByAuthor(string $author): array {
//        try {
//            // Verify author exists (optional but recommended)
//            $userRepository = new UserRepository();
//            if (!$userRepository->findByUsername($author)) {
//                throw new Exception("Author not found");
//            }
//
//            return $this->billRepository->getBillsByAuthor($author);
//        } catch (Exception $e) {
//            error_log("Error getting bills by author: " . $e->getMessage());
//            return [];
//        }
//    }
//}
//?>








<?php
require_once "../config.php";

class BillController {
    private BillRepository $billRepository;
    private VoteRepository $voteRepository;
    private AmendmentRepository $amendmentRepository;

    public function __construct() {
        $this->billRepository = new BillRepository();
        $this->voteRepository = new VoteRepository();
        $this->amendmentRepository = new AmendmentRepository();
    }

    public function createBill(string $title, string $description, string $author, string $draft): void {
        try {
            // Prepare bill data
            $billData = [
                'title' => $title,
                'description' => $description,
                'author' => $author,
                'draft' => $draft,
                'status' => 'Draft'
            ];

            // Validate bill fields
            $this->validateBillFields($billData);
            
            // Create the bill
            $this->billRepository->createBill($billData);

            // Redirect to MP Dashboard
            $this->redirectTo('/legislation_system_latest/views/dashboard_mp.php');
        } catch (Exception $e) {
            $this->handleError("Error creating bill: " . $e->getMessage());
        }
    }

    public function updateBill(string $billId, array $updatedData): void {
        try {
            $bill = $this->billRepository->getBillById($billId);
            if (!$bill) throw new Exception("Bill not found");

            // Verify authorization to update
            $this->verifyAuthorization($billId);

            // Update the bill
            $this->billRepository->updateBill($billId, $updatedData);
        } catch (Exception $e) {
            $this->handleError("Error updating bill: " . $e->getMessage());
        }
    }

    public function startVoting(string $billId): void {
        try {
            $this->verifyVotingAuthorization($billId);
            $this->billRepository->startVoting($billId);
            $this->redirectTo('/hit/views/bills.php');
        } catch (Exception $e) {
            $this->handleError("Error starting voting: " . $e->getMessage());
        }
    }

    public function recordVote(string $billId, string $username, string $vote): void {
        try {
            $bill = $this->billRepository->getBillById($billId);
            if (!$bill || $bill['status'] !== 'Voting Started') {
                throw new Exception("Invalid vote status");
            }

            $this->voteRepository->recordVote($billId, $username, $vote);
        } catch (Exception $e) {
            $this->handleError("Error recording vote: " . $e->getMessage());
        }
    }

    public function addAmendment(string $billId, string $content): void {
        try {
            $this->verifyAmendmentAuthorization($billId);
            $amendmentData = ['bill_id' => $billId, 'content' => $content];
            $this->amendmentRepository->addAmendment($amendmentData);
            $this->redirectTo('/legislation_system_latest/views/bill_details.php?id=' . $billId);
        } catch (Exception $e) {
            $this->handleError("Error adding amendment: " . $e->getMessage());
        }
    }

    public function getBillById(string $billId): ?array {
               return $this->billRepository->getBillById($billId);
           }

    public function getBillsByAuthor(string $author): array {
               try {
                   // Verify author exists (optional but recommended)
                   $userRepository = new UserRepository();
                   if (!$userRepository->findByUsername($author)) {
                       throw new Exception("Author not found");
                   }
        
                   return $this->billRepository->getBillsByAuthor($author);
               } catch (Exception $e) {
                   error_log("Error getting bills by author: " . $e->getMessage());
                   return [];
               }
           }

           public function getVoteCounts(string $billId): array {
            return $this->billRepository->getVoteCounts($billId);
        }
    private function validateBillFields(array $billData): void {
        foreach ($billData as $field => $value) {
            if (empty($value)) {
                throw new InvalidArgumentException("Missing required field: $field");
            }
        }
    }

    private function verifyAuthorization(string $billId): void {
        if (!$this->isAuthorizedToEdit($billId)) {
            throw new Exception("Unauthorized to edit this bill");
        }
    }

    private function verifyVotingAuthorization(string $billId): void {
        if (!$this->isAuthorizedToStartVoting($billId)) {
            throw new Exception("Unauthorized to start voting");
        }
    }

    private function verifyAmendmentAuthorization(string $billId): void {
        if (!$this->isAuthorizedToAmend($billId)) {
            throw new Exception("Unauthorized to add amendments");
        }
    }

    private function handleError(string $message): void {
        error_log($message);
        echo $message;
    }

    private function redirectTo(string $url): void {
        header("Location: $url");
        exit;
    }

    private function isAuthorizedToEdit(string $billId): bool {
        return isset($_SESSION['user']) && (
            $_SESSION['user']['role'] === 'Admin' || 
            ($_SESSION['user']['role'] === 'MP' && $this->billRepository->getBillById($billId)['author'] === $_SESSION['user']['username'])
        );
    }

    private function isAuthorizedToStartVoting(string $billId): bool {
        return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'Admin';
    }

    private function isAuthorizedToAmend(string $billId): bool {
        return isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['Admin', 'MP']);
    }
}
?>
