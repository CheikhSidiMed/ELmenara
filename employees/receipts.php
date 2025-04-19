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

    // Récupération des années académiques
    $result = $conn->query("SELECT year_name, start_date, end_date FROM academic_years ORDER BY year_id DESC");
    if ($result) {
        $resultYears = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Si une année est sélectionnée, récupérez ses dates de début et fin
    if ($selectedYear) {
        $stmt = $conn->prepare("SELECT start_date, end_date FROM academic_years WHERE year_name = ?");
        if ($stmt) {
            $stmt->bind_param('s', $selectedYear);
            $stmt->execute();
            $stmt->bind_result($startDate, $endDate);
            $stmt->fetch();
            $stmt->close();
        } else {
            die("Erreur de préparation de la requête : " . $conn->error);
        }
    }

    // Recherche
    $search = $_GET['search'] ?? '';
    $receipts = [];

    // Construction dynamique de la requête
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
            WHERE 1=1";

    if ($search) {
        $query .= " AND (r.student_id LIKE ?
                        OR s.student_name LIKE ?
                        OR a.agent_name LIKE ?
                        OR s.phone LIKE ?
                        OR a.phone LIKE ?
                        OR r.agent_id LIKE ?
                        OR r.receipt_date LIKE ?
                        OR r.receipt_id = ?)";
    }

    if ($startDate && $endDate) {
        $query .= " AND r.receipt_date BETWEEN ? AND ?";
    }

    $query .= " GROUP BY r.receipt_id ORDER BY r.receipt_id DESC LIMIT 100";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Erreur de préparation de la requête : " . $conn->error);
    }

    // Préparez les paramètres dynamiques
    $paramTypes = '';
    $params = [];

    if ($search) {
        $likeSearch = '%' . $search . '%';
        $paramTypes .= 'ssssssss';
        $params = array_merge($params, [$likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $search]);
    }

    if ($startDate && $endDate) {
        $paramTypes .= 'ss';
        $params = array_merge($params, [$startDate, $endDate]);
    }

    // Associez les paramètres dynamiques
    if ($params) {
        $stmt->bind_param($paramTypes, ...$params);
    }

    // Exécutez la requête et récupérez les résultats
    $stmt->execute();
    $result = $stmt->get_result();
    $receipts = $result->fetch_all(MYSQLI_ASSOC);

?>



<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة الإيصالات</title>
    
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <!-- <style>
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
    </style> -->
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 15px;
            box-sizing: border-box;
        }

        .receipt {
            background-color: white;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 5px;
            margin: auto;
            width: 100%;
            max-width: 800px; /* Maximum width for larger screens */
            box-sizing: border-box;
        }

        .receipt-header img {
            width: 100%;
            height: auto;
            border-bottom: 2px solid #007b5e;
            margin-bottom: 0px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .info-line {
            margin-bottom: 10px;
            font-weight: bold;
            color: #5a5a5a;
            word-break: break-word; /* Prevents text overflow */
        }

        .info-line span {
            color: #007b5e;
            font-weight: bold;
        }

        .info-container {
            display: flex;
            flex-wrap: wrap; /* Allows items to wrap on small screens */
            justify-content: space-between;
            margin-bottom: 10px;
            margin-top: 10px;
            align-items: center;
            gap: 10px; /* Adds space between items when they wrap */
        }

        .info-container div {
            flex: 1;
            min-width: 120px;
            text-align: center;
        }

        .info-container div:not(:last-child) {
            margin-right: 10px;
        }

        .info-container .highlight {
            color: #007b5e;
            font-weight: bold;
        }

        .summary-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            font-size: 14px;
            padding: 5px;
            border: 1px solid #000;
            border-radius: 5px;
        }

        .summary-container div {
            flex: 1;
            min-width: 100px; /* Minimum width before wrapping */
            text-align: center;
            font-weight: bold;
            color: #5a5a5a;
            padding: 2px;
            box-sizing: border-box;
        }

        .summary-container .text-primary {
            color: #17a2b8 !important;
        }

        .footer-note {
            text-align: right;
            margin-top: 20px;
            font-weight: bold;
            color: #5a5a5a;
            word-break: break-word;
        }

        table {
            border: 1px solid #000 !important;
            width: 100%;
            border-collapse: collapse;
        }

        th, td, .table-bordered {
            border: 1px solid black !important;
            padding: 8px;
            word-break: break-word; /* Prevents text overflow in cells */
        }

        @media (max-width: 600px) {
            .receipt {
                padding: 15px;
            }
            
            .info-container div, 
            .summary-container div {
                flex: 100%; /* Stack items vertically on small screens */
                margin-right: 0 !important;
                margin-bottom: 1px;
            }
            
            .info-container div:last-child,
            .summary-container div:last-child {
                margin-bottom: 0;
            }
            
            th, td {
                padding: 4px 0px !important;
                margin: 0px !important;
                font-size: 10px;
            }
        }

        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            
            .receipt {
                box-shadow: none;
                padding: 0;
                width: 100%;
            }
        }


        @media print {
            @page {
                size: A5;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0px;
                font-size: 10pt;
                color: #000; /* Force text to black */
            }
            .container {
                margin-top: -40px !important;
                margin-right: -39px !important;

                transform: scale(.69);
                transform-origin: right !important; /* Origine de l'échelle au centre */
                display: flex !important; /* Active le flexbox */
                justify-content: center !important; /* Centre horizontalement */
                align-items: center !important;
            }
            .info-container {
                padding-top: 4px !important;
                margin: 0px !important;
            }
            .container {
                max-width: 1200px !important;
                margin: 0 auto;
                padding: 0 2px;
                text-align: center;
            }

            .receipt {
                max-width: 80% !important;
                margin: 0 auto;
                margin-top: 20px;
                padding: 0px;
                border: none;
                color: #000;
                /* page-break-inside: avoid; */
            }

            table,  {
                width: 100% !important;
                border-collapse: collapse;
                border: 1px solid #000 !important;

            }

            th, td, .table-bordered {
                padding: 0px !important;
                margin: 0px !important;
                border: 1px solid black !important;
                text-align: center;
                color: #000;
                font-size: 9pt !important;
            }

            /* Styling for emphasis */
            .highlight {
                font-weight: bold;
                font-size: 9.5pt;
                color: #000;
            }

            /* Hide unnecessary print elements */
            .no-print, .print-button {
                display: none;
            }
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
                    <td><?php echo htmlspecialchars($receipt['total_amount']?? 'N/A'); ?></td>
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
