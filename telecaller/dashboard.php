<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('telecaller');

$user = current_user();

$page_title = 'Telecaller Dashboard';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <div class="mb-4">

        <span class="badge bg-light text-dark">
            TELECALLER
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
                    View and follow up with assigned enquiries.
                </p>

                <span class="badge bg-light text-dark">
                    Coming Next
                </span>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5 class="fw-bold">
                    Calls
                </h5>

                <p class="hm-muted">
                    Manage today's calls and call activities.
                </p>

                <span class="badge bg-light text-dark">
                    Coming Next
                </span>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5 class="fw-bold">
                    Follow-ups
                </h5>

                <p class="hm-muted">
                    Track upcoming and completed follow-ups.
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