<?php
// Database connection
$host = 'localhost';
$dbname = 'rathu';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Add this code RIGHT AFTER the database connection in index.php
// This will recalculate and fix the balance values

try {
    // Calculate actual total income
    $stmt = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM income");
    $income_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate actual total outcome
    $stmt = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM outcome");
    $outcome_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate current balance
    $current_balance = $income_total - $outcome_total;
    
    // Update balance table with correct values
    $stmt = $conn->prepare("UPDATE balance SET total_income = ?, total_outcome = ?, current_balance = ? WHERE id = 1");
    $stmt->execute([$income_total, $outcome_total, $current_balance]);
    
} catch(PDOException $e) {
    error_log("Balance recalculation error: " . $e->getMessage());
}
// Handle Delete Income - MUST BE BEFORE OTHER HANDLERS
if(isset($_GET['delete_income'])) {
    $id = $_GET['delete_income'];
    
    try {
        // First, get the amount to subtract from balance
        $stmt = $conn->prepare("SELECT amount FROM income WHERE id = ?");
        $stmt->execute([$id]);
        $income = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($income) {
            // Delete the income record
            $stmt = $conn->prepare("DELETE FROM income WHERE id = ?");
            $stmt->execute([$id]);
            
            // Update balance - subtract from total_income and current_balance
            $stmt = $conn->prepare("UPDATE balance SET total_income = total_income - ?, current_balance = current_balance - ? WHERE id = 1");
            $stmt->execute([$income['amount'], $income['amount']]);
        }
    } catch(PDOException $e) {
        // Handle error silently or log it
        error_log("Delete income error: " . $e->getMessage());
    }
    
    header("Location: index.php");
    exit();
}

// Handle Delete Outcome
if(isset($_GET['delete_outcome'])) {
    $id = $_GET['delete_outcome'];
    
    try {
        // First, get the amount to add back to balance
        $stmt = $conn->prepare("SELECT amount FROM outcome WHERE id = ?");
        $stmt->execute([$id]);
        $outcome = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($outcome) {
            // Delete the outcome record
            $stmt = $conn->prepare("DELETE FROM outcome WHERE id = ?");
            $stmt->execute([$id]);
            
            // Update balance - subtract from total_outcome and add back to current_balance
            $stmt = $conn->prepare("UPDATE balance SET total_outcome = total_outcome - ?, current_balance = current_balance + ? WHERE id = 1");
            $stmt->execute([$outcome['amount'], $outcome['amount']]);
        }
    } catch(PDOException $e) {
        error_log("Delete outcome error: " . $e->getMessage());
    }
    
    header("Location: index.php");
    exit();
}

// Handle Income Form Submission
if(isset($_POST['add_income'])) {
    $description = $_POST['income_description'];
    $amount = $_POST['income_amount'];
    
    $stmt = $conn->prepare("INSERT INTO income (description, amount, category, date) VALUES (?, ?, 'General', CURDATE())");
    $stmt->execute([$description, $amount]);
    
    // Update balance
    $stmt = $conn->prepare("UPDATE balance SET total_income = total_income + ?, current_balance = current_balance + ? WHERE id = 1");
    $stmt->execute([$amount, $amount]);
    
    header("Location: index.php");
    exit();
}

// Handle Outcome Form Submission
if(isset($_POST['add_outcome'])) {
    $description = $_POST['outcome_description'];
    $bill_number = $_POST['bill_number'];
    $amount = $_POST['outcome_amount'];
    
    $stmt = $conn->prepare("INSERT INTO outcome (description, bill_number, amount, category, date) VALUES (?, ?, ?, 'General', CURDATE())");
    $stmt->execute([$description, $bill_number, $amount]);
    
    // Update balance
    $stmt = $conn->prepare("UPDATE balance SET total_outcome = total_outcome + ?, current_balance = current_balance - ? WHERE id = 1");
    $stmt->execute([$amount, $amount]);
    
    header("Location: index.php");
    exit();
}

// Rest of your code continues here...

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle Income Form Submission
if(isset($_POST['add_income'])) {
    $description = $_POST['income_description'];
    $amount = $_POST['income_amount'];
    
    $stmt = $conn->prepare("INSERT INTO income (description, amount, category, date) VALUES (?, ?, 'General', CURDATE())");
    $stmt->execute([$description, $amount]);
    
    // Update balance
    $stmt = $conn->prepare("UPDATE balance SET total_income = total_income + ?, current_balance = current_balance + ? WHERE id = 1");
    $stmt->execute([$amount, $amount]);
    
    header("Location: index.php");
    exit();
}
// Handle Delete Income - CORRECTED VERSION
if(isset($_GET['delete_income'])) {
    $id = $_GET['delete_income'];
    
    try {
        // First, get the amount to subtract from balance
        $stmt = $conn->prepare("SELECT amount FROM income WHERE id = ?");
        $stmt->execute([$id]);
        $income = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($income) {
            // Delete the income record
            $stmt = $conn->prepare("DELETE FROM income WHERE id = ?");
            $stmt->execute([$id]);
            
            // Update balance - ONLY subtract from total_income and current_balance
            // DO NOT touch total_outcome
            $stmt = $conn->prepare("UPDATE balance SET 
                total_income = total_income - ?, 
                current_balance = current_balance - ? 
                WHERE id = 1");
            $stmt->execute([$income['amount'], $income['amount']]);
        }
    } catch(PDOException $e) {
        error_log("Delete income error: " . $e->getMessage());
    }
    
    header("Location: index.php");
    exit();
}

// Handle Delete Outcome - CORRECTED VERSION
if(isset($_GET['delete_outcome'])) {
    $id = $_GET['delete_outcome'];
    
    try {
        // First, get the amount to add back to balance
        $stmt = $conn->prepare("SELECT amount FROM outcome WHERE id = ?");
        $stmt->execute([$id]);
        $outcome = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($outcome) {
            // Delete the outcome record
            $stmt = $conn->prepare("DELETE FROM outcome WHERE id = ?");
            $stmt->execute([$id]);
            
            // Update balance - ONLY subtract from total_outcome and add back to current_balance
            // DO NOT touch total_income
            $stmt = $conn->prepare("UPDATE balance SET 
                total_outcome = total_outcome - ?, 
                current_balance = current_balance + ? 
                WHERE id = 1");
            $stmt->execute([$outcome['amount'], $outcome['amount']]);
        }
    } catch(PDOException $e) {
        error_log("Delete outcome error: " . $e->getMessage());
    }
    
    header("Location: index.php");
    exit();
}
// Handle Outcome Form Submission
if(isset($_POST['add_outcome'])) {
    $description = $_POST['outcome_description'];
    $bill_number = $_POST['bill_number'];
    $amount = $_POST['outcome_amount'];
    
    $stmt = $conn->prepare("INSERT INTO outcome (description, bill_number, amount, category, date) VALUES (?, ?, ?, 'General', CURDATE())");
    $stmt->execute([$description, $bill_number, $amount]);
    // Update balance
    $stmt = $conn->prepare("UPDATE balance SET total_outcome = total_outcome + ?, current_balance = current_balance - ? WHERE id = 1");
    $stmt->execute([$amount, $amount]);
    
    header("Location: index.php");
    exit();
}

// First, ensure balance record exists
$stmt = $conn->query("SELECT COUNT(*) as count FROM balance WHERE id = 1");
$count = $stmt->fetch(PDO::FETCH_ASSOC);

if($count['count'] == 0) {
    // Create initial balance record if it doesn't exist
    $stmt = $conn->prepare("INSERT INTO balance (id, total_income, total_outcome, current_balance) VALUES (1, 0.00, 0.00, 0.00)");
    $stmt->execute();
}

// Fetch current balance
$stmt = $conn->query("SELECT * FROM balance WHERE id = 1");
$balance = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure balance values are set (fallback to 0 if null)
if(!$balance) {
    $balance = [
        'total_income' => 0.00,
        'total_outcome' => 0.00,
        'current_balance' => 0.00,
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

// Ensure each value exists
$balance['total_income'] = isset($balance['total_income']) ? $balance['total_income'] : 0.00;
$balance['total_outcome'] = isset($balance['total_outcome']) ? $balance['total_outcome'] : 0.00;
$balance['current_balance'] = isset($balance['current_balance']) ? $balance['current_balance'] : 0.00;
$balance['last_updated'] = isset($balance['last_updated']) ? $balance['last_updated'] : date('Y-m-d H:i:s');

// REPLACE THE INCOME AND OUTCOME FETCHING CODE IN index.php
// Add this AFTER the balance fetching code and BEFORE the HTML section

// Fetch all income records with error handling
try {
    $stmt = $conn->query("SELECT * FROM income ORDER BY date DESC");
    $incomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure $incomes is always an array
    if(!$incomes) {
        $incomes = [];
    }
} catch(PDOException $e) {
    // If query fails, set empty array
    $incomes = [];
}

// Fetch all outcome records with error handling
try {
    $stmt = $conn->query("SELECT * FROM outcome ORDER BY date DESC");
    $outcomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure $outcomes is always an array
    if(!$outcomes) {
        $outcomes = [];
    }
} catch(PDOException $e) {
    // If query fails, set empty array
    $outcomes = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Personal Finance Manager</h1>
        <!-- Report Button -->
        <div class="report-section">
            <a href="generate_report.php" class="btn btn-report" target="_blank">
                <span>📄</span> Generate PDF Report
            </a>
        </div>
        <!-- Balance Display -->
        <div class="balance-section">
    <div class="balance-card total">
        <h3>Total Income</h3>
        <p class="amount income">Rs. <?php echo number_format((float)$balance['total_income'], 2); ?></p>
    </div>
    <div class="balance-card total">
        <h3>Total Outcome</h3>
        <p class="amount outcome">Rs. <?php echo number_format((float)$balance['total_outcome'], 2); ?></p>
    </div>
    <div class="balance-card main">
        <h3>Current Balance</h3>
        <p class="amount balance">Rs. <?php echo number_format((float)$balance['current_balance'], 2); ?></p>
    </div>
</div>

        <!-- Forms Section -->
        <div class="forms-section">
            <!-- Add Income Form -->
            <div class="form-card">
                <h2>Add Income</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="income_description" required>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" step="0.01" name="income_amount" required>
                    </div>
                    <button type="submit" name="add_income" class="btn btn-income">Add Income</button>
                </form>
            </div>

            <!-- Add Outcome Form -->
            <div class="form-card">
                <h2>Add Outcome</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="outcome_description" required>
                    </div>
                    <div class="form-group">
                        <label>Bill Number</label>
                        <input type="text" name="bill_number" placeholder="Enter bill number">
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" step="0.01" name="outcome_amount" required>
                    </div>
                    <button type="submit" name="add_outcome" class="btn btn-outcome">Add Outcome</button>
                </form>
            </div>
        </div>

        <!-- Transactions Section -->
        <div class="transaction-card">
    <h2>Income History</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($incomes) > 0): ?>
                    <?php foreach($incomes as $income): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($income['description']); ?></td>
                            <td class="amount-cell income">Rs. <?php echo number_format($income['amount'], 2); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($income['date'])); ?></td>
                            <td>
                                <a href="?delete_income=<?php echo $income['id']; ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Are you sure you want to delete this income record?');">
                                    🗑️ Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="no-data">No income records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Update the Outcome History table in index.php -->
<div class="transaction-card">
    <h2>Outcome History</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Bill Number</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($outcomes) > 0): ?>
                    <?php foreach($outcomes as $outcome): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($outcome['description']); ?></td>
                            <td><?php echo htmlspecialchars($outcome['bill_number']); ?></td>
                            <td class="amount-cell outcome">Rs. <?php echo number_format($outcome['amount'], 2); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($outcome['date'])); ?></td>
                            <td>
                                <a href="?delete_outcome=<?php echo $outcome['id']; ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Are you sure you want to delete this outcome record?');">
                                    🗑️ Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">No outcome records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
        </div>
    </div>
</body>
</html>