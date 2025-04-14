<?php
    session_start();
    include 'db_connection.php';



    if (!isset($_SESSION['userid'])) {
        echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
        exit();
    }
    
    $userid = $_SESSION['userid'];
    $role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null; 
    $activities = [];
    $activity_result = $conn->query("SELECT id, activity_name FROM activities");

    if ($activity_result->num_rows > 0) {
        while ($row = $activity_result->fetch_assoc()) {
            $activities[] = $row;
        }
    }
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
    <link rel="stylesheet" href="css/sweetalert2.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/themify-icons.css">
    <link rel="stylesheet" href="../assets/css/metisMenu.css">
    <link rel="stylesheet" href="../assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="../assets/css/slicknav.min.css">
    <link rel="stylesheet" href="../assets/css/typography.css">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/default-css.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <script src="../assets/js/vendor/modernizr-2.8.3.min.js"></script>
    <link rel="stylesheet" href="js/jquery-base-ui.css">
    <link rel="stylesheet" href="css/tajawal.css">


    <style>
        html, body {
            font-family: 'Tajawal', serif;
            font-size: 1.2rem;
            height: 100%;
            margin: 0;
        }
        li a {
            white-space: nowrap;
            /* overflow: hidden; */
            text-overflow: ellipsis;
            display: inline-block;
            max-width: 100%;
            margin-left: -19px;
        }
        .metismenu li a {
            position: relative;
            display: block;
            color: #8d97ad;
            font-size: 19px;
            text-transform: capitalize;
            padding: 15px 15px;
            letter-spacing: 0;
            font-weight: 400;
        }
        .main-content {
            height: 104%;
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
        .sidebar-menu {
            width: 19.5% !important;

        }
        /* .clo{
            padding: 8px;
            padding-left: 12px;
            padding-left: 20px;
            margin-top: -3px;
            margin-left: 1.2% !important;
            background: #000;
        } */

        @media (max-width: 768px) {
            .sidebar-menu {
                width: 87% !important;
            }
        }
    </style>
</head>
<body>
    <!-- preloader area start -->
    <div id="preloader">
        <div class="loader"></div>
    </div>
    <!-- page container area start -->
    <div class="page-container">
        <!-- sidebar menu area start -->
        <div class="sidebar-menu" >
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
                        <li><a href="update_cls_level.php" ><i class="fa fa-retweet"></i>  تحويل التلاميذ </a></li>
                        <li><a href="display_students.php"><i class="fa fa-edit"></i> تحديث التسجيل</a></li>
                        <li><a href="display_studentss.php"><i class="fa fa-user-times"></i> فصل تلميذ</a></li>
                        <li><a href="display_studentsss.php"><i class="fa fa-pause-circle"></i> تعليق تلميذ</a></li>
                        <li><a href="restore_student.php"><i class="fa fa-user-check"></i> استعادة تلميذ</a></li>
                    </ul>
                </li>
                <li>
                    <a href="agents.php"><i class="fa fa-plus-circle"></i> تسجيل الوكلاء</a>
                </li>
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-book"></i> الدورات والأنشطة</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="#" data-toggle="modal" data-target="#createActivityModal"><i class="fa fa-plus-square"></i> إنشاء دورة أو نشاط </a></li>
                        <li><a href="Iscrire_in_event.php" ><i class="fa fa-user-plus"></i> تسجيل في دورة أو نشاط </a></li>
                        <li><a href="list_activities.php"><i class="fa fa-list"></i> لائحة الدورات والأنشطة </a></li>
			            <li><a href="student_activities.php"><i class="fa fa-list"></i> لائحة الطلاب المسجلين </a></li>
                        <li><a href="absent_activities.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
                        <li><a href="student_activities_absence.php"><i class="fa fa-calendar-times"></i> إدارة غياب الطلاب</a></li>
                    </ul>
                </li>
            </ul>
            </li>
            <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
                <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-edit"></i><span>تعديل</span></a>
                <ul class="collapse">
                    <li><a href="view_agents.php"><i class="fa fa-user-tie"></i> بيانات الوكلاء</a></li>
                    <li><a href="view_data.php"><i class="fa fa-book"></i> بيانات المحظرة</a></li>
                    <li><a href="create_academic_year.php"><i class="fas fa-folder-open"></i>فتح السنة الدراسية</a></li>
                </ul>
            </li>
            <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
                <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user-cog"></i><span>شؤون الطلاب</span></a>
                <ul class="collapse">
                    <li><a href="weekly_followup.php"><i class="fa fa-calendar-week"></i>المتابعة الأسبوعية </a></li>
                    <li><a href="monthly_followup.php"><i class="fa fa-calendar-alt"></i> الحصيلة الشهرية</a></li>
                    <li><a href="Monthly_rating.php"><i class="fa fa-chart-line"></i> استمارة تقييم </a></li>
                    <li><a href="Teheji.php"><i class="fa fa-user-check"></i> تقييم أقسام التهجي</a></li>
                    <li><a href="absent.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
                    <li><a href="absence_student.php"><i class="fa fa-calendar-times"></i> إدارة غياب الطلاب</a></li>
                    <li><a href="quarterly_selection.php"><i class="fa fa-file-alt"></i>  الحصيلة الفصلية</a></li>
                    <li><a href="result.php"><i class="fa fa-calendar-week"></i> نتائج التقييم </a></li>
                    <li><a href="certifications/student_certf.php">📚  أرشيف الحفاظ</a></li>

                </ul>
            </li>
        </li>

            <?php elseif($role_id == 4): ?>
                <li  class="active">
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user-cog"></i><span>شؤون الطلاب</span></a>
                    <ul class="collapse">
                        <li><a href="student_data.php"><i class="fa fa-user"></i> بيانات الطلاب</a></li>
                        <li><a href="absent.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
                        <li><a href="absence_student.php"><i class="fa fa-calendar-times"></i> إدارة غياب الطلاب</a></li>
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
                        <li><a href="users_single.php"><i class="fa fa-user"></i> المستخدم </a></li>
                        <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> تسجيل الخروج </a></li>
                    </ul>
                </li>
            </ul>
            <?php elseif($role_id == 5): ?>
                <li class="active">
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-plus"></i><span>تسجيل</span></a>
                    <ul class="collapse">
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-sitemap"></i> الفروع و الأقسام</a>
                            <ul class="collapse list-unstyled">
                                <li><a href="#" data-toggle="modal" data-target="#addClassModal"><i class="fa fa-list"></i> إضافة قسم</a></li>
                                <li><a href="display_branches_classes.php"><i class="fa fa-th-list"></i> لائحة الفروع و الأقسام</a></li>
                                <li><a href="manage_classes.php"><i class="fa-solid fa-chalkboard"></i>إدارة الصفوف</a></li>
                                <li><a href="manage_branches.php"><i class="fa-solid fa-code-branch"></i>إدارة الفروع</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-user"></i> التلاميذ</a>
                            <ul class="collapse list-unstyled">
                                <li><a href="elmahdara/add_students.php" ><i class="fa fa-user-plus"></i> تسجيل التلاميذ</a></li>
                                <li><a href="update_cls_level.php" ><i class="fa fa-retweet"></i>  تحويل التلاميذ </a></li>
                                <li><a href="elmahdara/list_students_el.php"><i class="fa fa-edit"></i>  سجل الطلاب</a></li>
                                <li><a href="display_studentss.php"><i class="fa fa-user-times"></i> فصل تلميذ</a></li>
                                <!-- <li><a href="display_studentsss.php"><i class="fa fa-pause-circle"></i> تعليق تلميذ</a></li> -->
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-book"></i> الدورات والأنشطة</a>
                            <ul class="collapse list-unstyled">
                                <li><a href="#" data-toggle="modal" data-target="#createActivityModal"><i class="fa fa-plus-square"></i> إنشاء دورة أو نشاط </a></li>
                                <li><a href="Iscrire_in_event.php" ><i class="fa fa-user-plus"></i> تسجيل في دورة أو نشاط </a></li>
                                <li><a href="list_activities.php"><i class="fa fa-list"></i> لائحة الدورات والأنشطة </a></li>
                                <li><a href="student_activities.php"><i class="fa fa-list"></i> لائحة الطلاب المسجلين </a></li>
                                <li><a href="absent_activities.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
                                <li><a href="student_activities_absence.php"><i class="fa fa-calendar-times"></i> إدارة غياب الطلاب</a></li>
                                
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-edit"></i><span>تعديل</span></a>
                    <ul class="collapse">
                        <li><a href="view_agents.php"><i class="fa fa-user-tie"></i> بيانات الوكلاء</a></li>
                    </ul>
                </li>
                <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user-cog"></i><span>شؤون الطلاب</span></a>
                    <ul class="collapse">
                        <!-- <li><a href="weekly_followup.php"><i class="fa fa-calendar-week"></i> المتابعة الأسبوعية </a></li>
                        <li><a href="monthly_followup.php"><i class="fa fa-calendar-alt"></i> الحصيلة الشهرية</a></li>
                        <li><a href="Monthly_rating.php"><i class="fa fa-chart-line"></i> استمارة التقييم </a></li>
                        <li><a href="Teheji.php"><i class="fa fa-user-check"></i> تقييم أقسام التهجي</a></li> -->
                        <li><a href="elmahdara/ab_istimara.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
                        <li><a href="elmahdara/exam_is.php"><i class="fa fa-calendar-times"></i> حصيلة الإمتحان</a></li>
                        <!-- <li><a href="absence_student.php"><i class="fa fa-calendar-times"></i> إدارة غياب الطلاب</a></li>
                        <li><a href="quarterly_selection.php"><i class="fa fa-file-alt"></i>  الحصيلة الفصلية</a></li>
                        <li><a href="result.php"><i class="fa fa-calendar-week"></i> نتائج التقييم </a></li> -->
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
                            </ul>
                            <li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fas fa-folder-open"></i>   إدارة الحسابات</a>
                            <ul class="collapse list-unstyled">
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
                <li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-tasks"></i>  عمليات المعاينة</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="students_accounts.php"><i class="fa fa-user-graduate"></i> حسابات الطلاب </a></li>
                        <li><a href="receipts.php"><i class="fa fa-file-invoice"></i>  قائمة الأوصال</a></li>
                        <li><a href="Activities.php"><i class="fa fa-futbol"></i> حسابات الأنشطة </a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-file-invoice-dollar"></i>   التقارير المالية </a>
                    <ul class="collapse list-unstyled">
                        <li><a href="Daily.php"><i class="fa fa-calendar-day"></i> اليومية </a></li>
                        <li><a href="debt_report.php"><i class="fa fa-user-times"></i> الطلاب المدينون </a></li>
                        <li><a href="modify_calculation.php"><i class="fa fa-calculator"></i> تعديل عملية حسابية </a></li>
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
                            <li><a href="users_single.php"><i class="fa fa-user"></i> المستخدم </a></li>

                            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> تسجيل الخروج </a></li>
                        </ul>

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
                                <li><a href="manage_classes.php"><i class="fa-solid fa-chalkboard"></i>إدارة الصفوف</a></li>
                                <li><a href="manage_branches.php"><i class="fa-solid fa-code-branch"></i>إدارة الفروع</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-user"></i> التلاميذ</a>
                            <ul class="collapse list-unstyled">
                                <li><a href="add_student.php" ><i class="fa fa-user-plus"></i> تسجيل التلاميذ</a></li>
                                <li><a href="update_cls_level.php" ><i class="fa fa-retweet"></i>  تحويل التلاميذ </a></li>
                                <li><a href="display_students.php"><i class="fa fa-edit"></i> تحديث التسجيل</a></li>
                                <li><a href="display_studentss.php"><i class="fa fa-user-times"></i> فصل تلميذ</a></li>
                                <li><a href="display_studentsss.php"><i class="fa fa-pause-circle"></i> تعليق تلميذ</a></li>
                                <li><a href="restore_student.php"><i class="fa fa-user-check"></i> استعادة تلميذ</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="agents.php"><i class="fa fa-plus-circle"></i> تسجيل الوكلاء</a>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-book"></i> الدورات والأنشطة</a>
                            <ul class="collapse list-unstyled">
                                <li><a href="#" data-toggle="modal" data-target="#createActivityModal"><i class="fa fa-plus-square"></i> إنشاء دورة أو نشاط </a></li>
                                <li><a href="Iscrire_in_event.php" ><i class="fa fa-user-plus"></i> تسجيل في دورة أو نشاط </a></li>
                                <li><a href="list_activities.php"><i class="fa fa-list"></i> لائحة الدورات والأنشطة </a></li>
                                <li><a href="absent_activities.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
                                <li><a href="student_activities_absence.php"><i class="fa fa-calendar-times"></i> إدارة غياب الطلاب</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-edit"></i><span>تعديل</span></a>
                    <ul class="collapse">
                        <li><a href="view_agents.php"><i class="fa fa-user-tie"></i> بيانات الوكلاء</a></li>
                    </ul>
                </li>
                <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user-cog"></i><span>شؤون الطلاب</span></a>
                    <ul class="collapse">
                        <li><a href="weekly_followup.php"><i class="fa fa-calendar-week"></i> المتابعة الأسبوعية </a></li>
                        <li><a href="monthly_followup.php"><i class="fa fa-calendar-alt"></i> الحصيلة الشهرية</a></li>
                        <li><a href="Monthly_rating.php"><i class="fa fa-chart-line"></i> استمارة التقييم </a></li>
                        <li><a href="Teheji.php"><i class="fa fa-user-check"></i> تقييم أقسام التهجي</a></li>
                        <li><a href="absent.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
                        <li><a href="quarterly_selection.php"><i class="fa fa-file-alt"></i>  الحصيلة الفصلية</a></li>
                        <li><a href="result.php"><i class="fa fa-calendar-week"></i> نتائج التقييم </a></li>
                        <li><a href="certifications/student_certf.php">📚  أرشيف الحفاظ</a></li>


                    </ul>
                </li>
                </li>
                <li>
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-balance-scale"></i><span> المحاسبة</span></a>
                    <ul class="collapse">
                        <li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-keyboard"></i> عمليات الإدخال</a>
                            <ul class="collapse list-unstyled">
                                <li><a href="student_payment.php"><i class="fa fa-money-bill-wave"></i> تسديد رسوم التلاميذ </a></li>
                                <li><a href="Family_payment.php"><i class="fa fa-hand-holding-usd"></i> التسديد الأسري</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-file-invoice-dollar"></i>   التقارير المالية </a>
                            <ul class="collapse list-unstyled">
                                <li><a href="Daily.php"><i class="fa fa-calendar-day"></i> اليومية </a></li>
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
                            <li><a href="users_single.php"><i class="fa fa-user"></i> المستخدم </a></li>
                            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> تسجيل الخروج </a></li>
                        </ul>
                </li>

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
                                <li><a href="manage_classes.php"><i class="fa-solid fa-chalkboard"></i>إدارة الصفوف</a></li>
                                <li><a href="manage_branches.php"><i class="fa-solid fa-code-branch"></i>إدارة الفروع</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-user"></i> التلاميذ</a>
                            <ul class="collapse list-unstyled">
                                <li><a href="add_student.php" ><i class="fa fa-user-plus"></i> تسجيل التلاميذ</a></li>
                                <li><a href="update_cls_level.php" ><i class="fa fa-retweet"></i>  تحويل التلاميذ </a></li>
                                <li><a href="display_students.php"><i class="fa fa-edit"></i> تحديث التسجيل</a></li>
                                <li><a href="display_studentss.php"><i class="fa fa-user-times"></i> فصل تلميذ</a></li>
                                <li><a href="display_studentsss.php"><i class="fa fa-pause-circle"></i> تعليق تلميذ</a></li>
                                <li><a href="restore_student.php"><i class="fa fa-user-check"></i> استعادة تلميذ</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="agents.php"><i class="fa fa-plus-circle"></i> تسجيل الوكلاء</a>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-book"></i> الدورات والأنشطة</a>
                            <ul class="collapse list-unstyled">
                                <li><a href="#" data-toggle="modal" data-target="#createActivityModal"><i class="fa fa-plus-square"></i> إنشاء دورة أو نشاط </a></li>
                                <li><a href="Iscrire_in_event.php" ><i class="fa fa-user-plus"></i> تسجيل في دورة أو نشاط </a></li>
                                <li><a href="list_activities.php"><i class="fa fa-list"></i> لائحة الدورات والأنشطة </a></li>
                                <li><a href="student_activities.php"><i class="fa fa-list"></i> لائحة الطلاب المسجلين </a></li>
                                <li><a href="absent_activities.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
                                <li><a href="student_activities_absence.php"><i class="fa fa-calendar-times"></i> إدارة غياب الطلاب</a></li>
                                <li><a href="certifications/student_certf.php">📚  أرشيف الحفاظ</a></li>

                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-edit"></i><span>تعديل</span></a>
                    <ul class="collapse">
                        <li><a href="view_agents.php"><i class="fa fa-user-tie"></i> بيانات الوكلاء</a></li>
                    </ul>
                </li>
                <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user-cog"></i><span>شؤون الطلاب</span></a>
                    <ul class="collapse">
                        <li><a href="weekly_followup.php"><i class="fa fa-calendar-week"></i> المتابعة الأسبوعية </a></li>
                        <li><a href="monthly_followup.php"><i class="fa fa-calendar-alt"></i> الحصيلة الشهرية</a></li>
                        <li><a href="Monthly_rating.php"><i class="fa fa-chart-line"></i> استمارة التقييم </a></li>
                        <li><a href="Teheji.php"><i class="fa fa-user-check"></i> تقييم أقسام التهجي</a></li>
                        <li><a href="absent.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
                        <li><a href="quarterly_selection.php"><i class="fa fa-file-alt"></i>  الحصيلة الفصلية</a></li>
                        <li><a href="result.php"><i class="fa fa-calendar-week"></i> نتائج التقييم </a></li>
                        <li><a href="certifications/student_certf.php">📚  أرشيف الحفاظ</a></li>

                    </ul>
                </li>
                </li>
                <li>
                <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-balance-scale"></i><span> المحاسبة</span></a>
                <ul class="collapse">
                <li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-keyboard"></i> عمليات الإدخال</a>
                <ul class="collapse list-unstyled">
                    <li><a href="student_payment.php"><i class="fa fa-money-bill-wave"></i>  تسديد رسوم التلاميذ </a></li>
                    <li><a href="Family_payment.php"><i class="fa fa-hand-holding-usd"></i>  التسديد الأسري</a></li>
                    <li><a href="activitie_payment.php"><i class="fa fa-credit-card"></i>  تسديد رسوم الأنشطة </a></li>
                    <li><a href="operation.php"><i class="fa fa-calculator"></i>  تسجيل عملية حسابية</a></li>
                    <li><a href="expense_operation.php"><i class="fa fa-calculator"></i>  عمليات المصاريف </a></li>
                    <li><a href="donations.php"><i class="fa fa-hand-holding-heart"></i>   تسجيل عملية تبرع </a></li>
                    <li><a href="offers.php"><i class="fa fa-hand-holding-heart"></i>   تسجيل عملية مقدمي الخدمات </a></li>
                    <li><a href="garant_g/donations_garant.php"><i class="fa fa-hand-holding-heart"></i> تسديد الكفالات </a></li>
                </ul>
                <li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fas fa-folder-open"></i>   إدارة الحسابات</a>
                    <ul class="collapse list-unstyled">
                        <li><a href="Employee_registration.php" ><i class="fa fa-user-plus"></i> إنشاء حساب موظف</a></li>
                        <li><a href="manage_donations.php" ><i class="fa fa-dollar-sign"></i> إدارة حساب مداخيل</a></li>
                        <li><a href="manage_khadamat.php" ><i class="fa fa-dollar-sign"></i> إدارة حساب مقدمي الخدمات</a></li>
                        <li><a href="insert_expense_accountt.php"><i class="fa fa-money-bill"></i>فتح حساب مصاريف </a></li>
                        <li><a href="expense_donations.php" ><i class="fas fa-wallet"></i> حسابات المصاريف</a></li>
                        <li><a href="garant_g/garants_g.php"><i class="fa fa-money-bill"></i>إدارة حسابات الكفالات</a></li>
                        <li><a href="transaction_bancks_funds.php"><i class="fa fa-exchange-alt"></i> النقل بين الحسابات البنكيه</a></li>
                        <li><a href="up_payment_nature.php"><i class="fa fa-tags"></i>إدارة طبيعة الدفع</a></li>
                        <li><a href="#" data-toggle="modal" data-target="#c"><i class="fa fa-credit-card"></i> إنشاء حساب بنكي جديد</a></li>
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
                <li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-tasks"></i>  عمليات المعاينة</a>
                    <ul class="collapse list-unstyled">
                    <li><a href="banks.php"><i class="fa fa-university"></i>  الخزينة و البنوك </a></li>
                    <li><a href="students_accounts.php"><i class="fa fa-user-graduate"></i> حسابات الطلاب </a></li>
                    <li><a href="employess.php"><i class="fa fa-user-tie"></i> حسابات الموظفين </a></li>
                    <li><a href="donations_ccounts.php"><i class="fa fa-receipt"></i>  حسابات المداخيل </a></li>
                    <li><a href="expense_accounts.php"><i class="fa fa-receipt"></i> حسابات المصاريف </a></li>
                    <li><a href="receipts.php"><i class="fa fa-file-invoice"></i>  قائمة الأوصال</a></li>
                    <li><a href="Activities.php"><i class="fa fa-futbol"></i> حسابات الأنشطة </a></li>
                    <li><a href="discounts_list.php"><i class="fa fa-tags"></i> لائحة التخفيضات </a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-cogs"></i>   الإجراءات </a>
                    <ul class="collapse list-unstyled">
                    <li><a href="modify_calculation.php"><i class="fa fa-calculator"></i> تعديل عملية حسابية </a></li>
                    <!-- <li><a href="employees_salary.php"><i class="fa fa-money-bill-wave"></i>     إدخال الرواتب </a></li> -->
                    <li><a href="fire_employee.php"><i class="fa fa-user-slash"></i>  فصل موظف </a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-file-invoice-dollar"></i> التقارير المالية </a>
                    <ul class="collapse list-unstyled">
                    <li><a href="Daily.php"><i class="fa fa-calendar-day"></i> اليومية </a></li>
                    <li><a href="debt_report.php"><i class="fa fa-user-times"></i> الطلاب المدينون </a></li>
                    <li><a href="exempted student report.php"><i class="fa fa-user-check"></i>الطلاب المعفيون </a></li>
                    </ul>
                    </li>
                    </ul>
                </li>
                <li>
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user"></i><span> المستخدم</span></a>
                    <ul class="collapse">
                        <!-- User-related options -->
                        <li class="dropdown">
                        <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-cogs"></i> الإجراءات </a>
                        <ul class="collapse list-unstyled">
                            <li><a href="users_single.php"><i class="fa fa-user"></i> المستخدم </a></li>
                            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> تسجيل الخروج </a></li>
                        </ul>


            <!-- Restricted Access for role_id = 6 -->

            <?php elseif($role_id == 6): ?>
                <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user-cog"></i><span>شؤون الطلاب</span></a>
                    <ul class="collapse">
                        <li><a href="weekly_followup.php"><i class="fa fa-calendar-week"></i> المتابعة الأسبوعية </a></li>
                        <li><a href="monthly_followup.php"><i class="fa fa-calendar-alt"></i> الحصيلة الشهرية</a></li>
                        <li><a href="absent.php"><i class="fa fa-calendar-times"></i> استمارة الغياب</a></li>
                        <li><a href="quarterly_selection.php"><i class="fa fa-file-alt"></i>  الحصيلة الفصلية</a></li>

                    </ul>
                </li>

                <li>
                    <a href="javascript:void(0)" aria-expanded="true"><i class="fa fa-user"></i><span> المستخدم</span></a>
                    <ul class="collapse">
                        <!-- User-related options -->
                        <li class="dropdown">
                        <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-cogs"></i> الإجراءات </a>
                        <ul class="collapse list-unstyled">
                            <li><a href="users_single.php"><i class="fa fa-user"></i> المستخدم </a></li>
                            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> تسجيل الخروج </a></li>
                        </ul>

                </li>
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
                            <li><a href="expense_operation.php"><i class="fa fa-calculator"></i>     عمليات المصاريف </a></li>
                            <li><a href="donations.php"><i class="fa fa-hand-holding-heart"></i>     تسجيل عملية تبرع </a></li>
                            <li><a href="offers.php"><i class="fa fa-hand-holding-heart"></i>   تسجيل عملية مقدمي الخدمات </a></li>
                            <li><a href="garant_g/donations_garant.php"><i class="fa fa-hand-holding-heart"></i> تسديد الكفالات </a></li>
                        </ul>
                        <li class="dropdown"><a href="#" data-toggle="collapse" aria-expanded="true"><i class="fas fa-folder-open"></i>   إدارة الحسابات</a>
                            <ul class="collapse list-unstyled">
                                <li><a href="Employee_registration.php" ><i class="fa fa-user-plus"></i> إنشاء حساب موظف</a></li>
                                <li><a href="manage_donations.php" ><i class="fa fa-dollar-sign"></i> إدارة حساب مداخيل</a></li>
                                <li><a href="manage_khadamat.php" ><i class="fa fa-dollar-sign"></i> إدارة حساب مقدمي الخدمات</a></li>

                                <li><a href="insert_expense_accountt.php"><i class="fa fa-money-bill"></i>فتح حساب مصاريف </a></li>
                                <li><a href="expense_donations.php" ><i class="fas fa-wallet"></i> حسابات المصاريف</a></li>
                                <li><a href="garant_g/garants_g.php"><i class="fa fa-money-bill"></i>إدارة حسابات الكفالات</a></li>
                                <li><a href="transaction_bancks_funds.php"><i class="fa fa-exchange-alt"></i> النقل بين الحسابات البنكيه</a></li>

                                <!-- <li><a href="transaction_bancks_funds.php"><i class="fa fa-exchange-alt"></i> نقل الأموال من حساب الى حساب </a></li> -->
                                <li><a href="up_payment_nature.php"><i class="fa fa-tags"></i>إدارة طبيعة الدفع</a></li>
                                <li><a href="#" data-toggle="modal" data-target="#c"><i class="fa fa-credit-card"></i> إنشاء حساب بنكي جديد</a></li>
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
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-tasks"></i>  عمليات المعاينة</a>
                            <ul class="collapse list-unstyled">
                                <li><a href="banks.php"><i class="fa fa-university"></i> الخزينة و البنوك </a></li>
                                <li><a href="students_accounts.php"><i class="fa fa-user-graduate"></i> حسابات الطلاب </a></li>
                                <li><a href="employess.php"><i class="fa fa-user-tie"></i> حسابات الموظفين </a></li>
                                <li><a href="donations_ccounts.php"><i class="fa fa-receipt"></i>  حسابات المداخيل </a></li>
                                <li><a href="expense_accounts.php"><i class="fa fa-receipt"></i> حسابات المصاريف </a></li>
                                <li><a href="receipts.php"><i class="fa fa-file-invoice"></i>  قائمة الأوصال</a></li>
                                <li><a href="Activities.php"><i class="fa fa-futbol"></i> حسابات الأنشطة </a></li>
                                <li><a href="discounts_list.php"><i class="fa fa-tags"></i> لائحة التخفيضات </a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-cogs"></i> الإجراءات </a>
                            <ul class="collapse list-unstyled">
                                <li><a href="employees_salary.php"><i class="fa fa-money-bill-wave"></i> إدخال الرواتب </a></li>
                                <li><a href="fire_employee.php"><i class="fa fa-user-slash"></i> فصل موظف </a></li>
                                <li><a href="employees_manage.php"><i class="fa fa-user-edit"></i> إدارة الموظفين </a></li>
                                <li><a href="modify_calculation.php"><i class="fa fa-calculator"></i> تعديل عملية حسابية </a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="collapse" aria-expanded="true"><i class="fa fa-file-invoice-dollar"></i>   التقارير المالية </a>
                            <ul class="collapse list-unstyled">
                                <li><a href="Daily.php"><i class="fa fa-calendar-day"></i> اليومية </a></li>
                                <li><a href="debt_report.php"><i class="fa fa-user-times"></i> الطلاب المدينون </a></li>
                                <li><a href="exempted student report.php"><i class="fa fa-user-check"></i> الطلاب المعفيون </a></li>
                                <li><a href="Report of the month.php"><i class="fa fa-chart-line"></i> التقرير المالي الشهري </a></li>
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
            <div class="header-area">
                <div class="nav-btn pull-left clo">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>

    <!-- offset area start -->
    <div class="offset-area">
        <div class="offset-close"><i class="ti-close"></i></div>
    </div>

    <!-- Modal HTML END -->
    <div class="modal fade" id="k" tabindex="-1" role="dialog" aria-labelledby="c" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="branchModalLabel">إضافة فرع جديد </h5>
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
                <div class="form-group">
                    <label for="branchSelect">اختر الفرع</label>
                    <select class="form-control" id="branchSelect" name="branch_id" required>
                        <?php include 'db_connection.php';
                            $branches = [];
                            $result = $conn->query("SELECT branch_id, branch_name FROM branches");
                            while ($row = $result->fetch_assoc()) {
                                $branches[] = $row;
                            }

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
                    <label for="session" class="form-label">عدد الحلقات</label>
                    <input type="text" class="form-control" id="session" name="session" required>
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

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'تم الحفظ',
                    text: 'تم إنشاء المستوى بنجاح!',
                    confirmButtonText: 'موافق'
                }).then((result) => {
                    s
                    if (result.isConfirmed) {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('success');
                        window.history.replaceState(null, '', url);
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
                            <input 
                                type="number" 
                                class="form-control" 
                                id="Nbr" 
                                name="Nbr" 
                                value="<?php echo $nextAccountNumber; ?>" 
                                min="0" 
                                placeholder="أدخل رقم الحساب" 
                                required
                            >
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

    <script>
        document.getElementById('submitFormBtn').addEventListener('click', function (e) {
            e.preventDefault();
            var formData = new FormData(document.getElementById('ExpenseAccountForm'));

            fetch('Employee_registration.php', {
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


    <script>

        window.addEventListener('load', function() {
            toggleMonthlyFeesSection();
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

    <script>
        document.getElementById('submitFormBtn').addEventListener('click', function (e) {
            e.preventDefault();
            var formData = new FormData(document.getElementById('ExpenseAccountForm'));
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
                            response(data);
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.error("Autocomplete error: ", textStatus, errorThrown);
                        }
                    });
                },
                minLength: 2,
                select: function (event, ui) {
                    $('#name_student').val(ui.item.value);
                    return false; 
                }
            });
        });
    </script>


    <!-- <script src="js/bootstrap.min.js"></script> -->
    <script src="js/sweetalert2.min.js"></script>
    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="js/bootstrap.4.0.0.min.js"></script>
    <script src="js/bootstrap-4.5.2.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="../assets/js/vendor/jquery-2.2.4.min.js"></script>
    <script src="../assets/js/popper.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script src="../assets/js/owl.carousel.min.js"></script>
    <script src="../assets/js/metisMenu.min.js"></script>
    <script src="../assets/js/jquery.slimscroll.min.js"></script>
    <script src="../assets/js/jquery.slicknav.min.js"></script>
    <script src="../assets/js/plugins.js"></script>
    <script src="../assets/js/scripts.js"></script>

</body>
</html>