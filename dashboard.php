
<?php
session_start();

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once "backend/config.php";

$user_id = $_SESSION['user_id'];

// CSRF Security Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get Income and Expense Summary
$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense
    FROM transactions
    WHERE user_id = ?
");

$stmt->execute([$user_id]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

$total_income = (float)$summary['income'];
$total_expense = (float)$summary['expense'];
$balance = $total_income - $total_expense;

// Get Latest 10 Transactions
$stmt = $conn->prepare("
    SELECT *
    FROM transactions
    WHERE user_id = ?
    ORDER BY transaction_date DESC, id DESC
    LIMIT 10
");

$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Category-wise Expense Data
$stmt = $conn->prepare("
    SELECT
        category,
        SUM(amount) AS total
    FROM transactions
    WHERE user_id = ?
      AND type = 'expense'
    GROUP BY category
    ORDER BY total DESC
");

$stmt->execute([$user_id]);
$category_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chart_categories = [];
$chart_amounts = [];

foreach ($category_data as $item) {
    $chart_categories[] = $item['category'];
    $chart_amounts[] = (float)$item['total'];
}

// Money Format
function money($amount) {
    return '₹' . number_format((float)$amount, 2);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Smart Expense Tracker</title>

    <link rel="stylesheet" href="css/style.css">

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="dashboard-layout">

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="brand">
            💰 SmartTrack
        </div>

        <nav>
            <a href="dashboard.php" class="active">▦ Dashboard</a>
            <a href="#transactions">⇄ Transactions</a>
            <a href="#summary">📊 Summary</a>
            <a href="#charts">📈 Analytics</a>
        </nav>

        <a href="backend/logout.php" class="logout">
            ↪ Logout
        </a>

    </aside>


    <!-- Main Content -->
    <main class="main-content">

        <!-- Topbar -->
        <header class="topbar">

            <div>
                <h2>Dashboard</h2>
                <p>Manage your money smartly.</p>
            </div>

            <div class="user-profile">
                <div class="avatar">👤</div>

                <span>
                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                </span>
            </div>

        </header>


        <!-- Welcome Section -->
        <section class="welcome-section">

            <h1>
                Hello, <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>! 👋
            </h1>

            <p>Welcome to your personal expense tracker.</p>

        </section>


        <!-- Summary Cards -->
        <section class="stats-grid" id="summary">

            <div class="stat-card balance-card">
                <p>Total Balance</p>
                <h2><?= money($balance) ?></h2>
                <span>Available Balance</span>
            </div>

            <div class="stat-card">
                <p>Total Income</p>
                <h2 class="income-text">
                    <?= money($total_income) ?>
                </h2>
                <span>Money Received</span>
            </div>

            <div class="stat-card">
                <p>Total Expenses</p>
                <h2 class="expense-text">
                    <?= money($total_expense) ?>
                </h2>
                <span>Money Spent</span>
            </div>

        </section>


        <!-- Quick Actions -->
        <section class="quick-actions">

            <h2>Quick Actions</h2>

            <div class="action-buttons">

                <button onclick="location.href='add-transaction.php?type=income'">
                    + Add Income
                </button>

                <button onclick="location.href='add-transaction.php?type=expense'">
                    + Add Expense
                </button>

            </div>

        </section>


        <!-- Financial Analytics -->
        <section class="charts-section" id="charts">

            <div class="section-heading">
                <h2>📊 Financial Analytics</h2>
                <span>Visual overview of your finances</span>
            </div>

            <div class="charts-grid">

                <!-- Income vs Expense -->
                <div class="chart-card">

                    <h3>Income vs Expense</h3>

                    <div class="chart-container">
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>

                </div>


                <!-- Category-wise Expense -->
                <div class="chart-card">

                    <h3>Expense by Category</h3>

                    <div class="chart-container">
                        <canvas id="categoryExpenseChart"></canvas>
                    </div>

                </div>

            </div>

        </section>


        <!-- Recent Transactions -->
        <section class="transactions-section" id="transactions">

            <div class="section-heading">
                <h2>Recent Transactions</h2>
                <span>Latest 10 records</span>
            </div>


            <!-- Status Messages -->
            <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>

                <p class="income-text">
                    Transaction deleted successfully!
                </p>

            <?php elseif (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>

                <p class="income-text">
                    Transaction updated successfully!
                </p>

            <?php endif; ?>


            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>


                    <tbody>

                    <?php if (count($transactions) > 0): ?>

                        <?php foreach ($transactions as $row): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($row['category']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['description'] ?? '') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['transaction_date']) ?>
                                </td>

                                <td>
                                    <span class="<?= $row['type'] === 'income' ? 'income-text' : 'expense-text' ?>">
                                        <?= ucfirst($row['type']) ?>
                                    </span>
                                </td>

                                <td class="<?= $row['type'] === 'income' ? 'income-text' : 'expense-text' ?>">

                                    <?= $row['type'] === 'income' ? '+' : '-' ?>

                                    <?= money($row['amount']) ?>

                                </td>

                                <td>

                                    <!-- Edit Button -->
                                    <a href="edit-transaction.php?id=<?= (int)$row['id'] ?>">
                                        ✏️ Edit
                                    </a>


                                    <!-- Delete Button -->
                                    <form
                                        action="backend/delete_transaction.php"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this transaction?');"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$row['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                        >

                                        <button type="submit">
                                            🗑️ Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-state">
                                No transactions yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- Footer -->
        <footer class="dashboard-footer">
            © 2026 Smart Expense Tracker. All rights reserved.
        </footer>

    </main>

</div>


<!-- Chart Data and JavaScript -->
<script>

document.addEventListener("DOMContentLoaded", function () {

    // PHP Data
    const chartCategories = <?= json_encode($chart_categories) ?>;
    const chartAmounts = <?= json_encode($chart_amounts) ?>;

    const incomeAmount = <?= json_encode($total_income) ?>;
    const expenseAmount = <?= json_encode($total_expense) ?>;


    // Check Chart.js
    if (typeof Chart === "undefined") {
        console.error("Chart.js library could not be loaded.");
        return;
    }


    // Income vs Expense Bar Chart
    const incomeCanvas = document.getElementById("incomeExpenseChart");

    if (incomeCanvas) {

        new Chart(incomeCanvas, {

            type: "bar",

            data: {
                labels: ["Income", "Expense"],

                datasets: [{
                    label: "Amount (₹)",

                    data: [incomeAmount, expenseAmount],

                    backgroundColor: [
                        "#16a34a",
                        "#ef4444"
                    ],

                    borderRadius: 8,
                    barThickness: 55
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        display: false
                    }
                },

                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }

        });

    }


    // Category-wise Expense Pie Chart
    const expenseCanvas = document.getElementById("categoryExpenseChart");

    if (expenseCanvas) {

        new Chart(expenseCanvas, {

            type: "pie",

            data: {
                labels: chartCategories,

                datasets: [{
                    data: chartAmounts,

                    backgroundColor: [
                        "#6366f1",
                        "#f59e0b",
                        "#10b981",
                        "#ef4444",
                        "#8b5cf6",
                        "#06b6d4",
                        "#ec4899",
                        "#14b8a6",
                        "#f97316"
                    ],

                    borderWidth: 2,
                    borderColor: "#ffffff"
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        position: "bottom"
                    }
                }
            }

        });

    }

});

</script>

</body>
</html>
