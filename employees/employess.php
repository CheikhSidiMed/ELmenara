<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


// Fetch job filter from the request
$job_filter = null;
if (isset($_GET['job_id'])) {
    $job_filter = $_GET['job_id'];
}

// Fetch employees data
$employees = [];
$employees_query = "
    SELECT e.full_name, e.id, e.phone, e.balance, j.job_name
    FROM employees e
    JOIN jobs j ON e.job_id = j.id
";

if ($job_filter) {
    $employees_query .= " WHERE e.job_id = ?";
}

$stmt = $conn->prepare($employees_query);

if ($job_filter) {
    $stmt->bind_param('i', $job_filter);
}

$stmt->execute();
$result_employees = $stmt->get_result();

while ($row = $result_employees->fetch_assoc()) {
    $employees[] = $row;
}

// Fetch jobs data for the filter checkboxes
$jobs = [];
$jobs_query = "SELECT id, job_name FROM jobs";
$result_jobs = $conn->query($jobs_query);

if ($result_jobs->num_rows > 0) {
    while ($row = $result_jobs->fetch_assoc()) {
        $jobs[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الموظفين</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>


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

        .search-container input[type="text"] {
            border-radius: 30px;
            border: 2px solid #1BA078;
            padding: 12px 25px;
            width: 100%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease-in-out;
        }

        .search-container input[type="text"]::placeholder {
            color: #999;
            font-style: italic;
        }

        .search-container input[type="text"]:focus {
            border-color: #14865b;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            outline: none;
        }

        .filter-container {
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filter-container label {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }

        .filter-container input[type="checkbox"] {
            margin-left: 5px;
            transform: scale(1.2);
            accent-color: #1BA078;
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

        .view-icon {
            background-color: #1BA078;
            color: white;
            border-radius: 50%;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .view-icon:hover {
            background-color: #14865b;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .main-container h2 {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
        }

        .table-container {
            overflow-x: auto;
        }
    </style>
</head>

<body>

    <div class="container main-container">
        <div class="row align-items-center">
            <div class="col-2 icon-container">
                <img src="../images/o.jpeg" alt="icon">
            </div>
            <div class="col-10">
                <h2 class="header-title">الموظفين</h2>
            </div>
        </div>
        <!-- Home Button -->
        <div class="row justify-content-center mb-3">
            <div class="col-auto">
                <a href="home.php" class="btn btn-primary" style="border-radius: 30px; background-color: #1BA078; border: 2px solid #1BA078;">
                    <i class="bi bi-house-door-fill"></i> الصفحة الرئيسية
                </a>
            </div>
        </div>
        <div class="row search-container">
            <div class="col-12">
                <input type="text" id="searchInput" placeholder="البحث عن موظف : رقم الهاتف أو الاسم الشخصي">
            </div>
        </div>

        <form method="GET" action="">
            <div class="row filter-container">
                <?php foreach ($jobs as $job): ?>
                <div class="col d-flex align-items-center">
                    <input type="radio" name="job_id" value="<?php echo $job['id']; ?>" onchange="this.form.submit()" <?php echo isset($job_filter) && $job_filter == $job['id'] ? 'checked' : ''; ?>>
                    <label><?php echo $job['job_name']; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </form>

        <div class="row table-container">
            <div class="col-12">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>العملية</th>
                            <th>الاسم الكامل</th>
                            <th>الوظيفة</th>
                            <th>رقم الهاتف</th>
                            <th>رصيد الحساب</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
    <?php foreach ($employees as $employee): ?>
    <tr>
        <td>
            <span class="view-icon" onclick="viewEmployee('<?php echo $employee['id']; ?>')">&#128065;</span>
        </td>
        <td><?php echo $employee['full_name']; ?></td>
        <td><?php echo $employee['job_name']; ?></td>
        <td><?php echo $employee['phone']; ?></td>
        <td><?php echo number_format($employee['balance']); ?></td>
    </tr>
    <?php endforeach; ?>
</tbody>

                </table>
            </div>
        </div>
    </div>

    <script src="JS/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="jquery-ui.min.js"></script>
    <script>
        $(function() {
        $("#employeeSearch").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "employee_autocomplete.php",
                    dataType: "json",
                    data: {
                        term: request.term
                    },
                    success: function(data) {
                        // Display matching employees in the table
                        var tableBody = $("#employeeTableBody");
                        tableBody.empty();
                        $.each(data, function(index, employee) {
                            var row = "<tr>" +
                                "<td><span class='view-icon'>&#128065;</span></td>" +
                                "<td>" + employee.full_name + "</td>" +
                                "<td>" + employee.job_name + "</td>" +
                                "<td>" + employee.phone + "</td>" +
                                "<td>" + new Intl.NumberFormat().format(employee.balance) + "</td>" +
                                "</tr>";
                            tableBody.append(row);
                        });
                    }
                });
            },
            minLength: 2,
            appendTo: ".search-container", // Ensure the suggestions are appended within the container
            select: function(event, ui) {
                var selectedValue = ui.item.value;
                // Additional actions based on selection (optional)
            }
        });

        // Detect when the search field is cleared
        $("#employeeSearch").on('input', function() {
            var searchTerm = $(this).val().trim();

            if (searchTerm === "") {
                // If search is cleared, fetch and display all employees
                $.ajax({
                    url: "employee_autocomplete.php",
                    dataType: "json",
                    data: {
                        term: "" // Empty term to fetch all employees
                    },
                    success: function(data) {
                        var tableBody = $("#employeeTableBody");
                        tableBody.empty();
                        $.each(data, function(index, employee) {
                            var row = "<tr>" +
                                "<td><span class='view-icon'>&#128065;</span></td>" +
                                "<td>" + employee.full_name + "</td>" +
                                "<td>" + employee.job_name + "</td>" +
                                "<td>" + employee.phone + "</td>" +
                                "<td>" + new Intl.NumberFormat().format(employee.balance) + "</td>" +
                                "</tr>";
                            tableBody.append(row);
                        });
                    }
                });
            }
            });
            });
    </script>
<script>
        $('#searchInput').on('input', function() {
        let value = $(this).val().toLowerCase();
        $('#employeeTableBody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
function viewEmployee(employeeId) {
    window.location.href = 'employees_preview.php?employee_id=' + employeeId;
}
</script>


</body>

</html>

