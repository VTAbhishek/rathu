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

// REPLACE THE INCOME AND OUTCOME FETCHING CODE IN generate_report.php
// Add this AFTER the balance fetching code and BEFORE the HTML section

// Fetch income records with error handling
try {
    $stmt = $conn->query("SELECT * FROM income ORDER BY date DESC");
    $incomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(!$incomes) {
        $incomes = [];
    }
} catch(PDOException $e) {
    $incomes = [];
}

// Fetch outcome records with error handling
try {
    $stmt = $conn->query("SELECT * FROM outcome ORDER BY date DESC");
    $outcomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(!$outcomes) {
        $outcomes = [];
    }
} catch(PDOException $e) {
    $outcomes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Report</title>
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 20px;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        
        .report-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header .date {
            color: #666;
            font-size: 0.9em;
        }
        
        .balance-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .balance-box {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: white;
        }
        
        .balance-box.income {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .balance-box.outcome {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .balance-box.balance {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        .balance-box h3 {
            font-size: 1em;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .balance-box .amount {
            font-size: 1.8em;
            font-weight: bold;
        }
        
        .section-title {
            background: #667eea;
            color: white;
            padding: 12px 20px;
            font-size: 1.3em;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        thead {
            background: #f3f4f6;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #667eea;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .amount-income {
            color: #10b981;
            font-weight: bold;
            text-align: right;
        }
        
        .amount-outcome {
            color: #ef4444;
            font-weight: bold;
            text-align: right;
        }
        
        .no-data {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 20px;
        }
        
        .button-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="button-container no-print">
        <button onclick="window.print()" class="btn btn-print">🖨️ Print / Save as PDF</button>
        <a href="index.php" class="btn btn-back">← Back to Dashboard</a>
    </div>
    
    <div class="report-container">
        <div class="header">
            <h1>Finance Report</h1>
            <p class="date">Generated on: <?php echo date('d F Y, h:i A'); ?></p>
        </div>
        <div class="balance-summary">
    <div class="balance-box income">
        <h3>Total Income</h3>
        <div class="amount">Rs. <?php echo number_format((float)$balance['total_income'], 2); ?></div>
    </div>
    <div class="balance-box outcome">
        <h3>Total Outcome</h3>
        <div class="amount">Rs. <?php echo number_format((float)$balance['total_outcome'], 2); ?></div>
    </div>
    <div class="balance-box balance">
        <h3>Current Balance</h3>
        <div class="amount">Rs. <?php echo number_format((float)$balance['current_balance'], 2); ?></div>
    </div>
</div>
        
        <div class="section-title">Income History</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($incomes) > 0): ?>
                    <?php foreach($incomes as $income): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($income['date'])); ?></td>
                            <td><?php echo htmlspecialchars($income['description']); ?></td>
                            <td class="amount-income">Rs. <?php echo number_format($income['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="no-data">No income records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="section-title">Outcome History</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Bill Number</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($outcomes) > 0): ?>
                    <?php foreach($outcomes as $outcome): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($outcome['date'])); ?></td>
                            <td><?php echo htmlspecialchars($outcome['description']); ?></td>
                            <td><?php echo htmlspecialchars($outcome['bill_number']); ?></td>
                            <td class="amount-outcome">Rs. <?php echo number_format($outcome['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <tr>
                        <td colspan="4" class="no-data">No outcome records found</td>
                    </tr>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Personal Finance Manager - Generated Report</p>
            <p>Last Updated: <?php echo date('d F Y', strtotime($balance['last_updated'])); ?></p>
        </div>
    </div>
</body>
</html>