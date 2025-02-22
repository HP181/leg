<?php
session_start();
require_once "../controllers/BillController.php";
require_once "../controllers/AmendmentController.php";
require_once "../controllers/VoteController.php";

// Ensure that the user is logged in and has the correct role (MP)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'MP') {
    header("Location: login.php");
    exit();
}

// Initialize controllers
$billController = new BillController();
$amendmentController = new AmendmentController();
$voteController = new VoteController();
$user = $_SESSION['user']['username'];

// Handle form submission for creating a bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_bill'])) {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $author = $_SESSION['user']['username'];
        $draft = trim($_POST['draft']);

        $bill = new Bill(
            uniqid('bill_', true),
            $title,
            $description,
            $author,
            $draft,
            'Draft',
            date('Y-m-d H:i:s')
        );

        $errors = $bill->validate();
        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors));
        }

        // $billController->createBill();
        $billController->createBill($bill);
        header("Location: dashboard_mp.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error creating bill: " . $e->getMessage();
    }
}

// Handle Vote Casting by MP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cast_vote'])) {
    try {
        $billId = $_POST['bill_id'] ?? '';
        $voteType = $_POST['vote'] ?? '';
        $mpUsername = $_SESSION['user']['username'];

        if (empty($billId) || empty($voteType)) {
            throw new Exception("Missing required voting information");
        }

        $vote = new Vote(
            $billId,
            $mpUsername,
            $voteType,
            date('Y-m-d H:i:s')
        );

        // $voteController->recordVote($vote);
        $voteController->recordVote();
        header("Location: dashboard_mp.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error recording vote: " . $e->getMessage();
    }
}

// Handle Submit for Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_for_review'])) {
    try {
        $billId = $_POST['bill_id'];
        $bill = $billController->getBillById($billId);
        
        if (!$bill) {
            throw new Exception("Bill not found");
        }

        $bill->setStatus('Under Review');
        $billController->updateBill($bill);
        
        header("Location: dashboard_mp.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error submitting for review: " . $e->getMessage();
    }
}

// Load all bills created by the MP
try {
    $bills = $billController->getBillsByAuthor($user);
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading bills: " . $e->getMessage();
    $bills = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MP Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fa;
        }
        header {
            background-color: #2c5282;
            color: white;
            padding: 15px;
            text-align: center;
        }
        h2 {
            color: #2c5282;
        }
        p {
            font-size: 1.1rem;
            margin: 5px 0;
        }
        a {
            color: white;
            text-decoration: underline;
        }
        a:hover {
            text-decoration: underline;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
       
        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        input[type="text"], textarea, select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            font-size: 1rem;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
        }
        /* .actions{
            display: flex;
            justify-content: center;
            align-items: center;
        } */
        /* tr{
            display: flex;
            justify-content: space-between;
            align-items: center;
        } */
        .voting-section {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            margin-bottom: 10px;
        }
        .current-results, .final-results {
            margin-top: 15px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
        }
        .final-results h4 {
            margin-top: 0;
            color: #333;
        }
        .final-results strong {
            color: #2c5282;
        }
        .review-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .review-button:hover {
            background-color: #45a049;
        }
        .status-message {
            color: #666;
            font-style: italic;
            margin: 5px 0;
        }
        .bill-actions {
            /* display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            gap: 10px;
            border: 0px;
            border-bottom: 1px solid #ddd; */
        }
        .bill-actions a {
            background-color: #2c5282;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
        }
        .bill-actions a:hover {
            background-color: #45a049;
        }
        @media (max-width: 768px) {
           
            table {
                width: 100%;
                font-size: 0.9rem;
            }
            form {
                gap: 10px;
            }

            .table{
            overflow: scroll;
        }
        }
    </style>
</head>
<body>
    <header>
        <h2>MP Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($user); ?>!</p>
        <p>Your role: <?php echo htmlspecialchars($_SESSION['user']['role']); ?></p>
        <a href="logout.php">Logout</a>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <h2>Create New Bill</h2>
        <form method="post">
            <label for="title">Bill Title:</label>
            <input type="text" name="title" id="title" required>
            
            <label for="description">Bill Description:</label>
            <textarea name="description" id="description" required></textarea>
            
            <label for="author">Bill Author:</label>
            <input type="text" name="author" id="author" value="<?php echo htmlspecialchars($_SESSION['user']['username']); ?>" readonly>
            
            <label for="draft">Initial Draft:</label>
            <textarea name="draft" id="draft" required></textarea>
            
            <button type="submit" name="create_bill">Create Bill</button>
        </form>

        <h2>Your Bills</h2>

        <?php if (!empty($bills)): ?>
            <div class="table">
                <table>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="actions">Actions</th>
                        <th>Amendments</th>
                        <th>Voting Status & Results</th>
                        <th>Submit for Review</th>
                    </tr>
                    <?php foreach ($bills as $bill): ?>  
                        <tr>
                            <td><?php echo htmlspecialchars($bill->getTitle()); ?></td>
                            <td><?php echo htmlspecialchars($bill->getDescription()); ?></td>
                            <td><?php echo htmlspecialchars($bill->getStatus()); ?></td>
                            <td class="bill-actions">
                                <a href="edit_bill.php?bill_id=<?php echo htmlspecialchars($bill->getId()); ?>">Edit</a>
                            </td>
                            <td>
                                <?php
                                $amendments = $amendmentController->getByBillId($bill->getId());
                                if (!empty($amendments)): ?>
                                    <ul>
                                        <?php foreach ($amendments as $amendment): ?>
                                            <li>
                                                <strong>Amendment by <?php echo htmlspecialchars($amendment->getReviewer()); ?>:</strong>
                                                <p><?php echo htmlspecialchars($amendment->getAmendmentText()); ?></p>
                                                <em>Comments: <?php echo htmlspecialchars($amendment->getComments()); ?></em>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>No amendments yet.</p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $voteCounts = $voteController->getVoteCounts($bill->getId());
                                $totalVotes = $voteCounts['For'] + $voteCounts['Against'] + $voteCounts['Abstain'];
                                $userVote = $voteController->getUserVote($bill->getId());

                                if ($bill->getStatus() === 'Voting Started'): ?>
                                    <div class="voting-section">
                                        <form method="post">
                                            <input type="hidden" name="bill_id" value="<?php echo htmlspecialchars($bill->getId()); ?>">
                                            <label for="vote_<?php echo $bill->getId(); ?>">Cast Your Vote:</label>
                                            <select name="vote" id="vote_<?php echo $bill->getId(); ?>" required>
                                                <option value="For" <?php echo $userVote && $userVote->getVote() === 'For' ? 'selected' : ''; ?>>For</option>
                                                <option value="Against" <?php echo $userVote && $userVote->getVote() === 'Against' ? 'selected' : ''; ?>>Against</option>
                                                <option value="Abstain" <?php echo $userVote && $userVote->getVote() === 'Abstain' ? 'selected' : ''; ?>>Abstain</option>
                                            </select>
                                            <button type="submit" name="cast_vote">
                                                <?php echo $userVote ? 'Update Vote' : 'Submit Vote'; ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif (in_array($bill->getStatus(), ['Passed', 'Rejected'])): ?>
                                    <div class="final-results">
                                        <h4>Final Results:</h4>
                                        <p><strong>Status: <?php echo htmlspecialchars($bill->getStatus()); ?></strong></p>
                                        <p>Votes For: <?php echo $voteCounts['For']; ?></p>
                                        <p>Votes Against: <?php echo $voteCounts['Against']; ?></p>
                                        <p>Abstentions: <?php echo $voteCounts['Abstain']; ?></p>
                                        <p>Total Votes: <?php echo $totalVotes; ?></p>
                                    </div>
                                <?php else: ?>
                                    <p>Voting has not started yet.</p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bill->getStatus() === 'Draft'): ?>
                                    <form method="post">
                                        <input type="hidden" name="bill_id" value="<?php echo htmlspecialchars($bill->getId()); ?>">
                                        <button type="submit" name="submit_for_review" class="review-button">Submit for Review</button>
                                    </form>
                                <?php else: ?>
                                    <p class="status-message">
                                        <?php
                                        switch($bill->getStatus()) {
                                            case 'Under Review':
                                                echo 'Currently under review';
                                                break;
                                            case 'Review Complete':
                                                echo 'Review completed';
                                                break;
                                            case 'Voting Started':
                                                echo 'Voting in progress';
                                                break;
                                            case 'Passed':
                                            case 'Rejected':
                                                echo 'Final status: ' . $bill->getStatus();
                                                break;
                                        }
                                        ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php else: ?>
            <p>You have not created any bills yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
