
<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

require_login();

$user = current_user();

if (!$user || $user['role'] !== 'marketing') {
    http_response_code(403);
    exit('Access denied.');
}

$user_id = (int) $user['id'];


/*

| PERIOD

*/

$period = $_GET['period'] ?? '30';

switch ($period) {

    case 'today':
        $start_date = date('Y-m-d 00:00:00');
        $period_label = 'Today';
        break;

    case '7':
        $start_date = date(
            'Y-m-d 00:00:00',
            strtotime('-6 days')
        );
        $period_label = 'Last 7 Days';
        break;

    case '30':
    default:
        $start_date = date(
            'Y-m-d 00:00:00',
            strtotime('-29 days')
        );
        $period = '30';
        $period_label = 'Last 30 Days';
        break;
}


/*

| TASKS

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
                WHEN status = 'In Progress'
                THEN 1
                ELSE 0
            END
        ) AS in_progress_tasks,
        SUM(
            CASE
                WHEN status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_tasks
    FROM tasks
    WHERE assigned_to = ?
      AND created_at >= ?
");

$stmt->execute([
    $user_id,
    $start_date
]);

$task_stats = $stmt->fetch();


/*

| MARKETING VISITS

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
        ) AS planned_visits,

        SUM(
            CASE
                WHEN status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_visits

    FROM marketing_visits

    WHERE assigned_to = ?
      AND created_at >= ?
");

$stmt->execute([
    $user_id,
    $start_date
]);

$visit_stats = $stmt->fetch();


/*

| LEAD ACTIVITIES

*/

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_activities
    FROM lead_activities
    WHERE user_id = ?
      AND activity_at >= ?
");

$stmt->execute([
    $user_id,
    $start_date
]);

$activity_stats = $stmt->fetch();


/*

| LEADS ASSIGNED

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
                WHEN status = 'Appointment'
                THEN 1
                ELSE 0
            END
        ) AS appointment_leads,

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

    WHERE assigned_to = ?
      AND created_at >= ?
");

$stmt->execute([
    $user_id,
    $start_date
]);

$lead_stats = $stmt->fetch();


/*

| FEEDBACK / RELATIONSHIPS

*/

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_feedback,

        SUM(
            CASE
                WHEN relationship_status = 'Positive'
                THEN 1
                ELSE 0
            END
        ) AS positive_feedback,

        SUM(
            CASE
                WHEN relationship_status = 'Interested'
                THEN 1
                ELSE 0
            END
        ) AS interested_feedback,

        SUM(
            CASE
                WHEN relationship_status = 'Referral Potential'
                THEN 1
                ELSE 0
            END
        ) AS referral_potential

    FROM visit_feedback vf

    INNER JOIN marketing_visits mv
        ON vf.visit_id = mv.id

    WHERE mv.assigned_to = ?
      AND vf.created_at >= ?
");

$stmt->execute([
    $user_id,
    $start_date
]);

$feedback_stats = $stmt->fetch();


/*

| CONVERSION RATE

*/

$total_leads =
    (int) ($lead_stats['total_leads'] ?? 0);

$converted_leads =
    (int) ($lead_stats['converted_leads'] ?? 0);

$conversion_rate = 0;

if ($total_leads > 0) {

    $conversion_rate =
        round(
            ($converted_leads / $total_leads) * 100,
            1
        );
}


/*

| VISIT COMPLETION RATE

*/

$total_visits =
    (int) ($visit_stats['total_visits'] ?? 0);

$completed_visits =
    (int) ($visit_stats['completed_visits'] ?? 0);

$visit_completion_rate = 0;

if ($total_visits > 0) {

    $visit_completion_rate =
        round(
            ($completed_visits / $total_visits) * 100,
            1
        );
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>


<div class="container py-4">


    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                My Performance
            </h1>

            <p class="text-muted mb-0">
                Your marketing activity and performance summary.
            </p>

        </div>


        <!-- PERIOD FILTER -->

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

        Showing performance for:

        <strong>
            <?php echo htmlspecialchars($period_label); ?>
        </strong>

    </div>


    <!-- TASK PERFORMANCE -->

    <div class="row g-4 mb-4">


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Tasks
                    </h6>

                    <h3 class="mb-0">
                        <?php
                        echo (int) (
                            $task_stats['total_tasks']
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
                        Completed Tasks
                    </h6>

                    <h3 class="mb-0 text-success">
                        <?php
                        echo (int) (
                            $task_stats['completed_tasks']
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
                        In Progress
                    </h6>

                    <h3 class="mb-0">
                        <?php
                        echo (int) (
                            $task_stats['in_progress_tasks']
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
                        Pending Tasks
                    </h6>

                    <h3 class="mb-0 text-warning">
                        <?php
                        echo (int) (
                            $task_stats['pending_tasks']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>

            </div>

        </div>


    </div>


    <!-- LEAD PERFORMANCE -->

    <div class="row g-4 mb-4">


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

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Visited Leads
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $lead_stats['visited_leads']
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
                        Converted
                    </h6>

                    <h3 class="text-success">
                        <?php
                        echo $converted_leads;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Conversion Rate
                    </h6>

                    <h3>
                        <?php
                        echo $conversion_rate;
                        ?>%
                    </h3>

                </div>

            </div>

        </div>


    </div>


    <!-- VISIT PERFORMANCE -->

    <div class="row g-4 mb-4">


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Visits
                    </h6>

                    <h3>
                        <?php
                        echo $total_visits;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Completed Visits
                    </h6>

                    <h3 class="text-success">
                        <?php
                        echo $completed_visits;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Visit Completion
                    </h6>

                    <h3>
                        <?php
                        echo $visit_completion_rate;
                        ?>%
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Activities
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $activity_stats['total_activities']
                            ?? 0
                        );
                        ?>
                    </h3>

                </div>

            </div>

        </div>


    </div>


    <!-- RELATIONSHIP PERFORMANCE -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Relationship & Referral Activity
            </h5>

        </div>


        <div class="card-body">

            <div class="row g-4">


                <div class="col-md-3">

                    <h6 class="text-muted">
                        Total Feedback
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $feedback_stats[
                                'total_feedback'
                            ] ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-3">

                    <h6 class="text-muted">
                        Positive
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $feedback_stats[
                                'positive_feedback'
                            ] ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-3">

                    <h6 class="text-muted">
                        Interested
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $feedback_stats[
                                'interested_feedback'
                            ] ?? 0
                        );
                        ?>
                    </h3>

                </div>


                <div class="col-md-3">

                    <h6 class="text-muted">
                        Referral Potential
                    </h6>

                    <h3>
                        <?php
                        echo (int) (
                            $feedback_stats[
                                'referral_potential'
                            ] ?? 0
                        );
                        ?>
                    </h3>

                </div>


            </div>

        </div>

    </div>


    <!-- QUICK LINKS -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Quick Access
            </h5>

        </div>


        <div class="card-body">

            <a
                href="<?php echo BASE_URL; ?>/marketing/tasks/index.php"
                class="btn btn-outline-primary me-2 mb-2"
            >
                My Tasks
            </a>

            <a
                href="<?php echo BASE_URL; ?>/marketing/visits/index.php"
                class="btn btn-outline-primary me-2 mb-2"
            >
                My Visits
            </a>

            <a
                href="<?php echo BASE_URL; ?>/telecaller/appointments/index.php"
                class="btn btn-outline-primary mb-2"
            >
                Appointments
            </a>

        </div>

    </div>


</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>