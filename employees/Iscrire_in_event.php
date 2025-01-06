<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit(); 
}

$userid = $_SESSION['userid'];
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;

$activities = [];
$activity_result = $conn->query("SELECT id, activity_name, price FROM activities");

if ($activity_result->num_rows > 0) {
    while ($row = $activity_result->fetch_assoc()) {
        $activities[] = $row;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل في دورة أو نشاط</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
            text-align: right;
        }
        .container {
            background-color: #f2f2f2;
            border: 1px solid #0056b3;
            padding: 20px;
            border-radius: 10px;
        }
        /* Modal Custom Styling */
        .modal-header {
            background-color: #0056b3;
            border-radius: 10px;
            color: white;
            padding: 1rem;
        }
        .moda {
            padding-left: 2rem;
            padding-right: 2rem;
        }
        .modal-footer {
            background-color: #f1f1f1;
        }
        .form-label {
            font-weight: bold;
        }
        .btn-primary {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        #agentDropdown {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 150px;
            overflow-y: auto;

            border: 1px solid #ddd;
            background-color: #fff;
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
        /* Custom styling for dropdown */
        
        /* Fade-in effect for the modal */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }
        .home {
        font-size: 1em;
        padding: 8px 16px;
        color: #0056b3;
        background-color: #fff;
        font-size: 1.1em;
        font-weight: bold;

        }
        input[type="checkbox"] {
            width: 1.5rem;
            height: 1.5rem;
        }

    </style>
</head>
<body>

                            

<!-- Modal Structure -->
<div class="container mt-5" >
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header d-flex justify-content-between align-items-center mb-4">
            <h1 id="studentRegistrationLabe">تسجيل في دورة أو نشاط</h1>
            <button class="btn btn-primary home" onclick="window.location.href='home.php'">العودة إلى الصفحة الرئيسية</button>
        </div>
            <!-- <div class="modal-header">
                <h5 class="modal-title" id="subscribeActivityModalLabel">تسجيل في دورة أو نشاط</h5>
            </div> -->
            <form method="POST" id="activityForm"  action="subscribe_activity.php">

                    <div class="form-check col-3 h6 my-3">
                        <input type="checkbox" class="form-check-input custo-checkbox" id="toggleForm" name="toggle_form">
                        <div class="form-check-label custom-checkbox" for="toggleForm">تغيير إلى الاشتراك للطلبة الأجانب</div>
                    </div>

                    <div class="modal-body" id="localForm">
                        <div class="mb-3">
                            <label for="student_name" class="form-label">اسم التلميذ</label>
                            <input type="text" class="form-control" id="name_student" name="student_name" placeholder="أدخل اسم التلميذ" required>
                            <input type="hidden" class="form-control" id="name_id" name="student_id" required>
                            <div id="agentDropdown" class="dropdown-menu"></div>
                        </div>
                    </div>


                    <!-- Section pour les étudiants étrangers -->
                    <div class="modal-body" id="foreignForm" style="display: none;">
                        <div class="row d-flex align-items-center justify-content-between justify-content-center">
                        <div class="col-8 mb-3" id="ifNotExistSearch">
                            <label for="etr_name" class="form-label">اسم التلميذ</label>
                            <input type="text" class="form-control" id="etrang_student" name="etr_name" placeholder="أدخل اسم التلميذ">
                            <input type="hidden" class="form-control" id="etrang_id" name="etrang_id" required>
                            <div id="etrangDropdown" class="dropdown-menu"></div>
                        </div>

                            <!-- Case à cocher pour afficher/masquer -->
                            <div class="form-check mb-3 col-2">
                                <input type="checkbox" class="form-check-input custo-checkbox" id="toggleIfNotExist" name="toggleIfNotExist">
                                <label class="form-check-label custom-checkbox" for="toggleIfNotExist"> تسجيل الطالب</label>
                            </div>

                        </div>
                        <!-- if not exist -->
                        <div id="ifNotExistSection" style="display: none;">
                            <div class="row">
                                <hr>
                                <div class="form-group col-4">
                                    <label for="studentName">الاسم الكامل</label>
                                    <input type="text" class="form-control" name="studentName" placeholder="أدخل االاسم الكامل">
                                </div>

                                <div class="form-group col-4" id="studentPhoneContainer">
                                    <label for="nni"> الرقم الوطني (NNI)</label>
                                    <input type="text" class="form-control" name="NNI" placeholder="أدخل الرقم الوطني (NNI)">
                                </div>
                                <div class="form-group col-4" id="studentPhoneContainer">
                                    <label for="phone">رقم الهاتف </label>
                                    <input type="text" class="form-control" name="phone" placeholder="أدخل رقم الهاتف">
                                </div>
                                <div class="form-group col-4" id="studentPhoneContainer">
                                    <label for="wh">رقم الواتساب </label>
                                    <input type="text" class="form-control" name="wh" placeholder="أدخل رقم الواتساب">
                                </div>
                                <div class="form-group col-4">
                                    <label for="studentPhoto">الصورة</label>
                                    <input type="file" class="form-control" name="studentPhoto" >
                                </div>

                                <div class="form-group col-4">
                                    <label for="birthDate">تاريخ الميلاد</label>
                                    <input type="date" class="form-control" id="birthDate" name="birthDate">
                                </div>
                                <hr style="margin-top: 20px;">
                                <br><br>
                            </div>
                        </div>
                        <!-- end if -->

                    </div>
                    <div class="mb-3">
                        <label for="subscription_date" class="form-label">تاريخ التسجيل</label>
                        <input type="date" class="form-control" id="subscription_date" name="subscription_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="activity_id" class="form-label">اختر دورة أو نشاط</label>
                        <select class="form-select" id="activity_id" name="activity_id" required>
                            <option value="" disabled selected>اختر دورة أو نشاط</option>
                            <?php foreach ($activities as $activity): ?>
                                <option value="<?php echo $activity['id']; ?>" data-fee="<?php echo $activity['price']; ?>">
                                    <?php echo $activity['activity_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-4">
                            <label for="discount" class="form-label">الخصم</label>
                            <input type="number" class="form-control" id="discount" name="discount" required>
                        </div>
                        <div class="col-4"> 
                            <label for="fee" class="form-label">السعر</label>
                            <input type="number" class="form-control" id="fee" disabled>
                        </div>
                        <div class="col-4">
                            <label for="after_discount" class="form-label">بعد الخصم </label>
                            <input type="number" class="form-control" id="after_discount" name="after_discount" readonly>
                        </div>

                    </div>
                <div class="modal-footer mt-4">
                    <button type="submit" style="margin-left: 79%; margin-right: 1%;"  class="btn btn-primary moda">تسجيل</button>
                    <button type="reset" class="btn btn-secondary moda" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- Bootstrap JS (including Popper) -->
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
                    url: 'check_student.php',
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
                                 agentItem.on('click', function (e) {
                                    e.preventDefault(); 
                                    $('#name_id').val(agent.id); 
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
            $('#name_student').val(selectedAgentName);
            $('#agentDropdown').hide();
        });

        $(document).click(function(event) {
            if (!$(event.target).closest('#name_student, #agentDropdown').length) {
                $('#agentDropdown').hide();
            }
        });
    });

    document.getElementById('toggleForm').addEventListener('change', function () {
        const localForm = document.getElementById('localForm');
        const foreignForm = document.getElementById('foreignForm');
        const form = document.getElementById('activityForm');
        const localRequiredFields = localForm.querySelectorAll('[required]');
        const foreignRequiredFields = foreignForm.querySelectorAll('[required]');

        if (this.checked) {
            // Show foreign form and update action
            localForm.style.display = 'none';
            foreignForm.style.display = 'block';
            form.action = 'subscribe_activity_etrang.php';

            // Adjust required attributes
            localRequiredFields.forEach(field => field.required = false);
            foreignRequiredFields.forEach(field => field.required = true);
        } else {
            localForm.style.display = 'block';
            foreignForm.style.display = 'none';
            form.action = 'subscribe_activity.php';

            // Adjust required attributes
            foreignRequiredFields.forEach(field => field.required = false);
            localRequiredFields.forEach(field => field.required = true);
        }
    });


    document.getElementById('toggleIfNotExist').addEventListener('change', function () {
        const ifNotExistSection = document.getElementById('ifNotExistSection');
        const ifNotExistSearch = document.getElementById('ifNotExistSearch');

        if (this.checked) {
            ifNotExistSection.style.display = 'block';
            ifNotExistSearch.style.display = 'none';
        } else {
            ifNotExistSection.style.display = 'none';
            ifNotExistSearch.style.display = 'block';
        }
    });



    $(document).ready(function() {
        $('#etrang_student').on('input', function() {
            var agentPhone = $(this).val();

            if (agentPhone.length > 0) {
                $.ajax({
                    url: 'check_student_eranger.php',
                    type: 'POST',
                    data: { phone: agentPhone },
                    dataType: 'json',
                    success: function(response) {
                        var $dropdown = $('#etrangDropdown');
                        $dropdown.empty();

                        if (response.matches && response.matches.length > 0) {
                            response.matches.forEach(function(agent) {
                                var agentItem = $('<a>', {
                                    class: 'dropdown-item agent-item',
                                    href: '#',
                                    text: agent.name,
                                    'data-agent-id': agent.id
                                });
                                 agentItem.on('click', function (e) {
                                    e.preventDefault(); 
                                    $('#etrang_id').val(agent.id); 
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
                $('#etrangDropdown').hide();
            }
        });

        $(document).on('click', '.agent-item', function(e) {
            e.preventDefault();
            var selectedAgentName = $(this).text();
            $('#etrang_student').val(selectedAgentName);
            $('#etrangDropdown').hide();
        });

        $(document).click(function(event) {
            if (!$(event.target).closest('#etrang_student, #etrangDropdown').length) {
                $('#etrangDropdown').hide();
            }
        });
    });


</script>

<script>
    document.getElementById('activity_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const fee = selectedOption.getAttribute('data-fee');        
        document.getElementById('fee').value = fee ? fee : '';
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const feeInput = document.getElementById('fee');
        const discountInput = document.getElementById('discount');
        const afterDiscountInput = document.getElementById('after_discount');

        function calculateAfterDiscount() {
            const fee = parseFloat(feeInput.value) || 0; // Récupère le prix
            const discount = parseFloat(discountInput.value) || 0; // Récupère le discount
            const afterDiscount = fee - discount;

            afterDiscountInput.value = afterDiscount.toFixed(2); 
        }

        discountInput.addEventListener('input', calculateAfterDiscount);
    });
</script>


</body>
</html>
