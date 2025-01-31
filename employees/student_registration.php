<?php
include 'db_connection.php'; // Include your database connection script

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Fetch branches for the dropdown
$branches = [];
$result = $conn->query("SELECT branch_id, branch_name FROM branches");
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}

// Close the connection
$conn->close();
?>


<?php
// Include database connection
include 'db_connection.php';
// Start the session
session_start();

// Check if the session variable 'userid' is set
if (!isset($_SESSION['userid'])) {
    // If the user is not logged in, redirect them to the login page
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit(); // Stop further execution
}

// Access session variables safely
$userid = $_SESSION['userid'];
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null; // Use role_id instead of role_name

// Now you can proceed with the rest of your page logic

// Fetch the list of activities for the dropdown
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
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل التلاميذ</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            background-color: #f4f7f6;
        }

        .main-container {
            margin: 20px auto;
            padding: 20px;
            border-radius: 15px;
            background-color: white;
            max-width: 900px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border: 2px solid #1BA078;
        }

        .header-title {
            font-size: 24px;
            font-weight: bold;
            color: #1BA078;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        form .form-group {
            margin-bottom: 15px;
        }

        form input, form select, form button {
            font-family: 'Amiri', serif;
        }

        .section-title {
            font-size: 20px;
            color: #1BA078;
            font-weight: bold;
            margin-top: 20px;
        }

        .form-control {
            border-radius: 10px;
            padding: 8px 12px;
            border: 2px solid #1BA078;
        }

        .form-check-input {
            transform: scale(1.3);
            accent-color: #1BA078;
        }

        .btn-primary {
            background-color: #1BA078;
            border: none;
            font-size: 18px;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #14865b;
        }

        .file-input-label {
            border: 2px solid #1BA078;
            border-radius: 10px;
            padding: 8px 12px;
            text-align: center;
            background-color: #fff;
            cursor: pointer;
            font-size: 16px;
            color: #1BA078;
            width: 100%;
        }

        .file-input input[type="file"] {
            display: none;
        }
    </style>
</head>

<body>

    <div class="container main-container">
        <h2 class="header-title"><i class="bi bi-user-plus"></i> تسجيل التلاميذ</h2>
        <form id="studentRegistrationForm" enctype="multipart/form-data" method="POST" action="student.php">
            
            <!-- Section 1: تعريف الوكيل -->
            <div class="section-title">تعريف الوكيل</div>
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="agentPhone">رقم الهاتف</label>
                        <input type="number" class="form-control" id="agentPhone" name="agentPhone" placeholder="أدخل رقم الهاتف">
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="noAgentCheckbox" name="noAgentCheckbox">
                        <label class="form-check-label" for="noAgentCheckbox">بدون وكيل</label>
                    </div>
                </div>
            </div>

            <!-- Section 2: تعريف الطالب -->
            <div class="section-title">تعريف الطالب</div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="studentId">رقم تعريف طالب</label>
                        <input type="number" class="form-control" id="studentId" name="studentId" placeholder="أدخل رقم تعريف طالب" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="studentName">الإسم الكامل</label>
                        <input type="text" class="form-control" id="studentName" name="studentName" placeholder="أدخل الإسم الكامل" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="partCount">عدد الأحزاب</label>
                        <input type="number" class="form-control" id="partCount" name="partCount" placeholder="أدخل عدد الأحزاب" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="gender">الجنس</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="">اختر الجنس</option>
                            <option value="ذكر">ذكر</option>
                            <option value="أنثى">أنثى</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="birthDate">تاريخ الميلاد</label>
                        <input type="date" class="form-control" id="birthDate" name="birthDate" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="birthPlace">مكان الميلاد</label>
                        <input type="text" class="form-control" id="birthPlace" name="birthPlace" placeholder="أدخل مكان الميلاد" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="registrationDate">تاريخ التسجيل</label>
                        <input type="date" class="form-control" id="registrationDate" name="registrationDate" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="branch">الفرع</label>
                        <select class="form-control" id="branch" name="branch" required>
                            <option value="">اختر الفرع</option>
                            <!-- Populate branch options dynamically -->
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="class">القسم</label>
                        <select class="form-control" id="class" name="class" required>
                            <option value="">اختر القسم</option>
                            <!-- Populate class options dynamically -->
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group file-input">
                        <label for="studentPhoto" class="file-input-label">الصورة</label>
                        <input type="file" class="form-control" id="studentPhoto" name="studentPhoto" required>
                    </div>
                </div>
            </div>

            <!-- Section 3: طبيعة التسديد -->
            <div class="section-title">طبيعة التسديد</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="paymentNature" id="naturalPayment" value="طبيعي" required>
                        <label class="form-check-label" for="naturalPayment">طبيعي</label>
                    </div>
                    <?php if ($role_id == 1): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="paymentNature" id="exemptPayment" value="معفى" required>
                        <label class="form-check-label" for="exemptPayment">معفى</label>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div id="monthlyFeesSection" class="form-row">
            <!-- Section 4: الرسوم الشهرية -->
            <div id="monthlyFeesSection" class="section-title">الرسوم الشهرية</div>
            <div id="monthlyFeesSection" class="row">
                <div class="col-md-4">
                    <div  id="monthlyFeesSection" class="form-group">
                        <label for="fees">الرسوم</label>
                        <input type="number" class="form-control" id="fees" name="fees" placeholder="أدخل الرسوم" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="discount">الخصم</label>
                        <input type="number" class="form-control" id="discount" name="discount" placeholder="أدخل الخصم" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="remaining">المتبقى</label>
                        <input type="number" class="form-control" id="remaining" name="remaining" placeholder="أدخل المتبقى" required readonly>
                    </div>
                </div>
            </div>

        </form>
    </div>
      <div class="form-group">
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
                    </div>
                    
          

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <!-- JavaScript for logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle agent phone field based on the checkbox
            document.getElementById('noAgentCheckbox').addEventListener('change', function() {
                var agentPhoneField = document.getElementById('agentPhone').parentElement;
                if (this.checked) {
                    agentPhoneField.style.display = 'none';
                } else {
                    agentPhoneField.style.display = 'block';
                }
            });
        });

        // Calculate remaining amount
        function calculateRemaining() {
            var fees = parseFloat(document.getElementById('fees').value) || 0;
            var discount = parseFloat(document.getElementById('discount').value) || 0;
            document.getElementById('remaining').value = (fees - discount).toFixed(2);
        }

        document.getElementById('fees').addEventListener('input', calculateRemaining);
        document.getElementById('discount').addEventListener('input', calculateRemaining);
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch branches and classes data
    fetch('fetch_branches_classes.php')
        .then(response => response.json())
        .then(data => {
            const branches = data.branches;
            const classes = data.classes;

            const branchSelect = document.getElementById('branch');
            const classSelect = document.getElementById('class');
            const feesInput = document.getElementById('fees'); // The fees input field

            // Populate branches dropdown
            branches.forEach(branch => {
                const option = document.createElement('option');
                option.value = branch.branch_id;
                option.textContent = branch.branch_name;
                branchSelect.appendChild(option);
            });

            // Populate classes dropdown based on selected branch
            branchSelect.addEventListener('change', function() {
                const selectedBranchId = this.value;

                // Clear current class options
                classSelect.innerHTML = '<option value="">اختر القسم</option>';

                // Filter classes based on selected branch and populate class dropdown
                classes.filter(classItem => classItem.branch_id == selectedBranchId)
                    .forEach(classItem => {
                        const option = document.createElement('option');
                        option.value = classItem.class_id;
                        option.textContent = classItem.class_name;
                        classSelect.appendChild(option);
                    });

                // Clear the fees input when the branch changes
                feesInput.value = '';
            });

            // Populate the fees input based on selected class
            classSelect.addEventListener('change', function() {
                const selectedClassId = this.value;

                // Find the selected class and set the fees
                const selectedClass = classes.find(classItem => classItem.class_id == selectedClassId);
                if (selectedClass) {
                    feesInput.value = selectedClass.Price; // Set the fees input to the class price
                } else {
                    feesInput.value = ''; // Clear the fees input if no class is selected
                }
            });
        })
        .catch(error => console.error('Error fetching branch and class data:', error));
});

</script>
<script>
    document.getElementById('fees').addEventListener('input', calculateRemaining);
    document.getElementById('discount').addEventListener('input', calculateRemaining);

    function calculateRemaining() {
        var fees = parseFloat(document.getElementById('fees').value) || 0;
        var discount = parseFloat(document.getElementById('discount').value) || 0;
        var remaining = fees - discount;
        document.getElementById('remaining').value = remaining.toFixed(2);
    }

    document.getElementById('naturalPayment').addEventListener('change', toggleMonthlyFeesSection);
    document.getElementById('exemptPayment').addEventListener('change', toggleMonthlyFeesSection);

    function toggleMonthlyFeesSection() {
        var exemptPayment = document.getElementById('exemptPayment').checked;
        var monthlyFeesSection = document.getElementById('monthlyFeesSection');

        if (exemptPayment) {
            monthlyFeesSection.style.display = 'none';
        } else {
            monthlyFeesSection.style.display = 'flex';
        }
    }

    // Initialize the monthly fees section display based on the default selected payment nature
    window.addEventListener('load', function() {
        toggleMonthlyFeesSection();
    });
    </script>





<style>
    .section-separator {
        height: 2px;
        background-color: #e9ecef;
        margin: 20px 0;
    }

    @media (max-width: 767px) {
        .form-row {
            display: block;
        }
        .form-group {
            width: 100%;
        }
    }
</style>
</body>

</html>


