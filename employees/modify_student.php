<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


$id = $_GET['id'];

$sql = "
    SELECT s.student_name, s.part_count, s.registration_date, s.regstration_date_count, a.agent_id, a.agent_name, a.phone AS student_phone, a.phone, a.phone_2, a.whatsapp_phone
    FROM students s
    LEFT JOIN agents a ON s.agent_id = a.agent_id
    WHERE s.id = $id
";
$result = $conn->query($sql);
$student = $result->fetch_assoc();

$agentsQuery = "SELECT agent_id, agent_name FROM agents";
$agentsResult = $conn->query($agentsQuery); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_name = $_POST['student_name'];
    $part_count = $_POST['part_count'];
    $registration_date = $_POST['registration_date'];
    $regstration_date_count = $_POST['regstration_date_count'];
    $student_phone = $_POST['student_phone'] ?? '0'; 
    $agent_id = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;

// Prepare the SQL query
$sql = "
    UPDATE students SET 
        student_name = ?,
        part_count = '$part_count',
        registration_date = '$registration_date',
        regstration_date_count = ?,
        phone = ?,
        agent_id = ?
    WHERE id = ?
";
$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssssi",
    $student_name,
    $regstration_date_count,
    $student_phone,
    $agent_id,
    $id
);

    // if ($_POST['agent_id'] !== '') {
    //     $agent_id = $_POST['agent_id'];
    //     $agent_name = $_POST['agent_name'];
    //     $phone = $_POST['phone'];
    //     $phone_2 = $_POST['phone_2'];
    //     $whatsapp_phone = $_POST['whatsapp_phone'];
    //     $query = "UPDATE agents 
    //     SET agent_name = '$agent_name', phone = '$phone', phone_2 = '$phone_2', 
    //         whatsapp_phone = '$whatsapp_phone' 
    //     WHERE agent_id = '$agent_id'";
    //     $conn->query($query);
    // }
// Execute the query
if ($stmt->execute()) {
        $response = array('success' => true, 'message' => 'تمت العملية بنجاح');
    } else {
        $response = array('success' => false, 'message' => 'Oops! Something went wrong: ' . $conn->error);
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تعديل بيانات الطالب</title>
<link rel="stylesheet" href="css/sweetalert2.css"> 
<script src="js/sweetalert2.min.js"></script>
<script src="js/jquery-3.5.1.min.js"></script>
<link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
<!-- Add these links in the <head> of your HTML -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link href="css/bootstrap-icons.css" rel="stylesheet">
<link href="fonts/bootstrap-icons.css" rel="stylesheet">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }
    .full-width-divider {
        border-top: 3px solid #2e59d9; /* Change the color and thickness as needed */
        width: 100%;              /* Ensures it spans the full width */
        margin: 20px 0;           /* Adds space above and below the line */
    }

    h2 {
        font-family: 'Amiri', serif;
        text-align: center;
        margin-bottom: 20px;
        font-size: 2rem;
        color: #4e73df;
    }

    .container {
        margin-top: 50px;
        display: flex;
        justify-content: center;
    }

    .card {
        width: 100%;
        max-width: 1000px;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .form-horizontal .form-group {
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }

    .form-horizontal .form-group label {
        width: 25%;
        min-width: 120px;
        margin-bottom: 0;
        font-weight: 500;
    }

    .form-horizontal .form-group .form-control {
        flex: 1;
        border-radius: 0.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .form-horizontal .row {
        display: flex;
        flex-wrap: wrap;
    }

    .form-horizontal .col-md-6 {
        flex: 0 0 50%;
        max-width: 50%;
        padding: 5px 10px;
    }

    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
        border-radius: 0.25rem;
    }

    .btn-primary:hover {
        background-color: #2e59d9;
        border-color: #2653d4;
    }

    .card-header {
        background-color: #4e73df;
        color: white;
        font-size: 1.5rem;
        text-align: center;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }
</style>

<script>
    $(document).ready(function() {
        // Handle branch change
        $('#branch_id').change(function() {
            var branchId = $(this).val();
            $.ajax({
                url: 'fetch_classes.php',
                type: 'POST',
                data: { branch_id: branchId },
                success: function(response) {
                    $('#class_id').html(response);
                }
            });
        });

        // Set initial class options based on the current branch
        $('#branch_id').trigger('change');
        
        // Update remaining field based on fees and discount
        $('#fees, #discount').on('input', function() {
            var fees = parseFloat($('#fees').val()) || 0;
            var discount = parseFloat($('#discount').val()) || 0;
            var remaining = fees - discount;
            $('#remaining').val(remaining >= 0 ? remaining : 0);
        });

        // Update payment fields when payment nature changes
        $('#payment_nature').change(function() {
            if ($(this).val() == 'معفى') {
                $('#fees').val(0);
                $('#discount').val(0);
                $('#remaining').val(0);
            }
        });

        // Update fees when level changes
        $('#level_id').change(function() {
            var selectedOption = $(this).find(':selected');
            var fee = selectedOption.data('fee'); // Get fee from data attribute
            $('#fees').val(fee); // Update fees input
            $('#remaining').val(fee - (parseFloat($('#discount').val()) || 0)); // Update remaining
        });

        // Trigger change on page load to set initial fee based on selected level
        $('#level_id').trigger('change');
    });
</script>
</head>
<body>
    <div class="container"  style="direction: rtl;">
        <div class="card" >
            <div class="card-header">تعديل بيانات الطالب</div>
            <div class="card-body">
                <form method="POST" class="form-horizontal">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="student_name" style="margin-left: 30px;">اسم الطالب</label>
                                <input type="text" class="form-control" id="student_name" name="student_name" value="<?php echo htmlspecialchars($student['student_name']); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="part_count" style="margin-left: 30px;">عدد الأجزاء</label>
                                <input type="number" class="form-control" id="part_count" name="part_count" value="<?php echo htmlspecialchars($student['part_count'] ?? 0); ?>" min="0" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="student_phone" style="margin-left: 30px;"> الهاتف</label>
                                <input type="text" class="form-control" id="student_phone" name="student_phone" value="<?php echo htmlspecialchars($student['student_phone'] ?? 0); ?>" required>
                            </div>
                        </div>

                        <!-- Registration Date -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="regstration_date_count" style="margin-left: 30px;">تاريخ التسجيل</label>
                                <input type="date" class="form-control" id="regstration_date_count" name="regstration_date_count" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($student['regstration_date_count']))); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-10">
                            <div class="form-group">
                                <label for="registration_date" style="margin-left: 30px;">تاريخ التسدد </label>
                                <input type="date" class="form-control" id="registration_date" name="registration_date" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($student['registration_date']))); ?>" required>
                            </div>
                        </div>
                        <hr>
                        <div class="full-width-divider"></div> <!-- Divider -->
                        <div class="col-md-10">
                            <div class="form-group">
                                <label for="agent_id" style="margin-left: 30px;">اختر الوكيل</label>
                                <select class="form-control searchable-select" id="agent_id" name="agent_id">
                                    <option value="">اختر الوكيل</option>
                                    <?php while ($agent = $agentsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $agent['agent_id']; ?>" 
                                            <?php echo ($student['agent_id'] == $agent['agent_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($agent['agent_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <!-- Agent Phone 
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="agent_name" style="margin-left: 30px;"> الوكيل</label>
                                <input type="text" class="form-control" id="agent_name" name="agent_name" value="<?php echo htmlspecialchars($student['agent_name'] ?? '-'); ?>" >
                                </div>
                        </div>

                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" style="margin-left: 30px;"> الهاتف 1</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? '-'); ?>" >
                                </div>
                        </div>

                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone_2" style="margin-left: 30px;"> الهاتف 2</label>
                                <input type="text" class="form-control" id="phone_2" name="phone_2" value="<?php echo htmlspecialchars($student['phone_2'] ?? '-'); ?>" >
                                </div>
                        </div>
                        <input type="hidden" class="form-control" id="agent_id" name="agent_id" value="<?php echo htmlspecialchars($student['agent_id'] ?? ''); ?>" >

                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="whatsapp_phone" style="margin-left: 30px;"> الهاتف wh</label>
                                <input type="text" class="form-control" id="whatsapp_phone" name="whatsapp_phone" value="<?php echo htmlspecialchars($student['whatsapp_phone'] ?? '-'); ?>" >
                                </div>
                        </div> -->

                    </div>

                    <!-- Submit Button -->
                     <div class="row">

                         <div class="form-group" style="margin-left: 75%; margin-right: 7%;" >
                             <button type="button" class="btn btn-secondary" onclick="history.back();">العودة</button>
                        </div>
                        <div class="form-group ">
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                        </div>
                     </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (!empty($response)): ?>
        <script>
            Swal.fire({
                icon: '<?php echo $response['success'] ? "success" : "error"; ?>',
                title: '<?php echo $response['success'] ? "تمت العملية بنجاح" : "خطأ"; ?>',
                text: '<?php echo $response['message']; ?>',
                confirmButtonText: 'موافق'
            }).then(() => {
                if (<?php echo $response['success'] ? "true" : "false"; ?>) {
                    window.location.href = 'display_students.php';
                }
            });
        </script>
    <?php endif; ?>
    <script>
    $(document).ready(function() {
        $('.searchable-select').select2({
            placeholder: "اختر الوكيل",
            allowClear: true,
            width: '100%' // Adjust width to match your design
        });
    });
</script>
 
</body>
</html>
