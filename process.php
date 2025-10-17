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

// Recalculate and fix balance values
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

// Handle Delete Income
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
        error_log("Delete income error: " . $e->getMessage());
    }
    
    header("Location: index.html");
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
    
    header("Location: index.html");
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
    
    header("Location: index.html");
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
    
    header("Location: index.html");
    exit();
}

// Ensure balance record exists
$stmt = $conn->query("SELECT COUNT(*) as count FROM balance WHERE id = 1");
$count = $stmt->fetch(PDO::FETCH_ASSOC);

if($count['count'] == 0) {
    $stmt = $conn->prepare("INSERT INTO balance (id, total_income, total_outcome, current_balance) VALUES (1, 0.00, 0.00, 0.00)");
    $stmt->execute();
}

// Fetch current balance
$stmt = $conn->query("SELECT * FROM balance WHERE id = 1");
$balance = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$balance) {
    $balance = [
        'total_income' => 0.00,
        'total_outcome' => 0.00,
        'current_balance' => 0.00,
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

$balance['total_income'] = isset($balance['total_income']) ? $balance['total_income'] : 0.00;
$balance['total_outcome'] = isset($balance['total_outcome']) ? $balance['total_outcome'] : 0.00;
$balance['current_balance'] = isset($balance['current_balance']) ? $balance['current_balance'] : 0.00;
$balance['last_updated'] = isset($balance['last_updated']) ? $balance['last_updated'] : date('Y-m-d H:i:s');

// Fetch all income records
try {
    $stmt = $conn->query("SELECT * FROM income ORDER BY date DESC");
    $incomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(!$incomes) {
        $incomes = [];
    }
} catch(PDOException $e) {
    $incomes = [];
}

// Fetch all outcome records
try {
    $stmt = $conn->query("SELECT * FROM outcome ORDER BY date DESC");
    $outcomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(!$outcomes) {
        $outcomes = [];
    }
} catch(PDOException $e) {
    $outcomes = [];
}

// Return data as JSON for AJAX requests
if(isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    header('Content-Type: application/json');
    echo json_encode([
        'balance' => $balance,
        'incomes' => $incomes,
        'outcomes' => $outcomes
    ]);
    exit();
}
?>