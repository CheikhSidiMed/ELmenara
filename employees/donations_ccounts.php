<?php
// Include the database connection file
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


// Fetch all expense accounts initially
$sql = "
    SELECT 
        da.account_number,
        da.account_name,
        da.category,
        da.account_balance,
        COALESCE(SUM(dt.amount), 0) AS amount
    FROM 
        donate_accounts AS da
    LEFT JOIN 
        donate_transactions AS dt
    ON 
        da.id = dt.donate_account_id
    GROUP BY 
        da.id, da.account_number, da.account_name
";
$result = $conn->query($sql);

$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حسابات المصاريف</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f4f7f6;
        }

        .main-container {
            margin: 40px auto;
            padding: 30px;
            border-radius: 15px;
            background-color: white;
            border: 2px solid #1BA078;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
        }

        .header-title {
            font-size: 28px;
            font-weight: bold;
            color: #1BA078;
            display: flex;
            align-items: center;
        }

        .icon-container img {
            width: 60px;
            margin-left: 15px;
            filter: drop-shadow(2px 4px 6px #1BA078);
        }

        .search-container input[type="text"] {
            border-radius: 30px;
            border: 2px solid #1BA078;
            padding: 12px 25px;
            width: 100%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease-in-out;
        }

        .search-container input[type="text"]::placeholder {
            color: #999;
            font-style: italic;
        }

        .search-container input[type="text"]:focus {
            border-color: #14865b;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            outline: none;
        }

        .filter-container {
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filter-container label {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }

        .filter-container input[type="checkbox"] {
            margin-left: 5px;
            transform: scale(1.2);
            accent-color: #1BA078;
        }

        .table-container .table {
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table-container .table thead th {
            background-color: #1BA078;
            color: white;
            text-align: center;
            vertical-align: middle;
            font-size: 18px;
            padding: 15px;
        }

        .table-container .table tbody td {
            text-align: center;
            vertical-align: middle;
            font-size: 16px;
            padding: 12px;
            border-color: #ddd;
            direction : ltr;
        }

        .view-icon {
            background-color: #1BA078;
            color: white;
            border-radius: 50%;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .view-icon:hover {
            background-color: #14865b;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .main-container h2 {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
        }

        .table-container {
            overflow-x: auto;
        }

        .btn-custom {
            border-radius: 25px;
            background-color: #1BA078;
            color: white;
            border: 1px solid #1BA078;
            padding: 10px 20px;
            font-weight: bold;
            transition: all 0.3s ease-in-out;
        }

        .btn-custom:hover {
            background-color: #14865b;
            border-color: #14865b;
        }
    </style>
</head>

<body>

    <div class="container main-container">
        <div class="row align-items-center">
            <div class="col-2 icon-container">
                <img src="../images/i.png" alt="icon">
            </div>
            <div class="col-10">
                <h2 class="header-title">حسابات مداخيل</h2>
            </div>
        </div>
         <!-- Home Button -->
         <div class="row justify-content-center mb-3">
            <div class="col-auto">
                <a href="home.php" class="btn btn-primary" style="border-radius: 30px; background-color: #1BA078; border: 2px solid #1BA078;">
                    <i class="bi bi-house-door-fill"></i> الصفحة الرئيسية
                </a>
            </div>
        </div>

        <div class="row search-container">
            <div class="col-12">
                <input type="text" id="search" placeholder="البحث عن حساب : رقم الحساب أو اسم الحساب">
            </div>
        </div>

        <div class="row table-container">
            <div class="col-12">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>العملية</th>
                            <th>اسم الحساب</th>
                            <th>الفئة</th>
                            <th>رصيد الحساب</th>
                        </tr>
                    </thead>
                    <tbody id="accountsTableBody">
    <?php foreach ($accounts as $account) : ?>
        <tr>
            <td>
                <a href="donation_preview.php?account_number=<?= htmlspecialchars($account['account_number']) ?>" class="view-icon">&#128065;</a>
            </td>
            <td><?= htmlspecialchars($account['account_name']) ?></td>
            <td><?= htmlspecialchars($account['category']) ?></td>
            <td><?= htmlspecialchars(number_format($account['amount'])) ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>

                </table>
            </div>
        </div>

        
    </div>

    <script>
        document.getElementById('search').addEventListener('input', function() {
            var searchQuery = this.value;

            // Fetch data using AJAX
            fetch('fetch_donations_accounts.php?search=' + encodeURIComponent(searchQuery))
                .then(response => response.json())
                .then(data => {
                    var tableBody = document.getElementById('accountsTableBody');
                    tableBody.innerHTML = '';

                    data.forEach(account => {
                        var row = `
                            <tr>
                                <td><span class="view-icon">&#128065;</span></td>
                                <td>${account.account_name}</td>
                                <td>${account.category}</td>
                                <td>${account.account_balance}</td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                });
        });
    </script>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap-5.3.1.min.js"></script>
</body>

</html>
