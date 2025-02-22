<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config.php";
require_once "../controllers/BillController.php";
require_once "../controllers/AmendmentController.php";

// Ensure user is logged in as Reviewer
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Reviewer') {
    header("Location: login.php");
    exit();
}

// Initialize controllers
$billController = new BillController();
$amendmentController = new AmendmentController();

// Get bills that are Under Review
try {
    $bills = $billController->getBillsByStatus('Under Review');
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching bills: " . $e->getMessage();
    $bills = [];
}

// Handle Complete Review action
// Handle Complete Review action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_review'])) {
    try {
        $billId = $_POST['bill_id'] ?? '';
        if (empty($billId)) {
            throw new Exception("Bill ID is required");
        }

        $billController->completeReview($billId);
        
        header("Location: dashboard_reviewer.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Dashboard</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f9;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            color: #555;
        }

        a {
            color: #3498db;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 16px;
            text-align: left;
            border: 1px solid #ddd;
            background-color: #ffffff;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        td {
            color: #555;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .status-under-review {
            background-color: #FFC107;
            color: black;
        }

        .action-button {
            display: inline-block;
            padding: 10px 20px;
            margin: 4px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .suggest-amendment {
            background-color: #2196F3;
        }

        .complete-review {
            background-color: #4CAF50;
        }

        .action-button:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .status-under-review {
            background-color: #FFC107;
            color: black;
        }

        .no-bills {
            text-align: center;
            color: #777;
            font-style: italic;
        }

        /* Amendment list styling */
        ul.amendments-list {
            margin: 0;
            padding-left: 20px;
        }

        ul.amendments-list li {
            margin-bottom: 15px; /* Added vertical spacing between amendments */
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            table {
                font-size: 14px;
            }

            th, td {
                padding: 8px;
            }

            .action-button {
                padding: 6px 12px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 6px;
            }

            .action-button {
                font-size: 12px;
                padding: 5px 10px;
            }
        }
    </style>
</head>
<body>
    <h2>Reviewer Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</p>
    <p>Your role: <?php echo htmlspecialchars($_SESSION['user']['role']); ?></p>
    <a href="logout.php">Logout</a>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <?php 
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <h3>Bills Under Review</h3>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Author</th>
                <th>Status</th>
                <th>Current Amendments</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bills)): ?>
                <tr>
                    <td colspan="6" class="no-bills">No bills currently under review.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($bills as $bill): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bill->getTitle()); ?></td>
                        <td><?php echo htmlspecialchars($bill->getDescription()); ?></td>
                        <td><?php echo htmlspecialchars($bill->getAuthor()); ?></td>
                        <td>
                            <span class="status-badge status-under-review">
                                <?php echo htmlspecialchars($bill->getStatus()); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            try {
                                $amendments = $amendmentController->getByBillId($bill->getId());
                                if (!empty($amendments)): ?>
                                    <ul class="amendments-list">
                                        <?php foreach ($amendments as $amendment): ?>
                                            <li>
                                                <strong>Amendment by <?php echo htmlspecialchars($amendment->getReviewer()); ?></strong>
                                                <p><?php echo htmlspecialchars($amendment->getAmendmentText()); ?></p>
                                                <em>Comments: <?php echo htmlspecialchars($amendment->getComments()); ?></em>
                                                <small>Created: <?php echo date('Y-m-d H:i', strtotime($amendment->getCreatedAt())); ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>No amendments yet</p>
                                <?php endif;
                            } catch (Exception $e) {
                                error_log("Error displaying amendments: " . $e->getMessage());
                                echo "<p>Error loading amendments</p>";
                            }
                            ?>
                        </td>
                        <td>
                            <a href="suggest_amendment.php?bill_id=<?php echo htmlspecialchars($bill->getId()); ?>" 
                               class="action-button suggest-amendment">Suggest Amendment</a>

                            <form method="post" style="display: inline;">
                                <input type="hidden" name="bill_id" value="<?php echo htmlspecialchars($bill->getId()); ?>">
                                <button type="submit" name="complete_review" 
                                        class="action-button complete-review"
                                        onclick="return confirm('Are you sure you want to complete the review for this bill?');">
                                    Complete Review
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>