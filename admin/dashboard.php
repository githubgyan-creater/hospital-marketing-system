<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('admin');

$page_title = 'Admin Dashboard';

$user = current_user();

require_once __DIR__ . '/../includes/header.php';

?>

 


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

        <div class="mb-3">
            <span class="hm-gold fs-4">👥</span>
        </div>

        <h5 class="fw-bold">
            User Management
        </h5>

        <p class="hm-muted">
            Create and manage Manager, Telecaller,
            and Marketing Executive accounts.
        </p>

        <a
            href="<?php echo BASE_URL; ?>/admin/users/index.php"
            class="btn btn-hm-primary"
        >
            Manage Users
        </a>

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