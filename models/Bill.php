<!-- require_once "Repository.php";

class Bill {
    private $repo;

    public function __construct() {
        $this->repo = new Repository("bills.json");
    }

    public function createBill($title, $description, $author) {
        $bills = $this->repo->getAll();
        $bills[] = ["title" => $title, "description" => $description, "author" => $author, "status" => "Draft"];
        $this->repo->saveAll($bills);
    }

    public function getAllBills() {
        return $this->repo->getAll();
    }
} -->

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
        ?string $reviewCompletedAt,
        ?string $reviewedBy,
        ?string $votingFinalizedAt
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

    // Convert Bill object to array (for database operations)
    public function toArray(): array {
        return [
            'id'                  => $this->id,
            'title'               => $this->title,
            'description'         => $this->description,
            'author'              => $this->author,
            'draft'               => $this->draft,
            'status'              => $this->status,
            'created_at'          => $this->createdAt,
            'review_completed_at' => $this->reviewCompletedAt,
            'reviewed_by'         => $this->reviewedBy,
            'voting_finalized_at' => $this->votingFinalizedAt
        ];
    }
}


