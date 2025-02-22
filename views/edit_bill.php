<?php
session_start();
require_once "../controllers/BillController.php";

// Ensure user is logged in and has either the Admin or MP role
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Admin', 'MP'])) {
    header("Location: login.php");
    exit();
}

$billController = new BillController();

try {
    // Get bill ID and fetch bill
    $billId = $_GET['bill_id'] ?? '';
    if (empty($billId)) {
        throw new Exception("Bill ID is required");
    }

    $bill = $billController->getBillById($billId);
    if (!$bill) {
        throw new Exception("Bill not found");
    }

    // Check authorization
    if ($_SESSION['user']['role'] === 'MP' && $bill->getAuthor() !== $_SESSION['user']['username']) {
        throw new Exception("Unauthorized access");
    }

    // Handle form submission for editing
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Update bill object with new data
            $bill->setTitle($_POST['title']);
            $bill->setDescription($_POST['description']);
            $bill->setDraft($_POST['draft']);

            // Validate the updates
            $errors = $bill->validate();
            if (!empty($errors)) {
                throw new Exception(implode(", ", $errors));
            }

            // Update the bill
            $billController->updateBill($bill);

            // Redirect to the respective dashboard
            $redirectPath = $_SESSION['user']['role'] === 'MP' ? 'dashboard_mp.php' : 'dashboard_admin.php';
            header("Location: $redirectPath");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error updating bill: " . $e->getMessage();
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: " . ($_SESSION['user']['role'] === 'MP' ? 'dashboard_mp.php' : 'dashboard_admin.php'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bill</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        
        .container {
            width: 100%;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            font-size: 16px;
            margin: 10px 0 5px;
            display: block;
            color: #555;
        }

        input, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        textarea {
            height: 150px;
            resize: vertical;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        button:hover {
            background-color: #45a049;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            label {
                font-size: 14px;
            }

            input, textarea, button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Bill</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label for="title">Title:</label>
            <input type="text" 
                   name="title" 
                   value="<?php echo htmlspecialchars($bill->getTitle()); ?>" 
                   required>

            <label for="description">Description:</label>
            <textarea name="description" required><?php echo htmlspecialchars($bill->getDescription()); ?></textarea>

            <label for="draft">Draft:</label>
            <textarea name="draft" required><?php echo htmlspecialchars($bill->getDraft()); ?></textarea>

            <button type="submit">Update Bill</button>
        </form>
    </div>
</body>
</html>
