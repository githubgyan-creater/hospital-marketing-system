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
| FILTER
|--------------------------------------------------------------------------
*/

$status = trim($_GET['status'] ?? '');


/*
|--------------------------------------------------------------------------
| BUILD QUERY
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if ($status !== '') {

    $where[] = 'mp.status = ?';
    $params[] = $status;
}


/*
|--------------------------------------------------------------------------
| LOAD MARKETING PLANS
|--------------------------------------------------------------------------
*/

$sql = "
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

        u.name AS created_by_name

    FROM marketing_plans mp

    INNER JOIN users u
        ON mp.created_by = u.id
";


if (!empty($where)) {

    $sql .= "
        WHERE " .
        implode(' AND ', $where);
}


$sql .= "
    ORDER BY
        mp.start_date DESC,
        mp.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$plans = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| SUMMARY COUNTS
|--------------------------------------------------------------------------
*/

$count_stmt = $pdo->query("
    SELECT

        COUNT(*) AS total_plans,

        SUM(
            CASE
                WHEN status = 'Draft'
                THEN 1
                ELSE 0
            END
        ) AS draft_plans,

        SUM(
            CASE
                WHEN status = 'Active'
                THEN 1
                ELSE 0
            END
        ) AS active_plans,

        SUM(
            CASE
                WHEN status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_plans,

        SUM(
            CASE
                WHEN status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_plans

    FROM marketing_plans
");

$counts = $count_stmt->fetch();

?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<div class="container py-4">


    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Marketing Plans
            </h2>

            <p class="text-muted mb-0">
                Manage the next marketing plans for hospital growth.
            </p>

        </div>


        <a
            href="<?php echo BASE_URL; ?>/manager/marketing-plans/add.php"
            class="btn btn-primary"
        >
            + Add Marketing Plan
        </a>

    </div>


    <!-- SUMMARY -->

    <div class="row g-4 mb-4">


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Plans
                    </h6>

                    <h3 class="mb-0">
                        <?php
                        echo (int) (
                            $counts['total_plans']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Draft
                    </h6>

                    <h3 class="mb-0">
                        <?php
                        echo (int) (
                            $counts['draft_plans']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Active
                    </h6>

                    <h3 class="mb-0 text-primary">
                        <?php
                        echo (int) (
                            $counts['active_plans']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Completed
                    </h6>

                    <h3 class="mb-0 text-success">
                        <?php
                        echo (int) (
                            $counts['completed_plans']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>

            </div>

        </div>


    </div>


    <!-- FILTER -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-3 align-items-end">


                    <div class="col-md-4">

                        <label class="form-label">
                            Plan Status
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="">
                                All Plans
                            </option>

                            <option
                                value="Draft"
                                <?php
                                echo $status === 'Draft'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Draft
                            </option>

                            <option
                                value="Active"
                                <?php
                                echo $status === 'Active'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Active
                            </option>

                            <option
                                value="Completed"
                                <?php
                                echo $status === 'Completed'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Completed
                            </option>

                            <option
                                value="Cancelled"
                                <?php
                                echo $status === 'Cancelled'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Cancelled
                            </option>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Filter
                        </button>

                    </div>


                    <div class="col-md-2">

                        <a
                            href="<?php echo BASE_URL; ?>/manager/marketing-plans/index.php"
                            class="btn btn-outline-secondary w-100"
                        >
                            Reset
                        </a>

                    </div>


                </div>

            </form>

        </div>

    </div>


    <!-- PLANS TABLE -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Marketing Plans
            </h5>

        </div>


        <div class="card-body p-0">


            <?php if (empty($plans)): ?>

                <div class="p-4 text-muted">
                    No marketing plans found.
                </div>

            <?php else: ?>


                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">


                        <thead>

                            <tr>

                                <th>
                                    Plan
                                </th>

                                <th>
                                    Focus Area
                                </th>

                                <th>
                                    Duration
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Created By
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $plans as $plan
                            ): ?>


                                <tr>


                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $plan['plan_title']
                                            );
                                            ?>
                                        </strong>

                                        <?php if (
                                            !empty(
                                                $plan['objective']
                                            )
                                        ): ?>

                                            <div class="small text-muted">

                                                <?php
                                                echo htmlspecialchars(
                                                    mb_strimwidth(
                                                        $plan['objective'],
                                                        0,
                                                        100,
                                                        '...'
                                                    )
                                                );
                                                ?>

                                            </div>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $plan['focus_area']
                                                ?: '-'
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo date(
                                            'd M Y',
                                            strtotime(
                                                $plan['start_date']
                                            )
                                        );

                                        ?>

                                        -

                                        <?php

                                        echo date(
                                            'd M Y',
                                            strtotime(
                                                $plan['end_date']
                                            )
                                        );

                                        ?>

                                    </td>


                                    <td>


                                        <?php

                                        $badge_class =
                                            'bg-secondary';

                                        if (
                                            $plan['status']
                                            === 'Draft'
                                        ) {

                                            $badge_class =
                                                'bg-secondary';
                                        }

                                        if (
                                            $plan['status']
                                            === 'Active'
                                        ) {

                                            $badge_class =
                                                'bg-primary';
                                        }

                                        if (
                                            $plan['status']
                                            === 'Completed'
                                        ) {

                                            $badge_class =
                                                'bg-success';
                                        }

                                        if (
                                            $plan['status']
                                            === 'Cancelled'
                                        ) {

                                            $badge_class =
                                                'bg-danger';
                                        }

                                        ?>


                                        <span
                                            class="badge <?php
                                            echo $badge_class;
                                            ?>"
                                        >
                                            <?php
                                            echo htmlspecialchars(
                                                $plan['status']
                                            );
                                            ?>
                                        </span>


                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $plan['created_by_name']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <a
                                            href="<?php echo BASE_URL; ?>/manager/marketing-plans/view.php?id=<?php echo (int) $plan['id']; ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            View
                                        </a>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>


        </div>

    </div>


</div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>