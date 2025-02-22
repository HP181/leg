<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config.php";
require_once "../controllers/BillController.php";
require_once "../controllers/AmendmentController.php";
require_once "../controllers/VoteController.php";
require_once "../repositories/BillRepository.php";

// Ensure that the user is logged in and has the correct role
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Admin', 'MP'])) {
    header("Location: login.php");
    exit();
}

// Instantiate controllers and repositories
$billController = new BillController();
$amendmentController = new AmendmentController();
$voteController = new VoteController();
$billRepository = new BillRepository();

// Handle Voting Session Start
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_voting'])) {
    try {
        $billId = $_POST['bill_id'];
        $bill = $billController->getBillById($billId);
        
        if (!$bill) {
            throw new Exception("Bill not found");
        }
        
        if ($bill->canStartVoting()) {
            $bill->setStatus('Voting Started');
            $billController->updateBill($bill);
            header("Location: dashboard_admin.php");
            exit();
        } else {
            throw new Exception("Bill must be in 'Review Complete' status to start voting");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Handle Accept/Reject Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $billId = $_POST['bill_id'] ?? '';
        $action = $_POST['action'] ?? '';

        if (empty($billId) || !in_array($action, ['approve', 'reject'])) {
            throw new Exception("Invalid request");
        }

        $bill = $billController->getBillById($billId);
        if (!$bill) {
            throw new Exception("Bill not found");
        }

        $newStatus = $action === 'approve' ? 'Passed' : 'Rejected';
        $bill->setStatus($newStatus);
        $billController->updateBill($bill);
        
        header("Location: dashboard_admin.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Handle Finalize Voting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_voting'])) {
    try {
        $billId = $_POST['bill_id'] ?? '';
        if (empty($billId)) {
            throw new Exception("Bill ID is required");
        }

        $bill = $billController->getBillById($billId);
        if (!$bill) {
            throw new Exception("Bill not found");
        }

        if (!$bill->isVotingStarted()) {
            throw new Exception("Bill must be in voting started status");
        }

        $voteCounts = $voteController->getVoteCounts($billId);
        $newStatus = $bill->calculateVotingResult($voteCounts);
        
        $bill->setStatus($newStatus);
        $bill->setVotingFinalizedAt(date('Y-m-d H:i:s'));
        
        $billController->updateBill($bill);
        
        header("Location: dashboard_admin.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Fetch all bills
try {
    $bills = $billRepository->findAll();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $bills = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color: #f4f4f4;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        h2 {
            font-size: 1.5rem;
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1rem;
        }

        .user-role {
            font-weight: bold;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 5px;
        }

        .user-info>p {
            color: white;
            font-size: 1.5rem;
            margin: 0;
        }

        .logout-link {
            background: white;
            color: #667eea;
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s ease;
        }

        .logout-link:hover {
            background: #e1e1e1;
        }

        @media (max-width: 600px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .user-info {
                flex-direction: column;
            }
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Amendment Styles */
        .amendment-item {
            background: #f8f9fa;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 4px;
            border-left: 3px solid #2196F3;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .amendment-item:last-child {
            margin-bottom: 0;
        }

        .amendment-item strong {
            color: #333;
            display: block;
            margin-bottom: 8px;
        }

        .amendment-item p {
            margin: 8px 0;
            color: #555;
        }

        .amendment-item em {
            color: #666;
            display: block;
            margin-top: 8px;
            font-size: 0.9em;
        }

        /* Button and Form Styles */
        .action-select {
            padding: 8px 12px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            cursor: pointer;
        }

        .action-select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .update-status-btn,
        .start-voting-btn {
            padding: 8px 16px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .update-status-btn {
            background-color: #4CAF50;
        }

        .start-voting-btn {
            background-color: #2196F3;
        }

        .update-status-btn:hover,
        .start-voting-btn:hover {
            opacity: 0.9;
        }

        .update-status-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        /* Results Styles */
        .voting-results,
        .final-results {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 5px 0;
            background-color: #f8f9fa;
        }

        .voting-results h4,
        .final-results h4 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .voting-results p,
        .final-results p {
            margin: 8px 0;
            line-height: 1.4;
            color: #555;
        }

        /* Dashboard Header Styles */
        .dashboard-header {
            background-color: white;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .dashboard-header h2 {
            margin: 0;
            color: #333;
        }

        .user-info {
            color: #666;
            margin: 10px 0;
        }

        .logout-link {
            color: #dc3545;
            text-decoration: none;
        }

        .logout-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h2>Admin Dashboard</h2>
        <div class="user-info">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</p>
            <p>Role: <?php echo htmlspecialchars($_SESSION['user']['role']); ?></p>
            <a href="logout.php" class="logout-link">Logout</a>
        </div>
    </div>

    <h2>Bill Management</h2>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <?php 
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
                <th>Edit</th>
                <th>Amendments</th>
                <th>Voting Control</th>
                <th>Voting Results</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bills as $bill): ?>
                <tr>
                    <td><?php echo htmlspecialchars($bill->getId()); ?></td>
                    <td><?php echo htmlspecialchars($bill->getTitle()); ?></td>
                    <td><?php echo htmlspecialchars($bill->getDescription()); ?></td>
                    <td><?php echo htmlspecialchars($bill->getStatus()); ?></td>
                    <td>
                        <?php
                        $voteCounts = $voteController->getVoteCounts($bill->getId());
                        $totalVotes = $voteCounts['For'] + $voteCounts['Against'] + $voteCounts['Abstain'];
                        $isActionEnabled = $bill->getStatus() === 'Voting Started' && $totalVotes > 0;
                        ?>
                        <form method="post">
                            <input type="hidden" name="bill_id" value="<?php echo htmlspecialchars($bill->getId()); ?>">
                            <select name="action" class="action-select" <?php echo !$isActionEnabled ? 'disabled' : ''; ?>>
                                <option value="approve">Approve</option>
                                <option value="reject">Reject</option>
                            </select>
                            <button type="submit" class="update-status-btn" <?php echo !$isActionEnabled ? 'disabled' : ''; ?>>
                                Update Status
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="edit_bill.php?bill_id=<?php echo htmlspecialchars($bill->getId()); ?>">Edit</a>
                    </td>
                    <td>
                        <?php
                        $amendments = $amendmentController->getByBillId($bill->getId());
                        if (!empty($amendments)): ?>
                            <?php foreach ($amendments as $amendment): ?>
                                <div class="amendment-item">
                                    <strong>Amendment by <?php echo htmlspecialchars($amendment->getReviewer()); ?></strong>
                                    <p><?php echo htmlspecialchars($amendment->getAmendmentText()); ?></p>
                                    <em>Comments: <?php echo htmlspecialchars($amendment->getComments()); ?></em>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No amendments yet.</p>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($_SESSION['user']['role'] === 'Admin'): ?>
                            <?php if ($bill->getStatus() === 'Review Complete'): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="bill_id" value="<?php echo htmlspecialchars($bill->getId()); ?>">
                                    <button type="submit" name="start_voting" class="start-voting-btn" 
                                            onclick="return confirm('Are you sure you want to start voting for this bill?');">
                                        Start Voting
                                    </button>
                                </form>
                            <?php elseif ($bill->getStatus() === 'Voting Started'): ?>
                                <p>Voting in progress</p>
                                <p>Total votes: <?php echo $totalVotes; ?></p>
                            <?php else : ?>
                                <p>Not Started Yet</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($bill->getStatus() === 'Voting Started'): ?>
                            <div class="voting-results">
                                <h4>Current Results:</h4>
                                <p>For: <?php echo $voteCounts['For']; ?></p>
                                <p>Against: <?php echo $voteCounts['Against']; ?></p>
                                <p>Abstain: <?php echo $voteCounts['Abstain']; ?></p>
                                <p>Total Votes: <?php echo $totalVotes; ?></p>

                                <?php if ($totalVotes > 0):
                                    $validVotes = $voteCounts['For'] + $voteCounts['Against'];
                                    if ($validVotes > 0): 
                                        $forPercentage = ($voteCounts['For'] / $validVotes) * 100;
                                        $againstPercentage = ($voteCounts['Against'] / $validVotes) * 100;
                                        ?>
                                        <p>For: <?php echo number_format($forPercentage, 1); ?>%</p>
                                        <p>Against: <?php echo number_format($againstPercentage, 1); ?>%</p>
                                    <?php else: ?>
                                        <p>Only abstain votes recorded</p>
                                    <?php endif; ?>

                                    <?php if ($voteCounts['For'] === 0 && $voteCounts['Against'] === 0 && $voteCounts['Abstain'] > 0): ?>
                                        <p>Decision is neutral</p>
                                    <?php else: ?>
                                        <form method="post">
                                            <input type="hidden" name="bill_id" value="<?php echo htmlspecialchars($bill->getId()); ?>">
                                            <button type="submit" name="finalize_voting" class="update-status-btn">
                                                Finalize Voting
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php elseif (in_array($bill->getStatus(), ['Passed', 'Rejected'])): ?>
                            <div class="final-results">
                                <h4>Final Results:</h4>
                                <p>Status: <strong><?php echo htmlspecialchars($bill->getStatus()); ?></strong></p>
                                <p>For: <?php echo $voteCounts['For']; ?></p>
                                <p>Against: <?php echo $voteCounts['Against']; ?></p>
                                <p>Abstain: <?php echo $voteCounts['Abstain']; ?></p>
                                <p>Total Votes: <?php echo $totalVotes; ?></p>
                                <?php
                                $validVotes = $voteCounts['For'] + $voteCounts['Against'];
                                if ($validVotes > 0):
                                    $forPercentage = ($voteCounts['For'] / $validVotes) * 100;
                                    ?>
                                    <p>Approval Rate: <?php echo number_format($forPercentage, 1); ?>%</p>
                                <?php else: ?>
                                    <p>Only abstain votes recorded</p>
                                <?php endif; ?>
                            </div>
                            <?php else : ?>
                                <p>Not Started Yet</p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>