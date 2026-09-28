<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

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
| CURRENT MONTH
|--------------------------------------------------------------------------
*/

$month_start = date('Y-m-01 00:00:00');
$next_month = date(
    'Y-m-01 00:00:00',
    strtotime('first day of next month')
);

$month_label = date('F Y');


/*
|--------------------------------------------------------------------------
| LEAD SUMMARY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_leads,

        SUM(
            CASE
                WHEN status = 'New'
                THEN 1
                ELSE 0
            END
        ) AS new_leads,

        SUM(
            CASE
                WHEN status = 'Visited'
                THEN 1
                ELSE 0
            END
        ) AS visited_leads,

        SUM(
            CASE
                WHEN status = 'Converted'
                THEN 1
                ELSE 0
            END
        ) AS converted_leads,

        SUM(
            CASE
                WHEN status = 'Lost'
                THEN 1
                ELSE 0
            END
        ) AS lost_leads

    FROM leads

    WHERE created_at >= ?
      AND created_at < ?
");

$stmt->execute([
    $month_start,
    $next_month
]);

$lead_summary = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| TASK SUMMARY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_tasks,

        SUM(
            CASE
                WHEN status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_tasks,

        SUM(
            CASE
                WHEN status IN ('Pending', 'In Progress')
                THEN 1
                ELSE 0
            END
        ) AS open_tasks

    FROM tasks

    WHERE created_at >= ?
      AND created_at < ?
");

$stmt->execute([
    $month_start,
    $next_month
]);

$task_summary = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| VISIT SUMMARY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_visits,

        SUM(
            CASE
                WHEN status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_visits,

        SUM(
            CASE
                WHEN status = 'Planned'
                THEN 1
                ELSE 0
            END
        ) AS planned_visits

    FROM marketing_visits

    WHERE created_at >= ?
      AND created_at < ?
");

$stmt->execute([
    $month_start,
    $next_month
]);

$visit_summary = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| APPOINTMENT SUMMARY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_appointments,

        SUM(
            CASE
                WHEN status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_appointments,

        SUM(
            CASE
                WHEN status = 'No Show'
                THEN 1
                ELSE 0
            END
        ) AS no_show_appointments

    FROM appointments

    WHERE appointment_date >= ?
      AND appointment_date < ?
");

$stmt->execute([
    $month_start,
    $next_month
]);

$appointment_summary = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| ACTIVITY SUMMARY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_activities
    FROM lead_activities

    WHERE activity_at >= ?
      AND activity_at < ?
");

$stmt->execute([
    $month_start,
    $next_month
]);

$activity_summary = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| ACTION REQUIRED COUNTS
|--------------------------------------------------------------------------
*/

/* Overdue follow-ups */

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM leads
    WHERE next_action_at IS NOT NULL
      AND next_action_at < NOW()
      AND status NOT IN ('Converted', 'Lost')
");

$overdue_followups =
    (int) $stmt->fetch()['total'];


/* Unassigned leads */

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM leads
    WHERE assigned_to IS NULL
");

$unassigned_leads =
    (int) $stmt->fetch()['total'];


/* Overdue tasks */

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE due_date < NOW()
      AND status NOT IN ('Completed', 'Cancelled')
");

$overdue_tasks =
    (int) $stmt->fetch()['total'];


/* Overdue visits */

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM marketing_visits
    WHERE visit_date < NOW()
      AND status = 'Planned'
");

$overdue_visits =
    (int) $stmt->fetch()['total'];


$total_action_items =
    $overdue_followups
    + $unassigned_leads
    + $overdue_tasks
    + $overdue_visits;


/*
|--------------------------------------------------------------------------
| MARKETING PLANS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        plan_title,
        focus_area,
        start_date,
        end_date,
        status

    FROM marketing_plans

    WHERE status IN ('Draft', 'Active')

    ORDER BY
        CASE
            WHEN status = 'Active'
            THEN 0
            ELSE 1
        END,
        start_date ASC

    LIMIT 5
");

$plans = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| CONVENIENT VALUES
|--------------------------------------------------------------------------
*/

$total_leads =
    (int) ($lead_summary['total_leads'] ?? 0);

$new_leads =
    (int) ($lead_summary['new_leads'] ?? 0);

$visited_leads =
    (int) ($lead_summary['visited_leads'] ?? 0);

$converted_leads =
    (int) ($lead_summary['converted_leads'] ?? 0);

$lost_leads =
    (int) ($lead_summary['lost_leads'] ?? 0);


$total_tasks =
    (int) ($task_summary['total_tasks'] ?? 0);

$completed_tasks =
    (int) ($task_summary['completed_tasks'] ?? 0);

$open_tasks =
    (int) ($task_summary['open_tasks'] ?? 0);


$total_visits =
    (int) ($visit_summary['total_visits'] ?? 0);

$completed_visits =
    (int) ($visit_summary['completed_visits'] ?? 0);

$planned_visits =
    (int) ($visit_summary['planned_visits'] ?? 0);


$total_appointments =
    (int) (
        $appointment_summary[
            'total_appointments'
        ] ?? 0
    );

$completed_appointments =
    (int) (
        $appointment_summary[
            'completed_appointments'
        ] ?? 0
    );

$no_show_appointments =
    (int) (
        $appointment_summary[
            'no_show_appointments'
        ] ?? 0
    );


$total_activities =
    (int) (
        $activity_summary[
            'total_activities'
        ] ?? 0
    );


$conversion_rate = 0;

if ($total_leads > 0) {

    $conversion_rate =
        round(
            ($converted_leads / $total_leads) * 100,
            1
        );
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>


<div class="container py-4">


    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Management Review
            </h2>

            <p class="text-muted mb-0">
                Monthly review of marketing activities and outcomes.
            </p>

        </div>


        <div class="d-flex gap-2">

            <a
                href="<?php echo BASE_URL; ?>/manager/action-required.php"
                class="btn btn-outline-danger"
            >
                Action Required
            </a>

            <a
                href="<?php echo BASE_URL; ?>/marketing/marketing-plans/index.php"
                class="btn btn-primary"
            >
                Marketing Plans
            </a>

        </div>

    </div>


    <!-- PERIOD -->

    <div class="alert alert-light border mb-4">

        Review Period:

        <strong>
            <?php echo htmlspecialchars($month_label); ?>
        </strong>

    </div>


    <!-- KPI CARDS -->

    <div class="row g-4 mb-4">


        <!-- LEADS -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Leads
                    </h6>

                    <h3>
                        <?php
                        echo $total_leads;
                        ?>
                    </h3>

                    <div class="small text-muted">

                        New:
                        <?php echo $new_leads; ?>

                        |

                        Visited:
                        <?php echo $visited_leads; ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- CONVERTED -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Converted Leads
                    </h6>

                    <h3 class="text-success">
                        <?php
                        echo $converted_leads;
                        ?>
                    </h3>

                    <div class="small text-muted">

                        Conversion Rate:
                        <?php
                        echo $conversion_rate;
                        ?>%

                    </div>

                </div>

            </div>

        </div>


        <!-- VISITS -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Marketing Visits
                    </h6>

                    <h3>
                        <?php
                        echo $total_visits;
                        ?>
                    </h3>

                    <div class="small text-muted">

                        Completed:
                        <?php
                        echo $completed_visits;
                        ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- ACTIVITIES -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Activities
                    </h6>

                    <h3>
                        <?php
                        echo $total_activities;
                        ?>
                    </h3>

                    <div class="small text-muted">
                        Lead activities recorded
                    </div>

                </div>

            </div>

        </div>


    </div>


    <!-- OPERATIONAL REVIEW -->

    <div class="row g-4 mb-4">


        <!-- TASKS -->

        <div class="col-lg-4">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Task Review
                    </h5>

                </div>

                <div class="card-body">

                    <p>
                        <strong>
                            Total Tasks:
                        </strong>

                        <?php
                        echo $total_tasks;
                        ?>
                    </p>


                    <p class="text-success">
                        <strong>
                            Completed:
                        </strong>

                        <?php
                        echo $completed_tasks;
                        ?>
                    </p>


                    <p class="text-warning">
                        <strong>
                            Open:
                        </strong>

                        <?php
                        echo $open_tasks;
                        ?>
                    </p>


                </div>

            </div>

        </div>


        <!-- VISITS -->

        <div class="col-lg-4">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Visit Review
                    </h5>

                </div>

                <div class="card-body">

                    <p>
                        <strong>
                            Total:
                        </strong>

                        <?php
                        echo $total_visits;
                        ?>
                    </p>


                    <p class="text-success">
                        <strong>
                            Completed:
                        </strong>

                        <?php
                        echo $completed_visits;
                        ?>
                    </p>


                    <p>
                        <strong>
                            Planned:
                        </strong>

                        <?php
                        echo $planned_visits;
                        ?>
                    </p>


                </div>

            </div>

        </div>


        <!-- APPOINTMENTS -->

        <div class="col-lg-4">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Appointment Review
                    </h5>

                </div>

                <div class="card-body">

                    <p>
                        <strong>
                            Total:
                        </strong>

                        <?php
                        echo $total_appointments;
                        ?>
                    </p>


                    <p class="text-success">
                        <strong>
                            Completed:
                        </strong>

                        <?php
                        echo $completed_appointments;
                        ?>
                    </p>


                    <p class="text-danger">
                        <strong>
                            No Show:
                        </strong>

                        <?php
                        echo $no_show_appointments;
                        ?>
                    </p>


                </div>

            </div>

        </div>


    </div>


    <!-- ACTION REQUIRED SUMMARY -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    Action Required Summary
                </h5>

                <span class="badge bg-warning text-dark">
                    <?php
                    echo $total_action_items;
                    ?>
                    Items
                </span>

            </div>

        </div>


        <div class="card-body">


            <div class="row g-3">


                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <div class="text-muted small">
                            Overdue Follow-ups
                        </div>

                        <h4 class="mb-0 text-danger">

                            <?php
                            echo $overdue_followups;
                            ?>

                        </h4>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <div class="text-muted small">
                            Unassigned Leads
                        </div>

                        <h4 class="mb-0">

                            <?php
                            echo $unassigned_leads;
                            ?>

                        </h4>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <div class="text-muted small">
                            Overdue Tasks
                        </div>

                        <h4 class="mb-0 text-danger">

                            <?php
                            echo $overdue_tasks;
                            ?>

                        </h4>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <div class="text-muted small">
                            Overdue Visits
                        </div>

                        <h4 class="mb-0 text-danger">

                            <?php
                            echo $overdue_visits;
                            ?>

                        </h4>

                    </div>

                </div>


            </div>


            <div class="mt-3">

                <a
                    href="<?php echo BASE_URL; ?>/manager/action-required.php"
                    class="btn btn-outline-danger"
                >
                    Review Action Required
                </a>

            </div>


        </div>

    </div>


    <!-- MARKETING PLANS -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    Current Marketing Plans
                </h5>

                <a
                    href="<?php echo BASE_URL; ?>/marketing/marketing-plans/index.php"
                    class="btn btn-sm btn-outline-primary"
                >
                    View All Plans
                </a>

            </div>

        </div>


        <div class="card-body p-0">


            <?php if (empty($plans)): ?>

                <div class="p-4 text-muted">
                    No active or draft marketing plans found.
                </div>


            <?php else: ?>


                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Plan
                                </th>

                                <th>
                                    Focus Area
                                </th>

                                <th>
                                    Start
                                </th>

                                <th>
                                    End
                                </th>

                                <th>
                                    Status
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

                                        <?php
                                        echo htmlspecialchars(
                                            $plan['plan_title']
                                        );
                                        ?>

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

                                    </td>


                                    <td>

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

                                        $badge =
                                            $plan['status']
                                            === 'Active'
                                                ? 'bg-primary'
                                                : 'bg-secondary';

                                        ?>

                                        <span
                                            class="badge <?php
                                            echo $badge;
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

                                        <a
                                            href="<?php echo BASE_URL; ?>/marketing/marketing-plans/view.php?id=<?php echo (int) $plan['id']; ?>"
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


    <!-- MANAGEMENT ACTIONS -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Management Actions
            </h5>

        </div>


        <div class="card-body">

            <a
                href="<?php echo BASE_URL; ?>/manager/team/performance.php"
                class="btn btn-outline-primary me-2 mb-2"
            >
                Team Performance
            </a>


            <a
                href="<?php echo BASE_URL; ?>/manager/action-required.php"
                class="btn btn-outline-danger me-2 mb-2"
            >
                Action Required
            </a>


            <a
                href="<?php echo BASE_URL; ?>/marketing/marketing-plans/index.php"
                class="btn btn-outline-success mb-2"
            >
                Marketing Plans
            </a>

        </div>

    </div>


</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>