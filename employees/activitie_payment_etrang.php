<?php
// Include database connection
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

$response = []; // Initialize response array
$activities = []; // Initialize response array
$activitys = null;
$tot_price = 0;

// Handle GET request (fetch student and activity information)
// Handle GET request (fetch student and activity information)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];

    // SQL query to fetch student and activity details
    $sql = "SELECT 
                a.id AS id_act,
                s_e.name AS student_name, 
                s_e.phone, 
                sa.id AS student_activity_id, 
                s_e.id AS student_id, 
                a.activity_name, 
                sa.fee AS price, 
                sa.subscription_date
            FROM student_activities sa
            INNER JOIN students_etrang s_e ON sa.student_id_etrang = s_e.id
            INNER JOIN activities a ON sa.activity_id = a.id
            WHERE s_e.id = ?";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param('i', $student_id); // Bind student_id as an integer
    $stmt->execute();
    $result = $stmt->get_result();

    $activities = []; 
    $tot_price = 0;   

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;           
            $tot_price += $row['price']; 
        }
    } else {
        $activities = null;
        $tot_price = 0;
    }
    
    $stmt->close();


}


$bankList = [];
$sql = "SELECT account_id, bank_name FROM bank_accounts";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $bankList[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دفع رسوم الإشتراك </title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/amiri.css">
    <link rel="stylesheet" href="css/tajawal.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <link rel="stylesheet" href="css/jquery-ui.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }
        .container-main {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 1100px;
            margin: auto;
        }
        .header-title {
            font-family: 'Amiri', serif;
            font-size: 1.8rem;
            font-weight: bold;
        }
        .header-titlee {
            font-family: 'Amiri', serif;
            font-size: 1rem;
            font-weight: bold;
        }
        .form-select {
            font-size: 1rem;
            font-weight: bold;
            color: #1a73e8;
        }
        .input-group input {
            border-radius: 5px 0 0 5px;
            padding: 10px;
            border: 2px solid #1a73e8;
        }
        .input-group button {
            border-radius: 0 5px 5px 0;
            background-color: #1a73e8;
            color: #fff;
            padding: 0 20px;
            border: 2px solid #1a73e8;
        }
        .form-section {
            margin-bottom: 20px;
        }
        .form-section .bg-light {
            padding: 10px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            color: #333;
        }
        .form-section label {
            font-weight: bold;
            color: #1a73e8;
            display: block;
            margin-bottom: 5px;
        }
        .form-section div {
            font-size: 1.1rem;
            color: #555;
        }
        .payment-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .payment-info div {
            text-align: center;
            flex: 1;
        }
        .payment-info input,
        .payment-info select {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-top: 5px;
        }
        .months-card {
            background-color: #ffffff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .confirm-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .confirm-button:hover {
            background-color: #218838;
        }
        .section-title {
            font-family: 'Amiri', serif;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        .row-underline {
            border-bottom: 2px solid #1a73e8;
            margin-bottom: 20px;
        }
        .icon-left {
            float: left;
            font-size: 1.2rem;
            margin-right: 10px;
            color: #1a73e8;
        }
        .method-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        .method-section label {
            font-size: 1.1rem;
            color: #333;
        }
        .method-section select {
            padding: 8px;
            border-radius: 5px;
            border: 2px solid #1a73e8;
            width: 100%;
        }
        #selected-bank-name {
            margin-top: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            color: #1a73e8;
        }
        #etrangDropdown {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 150px;
            overflow-y: auto;

            border: 1px solid #ddd;
            background-color: #fff;
        }
        #agentDropdown {
            position: absolute;
            z-index: 1000;
            width: 94%;
            max-height: 150px;
            overflow-y: auto;

            border: 1px solid #ddd;
            background-color: #fff;
        }
    </style>
</head>
<body>

<div class="container-main">
    <!-- Header Section -->
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="header-title">  تسديد رسوم الإشتراك في نشاط أو دورة تكوينية للطلاب الأجانب </h1>
        <div>
                <a href="activitie_payment.php" class="btn bg-light">رجوع </a>
        </div>
        <div class="d-flex align-items-center">
            <a href="home.php" class="btn btn-primary d-flex align-items-center" style="margin-left: 15px;">
            <i class="bi bi-house-fill" style="margin-left: 5px;"></i>
                الرئيسية
            </a>
            <label class="form-select-title" for="financial-year" style="margin-left: 15px;">السنة المالية</label>
            <select id="financial-year" class="form-select w-100">
                <option><?php echo $last_year; ?></option>

            </select>
        </div>
    </div>
</div>
    
    <!-- Search Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="header-titlee">البحث عن الطالب</h2>
            <form method="GET" action="">
                <div class="input-group">
                    <input type="hidden" name="student_id" class="form-control" id="student_id" placeholder="رقم الهاتف (الرقم الشخصي)">
                    <input type="text" class="form-control" id="name_student" name="student_name" placeholder="أدخل اسم التلميذ" required>
                    <div id="agentDropdown" class="dropdown-menu mt-5"></div>
                    <button class="btn btn-outline-secondary border-2" type="submit">
                    <i class="bi bi-search"></i>

                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Info Section -->
    <?php if ((isset($activities))  && !empty($activities)): ?>
        <?php $firstActivity = $activities[0]; ?>
    <div class="row form-section">
        <div class="col-12 row-underline">
            <h2 class="section-title"> بيانات الطالب (ة):</h2>
        </div>
        <div class="col-12 d-flex justify-content-between text-center">
        <div>
                <label> رقم التعريف</label>
                <div><?php echo $firstActivity['student_id']; ?></div>
            </div>
            <div>
                <label>الاسم الكامل</label>
                <div><?php echo $firstActivity['student_name']; ?></div>
            </div>
            <div>
                <label>الرسوم</label>
                <div id="due-amount"><?php echo $tot_price ; ?> أوقية جديدة</div>
            </div>
        </div>
    </div>

    <!-- Payment Information Section -->
    <form method="POST" action="process_activitie_payment_ertanger.php">
        <input type="hidden" name="student_activity_id" value="<?php echo $firstActivity['student_id']; ?>">
        <input type="hidden" name="payment_method" id="payment_method">
        <input type="hidden" name="student_activitie_id" id="student_activitie_id">


        <div class="row form-section">
            <div class="col-6">
                <div class="payment-info">
                    <div>
                        <label for="arrears-paid">المبلغ المسدد</label>
                        <input type="text" name="paid_amount" id="arrears-paid" placeholder="0.00" oninput="calculateRemaining()">
                    </div>
                    <div>
                        <label for="arrears-remaining">الباقي</label>
                        <input type="text" name="remaining_amount" id="arrears-remaining" placeholder="0.00" readonly>
                    </div>
                </div>

                <div class="method-section mt-3">
                    <div>
                        <label for="method">طريقة الدفع</label>
                        <select id="method" name="payment_method" onchange="toggleBankModal(this.value)">
                            <option value="نقدي">نقدي</option>
                            <option value="بنكي">بنكي</option>
                        </select>
                    </div>
                    <!-- Display the selected bank name and value -->
                    <div id="selected-bank-name"></div>
                    <input type="hidden" id="selected-bank-id" name="bank">
                    <button type="submit" class="confirm-button">تأكيد العملية</button>
                </div>
            </div>

            <div class="col-6">
                <div class="months-card">
                    
                    <div>
                        <!-- <label>النشاط</label>
                        <div></div> -->
                        <div class="mb-3">
                        <label for="activity_id" class="form-label">اختر دورة أو نشاط</label>
                        <select class="form-select" id="activity_id" name="activity_id" required>
                            <option value="" disabled selected>اختر دورة أو نشاط</option>
                            <?php foreach ($activities as $activity): ?>
                                <option value="<?php echo $activity['activity_name']; ?>" data-price="<?php echo $activity['price']; ?>" data-id="<?php echo $activity['id_act']; ?>">
                                    <?php echo $activity['activity_name'] . '  ¬|¬  ' . 'تاريخ التسجيل: ' . $activity['subscription_date']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<!-- Bank Modal -->
<div class="modal fade" id="bankModal" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bankModalLabel">اختر البنك</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <select id="bank" name="bank" class="form-control" onchange="updateSelectedBankName()">
            <?php foreach ($bankList as $bank): ?>
                <option value="<?php echo $bank['account_id']; ?>"><?php echo $bank['bank_name']; ?></option>
            <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="selectBank()">تأكيد</button>
      </div>
    </div>
  </div>
</div>

<script src="js/jquery-3.5.1.min.js"></script>

<script src="js/jquery.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>

<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>



<!-- Include Bootstrap JS and dependencies -->
<script src="js/jquery-3.5.1.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('#name_student').on('input', function() {
            var agentPhone = $(this).val();

            if (agentPhone.length > 0) {
                $.ajax({
                    url: 'check_student_eranger.php',
                    type: 'POST',
                    data: { phone: agentPhone },
                    dataType: 'json',
                    success: function(response) {
                        var $dropdown = $('#agentDropdown');
                        $dropdown.empty();

                        if (response.matches && response.matches.length > 0) {
                            response.matches.forEach(function(agent) {
                                var agentItem = $('<a>', {
                                    class: 'dropdown-item agent-item',
                                    href: '#',
                                    text: agent.name,
                                    'data-agent-id': agent.id
                                });
                                $dropdown.append(agentItem);
                            });
                            $dropdown.show();
                        } else {
                            $dropdown.hide();
                        }
                    },
                    error: function() {
                        console.error('Error occurred during the AJAX call.');
                    }
                });
            } else {
                $('#agentDropdown').hide();
            }
        });

        $(document).on('click', '.agent-item', function(e) {
            e.preventDefault();
            var selectedAgentName = $(this).text();
            var selectedAgentId = $(this).data('agent-id');

            $('#name_student').val(selectedAgentName);
            $('#student_id').val(selectedAgentId);

            $('#agentDropdown').hide();
        });

        $(document).click(function(event) {
            if (!$(event.target).closest('#name_student, #agentDropdown').length) {
                $('#agentDropdown').hide();
            }
        });
    });


</script>

<script>
    document.getElementById('activity_id').addEventListener('change', function () {
        var selectedOption = this.options[this.selectedIndex];
        var price = selectedOption.getAttribute('data-price');
        $('#student_activitie_id').val(selectedOption.getAttribute('data-id'));
        document.getElementById('due-amount').textContent = price + ' أوقية جديدة';
    });


    function calculateRemaining() {
        var dueAmount = parseFloat(document.getElementById('due-amount').innerText) || 0;
        var paidAmount = parseFloat(document.getElementById('arrears-paid').value) || 0;
        var remainingAmount = dueAmount - paidAmount;
        document.getElementById('arrears-remaining').value = remainingAmount.toFixed(2);
    }

    function toggleBankModal(paymentMethod) {
        if (paymentMethod === 'بنكي') {
            var bankModal = new bootstrap.Modal(document.getElementById('bankModal'), {
                keyboard: false
            });
            bankModal.show();
        } else if (paymentMethod === 'نقدي') {
            clearSelectedBankName();
        }
    }

    function selectBank() {
        const bankSelect = document.getElementById('bank');
        const selectedBankName = bankSelect.options[bankSelect.selectedIndex].text;
        const selectedBankId = bankSelect.options[bankSelect.selectedIndex].value;
        document.getElementById('selected-bank-id').value = selectedBankId;
        document.getElementById('selected-bank-name').innerText = 'البنك المحدد: ' + selectedBankName;
    }

    function clearSelectedBankName() {
        document.getElementById('selected-bank-name').innerText = '';
    }
</script>

</body>
</html>

