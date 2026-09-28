<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

require_login();

$user = current_user();

if (
    !$user ||
    !in_array($user['role'], ['admin', 'manager'], true)
) {
    http_response_code(403);
    exit('Access denied.');
}


/*
|--------------------------------------------------------------------------
| GET PLAN ID
|--------------------------------------------------------------------------
*/

$plan_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($plan_id <= 0) {
    exit('Invalid marketing plan ID.');
}


/*
|--------------------------------------------------------------------------
| LOAD MARKETING PLAN
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        mp.id,
        mp.plan_title,
        mp.objective,
        mp.focus_area,
        mp.target_description,
        mp.start_date,
        mp.end_date,
        mp.status,
        mp.remarks,
        mp.created_at,
        mp.updated_at,

        u.name AS created_by_name

    FROM marketing_plans mp

    INNER JOIN users u
        ON mp.created_by = u.id

    WHERE mp.id = ?

    LIMIT 1
");

$stmt->execute([
    $plan_id
]);

$plan = $stmt->fetch();

if (!$plan) {
    exit('Marketing plan not found.');
}

?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<div class="container py-4">


    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Marketing Plan Details
            </h2>

            <p class="text-muted mb-0">
                Review the selected marketing plan.
            </p>

        </div>


        <a
            href="<?php echo BASE_URL; ?>/marketing/marketing-plans/index.php"
            class="btn btn-outline-secondary"
        >
            Back to Plans
        </a>

    </div>


    <!-- PLAN HEADER CARD -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-start">


                <div>

                    <h3 class="mb-2">

                        <?php
                        echo htmlspecialchars(
                            $plan['plan_title']
                        );
                        ?>

                    </h3>


                    <div class="text-muted">

                        Created by:

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $plan['created_by_name']
                            );
                            ?>

                        </strong>

                    </div>

                </div>


                <div>

                    <?php

                    $badge_class = 'bg-secondary';

                    if ($plan['status'] === 'Active') {
                        $badge_class = 'bg-primary';
                    }

                    if ($plan['status'] === 'Completed') {
                        $badge_class = 'bg-success';
                    }

                    if ($plan['status'] === 'Cancelled') {
                        $badge_class = 'bg-danger';
                    }

                    ?>


                    <span
                        class="badge <?php echo $badge_class; ?> fs-6"
                    >

                        <?php
                        echo htmlspecialchars(
                            $plan['status']
                        );
                        ?>

                    </span>

                </div>


            </div>

        </div>

    </div>


    <div class="row g-4">


        <!-- MAIN INFORMATION -->

        <div class="col-lg-8">


            <!-- OBJECTIVE -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Objective
                    </h5>

                </div>


                <div class="card-body">

                    <?php if (
                        !empty($plan['objective'])
                    ): ?>

                        <p class="mb-0">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $plan['objective']
                                )
                            );
                            ?>

                        </p>

                    <?php else: ?>

                        <span class="text-muted">
                            No objective provided.
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- TARGET -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Target / Expected Outcome
                    </h5>

                </div>


                <div class="card-body">

                    <?php if (
                        !empty(
                            $plan['target_description']
                        )
                    ): ?>

                        <p class="mb-0">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $plan[
                                        'target_description'
                                    ]
                                )
                            );
                            ?>

                        </p>

                    <?php else: ?>

                        <span class="text-muted">
                            No target provided.
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- REMARKS -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Remarks
                    </h5>

                </div>


                <div class="card-body">

                    <?php if (
                        !empty($plan['remarks'])
                    ): ?>

                        <p class="mb-0">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $plan['remarks']
                                )
                            );
                            ?>

                        </p>

                    <?php else: ?>

                        <span class="text-muted">
                            No remarks provided.
                        </span>

                    <?php endif; ?>

                </div>

            </div>


        </div>


        <!-- SUMMARY -->

        <div class="col-lg-4">


            <div class="card shadow-sm border-0 mb-4">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Plan Summary
                    </h5>

                </div>


                <div class="card-body">


                    <div class="mb-3">

                        <small class="text-muted">
                            Focus Area
                        </small>

                        <div class="fw-semibold">

                            <?php
                            echo htmlspecialchars(
                                $plan['focus_area']
                                    ?: '-'
                            );
                            ?>

                        </div>

                    </div>


                    <div class="mb-3">

                        <small class="text-muted">
                            Start Date
                        </small>

                        <div class="fw-semibold">

                            <?php
                            echo date(
                                'd M Y',
                                strtotime(
                                    $plan['start_date']
                                )
                            );
                            ?>

                        </div>

                    </div>


                    <div class="mb-3">

                        <small class="text-muted">
                            End Date
                        </small>

                        <div class="fw-semibold">

                            <?php
                            echo date(
                                'd M Y',
                                strtotime(
                                    $plan['end_date']
                                )
                            );
                            ?>

                        </div>

                    </div>


                    <div class="mb-3">

                        <small class="text-muted">
                            Created On
                        </small>

                        <div class="fw-semibold">

                            <?php
                            echo date(
                                'd M Y, h:i A',
                                strtotime(
                                    $plan['created_at']
                                )
                            );
                            ?>

                        </div>

                    </div>


                    <div>

                        <small class="text-muted">
                            Last Updated
                        </small>

                        <div class="fw-semibold">

                            <?php
                            echo date(
                                'd M Y, h:i A',
                                strtotime(
                                    $plan['updated_at']
                                )
                            );
                            ?>

                        </div>

                    </div>


                </div>

            </div>


             <!-- ACTION -->

<div class="card shadow-sm border-0">

    <div class="card-header bg-white">

        <h5 class="mb-0">
            Actions
        </h5>

    </div>

    <div class="card-body">

        <a
            href="<?php echo BASE_URL; ?>/marketing/marketing-plans/edit.php?id=<?php echo (int) $plan['id']; ?>"
            class="btn btn-primary w-100 mb-2"
        >
            Edit Marketing Plan
        </a>

        <a
            href="<?php echo BASE_URL; ?>/marketing/marketing-plans/index.php"
            class="btn btn-outline-secondary w-100"
        >
            Back to Plans
        </a>

    </div>

</div>


        </div>


    </div>


</div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>