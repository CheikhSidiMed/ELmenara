<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit(); 
}

$userid = $_SESSION['userid'];
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null; // Use role_id instead of role_name

$activities = [];
$activity_result = $conn->query("SELECT id, activity_name FROM activities");

if ($activity_result->num_rows > 0) {
    while ($row = $activity_result->fetch_assoc()) {
        $activities[] = $row;
    }
}
// Fetch job list from the jobs table
$jobList = [];
$sql = "SELECT id, job_name FROM jobs";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $jobList[] = $row;
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
    </style>
</head>
<body>

                            

<!-- Modal Structure -->
<div class="container mt-5" >
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header d-flex justify-content-between align-items-center">
            <h1 id="studentRegistrationLabe">تسجيل في دورة أو نشاط</h1>
            <button class="btn btn-primary home" onclick="window.location.href='home.php'">العودة إلى الصفحة الرئيسية</button>
        </div>
            <!-- <div class="modal-header">
                <h5 class="modal-title" id="subscribeActivityModalLabel">تسجيل في دورة أو نشاط</h5>
            </div> -->
            <form method="POST" action="subscribe_activity.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="student_name" class="form-label">اسم التلميذ</label>
                        <input type="text" class="form-control" id="name_student" name="student_name" placeholder="أدخل اسم التلميذ" required>
                        <div id="agentDropdown" class="dropdown-menu"></div>
                    </div>
                    <div class="mb-3">
                        <label for="activity_id" class="form-label">اختر دورة أو نشاط</label>
                        <select class="form-select" id="activity_id" name="activity_id" required>
                            <option value="" disabled selected>اختر دورة أو نشاط</option>
                            <?php foreach ($activities as $activity): ?>
                                <option value="<?php echo $activity['id']; ?>"><?php echo $activity['activity_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subscription_date" class="form-label">تاريخ التسجيل</label>
                        <input type="date" class="form-control" id="subscription_date" name="subscription_date" required>
                    </div>
                </div>
                <div class="modal-footer">
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
</script>
</body>
</html>
