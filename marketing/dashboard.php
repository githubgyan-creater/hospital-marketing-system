<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('marketing');

$user = current_user();

$page_title = 'Marketing Dashboard';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <div class="mb-4">

        <span class="badge bg-light text-dark">
            MARKETING EXECUTIVE
        </span>

        <h1 class="hm-page-title mt-2">
            My Day
        </h1>

        <p class="hm-muted">
            Welcome,
            <?php echo htmlspecialchars($user['name']); ?>.
        </p>

    </div>


    <div class="row g-4">

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5 class="fw-bold">
                    Leads
                </h5>

                <p class="hm-muted">
                    Manage assigned marketing enquiries.
                </p>

                <span class="badge bg-light text-dark">
                    Coming Next
                </span>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5 class="fw-bold">
                    Tasks
                </h5>

                <p class="hm-muted">
                    View and complete assigned marketing tasks.
                </p>

                <span class="badge bg-light text-dark">
                    Coming Next
                </span>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5 class="fw-bold">
                    Visits
                </h5>

                <p class="hm-muted">
                    Manage field visits and outreach activities.
                </p>

                <span class="badge bg-light text-dark">
                    Coming Next
                </span>

            </div>

        </div>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>