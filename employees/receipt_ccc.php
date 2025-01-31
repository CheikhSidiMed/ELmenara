<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


include 'db_connection.php';

$resultYears = [];
$selectedYear = $_GET['year'] ?? ''; 
$startDate = $endDate = '';

$result = $conn->query("SELECT year_name, start_date, end_date FROM academic_years ORDER BY year_id DESC");
if ($result) {
    $resultYears = $result->fetch_all(MYSQLI_ASSOC);
}

if ($selectedYear) {
    $stmt = $conn->prepare("SELECT start_date, end_date FROM academic_years WHERE year_name = ?");
    $stmt->bind_param('s', $selectedYear);
    $stmt->execute();
    $stmt->bind_result($startDate, $endDate);
    $stmt->fetch();
    $stmt->close();
}

$search = $_GET['search'] ?? '';
$receipts = [];

if ($search || $selectedYear) {
    $query = "SELECT 
                r.receipt_id, 
                s.student_name, 
                a.agent_name, 
                r.total_amount, 
                r.receipt_date, 
                r.receipt_description 
              FROM receipts AS r
              LEFT JOIN agents AS a ON a.agent_id = r.agent_id
              LEFT JOIN students AS s ON s.id = r.student_id
              JOIN receipt_payments AS rp ON rp.receipt_id = r.receipt_id
              WHERE (r.student_id LIKE ? 
                     OR s.student_name LIKE ? 
                     OR a.agent_name LIKE ? 
                     OR s.phone LIKE ?  
                     OR a.phone LIKE ? 
                     OR r.agent_id LIKE ? 
                     OR r.receipt_date LIKE ? 
                     OR r.receipt_id = ?) ORDER BY r.receipt_id DESC";


    if ($startDate && $endDate) {
        $query .= " AND r.receipt_date BETWEEN ? AND ?";
        $stmt = $conn->prepare($query);
    
        $likeSearch = '%' . $search . '%';
    
        $stmt->bind_param(
            'ssssssssss', 
            $likeSearch, 
            $likeSearch, 
            $likeSearch, 
            $likeSearch, 
            $likeSearch, 
            $likeSearch, 
            $likeSearch, 
            $search, 
            $startDate, 
            $endDate 
        );
    } else {
        $stmt = $conn->prepare($query);
        $likeSearch = '%' . $search . '%';
    
        $stmt->bind_param(
            'ssssssss', 
            $likeSearch, 
            $likeSearch, 
            $likeSearch, 
            $likeSearch, 
            $likeSearch,
            $likeSearch, 
            $likeSearch,
            $search  
        );
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $receipts = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query("SELECT 
                                r.receipt_id, 
                                s.student_name, 
                                a.agent_name, 
                                r.total_amount, 
                                r.receipt_date, 
                                r.receipt_description 
                            FROM receipts AS r
                            LEFT JOIN agents AS a ON a.agent_id = r.agent_id
                            LEFT JOIN students AS s ON s.id = r.student_id 
                            JOIN receipt_payments AS rp ON rp.receipt_id = r.receipt_id

                            ORDER BY r.receipt_id DESC");
    $receipts = $result->fetch_all(MYSQLI_ASSOC);
}
?>






<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة الإيصالات</title>
    
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
            text-align: right;
            background-color: #f5f5f5;
        }
        select {
            width: 300px;
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
            font-family: 'Tajawal', sans-serif;
            font-size: 16px;
            color: #333;
            appearance: none; /* Removes the default arrow in some browsers */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><path fill="%23333" d="M5 6.5l-5-5h10z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 10px;
            text-align: center;

        }

        select:hover {
            border-color: #007bff;
        }

        select:focus {
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        option {
            padding: 10px;
            text-align: center;
            background-color: #fff;
            color: #333;
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 35px;
        }
        .search-form {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .search-form input[type="text"] {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            width: 300px;
            text-align: right;
        }
        .search-form button {
            padding: 10px 20px;
            margin-right: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        table {
            width: 80%;
            margin: 0 auto;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 15px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .print-button {
            padding: 5px 15px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .autocomplete-suggestions {
            margin-top: 45px;
            margin-left: 503px;
            position: absolute;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            max-height: 200px;
            overflow-y: auto;
            width: 298px;
            z-index: 1000;
        }
        .autocomplete-suggestion {
            padding: 10px;
            cursor: pointer;
        }
        .autocomplete-suggestion:hover {
            background-color: #f0f0f0;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>


    <h1>قائمة الإيصالات</h1>            
        

<!-- Formulaire de recherche -->
<form method="GET" action="" class="search-form no-print" style="position: relative;">
    <input type="text" id="search-input" name="search" placeholder="بحث بواسطة رقم الطالب، رقم الوكيل، أو التاريخ" 
           value="<?php echo htmlspecialchars($search); ?>" onkeyup="fetchSuggestions(this.value)" autocomplete="off">
    <div id="autocomplete-list" class="autocomplete-suggestions"></div>

    <select name="year" id="year" onchange="this.form.submit()">
        <option value="">-- اختر العام --</option>
        <?php foreach ($resultYears as $year): ?>
            <option value="<?php echo htmlspecialchars($year['year_name']); ?>" 
                <?php if ($selectedYear == $year['year_name']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($year['year_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">بحث</button>
    <div class="d-flex align-items-center">
        <a href="home.php" class="btn btn-primary d-flex align-items-center" style="margin-right: 25px;">
            <i class="bi bi-house-fill" style="margin-left: 5px;"></i>
                الرئيسية
        </a> 
    </div>
</form>

<!-- Tableau des reçus -->
<table>
    <thead>
        <tr>
            <th>رقم الإيصال</th>
            <th>الطالب</th>
            <th>الوكيل</th>
            <th>المبلغ الكلي</th>
            <th>تاريخ الإيصال</th>
            <th>الوصف</th>
            <th class="no-print">إجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($receipts as $receipt): ?>
            <tr>
                <td><?php echo htmlspecialchars($receipt['receipt_id']); ?></td>
                <td><?php echo htmlspecialchars($receipt['student_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($receipt['agent_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($receipt['total_amount']); ?></td>
                <td><?php echo htmlspecialchars($receipt['receipt_date']); ?></td>
                <td><?php echo htmlspecialchars($receipt['receipt_description']); ?></td>
                <td class="no-print">
                    <button onclick="window.open('print_receipt.php?receipt_id=<?php echo $receipt['receipt_id']; ?>', '_blank')" class="print-button">طباعة</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
function fetchSuggestions(query) {
    if (query.length === 0) {
        document.getElementById("autocomplete-list").innerHTML = "";
        return;
    }
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "autocomplete_receipt.php?query=" + encodeURIComponent(query), true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById("autocomplete-list").innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}

function setInput(value) {
    document.getElementById("search-input").value = value;
    document.getElementById("autocomplete-list").innerHTML = "";
}
</script>

</body>
</html>




