<?php

class Amendment {
    private string $id;
    private string $billId;
    private string $reviewer;
    private string $amendmentText;
    private string $comments;
    private string $createdAt;

    public function __construct(
        string $id,
        string $billId,
        string $reviewer,
        string $amendmentText,
        string $comments,
        string $createdAt
    ) {
        $this->id = $id;
        $this->billId = $billId;
        $this->reviewer = $reviewer;
        $this->amendmentText = $amendmentText;
        $this->comments = $comments;
        $this->createdAt = $createdAt;
    }

    // Getters
    public function getId(): string {
        return $this->id;
    }

    public function getBillId(): string {
        return $this->billId;
    }

    public function getReviewer(): string {
        return $this->reviewer;
    }

    public function getAmendmentText(): string {
        return $this->amendmentText;
    }

    public function getComments(): string {
        return $this->comments;
    }

    public function getCreatedAt(): string {
        return $this->createdAt;
    }

    // Setters
    public function setReviewer(string $reviewer): void {
        $this->reviewer = $reviewer;
    }

    public function setAmendmentText(string $amendmentText): void {
        $this->amendmentText = $amendmentText;
    }

    public function setComments(string $comments): void {
        $this->comments = $comments;
    }

    // Business Logic Methods
    public function validate(): array {
        $errors = [];

        if (empty($this->billId)) {
            $errors[] = "Bill ID is required";
        }

        if (empty($this->reviewer)) {
            $errors[] = "Reviewer is required";
        }

        if (empty($this->amendmentText)) {
            $errors[] = "Amendment text is required";
        }

        if (empty($this->comments)) {
            $errors[] = "Comments are required";
        }

        return $errors;
    }

    public function isCreatedByUser(string $username): bool {
        return $this->reviewer === $username;
    }

    // Data conversion methods
    public function toArray(): array {
        return [
            'id' => $this->id,
            'bill_id' => $this->billId,
            'reviewer' => $this->reviewer,
            'amendment_text' => $this->amendmentText,
            'comments' => $this->comments,
            'created_at' => $this->createdAt
        ];
    }

    // Factory method
    public static function fromArray(array $data): self {
        return new self(
            $data['id'] ?? '',
            $data['bill_id'],
            $data['reviewer'],
            $data['amendment_text'],
            $data['comments'],
            $data['created_at'] ?? date('Y-m-d H:i:s')
        );
    }
}