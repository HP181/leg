<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config.php";
require_once "../repositories/BillRepository.php";
require_once "../repositories/AmendmentRepository.php";
require_once "../repositories/VoteRepository.php";

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$billRepository = new BillRepository();
$amendmentRepository = new AmendmentRepository();
$voteRepository = new VoteRepository();

// Initialize view type and filters
$viewType = $_GET['view_type'] ?? 'bills';
$exportType = $_POST['type'] ?? 'bills';
$filterStatus = $_GET['status'] ?? '';
$voteType = $_GET['vote_type'] ?? '';
$billFilter = $_GET['bill_filter'] ?? '';

// Get all bills for filters
$bills = $billRepository->getAllBills();
$statuses = ['Draft', 'Under Review', 'Review Complete', 'Voting Started', 'Passed', 'Rejected'];
$voteTypes = ['For', 'Against', 'Abstain'];

// Handle export requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    $type = $_POST['type'];
    $format = $_POST['format'];
    $exportStatus = $_POST['status'] ?? '';
    $exportVoteType = $_POST['vote_type'] ?? '';
    $exportBillFilter = $_POST['bill_filter'] ?? '';

    // Get data based on type and filters
    $data = [];
    switch ($type) {
        case 'bills':
            $data = $billRepository->getAllBills();
            if ($exportStatus) {
                $data = array_filter($data, fn($bill) => $bill['status'] === $exportStatus);
            }
            break;

        case 'votes':
            $votes = $voteRepository->getAllVotes();
            if ($exportVoteType) {
                $filteredVotes = [];
                foreach ($votes as $billId => $billVotes) {
                    foreach ($billVotes as $user => $vote) {
                        if ($vote === $exportVoteType) {
                            if (!isset($filteredVotes[$billId])) {
                                $filteredVotes[$billId] = [];
                            }
                            $filteredVotes[$billId][$user] = $vote;
                        }
                    }
                }
                $data = $filteredVotes;
            } else {
                $data = $votes;
            }
            
            // Add bill information to votes
            $exportData = [];
            foreach ($data as $billId => $billVotes) {
                $bill = $billRepository->getBillById($billId);
                foreach ($billVotes as $user => $vote) {
                    $exportData[] = [
                        'bill_id' => $billId,
                        'bill_title' => $bill ? $bill['title'] : 'Unknown Bill',
                        'user' => $user,
                        'vote' => $vote
                    ];
                }
            }
            $data = $exportData;
            break;

        case 'amendments':
            $amendments = $amendmentRepository->getAllAmendments();
            if ($exportBillFilter) {
                $amendments = array_filter($amendments, fn($amendment) => 
                    $amendment['bill_id'] === $exportBillFilter
                );
            }
            
            // Add bill titles to amendments
            $exportData = [];
            foreach ($amendments as $amendment) {
                $bill = $billRepository->getBillById($amendment['bill_id']);
                $exportData[] = array_merge($amendment, [
                    'bill_title' => $bill ? $bill['title'] : 'Unknown Bill'
                ]);
            }
            $data = $exportData;
            break;
    }

    // Export based on format
    $filename = $type . '_export_' . date('Y-m-d_His');
    switch ($format) {
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            echo json_encode($data, JSON_PRETTY_PRINT);
            exit;
            
        case 'xml':
            header('Content-Type: application/xml');
            header('Content-Disposition: attachment; filename="' . $filename . '.xml"');
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data/>');
            array_to_xml($data, $xml);
            echo $xml->asXML();
            exit;
    }
}

// Helper function to convert array to XML
function array_to_xml($array, &$xml) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (!is_numeric($key)) {
                $subnode = $xml->addChild("$key");
                array_to_xml($value, $subnode);
            } else {
                array_to_xml($value, $xml);
            }
        } else {
            $xml->addChild("$key", htmlspecialchars("$value"));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports & Exports</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .top {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .top>a{
            padding: 5px 10px;
            background-color: red;
            color: white;
            border-radius: 10px;
        }

        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .export-section {
            margin-bottom: 20px;
        }

        select, input {
            padding: 8px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f5f5f5;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">

    <div class="top">
        <h2>Reports & Data Export</h2>

        <a href="logout.php">Logout</a>
    </div>
        

        <!-- Export Form -->
        <div class="export-section">
            <h3>Export Data</h3>
            <form method="post" id="exportForm">
                <select name="type" id="exportType" onchange="updateExportFilters()">
                    <option value="bills" <?php echo $exportType === 'bills' ? 'selected' : ''; ?>>Bills</option>
                    <option value="votes" <?php echo $exportType === 'votes' ? 'selected' : ''; ?>>Voting Records</option>
                    <option value="amendments" <?php echo $exportType === 'amendments' ? 'selected' : ''; ?>>Amendments</option>
                </select>

                <select name="format" required>
                    <option value="json">JSON</option>
                    <option value="xml">XML</option>
                </select>

                <!-- Dynamic filters for export -->
                <span id="billExportFilters" class="dynamic-filter">
                    <select name="status">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>

                <span id="voteExportFilters" class="dynamic-filter">
                    <select name="vote_type">
                        <option value="">All Votes</option>
                        <?php foreach ($voteTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>">
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>

                <span id="amendmentExportFilters" class="dynamic-filter">
                    <select name="bill_filter">
                        <option value="">All Bills</option>
                        <?php foreach ($bills as $billId => $bill): ?>
                            <option value="<?php echo htmlspecialchars($billId); ?>">
                                <?php echo htmlspecialchars($bill['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>

                <button type="submit" name="export">Export</button>
            </form>
        </div>

        <!-- Filter Form -->
        <div class="filter-section">
            <h3>Filter Data</h3>
            <form method="get" id="filterForm">
                <select name="view_type" id="viewType" onchange="updateFilters()">
                    <option value="bills" <?php echo $viewType === 'bills' ? 'selected' : ''; ?>>Bills</option>
                    <option value="votes" <?php echo $viewType === 'votes' ? 'selected' : ''; ?>>Voting Records</option>
                    <option value="amendments" <?php echo $viewType === 'amendments' ? 'selected' : ''; ?>>Amendments</option>
                </select>

                <!-- Dynamic filters for view -->
                <span id="billFilters" class="dynamic-filter">
                    <select name="status">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" 
                                    <?php echo ($filterStatus === $status) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>

                <span id="voteFilters" class="dynamic-filter">
                    <select name="vote_type">
                        <option value="">All Votes</option>
                        <?php foreach ($voteTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>"
                                    <?php echo ($voteType === $type) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>

                <span id="amendmentFilters" class="dynamic-filter">
                    <select name="bill_filter">
                        <option value="">All Bills</option>
                        <?php foreach ($bills as $billId => $bill): ?>
                            <option value="<?php echo htmlspecialchars($billId); ?>"
                                    <?php echo ($billFilter === $billId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bill['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>

                <button type="submit">Apply Filters</button>
            </form>
        </div>

        <!-- Data Display Section -->
        <?php include 'data_display.php'; // Put the data display code in a separate file ?>
    </div>

    <script>
        function updateFilters() {
            const viewType = document.getElementById('viewType').value;
            document.querySelectorAll('#filterForm .dynamic-filter').forEach(filter => {
                filter.style.display = 'none';
            });
            
            switch(viewType) {
                case 'bills':
                    document.getElementById('billFilters').style.display = 'inline-block';
                    break;
                case 'votes':
                    document.getElementById('voteFilters').style.display = 'inline-block';
                    break;
                case 'amendments':
                    document.getElementById('amendmentFilters').style.display = 'inline-block';
                    break;
            }
        }

        function updateExportFilters() {
            const exportType = document.getElementById('exportType').value;
            document.querySelectorAll('#exportForm .dynamic-filter').forEach(filter => {
                filter.style.display = 'none';
            });
            
            switch(exportType) {
                case 'bills':
                    document.getElementById('billExportFilters').style.display = 'inline-block';
                    break;
                case 'votes':
                    document.getElementById('voteExportFilters').style.display = 'inline-block';
                    break;
                case 'amendments':
                    document.getElementById('amendmentExportFilters').style.display = 'inline-block';
                    break;
            }
        }

        // Initialize filters on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateFilters();
            updateExportFilters();
        });
    </script>
</body>
</html>