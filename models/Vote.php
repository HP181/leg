<?php

class Vote {
    private string $id;
    private string $billId;
    private string $username;
    private string $vote;
    private string $votedAt;
    private array $allowedVotes = ['For', 'Against', 'Abstain'];

    public function __construct(
        string $billId,
        string $username,
        string $vote,
        string $votedAt = '',
        string $id = ''
    ) {
        $this->id = $id;
        $this->billId = $billId;
        $this->username = $username;
        $this->setVote($vote); // Use setter for validation
        $this->votedAt = $votedAt ?: date('Y-m-d H:i:s');
    }

    // Getters
    public function getId(): string {
        return $this->id;
    }

    public function getBillId(): string {
        return $this->billId;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getVote(): string {
        return $this->vote;
    }

    public function getVotedAt(): string {
        return $this->votedAt;
    }

    // Setters
    public function setVote(string $vote): void {
        if (!in_array($vote, $this->allowedVotes)) {
            throw new InvalidArgumentException("Invalid vote type. Allowed types are: " . implode(', ', $this->allowedVotes));
        }
        $this->vote = $vote;
    }

    // Business Logic Methods
    public function validate(): array {
        $errors = [];

        if (empty($this->billId)) {
            $errors['bill_id'] = "Bill ID is required";
        }

        if (empty($this->username)) {
            $errors['username'] = "Username is required";
        }

        if (empty($this->vote)) {
            $errors['vote'] = "Vote is required";
        } elseif (!in_array($this->vote, $this->allowedVotes)) {
            $errors['vote'] = "Invalid vote type";
        }

        return $errors;
    }

    public function isVotedBy(string $username): bool {
        return $this->username === $username;
    }

    public function hasVoted(): bool {
        return !empty($this->vote);
    }

    // Data conversion methods
    public function toArray(): array {
        return [
            'id' => $this->id,
            'bill_id' => $this->billId,
            'username' => $this->username,
            'vote' => $this->vote,
            'voted_at' => $this->votedAt
        ];
    }

    // Factory method
    public static function fromArray(array $data): self {
        return new self(
            $data['bill_id'],
            $data['username'],
            $data['vote'],
            $data['voted_at'] ?? '',
            $data['id'] ?? ''
        );
    }
}