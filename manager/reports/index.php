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

| DATE RANGE

*/

$period = $_GET['period'] ?? '30';

switch ($period) {

    case 'today':

        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');
        $period_label = 'Today';

        break;


    case '7':

        $start_date = date(
            'Y-m-d 00:00:00',
            strtotime('-6 days')
        );

        $end_date = date('Y-m-d 23:59:59');

        $period_label = 'Last 7 Days';

        break;


    case '30':
    default:

        $start_date = date(
            'Y-m-d 00:00:00',
            strtotime('-29 days')
        );

        $end_date = date('Y-m-d 23:59:59');

        $period = '30';

        $period_label = 'Last 30 Days';

        break;
}


/*

| LEAD REPORT

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
                WHEN status = 'Interested'
                THEN 1
                ELSE 0
            END
        ) AS interested_leads,

        SUM(
            CASE
                WHEN status = 'Follow-up'
                THEN 1
                ELSE 0
            END
        ) AS followup_leads,

        SUM(
            CASE
                WHEN status = 'Appointment'
                THEN 1
                ELSE 0
            END
        ) AS appointment_leads,

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

    WHERE created_at BETWEEN ? AND ?
");

$stmt->execute([
    $start_date,
    $end_date
]);

$lead_report = $stmt->fetch();


/*

| TASK REPORT

*/

$stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_tasks,

        SUM(
            CASE
                WHEN status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_tasks,

        SUM(
            CASE
                WHEN status = 'In Progress'
                THEN 1
                ELSE 0
            END
        ) AS in_progress_tasks,

        SUM(
            CASE
                WHEN status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_tasks,

        SUM(
            CASE
                WHEN status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_tasks

    FROM tasks

    WHERE created_at BETWEEN ? AND ?
");

$stmt->execute([
    $start_date,
    $end_date
]);

$task_report = $stmt->fetch();


/*

| VISIT REPORT

*/

$stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_visits,

        SUM(
            CASE
                WHEN status = 'Planned'
                THEN 1
                ELSE 0
            END
        ) AS planned_visits,

        SUM(
            CASE
                WHEN status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_visits,

        SUM(
            CASE
                WHEN status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_visits

    FROM marketing_visits

    WHERE created_at BETWEEN ? AND ?
");

$stmt->execute([
    $start_date,
    $end_date
]);

$visit_report = $stmt->fetch();


/*

| APPOINTMENT REPORT

*/

$stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_appointments,

        SUM(
            CASE
                WHEN status = 'Scheduled'
                THEN 1
                ELSE 0
            END
        ) AS scheduled_appointments,

        SUM(
            CASE
                WHEN status = 'Confirmed'
                THEN 1
                ELSE 0
            END
        ) AS confirmed_appointments,

        SUM(
            CASE
                WHEN status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_appointments,

        SUM(
            CASE
                WHEN status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_appointments,

        SUM(
            CASE
                WHEN status = 'No Show'
                THEN 1
                ELSE 0
            END
        ) AS no_show_appointments

    FROM appointments

    WHERE appointment_date BETWEEN ? AND ?
");

$stmt->execute([
    $start_date,
    $end_date
]);

$appointment_report = $stmt->fetch();


/*

| ACTIVITY REPORT

*/

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_activities

    FROM lead_activities

    WHERE activity_at BETWEEN ? AND ?
");

$stmt->execute([
    $start_date,
    $end_date
]);

$activity_report = $stmt->fetch();


/*

| CONVERSION RATE

*/

$total_leads = (int) (
    $lead_report['total_leads'] ?? 0
);

$converted_leads = (int) (
    $lead_report['converted_leads'] ?? 0
);

$conversion_rate = 0;

if ($total_leads > 0) {

    $conversion_rate = round(
        ($converted_leads / $total_leads) * 100,
        1
    );
}

?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="container py-4">


    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                Marketing Reports
            </h1>

            <p class="text-muted mb-0">
                Marketing performance summary and activity report.
            </p>

        </div>


        <!-- PERIOD -->

        <form method="GET">

            <select
                name="period"
                class="form-select"
                onchange="this.form.submit()"
            >

                <option
                    value="today"
                    <?php
                    echo $period === 'today'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Today
                </option>

                <option
                    value="7"
                    <?php
                    echo $period === '7'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Last 7 Days
                </option>

                <option
                    value="30"
                    <?php
                    echo $period === '30'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Last 30 Days
                </option>

            </select>

        </form>

    </div>


    <!-- PERIOD -->

    <div class="alert alert-light border mb-4">

        Report Period:

        <strong>
            <?php echo htmlspecialchars($period_label); ?>
        </strong>

    </div>


    <!-- LEAD REPORT -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Lead Performance
            </h5>

        </div>


        <div class="card-body">

            <div class="row g-4">


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        Total
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $lead_report['total_leads']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        New
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $lead_report['new_leads']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        Interested
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $lead_report['interested_leads']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        Follow-ups
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $lead_report['followup_leads']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        Visited
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $lead_report['visited_leads']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        Converted
                    </h6>

                    <h3 class="text-success">
                        <?php
                        echo $converted_leads;
                        ?>
                    </h3>

                </div>


            </div>


            <hr>


            <div class="row">


                <div class="col-md-6">

                    <strong>
                        Conversion Rate:
                    </strong>

                    <?php echo $conversion_rate; ?>%

                </div>


                <div class="col-md-6">

                    <strong>
                        Lost:
                    </strong>

                    <?php
                    echo (int) (
                        $lead_report['lost_leads']
                        ?? 0
                    );
                    ?>

                </div>


            </div>

        </div>

    </div>


    <!-- TASK + VISIT -->

    <div class="row g-4 mb-4">


        <!-- TASKS -->

        <div class="col-lg-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Task Performance
                    </h5>

                </div>


                <div class="card-body">


                    <p>
                        <strong>
                            Total Tasks:
                        </strong>

                        <?php
                        echo (int) (
                            $task_report['total_tasks']
                            ?? 0
                        );
                        ?>
                    </p>


                    <p>
                        <strong>
                            Pending:
                        </strong>

                        <?php
                        echo (int) (
                            $task_report['pending_tasks']
                            ?? 0
                        );
                        ?>
                    </p>


                    <p>
                        <strong>
                            In Progress:
                        </strong>

                        <?php
                        echo (int) (
                            $task_report['in_progress_tasks']
                            ?? 0
                        );
                        ?>
                    </p>


                    <p class="text-success">

                        <strong>
                            Completed:
                        </strong>

                        <?php
                        echo (int) (
                            $task_report['completed_tasks']
                            ?? 0
                        );
                        ?>

                    </p>


                    <p>

                        <strong>
                            Cancelled:
                        </strong>

                        <?php
                        echo (int) (
                            $task_report['cancelled_tasks']
                            ?? 0
                        );
                        ?>

                    </p>


                </div>

            </div>

        </div>


        <!-- VISITS -->

        <div class="col-lg-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Marketing Visits
                    </h5>

                </div>


                <div class="card-body">


                    <p>
                        <strong>
                            Total Visits:
                        </strong>

                        <?php
                        echo (int) (
                            $visit_report['total_visits']
                            ?? 0
                        );
                        ?>
                    </p>


                    <p>
                        <strong>
                            Planned:
                        </strong>

                        <?php
                        echo (int) (
                            $visit_report['planned_visits']
                            ?? 0
                        );
                        ?>
                    </p>


                    <p class="text-success">

                        <strong>
                            Completed:
                        </strong>

                        <?php
                        echo (int) (
                            $visit_report['completed_visits']
                            ?? 0
                        );
                        ?>

                    </p>


                    <p>

                        <strong>
                            Cancelled:
                        </strong>

                        <?php
                        echo (int) (
                            $visit_report['cancelled_visits']
                            ?? 0
                        );
                        ?>

                    </p>


                </div>

            </div>

        </div>


    </div>


    <!-- APPOINTMENTS -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Appointment Performance
            </h5>

        </div>


        <div class="card-body">

            <div class="row g-4">


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        Total
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $appointment_report[
                                'total_appointments'
                            ] ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        Scheduled
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $appointment_report[
                                'scheduled_appointments'
                            ] ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        Confirmed
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $appointment_report[
                                'confirmed_appointments'
                            ] ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        Completed
                    </h6>

                    <h3 class="text-success">
                        <?php
                        echo (int) (
                            $appointment_report[
                                'completed_appointments'
                            ] ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        Cancelled
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $appointment_report[
                                'cancelled_appointments'
                            ] ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-4 col-lg-2">

                    <h6 class="text-muted">
                        No Show
                    </h6>

                    <h3 class="text-danger">
                        <?php
                        echo (int) (
                            $appointment_report[
                                'no_show_appointments'
                            ] ?? 0
                        );
                        ?>
                    </h3>

                </div>


            </div>

        </div>

    </div>


    <!-- ACTIVITY -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <h5>
                Lead Activities
            </h5>

            <h3 class="mb-0">

                <?php
                echo (int) (
                    $activity_report[
                        'total_activities'
                    ] ?? 0
                );
                ?>

            </h3>

            <div class="text-muted">
                Activities recorded during the selected period.
            </div>

        </div>

    </div>


    <!-- QUICK ACCESS -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Quick Access
            </h5>

        </div>


        <div class="card-body">


            <a
                href="<?php echo BASE_URL; ?>/manager/control-center.php"
                class="btn btn-outline-primary me-2 mb-2"
            >
                Control Center
            </a>


            <a
                href="<?php echo BASE_URL; ?>/manager/team/performance.php"
                class="btn btn-outline-primary me-2 mb-2"
            >
                Team Performance
            </a>


            <a
                href="<?php echo BASE_URL; ?>/manager/action-required.php"
                class="btn btn-outline-danger mb-2"
            >
                Action Required
            </a>


        </div>

    </div>


</div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>