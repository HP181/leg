<?php
require_once "../config.php";
require_once "../models/Bill.php";
require_once "../repositories/BillRepository.php";
require_once "../repositories/VoteRepository.php";
require_once "../repositories/AmendmentRepository.php";

class BillController {
    private BillRepository $billRepository;
    private VoteRepository $voteRepository;
    private AmendmentRepository $amendmentRepository;

    public function __construct() {
        $this->billRepository = new BillRepository();
        $this->voteRepository = new VoteRepository();
        $this->amendmentRepository = new AmendmentRepository();
    }

    public function createBill(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            $bill = new Bill(
                uniqid('bill_', true),
                $_POST['title'],
                $_POST['description'] ?? null,
                $_POST['author'],
                $_POST['draft'],
                'Draft',
                date('Y-m-d H:i:s')
            );

            $errors = $bill->validate();
            if (!empty($errors)) {
                throw new Exception(implode(", ", $errors));
            }

            $this->billRepository->createBill($bill);
            $this->redirectTo('/legislation_system_latest/views/dashboard_mp.php');
        } catch (Exception $e) {
            $this->handleError("Error creating bill: " . $e->getMessage());
        }
    }


    // In BillController class
    public function completeReview(string $billId): void {
        try {
            $bill = $this->billRepository->findById($billId);
            if (!$bill) {
                throw new Exception("Bill not found");
            }
    
            if (!$this->isAuthorizedForReview()) {
                throw new Exception("Unauthorized to complete review");
            }
    
            $bill->setStatus('Review Complete');
            $bill->setReviewCompletedAt(date('Y-m-d H:i:s')); // Format matches DATETIME
            $bill->setReviewedBy($_SESSION['user']['username']);
            
            $this->billRepository->updateBill($bill);
        } catch (Exception $e) {
            throw new Exception("Error completing review: " . $e->getMessage());
        }
    }

private function isAuthorizedForReview(): bool {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'Reviewer';
}

    // public function updateBill(string $billId): void {
    //     try {
    //         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    //             throw new Exception("Invalid request method");
    //         }

    //         $bill = $this->billRepository->findById($billId);
    //         if (!$bill) {
    //             throw new Exception("Bill not found");
    //         }

    //         if (!$this->isAuthorizedToEdit($bill)) {
    //             throw new Exception("Unauthorized to edit this bill");
    //         }

    //         $bill->setTitle($_POST['title']);
    //         $bill->setDescription($_POST['description'] ?? null);
    //         $bill->setDraft($_POST['draft'] ?? null);

    //         $errors = $bill->validate();
    //         if (!empty($errors)) {
    //             throw new Exception(implode(", ", $errors));
    //         }

    //         $this->billRepository->updateBill($bill);
    //         $this->redirectTo('/legislation_system_latest/views/bill_details.php?id=' . $billId);
    //     } catch (Exception $e) {
    //         $this->handleError("Error updating bill: " . $e->getMessage());
    //     }
    // }

   
   
    public function updateBill(Bill $bill): void {
        try {
            if (!$this->isAuthorizedToEdit($bill)) {
                throw new Exception("Unauthorized to edit this bill");
            }

            $errors = $bill->validate();
            if (!empty($errors)) {
                throw new Exception(implode(", ", $errors));
            }

            $this->billRepository->updateBill($bill);
        } catch (Exception $e) {
            $this->handleError("Error updating bill: " . $e->getMessage());
        }
    }
    // public function startVoting(string $billId): void {
    //     try {
    //         $bill = $this->billRepository->findById($billId);
    //         if (!$bill) {
    //             throw new Exception("Bill not found");
    //         }

    //         if (!$this->isAuthorizedToStartVoting()) {
    //             throw new Exception("Unauthorized to start voting");
    //         }

    //         $this->billRepository->startVoting($bill);
    //         $this->redirectTo('/legislation_system_latest/views/bills.php');
    //     } catch (Exception $e) {
    //         $this->handleError("Error starting voting: " . $e->getMessage());
    //     }
    // }

    // public function recordVote(string $billId, string $username, string $vote): void {
    //     try {
    //         $bill = $this->billRepository->findById($billId);
    //         if (!$bill || !$bill->isVotingStarted()) {
    //             throw new Exception("Invalid vote status");
    //         }

    //         $this->voteRepository->recordVote($billId, $username, $vote);
    //     } catch (Exception $e) {
    //         $this->handleError("Error recording vote: " . $e->getMessage());
    //     }
    // }

    // public function addAmendment(string $billId, string $content): void {
    //     try {
    //         $bill = $this->billRepository->findById($billId);
    //         if (!$bill) {
    //             throw new Exception("Bill not found");
    //         }

    //         if (!$this->isAuthorizedToAmend($bill)) {
    //             throw new Exception("Unauthorized to add amendments");
    //         }

    //         $amendmentData = [
    //             'bill_id' => $billId,
    //             'content' => $content,
    //             'created_by' => $_SESSION['user']['username'] ?? null
    //         ];

    //         $this->amendmentRepository->addAmendment($amendmentData);
    //         $this->redirectTo('/legislation_system_latest/views/bill_details.php?id=' . $billId);
    //     } catch (Exception $e) {
    //         $this->handleError("Error adding amendment: " . $e->getMessage());
    //     }
    // }

    public function getBillsByStatus(string $status): array {
        try {
            return $this->billRepository->findByStatus($status);
        } catch (Exception $e) {
            $this->handleError("Error getting bills by status: " . $e->getMessage());
            return [];
        }
    }

    public function getVotes(): array {
        return $this->voteRepository->findAll();
    }

    public function getBillById(string $billId): ?Bill {
        try {
            return $this->billRepository->findById($billId);
        } catch (Exception $e) {
            $this->handleError("Error getting bill: " . $e->getMessage());
            return null;
        }
    }

    public function getBillsByAuthor(string $author): array {
        try {
            return $this->billRepository->findByAuthor($author);
        } catch (Exception $e) {
            $this->handleError("Error getting bills by author: " . $e->getMessage());
            return [];
        }
    }

    // public function getVoteCounts(string $billId): array {
    //     try {
    //         return $this->billRepository->getVoteCounts($billId);
    //     } catch (Exception $e) {
    //         $this->handleError("Error getting vote counts: " . $e->getMessage());
    //         return ['For' => 0, 'Against' => 0, 'Abstain' => 0];
    //     }
    // }

    private function isAuthorizedToEdit(Bill $bill): bool {
        return isset($_SESSION['user']) && (
            $_SESSION['user']['role'] === 'Admin' || 
            ($_SESSION['user']['role'] === 'MP' && $bill->getAuthor() === $_SESSION['user']['username'])
        );
    }

    // private function isAuthorizedToStartVoting(): bool {
    //     return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'Admin';
    // }

    // private function isAuthorizedToAmend(Bill $bill): bool {
    //     return isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['Admin', 'MP']);
    // }

    private function handleError(string $message): void {
        error_log($message);
        $_SESSION['error'] = $message;
        header("Location: /legislation_system_latest/views/error.php");
        exit;
    }

    private function redirectTo(string $url): void {
        header("Location: $url");
        exit;
    }
}