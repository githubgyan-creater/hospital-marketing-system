 <?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('admin');

$page_title = 'Admin Dashboard';

$user = current_user();

require_once __DIR__ . '/../includes/header.php';

?>

<main class="container py-5">

    
    <!-- PAGE HEADER -->
    

    <div class="mb-4">

        <!-- <span class="badge text-bg-light">
            ADMINISTRATOR
        </span> -->

        <h1 class="hm-page-title mt-2">
            Admin Dashboard
        </h1>

        <p class="hm-muted">
            Welcome,
            <?php
            echo htmlspecialchars(
                $user['name']
            );
            ?>.
        </p>

    </div>


    
    <!-- ADMIN MODULES -->
    

    <div class="row g-4">


      
        <!-- USER MANAGEMENT -->
      

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="mb-3">
                    <span class="hm-gold fs-4">
                        👥
                    </span>
                </div>

                <h5 class="fw-bold">
                    User Management
                </h5>

                <p class="hm-muted">
                    Create and manage Manager, Telecaller,
                    Marketing Executive and other staff accounts.
                </p>

                <a
                    href="<?php echo BASE_URL; ?>/admin/users/index.php"
                    class="btn btn-hm-primary"
                >
                    Manage Users
                </a>

            </div>

        </div>


      
        <!-- REPORTS -->
      

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="mb-3">
                    <span class="hm-gold fs-4">
                        📊
                    </span>
                </div>

                <h5 class="fw-bold">
                    Reports
                </h5>

                <p class="hm-muted">
                    View system-wide leads, users, events,
                    appointments and marketing activity.
                </p>

                <a
                    href="<?php echo BASE_URL; ?>/admin/reports/index.php"
                    class="btn btn-hm-primary"
                >
                    View Reports
                </a>

            </div>

        </div>


      
        <!-- SYSTEM OVERVIEW -->
      

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="mb-3">
                    <span class="hm-gold fs-4">
                        ⚙️
                    </span>
                </div>

                <h5 class="fw-bold">
                    System Overview
                </h5>

                <p class="hm-muted">
                    Manage the overall hospital marketing
                    system and monitor operational data.
                </p>

                <a
                    href="<?php echo BASE_URL; ?>/manager/control-center.php"
                    class="btn btn-outline-primary"
                >
                    Open Overview
                </a>

            </div>

        </div>


    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>