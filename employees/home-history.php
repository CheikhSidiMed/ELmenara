<?php
    
    session_start();
    error_reporting(0);
    include('includes/dbconn.php');

    if(strlen($_SESSION['emplogin'])==0){   
    header('location:../index.php');
    }   else    {

 ?>

<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Demand d'achat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="../images/kenz1.png">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/themify-icons.css">
    <link rel="stylesheet" href="../assets/css/metisMenu.css">
    <link rel="stylesheet" href="../assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="../assets/css/slicknav.min.css">
    <!-- amchart css -->
    <link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all" />
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">
    <!-- others css -->
    <link rel="stylesheet" href="../assets/css/typography.css">
    <link rel="stylesheet" href="../assets/css/default-css.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <!-- modernizr css -->
    <script src="../assets/js/vendor/modernizr-2.8.3.min.js"></script>
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
                <a href="home.php"><img src="../images/kenz1.png" alt="logo"></a> 
                </div>
            </div>
            <div class="main-menu">
                <div class="menu-inner">
                    <nav>
                        <ul class="metismenu" id="menu">

                            <li class="#">
                                <a href="home.php" aria-expanded="true"><i class="ti-user"></i><span>Lancer un Demand
                                    </span></a>
                            </li>

                            <li class="active">
                                <a href="home-history.php" aria-expanded="true"><i class="ti-agenda"></i><span>Consulter l’historique de mes homes
                                    </span></a>
                            </li>

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
                <div class="row align-items-center">
                    <!-- nav and search button -->
                    <div class="col-md-6 col-sm-8 clearfix">
                        <div class="nav-btn pull-left">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        
                    </div>
                    <!-- profile info & task notification -->
                    <div class="col-md-6 col-sm-4 clearfix">
                        <ul class="notification-area pull-right">
                            <li id="full-view"><i class="ti-fullscreen"></i></li>
                            <li id="full-view-exit"><i class="ti-zoom-out"></i></li>
                            
                            
                            
                        </ul>
                    </div>
                </div>
            </div>
            <!-- header area end -->
            <!-- page title area start -->
            <div class="page-title-area">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <div class="breadcrumbs-area clearfix">
                            <h4 class="page-title pull-left">Historique de mes Demandes</h4>  
                        </div>
                    </div>
                    <div class="col-sm-6 clearfix">
                         <?php include '../includes/employee-profile-section.php'?>
                    </div>
                </div>
            </div>
            <!-- page title area end -->
            <div class="main-content-inner">
                <div class="row">
                    <!-- data table start -->
                    <div class="col-12 mt-5">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="header-title">Tableau de l’historique des Demandes</h4>
                                <?php if($error){?><div class="alert alert-danger alert-dismissible fade show"><strong>Info: </strong><?php echo htmlentities($error); ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            
                             </div><?php } 
                                 else if($msg){?><div class="alert alert-success alert-dismissible fade show"><strong>Info: </strong><?php echo htmlentities($msg); ?> 
                                 <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                 </div><?php }?>
                                <div class="data-tables">
                                    <table id="dataTable" class="table table-hover progress-table text-center">
                                        <thead class="bg-light text-capitalize">
                                            <tr>
                                                <th>#</th>
                                                <th width="200">Details du Demand</th>
                                                <th width="150">Appliqué</th>
                                                <th width="120">Validation Dr.site</th>
                                                <th width="120">Validation Dr.Logistique</th>
                                                <th width="120">Validation DAF</th>
                                                <th width="120">Status du Demand</th>
                                                <th width="50">Imprimer</th> <!-- Print Icon Column -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php 
                                        $eid=$_SESSION['eid'];
                                        $sql = "SELECT  id,Description,PostingDate,Dr_site_RemarkDate,Dr_site_Remark,Dr_logistique_Remark,Dr_logistique_RemarkDate,DAF_RemarkDate,DAF_Remark,Status from tblleaves where empid=:eid";
                                        $query = $dbh -> prepare($sql);
                                        $query->bindParam(':eid',$eid,PDO::PARAM_STR);
                                        $query->execute();
                                        $results=$query->fetchAll(PDO::FETCH_OBJ);
                                        $cnt=1;
                                        if($query->rowCount() > 0){
                                        foreach($results as $result)
                                        {  ?> 

                                            <tr id="demand-<?php echo htmlentities($result->id); ?>"> <!-- Added ID for each row -->
                                            <td> <?php echo htmlentities($result->id);?></td>
                                            <td><?php echo htmlentities($result->Description);?></td>
                                            <td><?php echo htmlentities($result->PostingDate);?></td>
                                            <td><?php if($result->Dr_site_Remark=="")
                                            {
                                            echo htmlentities('En attente');
                                            } else {
                                                // Displaying the image with CSS styling for size
                                                $imageData = base64_encode($result->Dr_site_Remark);
                                                echo '<img src="data:image/jpeg;base64,' . $imageData . '" alt="Image" style="max-width: 180px; max-height: 180px;" />'." ".""." ".$result->Dr_site_RemarkDate;
                                            }
                                            ?>
                                            </td>
                                            <td><?php if($result->Dr_logistique_Remark=="")
                                            {
                                            echo htmlentities('En attente');
                                            } else {
                                            echo htmlentities(($result->Dr_logistique_Remark)." "."Le"." ".$result->Dr_logistique_RemarkDate);
                                            }
                                            ?>
                                            </td>
                                            <td><?php if($result->DAF_Remark=="")
                                            {
                                            echo htmlentities('En attente');
                                            } else {
                                            echo htmlentities(($result->DAF_Remark)." "."Le"." ".$result->DAF_RemarkDate);
                                            }
                                            ?>
                                            </td>
                                            <td> <?php $stats = $result->Status;
                                                if($stats==1){ ?>
                                                    <span style="color: blue">En attente</span>
                                                <?php } elseif($stats==2) { ?>
                                                    <span style="color: red">Non approuvé</span>
                                                <?php } elseif($stats==0) { ?>
                                                    <span style="color: blue">En attente</span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <!-- Print Icon -->
                                                <a href="javascript:void(0);" onclick="printDemand(<?php echo htmlentities($result->id); ?>)">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                            </td>
                                            </tr>
                                        <?php $cnt++;} }?>
                                          
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- data table end -->
                </div>
            </div>
        </div>
        <!-- main content area end -->
        <!-- footer area start-->
        <?php include '../includes/footer.php' ?>
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

    <!-- Start datatable js -->
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
    <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>

    <!-- others plugins -->
    <script src="../assets/js/plugins.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
function printDemand(id) {
    // Show only the table content in a new window for printing
    var table = document.getElementById('dataTable');
    var headers = table.getElementsByTagName('thead')[0].innerHTML; // Get table headers
    var rowContent = document.getElementById('demand-' + id).innerHTML; // Extract content of specific row

    var printContents = "<table style='border-collapse: collapse;'>" + headers + rowContent + "</table>"; // Include table headers along with row content
    
    // Add an image
    var imageUrl = '../images/kenz1.png'; // Replace 'path_to_your_image.jpg' with the actual path to your image
    var imageSize = 'width="200"'; // Adjust the width as needed
    printContents = "<div style='text-align: center; margin-bottom: 20px;'><img src='" + imageUrl + "' alt='Image' " + imageSize + "></div>" + printContents; // Add image at the top and center, adjust margin-bottom

    // Open a new window with a temporary blank page
    var printWindow = window.open('', '_blank');
    
    // Write the content into the new window
    // Add title
    printWindow.document.write('<style>body { margin: 0; padding: 0; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #dddddd; text-align: left; padding: 8px; } tr:nth-child(even) { background-color: #f2f2f2; } tr:hover { background-color: #ddd; } .title { font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 20px; }</style>'); // Add CSS styles
    printWindow.document.write('<body>'); // Open body
    printWindow.document.write('<div class="title">Demand d\'achat</div>'); // Title
    printWindow.document.write(printContents); // Add image and table content
    printWindow.document.write('</body></html>'); // Close body and HTML
    
    // Ensure content is loaded before triggering print
    printWindow.document.close(); // Close document for writing
    printWindow.onload = function() {
        printWindow.print(); // Trigger print
        printWindow.close(); // Close the new window after printing is done
    };
}
</script>















</body>

</html>

<?php } ?>

