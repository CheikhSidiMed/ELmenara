<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير اليومي</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f4f7f6;
            padding: 20px;
        }

        .container {
            background-color: white;
            border: 2px solid #1BA078;
            border-radius: 15px;
            padding: 30px;
            max-width: 1200px;
            margin: auto;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .header-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .header-section img {
            width: 100%;
            max-width: 800px;
        }

        .header-title {
            font-size: 26px;
            font-weight: bold;
            color: #1BA078;
            margin-bottom: 20px;
        }

        .table-container {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table th,
        table td {
            border: 2px solid #1BA078;
            padding: 20px;
            font-size: 18px;
            text-align: center;
            vertical-align: middle;
        }

        table th {
            background-color: #1BA078;
            color: white;
            font-weight: bold;
            border-right: 2px solid white; /* Add border between th elements */
        }

        table th:last-child {
            border-right: none; /* Remove right border for last th */
        }

        table td {
            background-color: #fff;
        }

        .footer-summary {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 20px;
        }

        .footer-summary div {
            color: #1BA078;
            font-weight: bold;
        }

        .footer-summary div input {
            width: 100px;
            border: none;
            border-bottom: 2px solid #1BA078;
            background: none;
            text-align: center;
        }

        .date-container input[type="date"] {
            width: 180px;
            padding: 10px;
            border: 2px solid #1BA078;
            border-radius: 10px;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px;
            border: 2px solid #1BA078;
            margin-top: 10px;
        }

    </style>
</head>

<body>

    <div class="container">
        <!-- Header Section -->
        <div class="header-section">
            <img src="../images/header.png" alt="Header Image">
        </div>

        <!-- Title -->
        <h2 class="header-title">التقرير اليومي</h2>

        <!-- User and Date Section -->
        <div class="row">
            <div class="col-6">
                <label>المستخدم: __________________</label>
            </div>
            <div class="col-6 text-end">
                <label for="fromDate">من:</label>
                <input type="date" id="fromDate" class="form-control d-inline-block" style="width: 180px;">
                <label for="toDate">إلى:</label>
                <input type="date" id="toDate" class="form-control d-inline-block" style="width: 180px;">
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container mt-4">
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">البيان</th>
                        <th colspan="2">الصندوق</th>
                        <th colspan="3">البنوك</th>
                    </tr>
                    <tr>
                        <th>الدخل</th>
                        <th>الصرف</th>
                        <th>اسم البنك</th>
                        <th>إيداع</th>
                        <th>سحب</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>________________</td>
                        <td>________________</td>
                        <td>________________</td>
                        <td>________________</td>
                        <td>________________</td>
                        <td>________________</td>
                    </tr>
                    <tr>
                        <td>________________</td>
                        <td>________________</td>
                        <td>________________</td>
                        <td>________________</td>
                        <td>________________</td>
                        <td>________________</td>
                    </tr>
                </tbody>
            </table>
            <!-- Footer Summary -->
            <div class="footer-summary">
                <div>
                    المجاميع:
                    <input type="text">
                </div>
                <div>
                    الرصيد:
                    <input type="text">
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.min.js"></script>
</body>

</html>
