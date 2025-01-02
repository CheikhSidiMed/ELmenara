<?php
// Include database connection
include 'db_connection.php';

$activity_id = isset($_GET['activity_id']) ? $_GET['activity_id'] : 0;

$sql = "
SELECT DISTINCT a.activity_name, 
                s.student_name, 
                ap.payment_method, 
                ap.payment_date, 
                SUM(ap.paid_amount) AS paid_amount, 
                sa.subscription_date, 
                c.class_name, 
                a.price 
FROM student_activities  sa
LEFT JOIN activities_payments ap ON ap.student_activity_id = sa.id
LEFT JOIN students s ON sa.student_id = s.id
LEFT JOIN activities a ON sa.activity_id = a.id
LEFT JOIN classes c ON s.class_id = c.class_id
WHERE a.id = ?
GROUP BY s.id, s.class_id, a.activity_name, 
          sa.id
ORDER BY ap.payment_date DESC;
";



$stmt = $conn->prepare($sql);

// Check if the statement was prepared successfully
if ($stmt === false) {
    die('Error preparing the statement: ' . $conn->error);
}

$stmt->bind_param("i", $activity_id);
$stmt->execute();
$result = $stmt->get_result();

$activity_details = [];
while ($row = $result->fetch_assoc()) {
    $activity_details[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة نشاط</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/amiri.css">

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

        .activity-title {
            margin-top: 20px;
            font-size: 24px;
            color: #1BA078;
            font-weight: bold;
            text-align: center;
        }

        .activity-details {
            margin-top: 10px;
            text-align: center;
            font-size: 20px;
            color: #333;
            font-weight: bold;
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
        }

        .summary-container {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #1BA078;
            padding-top: 15px;
        }

        .summary-container .btn-custom {
            border-radius: 25px;
            background-color: #1BA078;
            color: white;
            border: 1px solid #1BA078;
            padding: 10px 20px;
            font-weight: bold;
            transition: all 0.3s ease-in-out;
        }

        .summary-container .btn-custom:hover {
            background-color: #14865b;
            border-color: #14865b;
        }

        .summary-container .total {
            font-size: 18px;
            font-weight: bold;
            color: #1BA078;
        }

        .summary-container .total span {
            color: #333;
            font-weight: bold;
        }

        .summary-data {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            color: #1BA078;
            font-weight: bold;
            margin-top: 10px;
            border-top: 2px solid #1BA078;
            padding-top: 15px;
        }

        .summary-data div {
            margin-right: 20px;
        }
        .receipt-header img {
            width: 100%;
            height: auto;
            border-bottom: 2px solid #007b5e;
            margin-bottom: 20px;
        }
        @media print {
            #printButton {
                display: none !important; /* Forcer le masquage */
            }
            table{
                color: black !important;
            }
        }
    </style>
</head>

<body>

    <div class="container main-container">
        <div class="receipt-header">
                <img src="../images/header.png" alt="Header Image">
        </div>
        <div class="row align-items-center">
            <div class="col-2 icon-container">
                <img src="../images/i.png" alt="icon">
            </div>
            <div class="col-10">
                <h2 class="header-title">معاينة نشاط</h2>
            </div>
        </div>

        <?php if (count($activity_details) > 0): ?>
            <div class="activity-details">
                <span>اسم النشاط :</span> <?php echo $activity_details[0]['activity_name']; ?>
            </div>

            <div class="row table-container">
                <div class="col-12">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>الاسم الكامل</th>
                                <th>القسم</th>
                                <th>تاريخ التسجيل</th>
                                <th>نوع الدفع</th>
                                <th>حالة الدفع</th>
                                <th>المبلغ المدفوع</th>
                                <th>تاريخ الدفع</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activity_details as $detail): ?>
                                <tr>
                                    <td><?php echo $detail['student_name']; ?></td>
                                    <td><?php echo $detail['class_name']; ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($detail['subscription_date'])); ?></td>
                                    <td><?php echo $detail['payment_method']; ?></td>
                                    <td>
                                    <?php 
                                        $remaining_amount = (float)$detail['price'] - (float)$detail['paid_amount']; 
                                        if ($remaining_amount === 0.00) {
                                            echo '<strong style="color: green; font-weight: bold;">دفع</strong>';
                                        } else {
                                            echo '<strong style="color: red;">' . number_format($remaining_amount, 2) . '</strong>';
                                        }  
                                    ?> </td>
                                    <td><?php echo number_format($detail['paid_amount']); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($detail['payment_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="summary-data">
                
                <div>
                    <span>الإيرادات:</span> <?php echo number_format(array_sum(array_column($activity_details, 'paid_amount'))); ?> MRU
                </div>
            </div>
            <div class="text-center mt-3">
            <button class="btn btn-success" id="printButton">طباعة الصفحة</button>
        </div>
        <?php else: ?>
            <p>لم يتم العثور على بيانات لهذا النشاط.</p>
        <?php endif; ?>
    </div>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

    <script>
        document.getElementById("printButton").addEventListener("click", function() {
            // Enregistrer le contenu original
            var originalContent = document.body.innerHTML;

            // Récupérer le contenu de .main-container
            var printContent = document.querySelector(".main-container").innerHTML;

            // Remplacer le contenu du body avec celui à imprimer
            document.body.innerHTML = printContent;

            // Lancer l'impression
            window.print();

            // Restaurer le contenu original après l'impression
            document.body.innerHTML = originalContent;
            window.location.reload(); // Recharger la page pour restaurer les fonctionnalités dynamiques
        });
    </script>


</body>

</html>
