<?php
// Include database connection
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class'];
    $month = $_POST['month'];
    $year = $_POST['year'];

     // Fetch class name and branch name
     $sql = "SELECT classes.class_name, branches.branch_name 
     FROM classes 
     JOIN branches ON classes.branch_id = branches.branch_id 
     WHERE classes.class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $stmt->bind_result($class_name, $branch_name);
    $stmt->fetch();
    $stmt->close();

    // Fetch students in the selected class
    $sql = "SELECT student_id, student_name FROM students WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
    $students[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>  استمارة الغياب الشهري</title>
    <link rel="stylesheet" href="js/css/bootstrap-4-5-2-min.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/amiri.css">
    <link rel="stylesheet" href="css/tajawal.css">

    <style>
        body {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            margin: 20px;
        }
        .header-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .sheet-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .sheet-header img {
            width: 100%;
            max-width: 600px;
            height: auto;
        }
        .sheet-header p {
            display: inline-block;
            margin: 0 10px; /* Adjusts space between the items */
            font-size: 14px; /* Adjust font size as needed */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 5px;
            text-align: center;
            font-size: 12px;
            word-wrap: break-word;
        }
        th {
            background-color: #f8f9fa;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .signature {
            width: 30%;
            text-align: center;
        }
        .print-button {
            margin-bottom: 20px;
            text-align: center;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        .btn i {
        margin-right: 8px; /* Adjust the value as needed */
    }
        
        /* Print-specific adjustments */
        @media print {
            @page {
                size: landscape;
                margin: 0.5cm; /* Adjust margin to fit more content */
            }
            
            body {
                margin: 0;
                font-size: 10px; /* Reduce font size for print */
            }
            
            .button-group {
                display: none;
            }
            
            table, th, td {
                font-size: 10px; /* Further reduce font size */
                padding: 2px; /* Reduce padding to fit content */
            }
            
            th, td {
                white-space: nowrap; /* Prevents line breaks in print */
            }
            h2 {
                font-size: 16px;
            }
        }
    </style>
    <script>
        function printPage() {
            window.print();
        }
    </script>
</head>
<body>
<div class="button-group">
    <button type="button" class="btn btn-primary d-flex align-items-center" onclick="printPage()">
    طباعة <i class="fas fa-print" style="margin-right: 8px;"></i> 
    </button>
    <button type="button" class="btn btn-primary d-flex align-items-center" onclick="window.location.href='home.php'">
    الصفحة الرئيسية <i class="fas fa-home mr-2"></i> 
    </button>

</div>

    <div class="sheet-header">
        <img src="../images/header.png" alt="Header Image">
        <h2>     استمارة الغياب الشهري</h2>
        <p>الشهر: <?php echo $month; ?>، السنة: <?php echo $year; ?></p>
        <p>الفرع: <?php echo $branch_name; ?></p>
        <p>القسم: <?php echo $class_name; ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th colspan="7" > التعريف</th>
                <th colspan="14">الاسم الكامل</th>
                <th colspan="9">رقم الهاتف</th>
                <th colspan="9">رقم الهاتف 2 </th>
                <th colspan="2">01</th>
                <th colspan="2">02</th>
                <th colspan="2">03</th>
                <th colspan="2">04</th>
                <th colspan="2">05</th>
                <th colspan="2">06</th>
                <th colspan="2">07</th>
                <th colspan="2">08</th>
                <th colspan="2">09</th>
                <th colspan="2">10</th>
                <th colspan="2">11</th>
                <th colspan="2">12</th>
                <th colspan="2">13</th>
                <th colspan="2">14</th>
                <th colspan="2">15</th>
                <th colspan="2">16</th>
                <th colspan="2">17</th>
                <th colspan="2">18</th>
                <th colspan="2">19</th>
                <th colspan="2">20</th>
                <th colspan="2">21</th>
                <th colspan="2">22</th>
                <th colspan="2">23</th>
                <th colspan="2">24</th>
                <th colspan="2">25</th>
                <th colspan="2">26</th>
                <th colspan="2">27</th>
                <th colspan="2">28</th>
                <th colspan="2">29</th>
                <th colspan="2">30</th>
                <th colspan="2">31</th>

                <th colspan="8">عدد الغياب</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td colspan="7"><?php echo $student['student_id']; ?></td>
                <td colspan="14"><?php echo $student['student_name']; ?></td>
                <td colspan="9" contenteditable="true"></td>
                <td colspan="9" contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td colspan ="8" contenteditable="true"></td>

               
                
            </tr>
            <?php endforeach; ?>
            <tr>
            <td colspan="7"></td>
                <td colspan="14"></td>
                <td colspan="9" contenteditable="true"></td>
                <td colspan="9" contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td colspan ="8" contenteditable="true"></td>
            </tr>
          
        </tbody>
    </table>
   
</body>
</html>
