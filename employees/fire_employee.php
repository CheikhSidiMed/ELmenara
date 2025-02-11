<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


// Vérifiez la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
error_log(print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fire_employee'])) {
    // Récupérer et sécuriser les entrées utilisateur
    $subscription_id = $conn->real_escape_string($_POST['subscription_id']);
    $terminationReason = $conn->real_escape_string($_POST['terminationReason']);
    $financialReceivables = $conn->real_escape_string($_POST['financialReceivables']);


    // Vérifiez si l'employé existe
    $sql = "SELECT * FROM employees WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $subscription_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();

        // Insérez les détails de l'employé dans la table suspended_employees
        $insertSQL = "INSERT INTO suspended_employees 
            (employee_number, full_name, balance, phone, job_id, salary, subscription_date, id_number, suspension_date, suspension_reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";
        $stmtInsert = $conn->prepare($insertSQL);
        $stmtInsert->bind_param(
            'ssdsidsss',
            $employee['employee_number'],
            $employee['full_name'],
            $financialReceivables,
            $employee['phone'],
            $employee['job_id'],
            $employee['salary'],
            $employee['subscription_date'],
            $employee['id_number'],
            $terminationReason
        );

        if ($stmtInsert->execute()) {
            $deleteSQL = "DELETE FROM employees WHERE id = ?";
            $stmtDelete = $conn->prepare($deleteSQL);
            $stmtDelete->bind_param('i', $subscription_id);

            if ($stmtDelete->execute()) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'تم إنهاء خدمة الموظف بنجاح ونقله إلى قائمة الإيقاف.']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'فشل في حذف الموظف من قائمة الموظفين.']);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'فشل في إضافة الموظف إلى قائمة الإيقاف.']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على الموظف.']);
    }

    $conn->close();
    exit;
}
?>


<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إجراءات فصل موظف</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sweetalert2.css">

    <link rel="stylesheet" href="css/jquery-base-ui.css">
    <style>
        body {
            font-family: 'Cairo', serif;
            direction: rtl;
            background-color: #ECF0F1;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .container {
            margin: 40px auto;
            padding: 20px;
            max-width: 1000px;
            background-color: white;
            border: 2px solid #2C3E50;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .header-title {
            font-size: 26px;
            font-weight: bold;
            color: #2C3E50;
            margin-bottom: 20px;
        }

        .search-container {
            margin-bottom: 20px;
            position: relative;
        }

        .search-container input {
            width: 100%;
            padding: 10px;
            border: 2px solid #2C3E50;
            border-radius: 8px;
            padding-left: 40px;
            font-size: 16px;
        }

        .search-container i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #2C3E50;
        }

        .form-section-title {
            font-size: 22px;
            font-weight: bold;
            color: #2C3E50;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 19px;
            font-weight: bold;

            color: #333;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #2C3E50;
            border-radius: 8px;
            font-size: 16px;
        }

        .checkbox-container {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #2C3E50;
        }

        .checkbox-container input {
            transform: scale(1.5);
            accent-color: #2C3E50;
        }

        .btn-confirm {
            background-color: #F39C12;
            color: white;
            border: 2px solid #F39C12;
            border-radius: 8px;
            font-size: 20px;
            padding: 15px;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .btn-confirm:hover {
            background-color: #D68910;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-confirm i {
            font-size: 24px;
        }

        .financial-section {
            margin-top: 20px;
        }

        .financial-section .form-group {
            margin-bottom: 20px;
        }

    </style>

</head>

<body>

    <div class="container">
        <h2 class="header-title"><i class="bi bi-person-lines-fill"></i> إجراءات فصل موظف</h2>

        <!-- Search Field -->
        <div class="search-container">
            <input type="text" id="employeeSearch" placeholder="البحث عن موظف">
            <i class="bi bi-search"></i>
        </div>

        <!-- Employee Information Section -->
        <h3 class="form-section-title">بيانات الموظف:</h3>
        <div class="row">
            <div class="col-md-4 form-group">
                <label for="employeeName">اسم الموظف:</label>
                <input type="text" id="employeeName" readonly>
            </div>
            <div class="col-md-4 form-group">
                <label for="employeePhone">رقم الهاتف:</label>
                <input type="text" id="employeePhone" readonly>
            </div>
            <div class="col-md-4 form-group">
                <label for="subscription_date">تاريخ التسجيل:</label>
                <input type="date" id="subscription_date" readonly>
            </div>
        </div>
        <input type="hidden" id="subscription_id" readonly>

        <!-- Termination Section -->
        <div class="checkbox-container">
            <!-- <input type="checkbox" id="terminateEmployee"> -->
            <label for="terminateEmployee">فصل الموظف</label>
            <i class="bi bi-check"></i>
        </div>

        <div class="form-group">
            <label for="terminationReason">سبب الفصل:</label>
            <textarea id="terminationReason" rows="3"></textarea>
        </div>

        <!-- Financial Section -->
        <h3 class="form-section-title">المستحقات المالية:</h3>
        <div class="financial-section">
            <div class="form-group">
            <input type="text" name="financialReceivables" id="financialReceivables" placeholder="أدخل المستحقات المالية" readonly>
            </div>
        </div>

        <!-- Confirm Button -->
        <div class="row justify-content-center">
            <div class="col-md-6">
                <button id="fireEmployeeBtn" class="btn-confirm">
                    <i class="bi bi-check"></i> تأكيد العملية
                </button>
            </div>
        </div>

        <!-- View Fired Employees Button -->
        <div class="row justify-content-center mt-3">
            <div class="col-md-6">
                <a href="suspended_employees.php" class="btn-confirm" style="text-decoration: none;">
                    <i class="bi bi-people-fill"></i> عرض الموظفين المفصولين
                </a>
            </div>
        </div>

    </div>

    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/sweetalert2.min.js"></script>

    <script>
        $(function () {
            $("#employeeSearch").autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: 'autocompleteeeeeee.php',
                        dataType: 'json',
                        data: {
                            term: request.term
                        },
                        success: function (data) {
                            response(data);
                        },
                        error: function () {
                            alert("Error fetching data.");
                        }
                    });
                },
                minLength: 2,
                select: function (event, ui) {
                    // Fill the fields with the selected employee's details
                    $("#employeeName").val(ui.item.label);
                    $("#employeePhone").val(ui.item.phone);
                    $("#subscription_date").val(ui.item.subscription_date);
                    $("#subscription_id").val(ui.item.id);

                    // Fetch salary and calculate financial receivables
                    $.ajax({
                        url: 'get_salary.php',
                        type: 'POST',
                        data: { employeePhone: ui.item.phone },
                        dataType: 'json',
                        success: function (response) {
                            const salary = response.salary;
                            const daysInMonth = new Date().getDate(); // Get current day of the month
                            const totalDaysInMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate(); // Days in the current month
                            const dailyRate = salary / totalDaysInMonth;
                            const earnedAmount = dailyRate * daysInMonth; // Calculate earnings based on days worked

                            // Display calculated financial receivables
                            $('#financialReceivables').val(earnedAmount.toFixed(2));
                        },
                        error: function () {
                            alert("Error fetching salary.");
                        }
                    });
                }
            });
        });
    </script>
<script>
    $(document).on('click', '#fireEmployeeBtn', function () {
        const employeePhone = $('#employeePhone').val().trim();
        const terminationReason = $('#terminationReason').val().trim();
        const subscription_id = $('#subscription_id').val().trim();
        const financialReceivables = $('#financialReceivables').val().trim();

        console.log({
            employeePhone,
            terminationReason,
            financialReceivables
        });


        // Envoi des données via AJAX
        $.ajax({
            url: 'fire_employee.php',
            type: 'POST',
            data: {
                fire_employee: true,
                financialReceivables,
                terminationReason,
                subscription_id
            },
            dataType: 'json',
            beforeSend: function () {
                Swal.fire({
                    title: 'جار المعالجة...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire('نجاح', response.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('خطأ', response.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                Swal.fire('خطأ', 'حدث خطأ أثناء معالجة الطلب.', 'error');
                console.error('Erreur AJAX:', status, error);
                console.error(xhr.responseText);
            }
        });
    });
</script>


</body>

</html>
