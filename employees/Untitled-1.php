    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">

    <link rel="stylesheet" href="css/amiri.css">
    <link rel="stylesheet" href="css/tajawal.css">

    <link rel="stylesheet" href="css/fontawesome.min.css">

    <link rel="stylesheet" href="css/jquery-ui.min.css">

    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">

    <script src="js/jquery.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>

<link rel="stylesheet" href="css/sweetalert2.css"> 
<script src="js/sweetalert2.min.js"></script>

<script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

    <i class="bi bi-house-fill" style="margin-left: 5px;"></i>

    <i class="bi bi-search"></i>

    <i class="bi bi-pencil-square"></i>


    
$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = ""; 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}
<link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">

<label class="form-select-title" for="financial-year" style="margin-left: 15px;">السنة المالية</label>
                <select id="financial-year" class="form-select w-100">
                <option><?php echo $last_year; ?></option>
                </select>




                <link rel="stylesheet" href="../assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/themify-icons.css">

    class="view-icon"
    <i class="bi bi-file-earmark-text-fill">
            <i class="bi bi-check-square"></i>
            <i class="bi bi-house-gear-fill"></i>
            <i class="bi bi-house-door-fill"></i>
            <i class="bi bi-printer-fill"></i>

            <div class="col-2 icon-container">
                <img src="../images/i.png" alt="icon">
            </div>