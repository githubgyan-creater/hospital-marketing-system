<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('admin');

$page_title = 'Admin Dashboard';

$user = current_user();

require_once __DIR__ . '/../includes/header.php';

?>

<nav class="navbar hm-navbar">

    <div class="container">

        <a
            href="<?php echo BASE_URL; ?>/"
            class="hm-brand"
        >
            Hospital Marketing
        </a>

        <a
            href="<?php echo BASE_URL; ?>/logout.php"
            class="btn btn-outline-light btn-sm"
        >
            Logout
        </a>

    </div>

</nav>


<main class="container py-5">

    <div class="mb-4">

        <span class="badge text-bg-light">
            ADMINISTRATOR
        </span>

        <h1 class="hm-page-title mt-2">
            Admin Dashboard
        </h1>

        <p class="hm-muted">
            Welcome,
            <?php echo htmlspecialchars($user['name']); ?>.
        </p>

    </div>


    <div class="row g-4">

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5>
                    User Management
                </h5>

                <p class="hm-muted">
                    Manage managers, telecallers and marketing
                    executives.
                </p>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5>
                    Marketing Operations
                </h5>

                <p class="hm-muted">
                    Leads, campaigns, referrals and events.
                </p>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5>
                    Reports
                </h5>

                <p class="hm-muted">
                    Team activity and marketing performance.
                </p>

            </div>

        </div>

    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>