<?php

class Bill {
    private string $id;
    private string $title;
    private ?string $description;
    private ?string $author;
    private ?string $draft;
    private string $status;
    private string $createdAt;
    private ?string $reviewCompletedAt;
    private ?string $reviewedBy;
    private ?string $votingFinalizedAt;

    public function __construct(
        string $id,
        string $title,
        ?string $description,
        ?string $author,
        ?string $draft,
        string $status,
        string $createdAt,
        ?string $reviewCompletedAt = null,
        ?string $reviewedBy = null,
        ?string $votingFinalizedAt = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->author = $author;
        $this->draft = $draft;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->reviewCompletedAt = $reviewCompletedAt;
        $this->reviewedBy = $reviewedBy;
        $this->votingFinalizedAt = $votingFinalizedAt;
    }

    // Getters
    public function getId(): string {
        return $this->id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function getAuthor(): ?string {
        return $this->author;
    }

    public function getDraft(): ?string {
        return $this->draft;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function getCreatedAt(): string {
        return $this->createdAt;
    }

    public function getReviewCompletedAt(): ?string {
        return $this->reviewCompletedAt;
    }

    public function getReviewedBy(): ?string {
        return $this->reviewedBy;
    }

    public function getVotingFinalizedAt(): ?string {
        return $this->votingFinalizedAt;
    }

    // Setters
    public function setTitle(string $title): void {
        $this->title = $title;
    }

    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    public function setAuthor(?string $author): void {
        $this->author = $author;
    }

    public function setDraft(?string $draft): void {
        $this->draft = $draft;
    }

    public function setStatus(string $status): void {
        $this->status = $status;
    }

    public function setReviewCompletedAt(?string $reviewCompletedAt): void {
        $this->reviewCompletedAt = $reviewCompletedAt;
    }

    public function setReviewedBy(?string $reviewedBy): void {
        $this->reviewedBy = $reviewedBy;
    }

    public function setVotingFinalizedAt(?string $votingFinalizedAt): void {
        $this->votingFinalizedAt = $votingFinalizedAt;
    }




    public function canStartVoting(): bool {
        return $this->status === 'Review Complete';
    }

    public function canBeEdited(): bool {
        return in_array($this->status, ['Draft', 'Under Review']);
    }

    public function isVotingStarted(): bool {
        return $this->status === 'Voting Started';
    }

    public function calculateVotingResult(array $voteCounts): string {
        $validVotes = $voteCounts['For'] + $voteCounts['Against'];
        if ($validVotes === 0) return $this->status;
        
        $forPercentage = ($voteCounts['For'] / $validVotes) * 100;
        return $forPercentage > 50 ? 'Passed' : 'Rejected';
    }

    // Validation Methods
    public function validate(): array {
        $errors = [];
        
        if (empty($this->title)) {
            $errors[] = "Title is required";
        }
        
        if (empty($this->author)) {
            $errors[] = "Author is required";
        }
        
        if (empty($this->draft)) {
            $errors[] = "Draft content is required";
        }
        
        return $errors;
    }

  // Factory method to create from array
    public static function fromArray(array $data): self {
        return new self(
            $data['id'],
            $data['title'],
            $data['description'] ?? null,
            $data['author'] ?? null,
            $data['draft'] ?? null,
            $data['status'],
            $data['created_at'],
            $data['review_completed_at'] ?? null,
            $data['reviewed_by'] ?? null,
            $data['voting_finalized_at'] ?? null
        );
    }
}


