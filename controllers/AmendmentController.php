<?php

require_once __DIR__ . "/../models/Amendment.php";
require_once __DIR__ . "/../repositories/AmendmentRepository.php";

class AmendmentController {
    private AmendmentRepository $amendmentRepository;

    public function __construct() {
        $this->amendmentRepository = new AmendmentRepository();
    }

    public function create(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            if (!isset($_SESSION['user'])) {
                throw new Exception("User must be logged in");
            }

            $amendment = Amendment::fromArray([
                'bill_id' => $_POST['bill_id'],
                'reviewer' => $_SESSION['user']['username'],
                'amendment_text' => $_POST['amendment_text'],
                'comments' => $_POST['comments'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $errors = $amendment->validate();
            if (!empty($errors)) {
                throw new Exception(implode(", ", $errors));
            }

            $this->amendmentRepository->create($amendment);
            $this->redirectTo('/legislation_system_latest/views/bill_details.php?id=' . $amendment->getBillId());
        } catch (Exception $e) {
            $this->handleError("Error creating amendment: " . $e->getMessage());
        }
    }

    public function update(string $id): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            $amendment = $this->amendmentRepository->findById($id);
            if (!$amendment) {
                throw new Exception("Amendment not found");
            }

            if (!$this->isAuthorizedToEdit($amendment)) {
                throw new Exception("Unauthorized to edit this amendment");
            }

            $amendment->setAmendmentText($_POST['amendment_text']);
            $amendment->setComments($_POST['comments']);

            $errors = $amendment->validate();
            if (!empty($errors)) {
                throw new Exception(implode(", ", $errors));
            }

            $this->amendmentRepository->update($amendment);
            $this->redirectTo('/legislation_system_latest/views/bill_details.php?id=' . $amendment->getBillId());
        } catch (Exception $e) {
            $this->handleError("Error updating amendment: " . $e->getMessage());
        }
    }

    public function delete(string $id): void {
        try {
            $amendment = $this->amendmentRepository->findById($id);
            if (!$amendment) {
                throw new Exception("Amendment not found");
            }

            if (!$this->isAuthorizedToDelete($amendment)) {
                throw new Exception("Unauthorized to delete this amendment");
            }

            $billId = $amendment->getBillId();
            $this->amendmentRepository->delete($id);
            $this->redirectTo('/legislation_system_latest/views/bill_details.php?id=' . $billId);
        } catch (Exception $e) {
            $this->handleError("Error deleting amendment: " . $e->getMessage());
        }
    }

    public function getByBillId(string $billId): array {
        try {
            return $this->amendmentRepository->findByBillId($billId);
        } catch (Exception $e) {
            $this->handleError("Error getting amendments: " . $e->getMessage());
            return [];
        }
    }

    public function getById(string $id): ?Amendment {
        try {
            return $this->amendmentRepository->findById($id);
        } catch (Exception $e) {
            $this->handleError("Error getting amendment: " . $e->getMessage());
            return null;
        }
    }

    public function getByReviewer(string $reviewer): array {
        try {
            return $this->amendmentRepository->findByReviewer($reviewer);
        } catch (Exception $e) {
            $this->handleError("Error getting amendments: " . $e->getMessage());
            return [];
        }
    }

    private function isAuthorizedToEdit(Amendment $amendment): bool {
        return isset($_SESSION['user']) && (
            $_SESSION['user']['role'] === 'Admin' || 
            ($amendment->isCreatedByUser($_SESSION['user']['username']))
        );
    }

    private function isAuthorizedToDelete(Amendment $amendment): bool {
        return isset($_SESSION['user']) && (
            $_SESSION['user']['role'] === 'Admin' || 
            ($amendment->isCreatedByUser($_SESSION['user']['username']))
        );
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