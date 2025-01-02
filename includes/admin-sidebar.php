<nav>
        <ul class="metismenu" id="menu">
        <li class="<?php if($page=='dashboard') {echo 'active';} ?>"><a href="dashboard.php"><i class="ti-dashboard"></i> <span>Dashboard</span></a></li>
                

        <li class="<?php if($page=='manage-leave') {echo 'active';} ?>">
            <a href="javascript:void(0)" aria-expanded="true"><i class="ti-briefcase"></i><span>Gérer les demandes</span></a>

            <ul class="collapse">
                <li ><a href="pending-history.php"><i class="fa fa-spinner"></i> En attente</a></li>
                <li ><a href="approved-history.php"><i class="fa fa-check"></i> Approuvé</a></li>
                <li ><a href="declined-history.php"><i class="fa fa-times-circle"></i> Refusé </a></li>
              
            </ul>
            
        </li>

        
                            
    </ul>
</nav>