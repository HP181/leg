<?php
require_once "../controllers/BillController.php";
require_once "../controllers/VoteController.php";
require_once "../controllers/AmendmentController.php";

// Debug incoming parameters
error_log("View Type: " . $viewType);
error_log("Filter Status: " . ($filterStatus ?: 'none'));
error_log("Vote Type: " . ($voteType ?: 'none'));
error_log("Bill Filter: " . ($billFilter ?: 'none'));

// Initialize controllers with validation
if (!isset($billController)) {
    error_log("Creating new BillController");
    $billController = new BillController();
}
if (!isset($voteController)) {
    error_log("Creating new VoteController");
    $voteController = new VoteController();
}
if (!isset($amendmentController)) {
    error_log("Creating new AmendmentController");
    $amendmentController = new AmendmentController();
}

try {
    switch ($viewType):
        case 'bills':
            error_log("Fetching bills...");
            $bills = [];
            
            if ($filterStatus) {
                error_log("Fetching bills by status: " . $filterStatus);
                $bills = $billController->getBillsByStatus($filterStatus);
            }  else{
                error_log("Fetching all bills");
                $bills = $billController->getBillsByAuthor('%');
            }

            error_log("Found " . count($bills) . " bills");
            
            if (!is_array($bills)) {
                error_log("Warning: Bills is not an array");
                $bills = [];
            }
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Author</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bills)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No bills found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bills as $bill): ?>
                            <?php
                            if (!is_object($bill)) {
                                error_log("Warning: Bill is not an object: " . print_r($bill, true));
                                continue;
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill->getTitle()); ?></td>
                                <td><?php echo htmlspecialchars($bill->getDescription()); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($bill->getStatus()); ?>">
                                        <?php echo htmlspecialchars($bill->getStatus()); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($bill->getAuthor()); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
            break;

        case 'votes':
            error_log("Fetching votes...");
            try {
                if ($voteType) {
                    error_log("Fetching votes by type: " . $voteType);
                    $votes = $voteController->getVotesByType($voteType);
                } else {
                    error_log("Fetching all votes");
                    $votes = $voteController->getVotes();
                }

                error_log("Found " . (is_array($votes) ? count($votes) : 'non-array') . " votes");
                
                if (!is_array($votes)) {
                    error_log("Warning: Votes is not an array");
                    $votes = [];
                }
            } catch (Exception $e) {
                error_log("Error getting votes: " . $e->getMessage());
                $votes = [];
            }
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Bill Title</th>
                        <th>User</th>
                        <th>Vote</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($votes)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No votes found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($votes as $vote): ?>
                            <?php
                            if (!is_object($vote)) {
                                error_log("Warning: Vote is not an object: " . print_r($vote, true));
                                continue;
                            }
                            
                            try {
                                $bill = $billController->getBillById($vote->getBillId());
                                error_log("Found bill for vote: " . ($bill ? "yes" : "no"));
                            } catch (Exception $e) {
                                error_log("Error getting bill for vote: " . $e->getMessage());
                                $bill = null;
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill ? $bill->getTitle() : 'Unknown Bill'); ?></td>
                                <td><?php echo htmlspecialchars($vote->getUsername()); ?></td>
                                <td>
                                    <span class="vote-badge vote-<?php echo strtolower($vote->getVote()); ?>">
                                        <?php echo htmlspecialchars($vote->getVote()); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($vote->getVotedAt()); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
            break;

        case 'amendments':
            error_log("Fetching amendments...");
            try {
                if ($billFilter) {
                    error_log("Fetching amendments by bill ID: " . $billFilter);
                    $amendments = $amendmentController->getByBillId($billFilter);
                } else {
                    error_log("Fetching all amendments");
                    $amendments = $amendmentController->getAll();
                }

                error_log("Found " . (is_array($amendments) ? count($amendments) : 'non-array') . " amendments");
                
                if (!is_array($amendments)) {
                    error_log("Warning: Amendments is not an array");
                    $amendments = [];
                }
            } catch (Exception $e) {
                error_log("Error getting amendments: " . $e->getMessage());
                $amendments = [];
            }
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Bill Title</th>
                        <th>Reviewer</th>
                        <th>Amendment Text</th>
                        <th>Comments</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($amendments)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No amendments found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($amendments as $amendment): ?>
                            <?php
                            if (!is_object($amendment)) {
                                error_log("Warning: Amendment is not an object: " . print_r($amendment, true));
                                continue;
                            }
                            
                            try {
                                $bill = $billController->getBillById($amendment->getBillId());
                                error_log("Found bill for amendment: " . ($bill ? "yes" : "no"));
                            } catch (Exception $e) {
                                error_log("Error getting bill for amendment: " . $e->getMessage());
                                $bill = null;
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill ? $bill->getTitle() : 'Unknown Bill'); ?></td>
                                <td><?php echo htmlspecialchars($amendment->getReviewer()); ?></td>
                                <td><?php echo htmlspecialchars($amendment->getAmendmentText()); ?></td>
                                <td><?php echo htmlspecialchars($amendment->getComments()); ?></td>
                                <td><?php echo htmlspecialchars($amendment->getCreatedAt()); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
            break;
    endswitch;
} catch (Exception $e) {
    error_log("Major error in data display: " . $e->getMessage());
    echo '<div class="error-message">' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<style>
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.9em;
        color: white;
    }

    .vote-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.9em;
        color: white;
        display: inline-block;
        min-width: 60px;
        text-align: center;
    }

    .vote-for {
        background-color: #28a745;
    }

    .vote-against {
        background-color: #dc3545;
    }

    .vote-abstain {
        background-color: #6c757d;
    }

    .status-badge {
        background-color: #007bff;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th, td {
        padding: 12px;
        text-align: left;
        border: 1px solid #dee2e6;
    }

    th {
        background-color: #f8f9fa;
        font-weight: bold;
        color: #495057;
    }

    tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    tr:hover {
        background-color: #f2f2f2;
    }

    tbody tr:hover {
        background-color: rgba(0,123,255,0.05);
    }
</style>