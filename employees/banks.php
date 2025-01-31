<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


// Fetch funds data
$funds = [];
$funds_query = "SELECT  id , fund_name, balance FROM funds";
$result_funds = $conn->query($funds_query);
if ($result_funds->num_rows > 0) {
    while ($row = $result_funds->fetch_assoc()) {
        $funds[] = $row;
    }
}

// Fetch bank accounts data
$banks = [];
$banks_query = "SELECT account_id , bank_name, balance FROM bank_accounts";
$result_banks = $conn->query($banks_query);
if ($result_banks->num_rows > 0) {
    while ($row = $result_banks->fetch_assoc()) {
        $banks[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الخزينة و البنوك</title>
    <!-- Include Font Awesome -->
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/cairo.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #e9ecef;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            direction: rtl;
            text-align: right;
        }

        .container {
            background-color: #ffffff;
            border: 2px solid #027f40;
            border-radius: 12px;
            padding: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0px 5px 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .container h2 {
            font-size: 24px;
            color: #027f40;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .container h2 img {
            width: 40px;
            height: 40px;
            margin-left: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            margin-top: 15px;
            direction: rtl;
        }

        table th, table td {
            padding: 15px;
            border: 1px solid #ddd;
            font-size: 18px;
        }

        table th {
            background-color: #f5f5f5;
            color: #555555;
            font-weight: bold;
        }

        table td {
            color: #027f40;
            font-weight: bold;
            text-align: center;
            direction: ltr;
        }

        .eye-icon {
            color: #ffffff;
            background-color: #027f40;
            padding: 8px;
            border-radius: 50%;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            width: 35px;
            height: 35px;
            transition: background-color 0.3s ease;
        }

        .eye-icon i {
            font-size: 18px;
        }

        .eye-icon:hover {
            background-color: #025c2b;
        }

        /* Ensure numbers are displayed correctly */
        td {
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><img src="../images/m.png" alt="Icon"> الخزينة و البنوك</h2>
        <table>
            <thead>
                <tr>
                    <th>الحساب</th>
                    <th>الرصيد الحالي</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
    <!-- Display funds data -->
    <?php foreach ($funds as $fund): ?>
    <tr>
        <td><?php echo $fund['fund_name']; ?></td>
        <td><?php echo number_format($fund['balance']); ?> MRU </td>
        <td class="eye-icon">
            <a href="Account Preview.php?fund_id=<?php echo $fund['id']; ?>"><i class="bi bi-eye"></i></a>
        </td>
    </tr>
    <?php endforeach; ?>

    <!-- Display bank accounts data -->
    <?php foreach ($banks as $bank): ?>
    <tr>
        <td><?php echo $bank['bank_name']; ?></td>
        <td><?php echo number_format($bank['balance']); ?> MRU </td>
        <td class="eye-icon">
            <a href="Account Preview.php?bank_id=<?php echo $bank['account_id']; ?>"><i class="bi bi-eye"></i></a>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>

        </table>
    </div>
</body>
</html>
