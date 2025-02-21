<?php
session_start();

// Ensure user is logged in as Reviewer
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Reviewer') {
    header("Location: login.php");
    exit();
}

require_once "../repositories/AmendmentRepository.php"; // Amendment repository to save amendments
require_once "../repositories/BillRepository.php"; // To fetch the specific bill

$billId = $_GET['bill_id'] ?? null;
if (!$billId) {
    die("No Bill ID provided.");
}

$billRepository = new BillRepository();
$bill = $billRepository->getBillById($billId); // Fetch the specific bill

if (!$bill) {
    die("Bill not found.");
}

// Process form submission to add amendment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amendmentText = trim($_POST['amendment'] ?? '');
    $comments = trim($_POST['comments'] ?? '');
    
    if (!empty($amendmentText) && !empty($comments)) {
        try {
            // Create a new AmendmentRepository instance and save the amendment
            $amendmentRepository = new AmendmentRepository();
            $amendmentRepository->addAmendment([
                'bill_id' => $billId,
                'reviewer' => $_SESSION['user']['username'],
                'amendment_text' => $amendmentText,
                'comments' => $comments,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Redirect to the dashboard after successfully submitting the amendment
            header("Location: dashboard_reviewer.php");
            exit();
        } catch (Exception $e) {
            $error = "Error saving amendment: " . $e->getMessage();
        }
    } else {
        $error = "Please fill out all fields.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggest Amendment</title>
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

        label {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-top: 8px;
            resize: vertical;
            font-size: 16px;
            color: #333;
        }

        textarea:focus {
            border-color: #4CAF50;
            outline: none;
        }

        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #45a049;
        }

        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-container p {
            color: red;
            font-size: 14px;
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            h2 {
                font-size: 20px;
            }

            button {
                padding: 8px 16px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            h2 {
                font-size: 18px;
            }

            textarea {
                font-size: 14px;
            }

            button {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Suggest Amendment for Bill: <?php echo htmlspecialchars($bill['title']); ?></h2>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!$amendmentText || !$comments)): ?>
            <p>Please fill out all fields.</p>
        <?php endif; ?>

        <form method="POST">
            <label for="amendment">Amendment Text:</label><br>
            <textarea name="amendment" rows="5" required></textarea><br><br>

            <label for="comments">Comments:</label><br>
            <textarea name="comments" rows="5" required></textarea><br><br>

            <button type="submit">Submit Amendment</button>
        </form>
    </div>

</body>
</html>
