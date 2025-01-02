<?php
include 'db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

$processed_year = $last_year;  
$sql = "SELECT month FROM processed_salaries WHERE year = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $processed_year);
$stmt->execute();
$result = $stmt->get_result();

$processed_months = [];
while ($row = $result->fetch_assoc()) {
    $processed_months[] = $row['month'];
}

?>

<!DOCTYPE html>
<html lang="ar">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدخال الرواتب</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sweetalert2.css"> 

    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f4f7f6;
            color: #333;
        }

        .main-container {
            margin: 40px auto;
            padding: 30px;
            border-radius: 15px;
            background-color: white;
            border: 2px solid #1BA078;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            transition: all 0.3s ease-in-out;
        }

        .header-title {
            font-size: 32px;
            font-weight: bold;
            color: #1BA078;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .icon-container img {
            width: 50px;
            margin-left: 15px;
        }

        .year-select {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .year-select button {
            background-color: #1BA078;
            color: white;
            border: 2px solid #1BA078;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 16px;
            font-family: 'Amiri', serif;
        }

        .confirm-box {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 40px;
            padding: 20px;
            border: 2px solid #1BA078;
            border-radius: 10px;
            color: #1BA078;
            font-size: 26px;
            font-weight: bold;
        }

        .confirm-box i {
            font-size: 32px;
            margin-right: 10px;
        }

        .months-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 30px;
        }

        .month-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background-color: #fff;
            border: 2px solid #1BA078;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease-in-out;
            font-family: 'Amiri', serif;
        }

        .month-box:hover {
            background-color: #f9f9f9;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .month-box span {
            font-size: 18px;
            font-weight: bold;
            color: #1BA078;
        }

        .month-box input[type="checkbox"] {
            transform: scale(1.4);
            accent-color: #1BA078;
        }

        /* Additional styling for hover on the checkboxes */
        .month-box:hover input[type="checkbox"] {
            transform: scale(1.6);
        }

        .month-box input[type="checkbox"]:checked + span {
            font-weight: bold;
            color: #14865b;
        }
        .btn-confirm {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            color: #1BA078;
            border: 2px solid #1BA078;
            border-radius: 10px;
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
            width: 100%;
            transition: all 0.3s ease-in-out;
            cursor: pointer;
        }

        .btn-confirm i {
            font-size: 32px;
            margin-right: 10px;
        }

        .btn-confirm:hover {
            background-color: #f9f9f9;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-confirm:focus {
            outline: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
        }

    </style>
</head>

<body>

    <div class="container main-container">
        <div class="row align-items-center">
            <div class="col-2 icon-container">
                <img src="../images/i.png" alt="icon">
            </div>
            <div class="col-7">
                <h2 class="header-title">إدخال الرواتب</h2>
            </div>
            <div class="year-select col-3 ">
                    <label class="form-select-title" for="financial-year" style="margin-left: 15px;">السنة المالية</label>
                    <select id="financial-year" class="form-select w-100">
                    <option><?php echo $last_year; ?></option>
                    </select>
            </div>
        </div>


        <div class="row justify-content-center">
            <div class="col-md-6">
                <button class="btn btn-confirm"><i class="bi bi-check"></i> تأكيد العملية</button>
            </div>
        </div>

        <div class="months-container">
            <?php
            $months = [
                'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 
                'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 
                'أكتوبر', 'نوفمبر', 'ديسمبر'
            ];

            foreach ($months as $index => $month_name) {
                $month_number = $index + 1; // Convert index to month number (1-12)
                $is_disabled = in_array($month_name, $processed_months) ? 'disabled' : ''; // Check if the month is processed
                ?>
                <div class="month-box <?php echo $is_disabled ? 'disabled' : ''; ?>">
                    <span><?php echo $month_name; ?></span>
                    <input type="checkbox" <?php echo $is_disabled; ?>>
                </div>
                <?php
            }
            ?>
        </div>

    </div>

    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/sweetalert2.min.js"></script>
    
<script>
document.querySelector('.btn-confirm').addEventListener('click', function() {
    let selectedMonths = [];
    let checkboxes = document.querySelectorAll('.month-box input[type="checkbox"]');

    checkboxes.forEach(checkbox => {
        if (checkbox.checked && !checkbox.disabled) {
            selectedMonths.push(checkbox.parentElement.querySelector('span').textContent.trim());
        }
    });

    if (selectedMonths.length > 0) {
        fetch('process_salaries.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ months: selectedMonths })  // Change  dynamically if needed
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Swal.fire notification on success
                Swal.fire({
                    icon: 'success',
                    title: 'نجاح',
                    text: data.message,
                    confirmButtonText: 'حسنًا'
                }).then(() => {
                    // Disable the processed months
                    checkboxes.forEach(checkbox => {
                        if (selectedMonths.includes(checkbox.parentElement.querySelector('span').textContent.trim())) {
                            checkbox.disabled = true;
                            checkbox.checked = false; // Uncheck the checkbox after processing
                        }
                    });
                });
            } else {
                // Swal.fire notification on failure
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: data.message,
                    confirmButtonText: 'حسنًا'
                });
            }
        })
        .catch(error => {
            // Swal.fire for any unexpected errors
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'حدث خطأ أثناء معالجة الرواتب',
                confirmButtonText: 'حسنًا'
            });
        });
    } else {
        // Swal.fire notification if no month is selected
        Swal.fire({
            icon: 'warning',
            title: 'تحذير',
            text: 'يرجى اختيار شهر واحد على الأقل',
            confirmButtonText: 'حسنًا'
        });
    }
});
</script>


</body>

</html>
