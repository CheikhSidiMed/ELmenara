<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير المالي الشهر</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #eef2f5;
        }

        .main-container {
            margin: 80px auto;
            padding: 20px;
            border-radius: 12px;
            background-color: white;
            border: 2px solid #1BA078;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            max-width: 550px;
            transition: all 0.3s ease-in-out;
        }

        .header-title {
            font-size: 26px;
            font-weight: bold;
            color: #1BA078;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .select-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .form-select {
            border-radius: 8px;
            padding: 8px 12px;
            border: 2px solid #1BA078;
            font-family: 'Amiri', serif;
            width: 100%;
            transition: all 0.3s ease-in-out;
        }

        .form-select:focus {
            border-color: #14865b;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            outline: none;
        }

        label {
            width: 120px;
            color: #333;
            font-weight: bold;
        }

        .confirm-box {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 30px;
            padding: 15px;
            border: 2px solid #1BA078;
            border-radius: 10px;
            background-color: white;
            color: #1BA078;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .confirm-box i {
            font-size: 24px;
            margin-right: 10px;
        }

        .confirm-box:hover {
            background-color: #f5f5f5;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .confirm-box:active {
            transform: scale(0.98);
        }

        .footer-note {
            font-size: 14px;
            text-align: center;
            margin-top: 20px;
            color: #888;
        }
    </style>
</head>

<body>

    <div class="container main-container">
        <!-- Header Title -->
        <h2 class="header-title">
            <i class="bi bi-file-earmark-text-fill"></i> التقرير المالي الشهر
        </h2>

        <!-- Year and Month Selection -->
        <div class="select-box">
            <label for="year-select">السنة المالية:</label>
            <select id="year-select" class="form-select" >
                <option selected><?php echo $last_year; ?></option>
                
            </select>
        </div>

        <div class="select-box">
            <label for="month-select">الشهر:</label>
            <select id="month-select" class="form-select" required>
                <!-- Months will be dynamically populated here -->
            </select>
        </div>

        <!-- Confirmation Button -->
        <div class="confirm-box" onclick="redirectToReport()">
            <i class="bi bi-check"></i> تأكيد العملية
        </div>

        <!-- Optional Footer Note -->
        <div class="footer-note">يرجى التحقق من البيانات قبل تأكيد العملية.</div>
    </div>

    <script>
        const yearSelect = document.getElementById("year-select");
        const monthSelect = document.getElementById("month-select");

        const months = [
            { value: "يناير", label: "يناير" },
            { value: "فبراير", label: "فبراير" },
            { value: "مارس", label: "مارس" },
            { value: "أبريل", label: "أبريل" },
            { value: "مايو ", label: "مايو" },
            { value: "يونيو", label: "يونيو" },
            { value: "يوليو", label: "يوليو" },
            { value: "أغسطس", label: "أغسطس" },
            { value: "سبتمبر", label: "سبتمبر" },
            { value: "أكتوبر", label: "أكتوبر" },
            { value: "نوفمبر", label: "نوفمبر" },
            { value: "ديسمبر", label: "ديسمبر" }
        ];

        function populateMonths(months) {
            monthSelect.innerHTML = "";
            months.forEach(month => {
                const option = document.createElement("option");
                option.value = month.value;
                option.textContent = month.label;
                monthSelect.appendChild(option);
            });
        }

        // Initially populate months for the selected year (2024-2023)
        window.onload = function () {
            populateMonths(months); // Default year is 2024-2023
        }

        yearSelect.addEventListener("change", function () {
            populateMonths(months);
        });

        function redirectToReport() {
            const selectedYear = yearSelect.value;
            const selectedMonth = monthSelect.value;

            window.location.href = `Monthly financial report.php?year=${selectedYear}&month=${selectedMonth}`;
        }
    </script>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>
