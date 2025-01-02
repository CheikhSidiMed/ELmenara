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



<!doctype html>
<html class="no-js" lang="en">
<head>
<meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>محظرة المنارة و الرباط</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="../images/menar.png">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="css/sweetalert2.css"> 
    <script src="js/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="css/bootstrap-4.0.0.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="js/jquery-3.5.1.min.js"></script>
<!-- Include jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="js/jquery-3.5.1.min.js"></script>


    <link rel="stylesheet" href="../assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/themify-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/metisMenu.css">
    <link rel="stylesheet" href="../assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="../assets/css/slicknav.min.css">
    <link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all" />
    <link rel="stylesheet" href="../assets/css/typography.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/default-css.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <script src="../assets/js/vendor/modernizr-2.8.3.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        .main-content {
            height: 110vh;
            background-image: url('../images/menar 2.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .header-area {
            background-color: transparent;
            margin-top: -20px;
            border: none;
        }
        .header-area .nav-btn span {
            background-color: white;
        }
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background-color: #f7f7f7;
            border-bottom: 1px solid #ddd;
        }
        .modal-footer {
            border-top: 1px solid #ddd;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
        .section-separator {
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- preloader area start -->
    <div id="preloader">
        <div class="loader"></div>
    </div>
    <!-- preloader area end -->
    <!-- page container area start -->
    <div class="page-container">
        <!-- sidebar menu area start -->
        <div class="sidebar-menu">
            <div class="sidebar-header">
                <div class="logo">
                    <a href="home.php"><img src="../images/menar.png" alt="logo"></a> 
                </div>
            </div>
            <div class="main-menu">
                <div class="menu-inner">
                <nav>
    <ul class="metismenu" id="menu">

        <!-- Full Access for role_id = 1 (Admin) -->
        <?php if($role_id == 1): ?>
        <li class="active">
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-plus"></i><span>تسجيل</span></a>
            <ul class="collapse">
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-sitemap"></i> الفروع و الأقسام</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="#" data-toggle="modal" data-target="#k"><i class="fa fa-plus-circle"></i>  إضافة فرع</a></li>
                        <li><a href="#" data-toggle="modal" data-target="#addClassModal"><i class="fa fa-list"></i> إضافة قسم</a></li>
                        <li><a href="display_branches_classes.php"><i class="fa fa-th-list"></i> لائحة الفروع و الأقسام</a></li>
                        <li><a href="manage_classes.php"><i class="fa-solid fa-chalkboard"></i>إدارة الصفوف</a></li>
                        <li><a href="manage_branches.php"><i class="fa-solid fa-code-branch"></i>إدارة الفروع</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-user"></i> التلاميذ</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="add_student.php" ><i class="fa fa-user-plus"></i> تسجيل التلاميذ</a></li>
                        <li><a href="display_students.php"><i class="fa fa-edit"></i> تحديث التسجيل</a></li>
                        <li><a href="update_cls_level.php"><i class="fa fa-arrows-alt"></i> نقل الطلاب بين الفروع و الأقسام</a></li>
                        <li><a href="display_studentss.php"><i class="fa fa-user-times"></i> فصل تلميذ</a></li>
                        <li><a href="display_studentsss.php"><i class="fa fa-pause-circle"></i> تعليق تلميذ</a></li>
                        <li><a href="restore_student.php"><i class="fa fa-user-check"></i> استعادة تلميذ</a></li>
                    </ul>
                </li>
                <li>
        <a href="agents.php"><i class="fa fa-plus-circle"></i> تسجيل الوكلاء</a>
    </li>



                <li><a href="#" data-toggle="modal" data-target="#f"><i class="fa fa-user-plus"></i> إنشاء حساب موظف</a></li>
                <li><a href="manage_donations.php" ><i class="fa fa-dollar-sign"></i> إنشاء حساب مداخيل</a></li>
                <li><a href="#" data-toggle="modal" data-target="#b"><i class="fa fa-money-bill"></i> إنشاء حساب مصاريف</a></li>
                <li><a href="expense_donations.php" ><i class="fas fa-wallet"></i>إدارة حسابات المصاريف</a></li>
                <li><a href="#" data-toggle="modal" data-target="#c"><i class="fa fa-credit-card"></i> إنشاء حساب بنكي جديد</a></li>
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-book"></i> دورات و الأنشطة</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="#" data-toggle="modal" data-target="#createActivityModal"><i class="fa fa-plus-square"></i> إنشاء دورة أو نشاط </a></li>
                        <li><a href="Iscrire_in_event.php" ><i class="fa fa-user-plus"></i> تسجيل في دورة أو نشاط </a></li>
                        <li><a href="list_activities.php"><i class="fa fa-list"></i> لائحة دورات و الأنشطة </a></li>
                        <li><a href="student_activities.php"><i class="fa fa-list"></i> لائحة الطلاب المسجلين </a></li>
                    </ul>
                </li>
                <li class="dropdown">
    <a href="#" data-toggle="collapse" aria-expanded="true">
        <i class="fas fa-layer-group"></i> المستويات
    </a>
    <ul class="collapse list-unstyled">
        <li>
            <a href="#" data-toggle="modal" data-target="#Levels">
                <i class="fas fa-level-up-alt"></i> إضافة مستوى
            </a>
        </li>
        <li>
            <a href="levels.php">
                <i class="fas fa-list-ul"></i> لائحة المستويات
            </a>
        </li>
    </ul>
</li>

            </ul>
        </li>
        <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-edit"></i><span>تعديل</span></a>
            <ul class="collapse">
                
                <li><a href="discounts_list.php"><i class="fa fa-tags"></i> لائحة تخفيضات </a></li>
                <li><a href="up_payment_nature.php"><i class="fa fa-tags"></i>إدارة طبيعة الدفع</a></li>

                <li><a href="view_agents.php"><i class="fa fa-user-tie"></i> بيانات الوكلاء</a></li>
                <li><a href="view_data.php"><i class="fa fa-book"></i> بيانات المحظرة</a></li>
                <li><a href="create_academic_year.php"><i class="fas fa-folder-open"></i>فتح السنة الدراسية</a></li>
            </ul>
        </li>
 
    <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user-cog"></i><span>شؤون الطلاب</span></a>
    <ul class="collapse">
    <li><a href="weekly_followup.php"><i class="fa fa-calendar-week"></i> استمارة المتابعة الأسبوعية </a></li>
        <li><a href="monthly_followup.php"><i class="fa fa-calendar-alt"></i> استمارة الحصيلة الشهرية</a></li>
        <li><a href="Monthly_rating.php"><i class="fa fa-chart-line"></i> استمارة التقويم الشهري </a></li>
        <li><a href="Teheji.php"><i class="fa fa-user-check"></i> استمارة التقويم للتهجي</a></li>
        <li><a href="absent.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
        <li><a href="absence_student.php"><i class="fa fa-calendar-times"></i> إدارة غياب الطلاب</a></li>
        <li><a href="quarterly_selection.php"><i class="fa fa-file-alt"></i>  الحصيلة الفصلية</a></li>

    </ul>
</li>
    </li>

        <!-- Menu items accessible to both role_id = 1 and role_id = 2 -->
        <?php elseif($role_id == 2): ?>
        <li class="active">
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-plus"></i><span>تسجيل</span></a>
            <ul class="collapse">
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-sitemap"></i> الفروع و الأقسام</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="#" data-toggle="modal" data-target="#k"><i class="fa fa-plus-circle"></i>  إضافة فرع</a></li>
                        <li><a href="#" data-toggle="modal" data-target="#addClassModal"><i class="fa fa-list"></i> إضافة قسم</a></li>
                        <li><a href="display_branches_classes.php"><i class="fa fa-th-list"></i> لائحة الفروع و الأقسام</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-user"></i> التلاميذ</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="add_student.php" data-toggle="modal" data-target=""><i class="fa fa-user-plus"></i> تسجيل التلاميذ</a></li>
                        <li><a href="display_students.php"><i class="fa fa-edit"></i> تحديث التسجيل</a></li>
                        <li><a href="update_cls_level.php"><i class="fa fa-arrows-alt"></i> نقل الطلاب بين الفروع و الأقسام</a></li>
                        <li><a href=""><i class="fa fa-user-times"></i> فصل تلميذ</a></li>
                        <li><a href=""><i class="fa fa-pause-circle"></i> تعليق تلميذ</a></li>
                        <li><a href=""><i class="fa fa-user-check"></i> استعادة تلميذ</a></li>

                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-book"></i> دورات و الأنشطة</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="#" data-toggle="modal" data-target="#createActivityModal"><i class="fa fa-plus-square"></i> إنشاء دورة أو نشاط </a></li>
                        <li><a href="Iscrire_in_event.php" ><i class="fa fa-user-plus"></i> تسجيل في دورة أو نشاط </a></li>
                        <li><a href="list_activities.php"><i class="fa fa-list"></i> لائحة دورات و الأنشطة </a></li>
                    </ul>
                </li>
                <li class="dropdown">
    <a href="#" data-toggle="collapse" aria-expanded="true">
        <i class="fas fa-layer-group"></i> المستويات
    </a>
    <ul class="collapse list-unstyled">
        <li>
            <a href="#" data-toggle="modal" data-target="#Levels">
                <i class="fas fa-level-up-alt"></i> إضافة مستوى
            </a>
        </li>
        <li>
            <a href="levels.php">
                <i class="fas fa-list-ul"></i> لائحة المستويات
            </a>
        </li>
    </ul>
</li>

            </ul>
        </li>
        <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-edit"></i><span>تعديل</span></a>
            <ul class="collapse">
                
                
                <li><a href="view_agents.php"><i class="fa fa-user-tie"></i> بيانات الوكلاء</a></li>
                <li><a href="view_data.php"><i class="fa fa-book"></i> بيانات المحظرة</a></li>
            </ul>
        </li>
 
    <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user-cog"></i><span>شؤون الطلاب</span></a>
    <ul class="collapse">
    <li><a href="weekly_followup.php"><i class="fa fa-calendar-week"></i> استمارة المتابعة الأسبوعية </a></li>
        <li><a href="monthly_followup.php"><i class="fa fa-calendar-alt"></i> استمارة الحصيلة الشهرية</a></li>
        <li><a href="Monthly_rating.php"><i class="fa fa-chart-line"></i> استمارة التقويم الشهري </a></li>
        <li><a href="Teheji.php"><i class="fa fa-user-check"></i> استمارة التقويم للتهجي</a></li>
        <li><a href="absent.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
        <li><a href="quarterly_selection.php"><i class="fa fa-file-alt"></i>  الحصيلة الفصلية</a></li>

    </ul>
</li>
    </li>

        
        <li>
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user"></i><span> المستخدم</span></a>
            <ul class="collapse">
                <!-- User-related options -->
                <li class="dropdown">
    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-cogs"></i>   الإجراءات </a>
    <ul class="collapse list-unstyled">
    <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> تسجيل الخروج </a></li>


</ul>



        <!-- Restricted Access for role_id = 2 -->
     
         <!-- Menu items accessible to both role_id = 1 and role_id = 3 -->
         <?php elseif($role_id == 3): ?>
            <li class="active">
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-plus"></i><span>تسجيل</span></a>
            <ul class="collapse">
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-sitemap"></i> الفروع و الأقسام</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="#" data-toggle="modal" data-target="#k"><i class="fa fa-plus-circle"></i>  إضافة فرع</a></li>
                        <li><a href="#" data-toggle="modal" data-target="#addClassModal"><i class="fa fa-list"></i> إضافة قسم</a></li>
                        <li><a href="display_branches_classes.php"><i class="fa fa-th-list"></i> لائحة الفروع و الأقسام</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-user"></i> التلاميذ</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="add_student.php" data-toggle="modal" data-target=""><i class="fa fa-user-plus"></i> تسجيل التلاميذ</a></li>
                        <li><a href="display_students.php"><i class="fa fa-edit"></i> تحديث التسجيل</a></li>
                    </ul>
                </li>
                <li>
        <a href="agents.php"><i class="fa fa-plus-circle"></i> تسجيل الوكلاء</a>
    </li>



                <li><a href="#" data-toggle="modal" data-target="#f"><i class="fa fa-user-plus"></i> إنشاء حساب موظف</a></li>
                <li><a href="#" data-toggle="modal" data-target="#a"><i class="fa fa-dollar-sign"></i> إنشاء حساب مداخيل</a></li>
                <li><a href="#" data-toggle="modal" data-target="#b"><i class="fa fa-money-bill"></i> إنشاء حساب مصاريف</a></li>
                <li><a href="#" data-toggle="modal" data-target="#c"><i class="fa fa-credit-card"></i> إنشاء حساب بنكي جديد</a></li>
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-book"></i> دورات و الأنشطة</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="#" data-toggle="modal" data-target="#createActivityModal"><i class="fa fa-plus-square"></i> إنشاء دورة أو نشاط </a></li>
                        <li><a href="Iscrire_in_event.php" ><i class="fa fa-user-plus"></i> تسجيل في دورة أو نشاط </a></li>
                        <li><a href="list_activities.php"><i class="fa fa-list"></i> لائحة دورات و الأنشطة </a></li>
                    </ul>
                </li>
                <li class="dropdown">
    <a href="#" data-toggle="collapse" aria-expanded="true">
        <i class="fas fa-layer-group"></i> المستويات
    </a>
    <ul class="collapse list-unstyled">
        <li>
            <a href="#" data-toggle="modal" data-target="#Levels">
                <i class="fas fa-level-up-alt"></i> إضافة مستوى
            </a>
        </li>
        <li>
            <a href="levels.php">
                <i class="fas fa-list-ul"></i> لائحة المستويات
            </a>
        </li>
    </ul>
</li>

            </ul>
        </li>
        <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-edit"></i><span>تعديل</span></a>
            <ul class="collapse">
                
                <li><a href="discounts_list.php"><i class="fa fa-tags"></i> لائحة تخفيضات </a></li>
                <li><a href="view_agents.php"><i class="fa fa-user-tie"></i> بيانات الوكلاء</a></li>
                <li><a href="view_data.php"><i class="fa fa-book"></i> بيانات المحظرة</a></li>
            </ul>
        </li>
 
    <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user-cog"></i><span>شؤون الطلاب</span></a>
    <ul class="collapse">
    <li><a href="weekly_followup.php"><i class="fa fa-calendar-week"></i> استمارة المتابعة الأسبوعية </a></li>
        <li><a href="monthly_followup.php"><i class="fa fa-calendar-alt"></i> استمارة الحصيلة الشهرية</a></li>
        <li><a href="Monthly_rating.php"><i class="fa fa-chart-line"></i> استمارة التقويم الشهري </a></li>
        <li><a href="Teheji.php"><i class="fa fa-user-check"></i> استمارة التقويم للتهجي</a></li>
        <li><a href="absent.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
        <li><a href="quarterly_selection.php"><i class="fa fa-file-alt"></i>  الحصيلة الفصلية</a></li>

    </ul>
</li>
    </li>
        <li>
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-balance-scale"></i><span> المحاسبة</span></a>
            <ul class="collapse">
            <li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-keyboard"></i> عمليات الإدخال</a>

<ul class="collapse list-unstyled">
<li><a href="student_payment.php"><i class="fa fa-money-bill-wave"></i>   تسديد رسوم التلاميذ </a></li>
<li><a href="Family_payment.php"><i class="fa fa-hand-holding-usd"></i>   التسديد الأسري</a></li>
<li><a href="activitie_payment.php"><i class="fa fa-credit-card"></i>   تسديد رسوم الأنشطة </a></li>
<li><a href="operation.php"><i class="fa fa-calculator"></i>   تسجيل عملية حسابية</a></li>
<li><a href="Expense operation.php"><i class="fa fa-calculator"></i>     عمليات المصاريف </a></li>
<li><a href="payments.php"><i class="fa fa-calculator"></i> تاريخ الدفع للطلاب </a></li>



</ul>
<li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-tasks"></i>  عمليات المعاينة</a>

<ul class="collapse list-unstyled">
<li><a href="banks.php"><i class="fa fa-university"></i>     الخزينة و البنوك </a></li>
<li><a href="students_accounts.php"><i class="fa fa-user-graduate"></i>    حسابات الطلاب </a></li>
<li><a href="employess.php"><i class="fa fa-user-tie"></i>     حسابات الموظفين </a></li>
<li><a href="Expense Accounts.php"><i class="fa fa-receipt"></i>  حسابات المصاريف </a></li>
<li><a href="Activities.php"><i class="fa fa-futbol"></i>      حسابات الأنشطة </a></li>
</ul>
</li>
<li class="dropdown">
<a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-cogs"></i>   الإجراءات </a>

<ul class="collapse list-unstyled">
<li><a href="employees_salary.php"><i class="fa fa-money-bill-wave"></i>     إدخال الرواتب </a></li>
<li><a href="fire_employee.php"><i class="fa fa-user-slash"></i>     فصل موظف </a></li>

<li><a href="Modify a calculation.php"><i class="fa fa-calculator"></i>      تعديل عملية حسابية </a></li>
</ul>
</li>
<li class="dropdown">
<a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-file-invoice-dollar"></i>   التقارير المالية </a>

<ul class="collapse list-unstyled">
<li><a href="Daily.php"><i class="fa fa-calendar-day"></i>      اليومية </a></li>
<li><a href="Debt report.php"><i class="fa fa-user-times"></i>      الطلاب المدينين </a></li>
<li><a href="exempted student report.php"><i class="fa fa-user-check"></i>        الطلاب المعفيين </a></li>
<li><a href="Report of the month.php"><i class="fa fa-chart-line"></i>        التقرير المالي الشهري </a></li>
</ul>
</li>
</ul>
        </li>

       

       
        <li>
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user"></i><span> المستخدم</span></a>
            <ul class="collapse">
                <!-- User-related options -->
                <li class="dropdown">
    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-cogs"></i>   الإجراءات </a>
    <ul class="collapse list-unstyled">
    <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> تسجيل الخروج </a></li>


</ul>



        <!-- Restricted Access for role_id = 2 -->
        <?php endif; ?>

        <!-- This section will be visible only to role_id = 1 -->
        <?php if($role_id == 1): ?>
        <li>
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-balance-scale"></i><span> المحاسبة</span></a>
            <ul class="collapse">
            <li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-keyboard"></i> عمليات الإدخال</a>

<ul class="collapse list-unstyled">
<li><a href="student_payment.php"><i class="fa fa-money-bill-wave"></i>   تسديد رسوم التلاميذ </a></li>
<li><a href="Family_payment.php"><i class="fa fa-hand-holding-usd"></i>   التسديد الأسري</a></li>
<li><a href="activitie_payment.php"><i class="fa fa-credit-card"></i>   تسديد رسوم الأنشطة </a></li>
<li><a href="operation.php"><i class="fa fa-calculator"></i>   تسجيل عملية حسابية</a></li>
<li><a href="Expense operation.php"><i class="fa fa-calculator"></i>     عمليات المصاريف </a></li>
<li><a href="donations.php"><i class="fa fa-hand-holding-heart"></i>     تسجيل عملية تبرع </a></li>




</ul>
<li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-tasks"></i>  عمليات المعاينة</a>

<ul class="collapse list-unstyled">
<li><a href="banks.php"><i class="fa fa-university"></i>     الخزينة و البنوك </a></li>
<li><a href="students_accounts.php"><i class="fa fa-user-graduate"></i>    حسابات الطلاب </a></li>
<li><a href="employess.php"><i class="fa fa-user-tie"></i>     حسابات الموظفين </a></li>
<li><a href="donations_ccounts.php"><i class="fa fa-receipt"></i>  حسابات مداخيل </a></li>
<li><a href="Expense Accounts.php"><i class="fa fa-receipt"></i> حسابات المصاريف </a></li>
<li><a href="receipts.php"><i class="fa fa-file-invoice"></i>  قائمة الإيصالات</a></li>
<li><a href="Activities.php"><i class="fa fa-futbol"></i> حسابات الأنشطة </a></li>
</ul>
</li>
<li class="dropdown">
<a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-cogs"></i>   الإجراءات </a>

<ul class="collapse list-unstyled">
<li><a href="employees_salary.php"><i class="fa fa-money-bill-wave"></i>     إدخال الرواتب </a></li>
<li><a href="fire_employee.php"><i class="fa fa-user-slash"></i>     فصل موظف </a></li>

<li><a href="Modify a calculation.php"><i class="fa fa-calculator"></i>      تعديل عملية حسابية </a></li>
</ul>
</li>
<li class="dropdown">
<a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-file-invoice-dollar"></i>   التقارير المالية </a>

<ul class="collapse list-unstyled">
<li><a href="Daily.php"><i class="fa fa-calendar-day"></i>      اليومية </a></li>
<li><a href="Debt report.php"><i class="fa fa-user-times"></i>      الطلاب المدينين </a></li>
<li><a href="exempted student report.php"><i class="fa fa-user-check"></i>        الطلاب المعفيين </a></li>
<li><a href="Report of the month.php"><i class="fa fa-chart-line"></i>        التقرير المالي الشهري </a></li>
</ul>
</li>
</ul>
        </li>

        <li>
            <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user"></i><span> المستخدم</span></a>
            <ul class="collapse">
                <!-- User-related options -->
                <li class="dropdown">
    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-cogs"></i>   الإجراءات </a>

    <ul class="collapse list-unstyled">
    <li><a href="users.php"><i class="fa fa-users"></i> المستخدمين </a></li>
<li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> تسجيل الخروج </a></li>


    </ul>
</li>
            </ul>
        </li>
        <?php endif; ?>

    </ul>
</nav>

                </div>
            </div>
        </div>
        <!-- sidebar menu area end -->
        <!-- main content area start -->
        <div class="main-content">
            <!-- header area start -->
            <div class="header-area">
                <div class="nav-btn pull-left">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
            <!-- header area end -->
            <!-- page title area start -->
        <!-- main content area end -->
        <!-- footer area start-->
        <!-- footer area end-->
    </div> 
    <!-- page container area end -->
    <!-- offset area start -->
    <div class="offset-area">
        <div class="offset-close"><i class="ti-close"></i></div>
    </div>
    <!-- offset area end -->
    <!-- jquery latest version -->
    <script src="../assets/js/vendor/jquery-2.2.4.min.js"></script>
    <!-- bootstrap 4 js -->
    <script src="../assets/js/popper.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script src="../assets/js/owl.carousel.min.js"></script>
    <script src="../assets/js/metisMenu.min.js"></script>
    <script src="../assets/js/jquery.slimscroll.min.js"></script>
    <script src="../assets/js/jquery.slicknav.min.js"></script>
    <!-- others plugins -->
    <script src="../assets/js/plugins.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <!-- Modal HTML -->
<!-- Modal HTML END -->
<div class="modal fade" id="k" tabindex="-1" role="dialog" aria-labelledby="c" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="branchModalLabel">   إضافة فرع جديد </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <form action="submit_branch.php" method="post">
    <div class="form-group">
        <label for="accountNumber"> إسم الفرع</label>
        <input type="text" class="form-control" id="account_number" name="branchName"  placeholder="أدخل  إسم الفرع" required>
    </div>
    <button type="submit" class="btn btn-primary">حفظ</button>
</form>

            </div>
        </div>
    </div>
</div>

<!-- Add Section Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" role="dialog" aria-labelledby="addClassModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addClassModalLabel">إضافة قسم</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="addClassForm" method="POST" action="add_class.php">
          <!-- Branch Dropdown -->
          <div class="form-group">
            <label for="branchSelect">اختر الفرع</label>
            <select class="form-control" id="branchSelect" name="branch_id" required>
            <?php
include 'db_connection.php'; // Include your database connection script

// Fetch branches for the dropdown
$branches = [];
$result = $conn->query("SELECT branch_id, branch_name FROM branches");
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}

// Close the connection
$conn->close();
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var branchSelect = document.getElementById('branchSelect');
    var branches = <?php echo json_encode($branches); ?>;

    branches.forEach(function(branch) {
        var option = document.createElement('option');
        option.value = branch.branch_id;
        option.textContent = branch.branch_name;
        branchSelect.appendChild(option);
    });
});
</script>
</select>
          </div>

          <!-- Class Name Field -->
          <div class="form-group">
            <label for="className">اسم القسم</label>
            <input type="text" class="form-control" id="class_name" name="class_name" placeholder="أدخل اسم القسم" required>
          </div>

          

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
            <button type="submit" class="btn btn-primary">إضافة القسم</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<!-- Modal for subscribing to an activity -->
<div class="modal fade" id="subscribeActivityModal" tabindex="-1" aria-labelledby="subscribeActivityModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="subscribeActivityModalLabel">تسجيل في دورة أو نشاط</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="subscribe_activity.php">
        <div class="modal-body">
          <div class="mb-3">
            <label for="name_student" class="form-label">اسم التلميذ</label>
            <input type="text" class="form-control" id="name_student" name="name" placeholder="أدخل اسم التلميذ" required>
            <div id="agentDropdown" class="dropdown-menu" style="display: none;"></div>

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
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
          <button type="submit" class="btn btn-primary">تسجيل</button>
        </div>
      </form>
    </div>
  </div>
</div>



<!-- Scripts for jQuery and jQuery UI -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<script>
$(document).ready(function () {
    $('#name_student').autocomplete({
        source: function (request, response) {
            $.ajax({
                url: 'fetchh.php',
                type: 'GET',
                dataType: 'json',
                data: { term: request.term },
                success: function (data) {
                    response(data); // Pass the data to the autocomplete function
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("Autocomplete error: ", textStatus, errorThrown);
                }
            });
        },
        minLength: 2, // Search after 2 characters
        select: function (event, ui) {
            // When a student is selected, set the input value
            $('#name_student').val(ui.item.value);
            return false; // Prevent default action
        }
    });
});
</script>


<!-- Modal for Creating Activity -->
<div class="modal fade" id="createActivityModal" tabindex="-1" aria-labelledby="createActivityModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createActivityModalLabel">إنشاء دورة أو نشاط</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="createActivityForm" action="insert_activity.php" method="POST">
          <div class="mb-3">
            <label for="activityName" class="form-label">اسم الدورة أو النشاط</label>
            <input type="text" class="form-control" id="activityName" name="activity_name" required>
          </div>
          <div class="mb-3">
            <label for="startDate" class="form-label">تاريخ البداية</label>
            <input type="date" class="form-control" id="startDate" name="start_date" required>
          </div>
          <div class="mb-3">
            <label for="endDate" class="form-label">تاريخ النهاية</label>
            <input type="date" class="form-control" id="endDate" name="end_date" required>
          </div>
          <div class="mb-3">
            <label for="price" class="form-label">السعر</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            <button type="submit" class="btn btn-primary">حفظ</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Creating Levels -->
<div class="modal fade" id="Levels" tabindex="-1" aria-labelledby="LevelsLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="LevelsLabel">  إضافة مستوى </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="createActivityForm" action="insert_level.php" method="POST">
          <div class="mb-3">
            <label for="activityName" class="form-label">   اسم المستوى </label>
            <input type="text" class="form-control" id="level_name" name="level_name" required>
          </div>
          <div class="mb-3">
            <label for="price" class="form-label">السعر</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            <button type="submit" class="btn btn-primary">حفظ</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure SweetAlert2 is loaded and ready before running
        Swal.fire({
            icon: 'success',
            title: 'تم الحفظ',
            text: 'تم إنشاء المستوى بنجاح!',
            confirmButtonText: 'موافق'
        }).then((result) => {
            // After the alert is closed, remove the query parameters
            if (result.isConfirmed) {
                const url = new URL(window.location.href);
                url.searchParams.delete('success');
                window.history.replaceState(null, '', url); // Replace the current URL without reloading the page
            }
        });
    });
</script>
<?php endif; ?>




<!-- Modal HTML END -->
<div class="modal fade" id="a" tabindex="-1" role="dialog" aria-labelledby="a" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="branchModalLabel"> فتح حساب مداخيل</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="branchForm">
                    <div class="form-group">
                        <label for="branchName"> رقم الحساب</label>
                        <input type="number" class="form-control" id="Nbr" min="0" placeholder="أدخل  رقم الحساب" required>
                    </div>
                    <div class="form-group">
                        <label for="subscriptionAmount"> اسم الحساب</label>
                        <input type="text" class="form-control" id="Nom" placeholder="أدخل  اسم الحساب" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                <button type="submit" form="branchForm" class="btn btn-primary">حفظ </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal HTML END -->
<div class="modal fade" id="b" tabindex="-1" role="dialog" aria-labelledby="b" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="branchModalLabel">فتح حساب مصاريف</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="ExpenseAccountForm">
                    <div class="form-group">
                        <label for="Nbr">رقم الحساب</label>
                        <input type="number" class="form-control" id="Nbr" name="Nbr" min="0" placeholder="أدخل رقم الحساب" required>
                    </div>
                    <div class="form-group">
                        <label for="Nom">اسم الحساب</label>
                        <input type="text" class="form-control" id="Nom" name="Nom" placeholder="أدخل اسم الحساب" required>
                    </div>
                    <div class="form-group">
                        <label for="Category">الفئة</label>
                        <input type="text" class="form-control" id="Category" name="Category" placeholder="أدخل الفئة" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                <button type="submit" class="btn btn-primary" id="submitFormBtn">حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script>
  document.getElementById('submitFormBtn').addEventListener('click', function (e) {
    e.preventDefault(); // Prevent the form from submitting traditionally

    // Get form data
    var formData = new FormData(document.getElementById('ExpenseAccountForm'));

    // Send AJAX request to insert_expense_account.php
    fetch('insert_expense_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if the response is ok (status in the range 200-299)
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Handle the response
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'تم بنجاح',
                text: data.message,
                confirmButtonText: 'موافق'
            }).then(() => {
                // Clear form fields
                document.getElementById('ExpenseAccountForm').reset();
                
                // Close the modal properly
                $('#b').modal('hide');

                // Ensure modal-related classes are removed
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();

                // Optionally, refresh parts of the UI or perform any other updates
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: data.message,
                confirmButtonText: 'موافق'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'حدث خطأ أثناء معالجة الطلب.',
            confirmButtonText: 'موافق'
        });
        console.error('There was a problem with the fetch operation:', error);
    });
});


</script>




<!-- Modal HTML END -->
<div class="modal fade" id="c" tabindex="-1" role="dialog" aria-labelledby="c" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="branchModalLabel">فتح حساب بنكي جديد</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <form action="bank.php" method="post">
    <div class="form-group">
        <label for="accountNumber">رقم الحساب</label>
        <input type="number" class="form-control" id="account_number" name="account_number" min="0" placeholder="أدخل رقم الحساب" required>
    </div>
    <div class="form-group">
        <label for="bankName">إسم البنك</label>
        <input type="text" class="form-control" id="bank_name" name="bank_name" placeholder="أدخل إسم البنك" required>
    </div>
    <div class="form-group">
        <label for="balance">الرصيد</label>
        <input type="text" class="form-control" id="balance" name="balance" placeholder="المبلغ الموجود" required>
    </div>
    <button type="submit" class="btn btn-primary">حفظ</button>
</form>

            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="agentRegistrationModal" tabindex="-1" role="dialog" aria-labelledby="agentRegistrationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="AgnetModalLabel">تسجيل الوكلاء</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <form id="AgnetForm" method="POST" action="insert_agent.php">
                    <div class="form-row">
                    <div class="form-group col-md-6">
    <label for="phone">الهاتف</label>
    <input type="text" class="form-control form-control-lg" id="phone" name="phone" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
    <small class="form-text text-muted">أدخل 8 أرقام فقط.</small>
</div>
                        <div class="form-group col-md-6">
                            <label for="Nom">الإسم</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="أدخل الإسم" required>
                        </div>
                    </div>
                    <div class="form-row">
                    <div class="form-group col-md-6">
    <label for="phone"> 2 الهاتف</label>
    <input type="text" class="form-control form-control-lg" id="phone_2" name="phone_2" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
    <small class="form-text text-muted">أدخل 8 أرقام فقط.</small>
</div>
                        <div class="form-group col-md-6">
                            <label for="profession">المهنة</label>
                            <input type="text" class="form-control form-control-lg" id="profession" name="profession" placeholder="المهنة" required>
                        </div>
                    </div>
                    <div class="form-row">
                    <div class="form-group col-md-6">
    <label for="phone"> رقم هاتف الواتس اب </label>
    <input type="text" class="form-control form-control-lg" id="whatsapp_phone" name="whatsapp_phone" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
    <small class="form-text text-muted">أدخل 8 أرقام فقط.</small>
</div>
                    </div>
                    <button type="submit" form="AgnetForm" class="btn btn-primary">حفظ</button>
                </form>
            </div>
            <div class="modal-footer">
         
            </div>
        </div>
    </div>
</div>

<!-- Modal HTML START -->
<div class="modal fade" id="f" tabindex="-1" role="dialog" aria-labelledby="f" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="branchModalLabel">اكتتاب الموظفين</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <form id="Employeform" action="add_employee.php" method="POST">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="Nbr">رقم الموظف</label>
                            <input type="number" class="form-control form-control-lg" id="Nbr" name="Nbr" min="0" placeholder="أدخل رقم الموظف" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="Nom">الإسم الكامل</label>
                            <input type="text" class="form-control form-control-lg" id="Nom" name="Nom" placeholder="أدخل الإسم الكامل" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="address">الرصيد</label>
                            <input type="text" class="form-control form-control-lg" id="address" name="address" placeholder="أدخل الرصيد" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="phone">الهاتف</label>
                            <input type="text" class="form-control form-control-lg" id="phone" name="phone" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
                            <small class="form-text text-muted">أدخل 8 أرقام فقط.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="profession">الوظيفة</label>
                            <select id="profession" name="profession" class="form-control form-control-lg" required>
                                <option value="">اختر الوظيفة</option>
                                <?php foreach ($jobList as $job): ?>
                                    <option value="<?php echo $job['id']; ?>"><?php echo $job['job_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="salary">الراتب</label>
                            <input type="number" class="form-control form-control-lg" id="salary" name="salary" placeholder="الراتب" min="0" required>
                        </div>
                    </div>
                    <div class="form-row">
                    <div class="form-group col-md-6">
                            <label for="idNumber">رقم بطاقة التعريف</label>
                            <input type="text" class="form-control form-control-lg" id="idNumber" name="idNumber" pattern="\d{10}" maxlength="10" placeholder="أدخل رقم بطاقة التعريف" required>
                        </div>
                        
                       
                        <div class="form-group col-md-6">
                            <label for="Date">تاريخ الاكتتاب</label>
                            <input type="date" class="form-control form-control-lg" id="Date" name="Date" required>
                        </div>
                    </div>
                    <button type="submit" form="Employeform" class="btn btn-primary">حفظ</button>

                </form>
            </div>
           
        </div>
    </div>
</div>

<!-- Modal -->

<div class="modal fade" id="studentRegistrationModal" tabindex="-1" role="dialog" aria-labelledby="studentRegistrationModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-lg" role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="studentRegistrationModalLabel">تسجيل التلاميذ</h5>

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <div class="modal-body">

                <form id="" enctype="multipart/form-data" method="POST" action="student.php" accept-charset="UTF-8">

                     <!-- Section 1: تعريف الوكيل -->

                     <div id="agentSection" class="form-row">

                        <h5 class="col-12">تعريف الوكيل</h5>

                        <div class="form-group col-md-6">

                            <label for="agentPhone">رقم الهاتف</label>

                            <input type="number" class="form-control" id="agentPhone" name="agentPhone" placeholder="أدخل رقم الهاتف" required>

                        </div>

                        <div class="form-group col-md-6 d-flex align-items-end">

                            <div class="form-check">

                                <input class="form-check-input" type="checkbox" id="noAgentCheckbox" name="noAgentCheckbox">

                                <label class="form-check-label" for="noAgentCheckbox">بدون وكيل</label>

                            </div>

                        </div>

                    </div> 

                    <!-- Card to display agent information and related students -->
                    <div class="form-group col-md-12">
                                                <div class="card" id="agentInfoCard" style="display: none;">
                                                    <div class="card-body">
                                                        <h5 class="card-title" id="agentName">اسم الوكيل: </h5>
                                                        <p class="card-text"><strong>الطلاب المرتبطين:</strong></p>
                                                        <ul id="relatedStudentsList"></ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                    <div class="section-separator"></div>

                    <!-- Section 2: تعريف الطالب -->

                    <div id="studentSection" class="form-row">

                        <h5 class="col-12">تعريف الطالب</h5>

                        <div class="form-group col-md-4">
                        <label for="studentId">رقم تعريف طالب</label>
                        <input type="number" class="form-control" id="studentId" name="studentId" placeholder="أدخل رقم تعريف طالب" min="0" required readonly>
                    </div>


                        <div class="form-group col-md-4">

                            <label for="studentName">الإسم الكامل</label>

                            <input type="text" class="form-control" id="studentName" name="studentName" placeholder="أدخل الإسم الكامل" required>

                        </div>

                        <div class="form-group col-md-4">

                            <label for="partCount">عدد الأحزاب</label>

                            <input type="number" class="form-control" id="partCount" name="partCount" placeholder="أدخل عدد الأحزاب" min="0" required>

                        </div>

                        <div class="form-group col-md-4">

                            <label for="gender">الجنس</label>

                            <select class="form-control" id="gender" name="gender" required>

                                <option value="">اختر الجنس</option>

                                <option value="ذكر">ذكر</option>

                                <option value="أنثى">أنثى</option>

                            </select>

                        </div>

                        <div class="form-group col-md-4">

                            <label for="birthDate">تاريخ الميلاد</label>

                            <input type="date" class="form-control" id="birthDate" name="birthDate" required>

                        </div>

                        <div class="form-group col-md-4">

                            <label for="birthPlace">مكان الميلاد</label>

                            <input type="text" class="form-control" id="birthPlace" name="birthPlace" placeholder="أدخل مكان الميلاد" >

                        </div>

                        <div class="form-group col-md-4">

                            <label for="registrationDate">تاريخ التسجيل</label>

                            <input type="date" class="form-control" id="registrationDate" name="registrationDate" required>

                        </div>

                        <div class="form-group col-md-4">

                            <label for="branch">الفرع</label>

                            <select class="form-control" id="branch" name="branch" required>

                                <option value="">اختر الفرع</option>

                                <!-- Branch options will be populated here -->

                            </select>

                        </div>

                        <div class="form-group col-md-4">

                            <label for="class">القسم</label>

                            <select class="form-control" id="class" name="class" required>

                                <option value="">اختر القسم</option>

                                <!-- Class options will be populated here -->

                            </select>

                        </div>

                        <div class="form-group col-md-4">

                            <label for="studentPhoto">الصورة</label>

                            <input type="file" class="form-control" id="studentPhoto" name="studentPhoto" >

                        </div>

                        <div class="form-group col-md-4">
                        <label for="level">المستوى</label>
                        <select class="form-control" id="level" name="level" required>
                            <option value="">اختر المستوى</option>
                            <!-- Level options will be populated here -->
                        </select>
                    </div> 

                    </div>
                    <div class="form-group col-md-6" id="studentPhoneContainer" style="display: none;">
                        <label for="studentphone">رقم هاتف تلميذ</label>
                        <input type="number" class="form-control" id="studentphone" name="studentphone" placeholder="أدخل رقم الهاتف">
                    </div>


                    <div class="section-separator"></div>

                    <!-- Section 3: طبيعة التسديد -->

                    <div id="paymentNatureSection" class="form-row">

                        <h5 class="col-12">طبيعة التسديد</h5>

                        <div class="form-group col-md-6 d-flex justify-content-between">

                            <div class="form-check">

                                <input class="form-check-input" type="radio" name="paymentNature" id="naturalPayment" value="طبيعي" required Checked>

                                <label class="form-check-label" for="naturalPayment">طبيعي</label>

                            </div>

                            <!-- Only show this section if role_id is 1 -->

                            <?php if ($role_id == 1): ?>

                            <div class="form-check">

                                <input class="form-check-input" type="radio" name="paymentNature" id="exemptPayment" value="معفى" required>

                                <label class="form-check-label" for="exemptPayment">معفى</label>

                            </div>

                            <?php endif; ?>

                        </div>

                    </div>

                    <div class="section-separator"></div>

                    <!-- Section 4: الرسوم الشهرية -->

                    <div id="monthlyFeesSection" class="form-row">

                        <h5 class="col-12">الرسوم الشهرية</h5>

                        <div class="form-group col-md-4">

                            <label for="fees">الرسوم</label>

                            <input type="number" class="form-control" id="fees" name="fees" placeholder="أدخل الرسوم" min="0" required>

                        </div>

                        <div class="form-group col-md-4">

                            <label for="discount">الخصم</label>

                           <input type="number" class="form-control" id="discount" name="discount" placeholder="أدخل الخصم" min="0" value="">


                        </div>

                        <div class="form-group col-md-4">

                            <label for="remaining">المتبقى</label>

                            <input type="number" class="form-control" id="remaining" name="remaining" placeholder="أدخل المتبقى" min="0" readonly>

                        </div>

                    </div>
                    <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>

                <button type="submit" class="btn btn-primary">حفظ</button>

            </div>


                </form>

            </div>

            
        </div>

    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Submit form via AJAX to display Swal.fire on success or failure
    document.getElementById('studentRegistrationForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        let formData = new FormData(this); // Create a FormData object to handle file uploads
        
        fetch('student.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())  // Parse the response as JSON
        .then(data => {
            if (data.success) {
                // If successful, show Swal success message
                Swal.fire({
                    icon: 'success',
                    title: 'تم التسجيل بنجاح',
                    text: data.message,
                    confirmButtonText: 'حسنًا',
                });
                // Optionally, you can clear the form fields if needed
                this.reset(); // Reset the form fields if you want to clear them after submission
            } else {
                // If there was an error, show Swal error message
                Swal.fire({
                    icon: 'error',
                    title: 'حدث خطأ',
                    text: data.message,
                    confirmButtonText: 'حسنًا',
                });
            }
        })
        .catch(error => {
            // Handle any unexpected errors
            Swal.fire({
                icon: 'error',
                title: 'حدث خطأ غير متوقع',
                text: 'يرجى المحاولة مرة أخرى لاحقًا',
                confirmButtonText: 'حسنًا',
            });
        });
    });
</script>


<!-- Include SweetAlert -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
    $(document).ready(function() {
        $('#agentPhone').on('input', function() {
            var agentPhone = $(this).val();

            if (agentPhone.length == 4) {
                $.ajax({
                    url: 'check_agent_phone.php',
                    type: 'POST',
                    data: { phone: agentPhone },
                    success: function(response) {
                        console.log("Response from server:", response); // Debugging line
                        if (response === 'not_found') {
                            // Show SweetAlert instead of alert
                            Swal.fire({
                                title: '!رقم جديد',
                                text: 'يرجى تسجيل وكيل جديد',
                                icon: 'warning',
                                confirmButtonText: 'موافق',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Redirect to agents.php instead of showing the modal
                                    window.location.href = 'agents.php';
                                }
                            });
                        }
                    },
                    error: function() {
                        console.error('Error occurred during the AJAX call.');
                    }
                });
            }
        });
    });
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
            });
        })
        .catch(error => console.error('Error fetching branch and class data:', error));
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>




    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

<script>
    // Calculate remaining when the modal is loaded
        $('#studentRegistrationModal').on('shown.bs.modal', function () {
            var fees = parseFloat(document.getElementById('fees').value) || 0;
            var discount = 0;  // Set discount to 0 initially
            var remaining = fees - discount;
            document.getElementById('remaining').value = remaining.toFixed(2);
        });

        // Add event listeners for fees and discount inputs
        document.getElementById('fees').addEventListener('input', calculateRemaining);
        document.getElementById('discount').addEventListener('input', calculateRemaining);

        // Function to calculate the remaining amount
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
        var feesInput = document.getElementById('fees');
        var discountInput = document.getElementById('discount');
        var remainingInput = document.getElementById('remaining');

        if (exemptPayment) {
            monthlyFeesSection.style.display = 'none';
            // Clear and disable the fees and discount inputs when exempt
            feesInput.value = '';
            discountInput.value = '';
            remainingInput.value = '';
            feesInput.disabled = true;
            discountInput.disabled = true;
            remainingInput.disabled = true;
        } else {
            monthlyFeesSection.style.display = 'flex';
            feesInput.disabled = false;
            discountInput.disabled = false;
            remainingInput.disabled = false;
        }
    }

    // Initialize the monthly fees section display based on the default selected payment nature
    window.addEventListener('load', function() {
        toggleMonthlyFeesSection();
    });

    document.getElementById('noAgentCheckbox').addEventListener('change', toggleAgentPhone);

    function toggleAgentPhone() {
        var noAgentCheckbox = document.getElementById('noAgentCheckbox').checked;
        var agentPhone = document.getElementById('agentPhone');

        if (noAgentCheckbox) {
            agentPhone.parentElement.style.display = 'none';
            agentPhone.disabled = true; // Disable the agent phone input
            agentPhone.value = ''; // Clear the agent phone input
        } else {
            agentPhone.parentElement.style.display = 'block';
            agentPhone.disabled = false; // Enable the agent phone input
        }
    }

    // Initialize the agent phone display based on the default state of the checkbox
    window.addEventListener('load', function() {
        toggleAgentPhone();
    });

</script>
<script>
    $(document).ready(function() {
        $('#class').on('change', function() {
            var classId = $(this).val();
            if (classId) {
                $.ajax({
                    url: 'get_class_price.php', // PHP script to fetch the price
                    type: 'POST',
                    data: { class_id: classId },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            $('#fees').val(data.price);
                        } else {
                            $('#fees').val(''); // Clear the field if no price is found
                        }
                    },
                    error: function() {
                        alert('Error retrieving class price');
                    }
                });
            } else {
                $('#fees').val(''); // Clear the field if no class is selected
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const noAgentCheckbox = document.getElementById('noAgentCheckbox');
        const studentPhoneContainer = document.getElementById('studentPhoneContainer');
        const studentPhoneInput = document.getElementById('studentphone');

        // Function to toggle the student phone field based on checkbox
        function toggleStudentPhoneField() {
            if (noAgentCheckbox.checked) {
                studentPhoneContainer.style.display = 'block';  // Show the phone field
                studentPhoneInput.setAttribute('required', 'required');  // Add required attribute
            } else {
                studentPhoneContainer.style.display = 'none';   // Hide the phone field
                studentPhoneInput.removeAttribute('required');  // Remove required attribute
                studentPhoneInput.value = '';  // Clear the input value if it's hidden
            }
        }

        // Call the function on page load
        toggleStudentPhoneField();

        // Attach the event listener to the checkbox
        noAgentCheckbox.addEventListener('change', toggleStudentPhoneField);
    });
</script>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch levels data
        fetch('fetch_levels.php')
            .then(response => response.json())
            .then(data => {
                const levels = data.levels;
                const levelSelect = document.getElementById('level'); // The level dropdown
                const feesInput = document.getElementById('fees'); // The fees input field

                // Ensure the select is empty before populating (optional)
                levelSelect.innerHTML = '<option value="">اختر المستوى</option>';

                // Check if levels are returned
                if (levels.length > 0) {
                    // Populate the level dropdown
                    levels.forEach(level => {
                        const option = document.createElement('option');
                        option.value = level.id; // Use 'level.id' instead of 'level.level_id'
                        option.textContent = level.level_name; // Only display the level name
                        option.setAttribute('data-price', level.price); // Store price in data attribute
                        levelSelect.appendChild(option);
                    });
                } else {
                    // If no levels, show a message in the dropdown (optional)
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'لا توجد مستويات متاحة';
                    levelSelect.appendChild(option);
                }

                // Populate the fees input based on selected level
                levelSelect.addEventListener('change', function() {
                    const selectedLevel = this.options[this.selectedIndex]; // Get selected option
                    const price = selectedLevel.getAttribute('data-price'); // Get price from data attribute

                    if (price) {
                        feesInput.value = price; // Set the fees input to the level price
                    } else {
                        feesInput.value = ''; // Clear the fees input if no level is selected
                    }
                });
            })
            .catch(error => console.error('Error fetching levels data:', error));
    });


</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<script>
  $(document).ready(function() {
    // Enable autocomplete for the student name field
    $('#name_student').autocomplete({
      source: function(request, response) {
        $.ajax({
          url: 'fetchh.php', // Path to the server script that searches for students
          type: 'GET',
          dataType: 'json',
          data: {
            term: request.term // Send the term to the server for searching
          },
          success: function(data) {
            response(data); // Pass the matched student names to the autocomplete list
          }
        });
      },
      minLength: 2, // Minimum number of characters before the search starts
      select: function(event, ui) {
        // Set the selected student name in the input field
        $('#name_student').val(ui.item.value);
      }
    });
  });
</script>

<script>
    document.getElementById('agentPhone').addEventListener('input', function() {
        var agentPhone = this.value;

        // Proceed only if the phone number has 8 or more digits
        if (agentPhone.length >= 8) {
            $.ajax({
                url: 'fetch_agent_info.php',
                type: 'POST',
                data: { phone: agentPhone },
                success: function(response) {
                    var data = JSON.parse(response);
                    
                    // Check if agent is found
                    if (data.success) {
                        // Display agent's name
                        document.getElementById('agentName').textContent = 'اسم الوكيل: ' + data.agent_name;

                        // Populate the list of related students
                        var relatedStudentsList = document.getElementById('relatedStudentsList');
                        relatedStudentsList.innerHTML = ''; // Clear any previous data
                        data.related_students.forEach(function(student) {
                            var listItem = document.createElement('li');
                            listItem.textContent = student;
                            relatedStudentsList.appendChild(listItem);
                        });

                        // Show the card with agent info
                        document.getElementById('agentInfoCard').style.display = 'block';
                    } else {
                        // Agent not found, show message and clear the related students list
                        document.getElementById('agentName').textContent = 'لم يتم العثور على وكيل.';
                        document.getElementById('relatedStudentsList').innerHTML = ''; // Clear the list
                        
                        // Show the card with the message
                        document.getElementById('agentInfoCard').style.display = 'block';
                    }
                },
                error: function() {
                    // Handle error, show error message
                    document.getElementById('agentName').textContent = 'حدث خطأ أثناء جلب معلومات الوكيل.';
                    document.getElementById('relatedStudentsList').innerHTML = ''; // Clear the list
                    document.getElementById('agentInfoCard').style.display = 'block';
                }
            });
        } else {
            // If the phone number is less than 8 digits, hide the card
            document.getElementById('agentInfoCard').style.display = 'none';
            document.getElementById('agentName').textContent = ''; // Clear agent name
            document.getElementById('relatedStudentsList').innerHTML = ''; // Clear the related students list
        }
    });
</script>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        console.log("jQuery version:", $.fn.jquery);

        $('#studentRegistrationModal').on('show.bs.modal', function() {
            console.log("Modal is about to open...");

            $.ajax({
                url: 'http://localhost/EL%20menara/employees/get_last_student_id.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log("Response from get_last_student_id.php:", response);
                    if (response && response.last_student_id !== undefined) {
                        let nextStudentId = response.last_student_id + 1;
                        console.log("Next Student ID:", nextStudentId);
                        $('#studentId').val(nextStudentId);
                    } else {
                        console.error("Invalid response structure:", response);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error fetching the last student ID:", textStatus, errorThrown);
                }
            });
        });
    });
</script>






</body>
</html>