<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav  sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #d86303;">

        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center">
            <div class="sidebar-brand-icon">
            </div>
            <div class="sidebar-brand-text mx-3">Synchronization System</div>
        </a>


        <!-- Divider -->
        <hr class="sidebar-divider">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo base_url('users') ?>">
                <i class="fas fa-fw fa-users"></i>
                <span>Users</span></a>
        </li>

        <!-- Nav Item - Utilities Collapse Menu -->
        <li class="nav-item">
            <a class="nav-link" href="<?php echo base_url('auth/db-list'); ?>">
                <i class="fas fa-database"></i>
                <span>Koneksi Database</span></a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?php echo base_url('home/log'); ?>">
                <i class="fas fa-binoculars"></i>
                <span>Logs</span></a>
        </li>



        <div class="sidebar-heading">
            Sinkron by Date
        </div>
        <!-- Nav Item - Utilities Collapse Menu -->

        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#finance"
                aria-expanded="true" aria-controls="finance">
                <i class="fas fa-fw fa-sync"></i>
                <span>Sinkron</span>
            </a>
            <div id="finance" class="collapse" aria-labelledby="headingUtilities"
                data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="<?= base_url('SyncTransaction'); ?>">Transaction Sync By Date</a>
                    <a class="collapse-item" href="<?= base_url('Transaction-no'); ?>">Transaction Sync By No</a>
                </div>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?php echo base_url('get-data'); ?>">
                <i class="fas fa-fw fa-sync"></i>
                <span>Get Data Manually</span></a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?php echo base_url('get-data-no'); ?>">
                <i class="fas fa-fw fa-sync"></i>
                <span>Get Data Manually By NO</span></a>
        </li>




        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">

        <!-- Sidebar Toggler (Sidebar) -->
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>

    </ul>
    <!-- End of Sidebar -->