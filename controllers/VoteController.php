<?php

require_once "../config.php";
require_once "../models/Vote.php";
require_once "../repositories/VoteRepository.php";
require_once "../repositories/BillRepository.php";

class VoteController {
    private VoteRepository $voteRepository;
    private BillRepository $billRepository;

    public function __construct() {
        $this->voteRepository = new VoteRepository();
        $this->billRepository = new BillRepository();
    }

    public function recordVote(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            if (!isset($_SESSION['user'])) {
                throw new Exception("User must be logged in to vote");
            }

            $billId = $_POST['bill_id'] ?? '';
            $voteType = $_POST['vote'] ?? '';

            // Validate bill exists and is in voting state
            $bill = $this->billRepository->findById($billId);
            if (!$bill || !$bill->isVotingStarted()) {
                throw new Exception("Invalid bill or voting not started");
            }

            // Create and validate vote
            $vote = new Vote(
                $billId,
                $_SESSION['user']['username'],
                $voteType
            );

            $errors = $vote->validate();
            if (!empty($errors)) {
                throw new Exception(implode(", ", $errors));
            }

            // Record the vote
            $this->voteRepository->create($vote);

            // Redirect back to bill details
            $this->redirectTo("/legislation_system_latest/views/dashboard_mp.php");
        } catch (Exception $e) {
            $this->handleError("Error recording vote: " . $e->getMessage());
        }
    }

    // public function getVotes(string $billId): array {
    //     try {
    //         return $this->voteRepository->findByBillId($billId);
    //     } catch (Exception $e) {
    //         $this->handleError("Error getting votes: " . $e->getMessage());
    //         return [];
    //     }
    // }

    public function getUserVote(string $billId): ?Vote {
        try {
            if (!isset($_SESSION['user'])) {
                return null;
            }

            return $this->voteRepository->findByUser($billId, $_SESSION['user']['username']);
        } catch (Exception $e) {
            $this->handleError("Error getting user vote: " . $e->getMessage());
            return null;
        }
    }

    public function getVoteCounts(string $billId): array {
        try {
            return $this->voteRepository->getVoteCounts($billId);
        } catch (Exception $e) {
            $this->handleError("Error getting vote counts: " . $e->getMessage());
            return ['For' => 0, 'Against' => 0, 'Abstain' => 0];
        }
    }

    // public function changeVote(): void {
    //     try {
    //         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    //             throw new Exception("Invalid request method");
    //         }

    //         if (!isset($_SESSION['user'])) {
    //             throw new Exception("User must be logged in to change vote");
    //         }

    //         $billId = $_POST['bill_id'] ?? '';
    //         $newVoteType = $_POST['vote'] ?? '';

    //         // Create and validate new vote
    //         $vote = new Vote(
    //             $billId,
    //             $_SESSION['user']['username'],
    //             $newVoteType
    //         );

    //         $errors = $vote->validate();
    //         if (!empty($errors)) {
    //             throw new Exception(implode(", ", $errors));
    //         }

    //         // Update the vote
    //         $this->voteRepository->create($vote);

    //         $this->redirectTo("/legislation_system_latest/views/bill_details.php?id=$billId&voted=1");
    //     } catch (Exception $e) {
    //         $this->handleError("Error changing vote: " . $e->getMessage());
    //     }
    // }

    public function deleteVote(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            if (!isset($_SESSION['user'])) {
                throw new Exception("User must be logged in to delete vote");
            }

            $billId = $_POST['bill_id'] ?? '';
            
            $this->voteRepository->deleteVote($billId, $_SESSION['user']['username']);

            $this->redirectTo("/legislation_system_latest/views/bill_details.php?id=$billId&deleted=1");
        } catch (Exception $e) {
            $this->handleError("Error deleting vote: " . $e->getMessage());
        }
    }

    // controllers/VoteController.php
public function getVotes(): array {
    try {
        return $this->voteRepository->findAll();
    } catch (Exception $e) {
        $this->handleError("Error getting votes: " . $e->getMessage());
        return [];
    }
}

// For getting votes by type
public function getVotesByType(string $voteType): array {
    try {
        $allVotes = $this->voteRepository->findAll();
        return array_filter($allVotes, fn($vote) => $vote->getVote() === $voteType);
    } catch (Exception $e) {
        $this->handleError("Error getting votes by type: " . $e->getMessage());
        return [];
    }
}

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