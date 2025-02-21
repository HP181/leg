<?php
// Data Display Section
$viewType = $_GET['view_type'] ?? 'bills';
$filterStatus = $_GET['status'] ?? '';
$voteType = $_GET['vote_type'] ?? '';
$billFilter = $_GET['bill_filter'] ?? '';

switch ($viewType):
    case 'bills':
        $bills = $billRepository->getAllBills();
        if ($filterStatus) {
            $bills = array_filter($bills, fn($bill) => $bill['status'] === $filterStatus);
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
                        <tr>
                            <td><?php echo htmlspecialchars($bill['title']); ?></td>
                            <td><?php echo htmlspecialchars($bill['description']); ?></td>
                            <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($bill['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($bill['author']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        break;

    case 'votes':
        $votes = $voteRepository->getAllVotes();
        // Filter votes by type if selected
        if ($voteType) {
            $filteredVotes = [];
            foreach ($votes as $billId => $billVotes) {
                foreach ($billVotes as $user => $vote) {
                    if ($vote === $voteType) {
                        if (!isset($filteredVotes[$billId])) {
                            $filteredVotes[$billId] = [];
                        }
                        $filteredVotes[$billId][$user] = $vote;
                    }
                }
            }
            $votes = $filteredVotes;
        }
        ?>
        <table>
            <thead>
                <tr>
                    <th>Bill Title</th>
                    <th>User</th>
                    <th>Vote</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($votes)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">No votes found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($votes as $billId => $billVotes): ?>
                        <?php 
                        $bill = $billRepository->getBillById($billId);
                        $billTitle = $bill ? $bill['title'] : 'Unknown Bill';
                        ?>
                        <?php foreach ($billVotes as $user => $vote): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($billTitle); ?></td>
                                <td><?php echo htmlspecialchars($user); ?></td>
                                <td>
                                    <span class="vote-badge vote-<?php echo strtolower($vote); ?>">
                                        <?php echo htmlspecialchars($vote); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        break;

    case 'amendments':
        $amendments = $amendmentRepository->getAllAmendments();
        // Filter amendments by bill if selected
        if ($billFilter) {
            $amendments = array_filter($amendments, fn($amendment) => $amendment['bill_id'] === $billFilter);
        }
        ?>
        <table>
            <thead>
                <tr>
                    <th>Bill Title</th>
                    <th>Reviewer</th>
                    <th>Amendment Text</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($amendments)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No amendments found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($amendments as $amendment): ?>
                        <?php 
                        $bill = $billRepository->getBillById($amendment['bill_id']);
                        $billTitle = $bill ? $bill['title'] : 'Unknown Bill';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($billTitle); ?></td>
                            <td><?php echo htmlspecialchars($amendment['reviewer']); ?></td>
                            <td><?php echo htmlspecialchars($amendment['amendment_text']); ?></td>
                            <td><?php echo htmlspecialchars($amendment['comments']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        break;
endswitch;
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