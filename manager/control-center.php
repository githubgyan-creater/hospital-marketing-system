<?php

require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', 'manager');

$user = current_user();

/*
|--------------------------------------------------------------------------
| Date / Time
|--------------------------------------------------------------------------
*/

$today = date('Y-m-d');

$month_start = date('Y-m-01');

$month_end = date('Y-m-t');

/*
|--------------------------------------------------------------------------
| Today's Leads
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE DATE(created_at) = ?
");

$stmt->execute([
    $today
]);

$todays_leads = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Follow-ups Due Today
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE next_action_at IS NOT NULL
      AND DATE(next_action_at) = ?
      AND status NOT IN ('Converted', 'Lost')
");

$stmt->execute([
    $today
]);

$followups_due = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Appointments Today
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM appointments
    WHERE DATE(appointment_date) = ?
      AND status NOT IN ('Cancelled', 'No Show')
");

$stmt->execute([
    $today
]);

$appointments_today = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Active Events
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM events
    WHERE status IN ('Planned', 'Ongoing')
      AND event_date >= ?
");

$stmt->execute([
    $today
]);

$active_events = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| New Leads
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE status = 'New'
");

$stmt->execute();

$new_leads = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Converted Leads - Current Month
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE status = 'Converted'
      AND DATE(created_at) BETWEEN ? AND ?
");

$stmt->execute([
    $month_start,
    $month_end
]);

$converted_leads = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Overdue Follow-ups
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE next_action_at IS NOT NULL
      AND next_action_at < NOW()
      AND status NOT IN ('Converted', 'Lost')
");

$stmt->execute();

$overdue_followups = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Today's Team Activities
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM lead_activities
    WHERE DATE(activity_at) = ?
");

$stmt->execute([
    $today
]);

$todays_activities = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Today's Leads List
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        l.id,
        l.name,
        l.phone,
        l.service_interest,
        l.status,
        l.priority,
        l.next_action_type,
        l.next_action_at,
        u.name AS assigned_staff

    FROM leads l

    LEFT JOIN users u
        ON l.assigned_to = u.id

    WHERE DATE(l.created_at) = ?

    ORDER BY l.created_at DESC

    LIMIT 10
");

$stmt->execute([
    $today
]);

$todays_leads_list = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Follow-ups Due / Overdue List
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        l.id,
        l.name,
        l.phone,
        l.status,
        l.priority,
        l.next_action_type,
        l.next_action_at,
        u.name AS assigned_staff

    FROM leads l

    LEFT JOIN users u
        ON l.assigned_to = u.id

    WHERE l.next_action_at IS NOT NULL
      AND l.next_action_at <= DATE_ADD(NOW(), INTERVAL 1 DAY)
      AND l.status NOT IN ('Converted', 'Lost')

    ORDER BY l.next_action_at ASC

    LIMIT 10
");

$stmt->execute();

$followups_list = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Today's Appointments List
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.lead_id,
        a.appointment_date,
        a.appointment_type,
        a.status,

        l.name AS lead_name,
        l.phone AS lead_phone,
        l.service_interest

    FROM appointments a

    INNER JOIN leads l
        ON a.lead_id = l.id

    WHERE DATE(a.appointment_date) = ?

    ORDER BY a.appointment_date ASC

    LIMIT 10
");

$stmt->execute([
    $today
]);

$todays_appointments = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Active Events List
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        event_name,
        event_type,
        event_date,
        location,
        status

    FROM events

    WHERE status IN ('Planned', 'Ongoing')
      AND event_date >= ?

    ORDER BY event_date ASC

    LIMIT 10
");

$stmt->execute([
    $today
]);

$active_events_list = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Recent Team Activity
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        la.id,
        la.activity_type,
        la.description,
        la.activity_at,

        u.name AS user_name,

        l.id AS lead_id,
        l.name AS lead_name

    FROM lead_activities la

    INNER JOIN users u
        ON la.user_id = u.id

    INNER JOIN leads l
        ON la.lead_id = l.id

    ORDER BY la.activity_at DESC

    LIMIT 10
");

$team_activities = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function status_class(string $status): string
{
    switch ($status) {

        case 'New':
            return 'bg-primary';

        case 'Contacted':
            return 'bg-info text-dark';

        case 'Interested':
            return 'bg-success';

        case 'Converted':
            return 'bg-success';

        case 'Lost':
            return 'bg-danger';

        case 'Scheduled':
            return 'bg-primary';

        case 'Confirmed':
            return 'bg-success';

        case 'Completed':
            return 'bg-dark';

        case 'Cancelled':
            return 'bg-danger';

        case 'No Show':
            return 'bg-warning text-dark';

        case 'Planned':
            return 'bg-primary';

        case 'Ongoing':
            return 'bg-warning text-dark';

        default:
            return 'bg-secondary';
    }
}


function priority_class(string $priority): string
{
    switch ($priority) {

        case 'High':
            return 'bg-danger';

        case 'Medium':
            return 'bg-warning text-dark';

        case 'Low':
            return 'bg-secondary';

        default:
            return 'bg-secondary';
    }
}


$page_title = 'Manager Control Center';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manager Control Center
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f7f5ef;
            color: #243746;
        }

        .hm-card {
            background: #ffffff;
            border: 1px solid #e5e1d7;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(23, 50, 77, 0.06);
        }

        .hm-primary {
            background: #17324d;
            color: #ffffff;
            border: none;
        }

        .hm-primary:hover {
            background: #12283d;
            color: #ffffff;
        }

        .hm-muted {
            color: #71808c;
        }

        .section-title {
            color: #17324d;
            font-weight: 700;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #17324d;
        }

        .metric-label {
            color: #71808c;
            font-size: 0.9rem;
        }

        .activity-item {
            border-bottom: 1px solid #eeeeee;
            padding: 12px 0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .table th {
            white-space: nowrap;
        }

        .small-action {
            font-size: 0.82rem;
        }

    </style>

</head>

<body>

<div class="container py-4">


    <!-- ========================================================= -->
    <!-- PAGE HEADER -->
    <!-- ========================================================= -->

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Manager Control Center
            </h2>

            <p class="hm-muted mb-0">
                Monitor today's hospital marketing operations.
            </p>

        </div>

        <div class="mt-3 mt-md-0">

            <span class="hm-muted me-2">
                Welcome,
            </span>

            <strong>
                <?php echo htmlspecialchars($user['name']); ?>
            </strong>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- TOP METRICS -->
    <!-- ========================================================= -->

    <div class="row g-3 mb-4">


        <!-- Today's Leads -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="metric-label">
                    Today's Leads
                </div>

                <div class="metric-value">
                    <?php echo $todays_leads; ?>
                </div>

                <div class="small hm-muted">
                    Leads created today
                </div>

            </div>

        </div>


        <!-- Follow-ups -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="metric-label">
                    Follow-ups Due
                </div>

                <div class="metric-value">
                    <?php echo $followups_due; ?>
                </div>

                <div class="small hm-muted">
                    Due today
                </div>

            </div>

        </div>


        <!-- Appointments -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="metric-label">
                    Appointments Today
                </div>

                <div class="metric-value">
                    <?php echo $appointments_today; ?>
                </div>

                <div class="small hm-muted">
                    Scheduled for today
                </div>

            </div>

        </div>


        <!-- Active Events -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="metric-label">
                    Active Events
                </div>

                <div class="metric-value">
                    <?php echo $active_events; ?>
                </div>

                <div class="small hm-muted">
                    Planned or ongoing
                </div>

            </div>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- SECONDARY METRICS -->
    <!-- ========================================================= -->

    <div class="row g-3 mb-4">


        <!-- New Leads -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="metric-label">
                    New Leads
                </div>

                <div class="metric-value">
                    <?php echo $new_leads; ?>
                </div>

                <div class="small hm-muted">
                    Currently in New status
                </div>

            </div>

        </div>


        <!-- Converted Leads -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="metric-label">
                    Converted This Month
                </div>

                <div class="metric-value">
                    <?php echo $converted_leads; ?>
                </div>

                <div class="small hm-muted">
                    Based on lead creation month
                </div>

            </div>

        </div>


        <!-- Overdue -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="metric-label">
                    Overdue Follow-ups
                </div>

                <div class="metric-value">
                    <?php echo $overdue_followups; ?>
                </div>

                <div class="small hm-muted">
                    Action required
                </div>

            </div>

        </div>


        <!-- Activities -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="metric-label">
                    Today's Activities
                </div>

                <div class="metric-value">
                    <?php echo $todays_activities; ?>
                </div>

                <div class="small hm-muted">
                    Team activity recorded today
                </div>

            </div>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- QUICK ACTIONS -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <h4 class="section-title mb-3">
            Quick Access
        </h4>

        <div class="d-flex flex-wrap gap-2">

            <a
                href="<?php echo BASE_URL; ?>/manager/leads/index.php"
                class="btn btn-outline-primary"
            >
                Leads
            </a>

            <a
                href="<?php echo BASE_URL; ?>/events/index.php"
                class="btn btn-outline-primary"
            >
                Events
            </a>

            <a
                href="<?php echo BASE_URL; ?>/manager/events/performance.php"
                class="btn btn-outline-primary"
            >
                Event Performance
            </a>

            <a
                href="<?php echo BASE_URL; ?>/telecaller/appointments/index.php"
                class="btn btn-outline-primary"
            >
                Appointments
            </a>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- TODAY'S LEADS -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h4 class="section-title mb-1">
                    Today's Leads
                </h4>

                <div class="hm-muted small">
                    Latest leads created today
                </div>

            </div>

            <a
                href="<?php echo BASE_URL; ?>/manager/leads/index.php"
                class="small-action text-decoration-none"
            >
                View All
            </a>

        </div>


        <?php if (empty($todays_leads_list)): ?>

            <div class="alert alert-light border mb-0">
                No leads were created today.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>Lead</th>
                            <th>Phone</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assigned To</th>
                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($todays_leads_list as $lead): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $lead['name']
                                    );
                                    ?>
                                </strong>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $lead['phone']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $lead['service_interest']
                                    ?: '-'
                                );
                                ?>
                            </td>

                            <td>

                                <span
                                    class="badge <?php echo status_class($lead['status']); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['status']
                                    );
                                    ?>

                                </span>

                            </td>

                            <td>

                                <span
                                    class="badge <?php echo priority_class($lead['priority']); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['priority']
                                    );
                                    ?>

                                </span>

                            </td>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $lead['assigned_staff']
                                    ?? 'Unassigned'
                                );
                                ?>

                            </td>

                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
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


    <!-- ========================================================= -->
    <!-- FOLLOW-UPS + APPOINTMENTS -->
    <!-- ========================================================= -->

    <div class="row g-4 mb-4">


        <!-- Follow-ups -->

        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>

                        <h4 class="section-title mb-1">
                            Follow-ups
                        </h4>

                        <div class="hm-muted small">
                            Due and overdue actions
                        </div>

                    </div>

                </div>


                <?php if (empty($followups_list)): ?>

                    <div class="alert alert-light border mb-0">
                        No pending follow-ups.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>Lead</th>
                                    <th>Next Action</th>
                                    <th>Due</th>
                                    <th>Staff</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($followups_list as $followup): ?>

                                <?php

                                $is_overdue =
                                    strtotime(
                                        $followup['next_action_at']
                                    ) < time();

                                ?>

                                <tr>

                                    <td>

                                        <a
                                            href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $followup['id']; ?>"
                                            class="text-decoration-none fw-semibold"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $followup['name']
                                            );
                                            ?>

                                        </a>

                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $followup['next_action_type']
                                            ?: '-'
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <span
                                            class="<?php echo $is_overdue ? 'text-danger fw-semibold' : ''; ?>"
                                        >

                                            <?php
                                            echo date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $followup['next_action_at']
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $followup['assigned_staff']
                                            ?? 'Unassigned'
                                        );
                                        ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>


        <!-- Appointments -->

        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>

                        <h4 class="section-title mb-1">
                            Today's Appointments
                        </h4>

                        <div class="hm-muted small">
                            Today's patient appointments
                        </div>

                    </div>

                </div>


                <?php if (empty($todays_appointments)): ?>

                    <div class="alert alert-light border mb-0">
                        No appointments scheduled for today.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>Lead</th>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>Status</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($todays_appointments as $appointment): ?>

                                <tr>

                                    <td>

                                        <div class="fw-semibold">

                                            <?php
                                            echo htmlspecialchars(
                                                $appointment['lead_name']
                                            );
                                            ?>

                                        </div>

                                        <div class="small hm-muted">

                                            <?php
                                            echo htmlspecialchars(
                                                $appointment['lead_phone']
                                            );
                                            ?>

                                        </div>

                                    </td>

                                    <td>

                                        <?php
                                        echo date(
                                            'h:i A',
                                            strtotime(
                                                $appointment['appointment_date']
                                            )
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $appointment['appointment_type']
                                            ?: '-'
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <span
                                            class="badge <?php echo status_class($appointment['status']); ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $appointment['status']
                                            );
                                            ?>

                                        </span>

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


    <!-- ========================================================= -->
    <!-- ACTIVE EVENTS -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h4 class="section-title mb-1">
                    Active Events
                </h4>

                <div class="hm-muted small">
                    Upcoming and ongoing marketing events
                </div>

            </div>

            <a
                href="<?php echo BASE_URL; ?>/events/index.php"
                class="small-action text-decoration-none"
            >
                View All
            </a>

        </div>


        <?php if (empty($active_events_list)): ?>

            <div class="alert alert-light border mb-0">
                No active events found.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>Event</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($active_events_list as $event): ?>

                        <tr>

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $event['event_name']
                                    );
                                    ?>

                                </strong>

                            </td>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $event['event_type']
                                );
                                ?>

                            </td>

                            <td>

                                <?php
                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $event['event_date']
                                    )
                                );
                                ?>

                            </td>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $event['location']
                                    ?: '-'
                                );
                                ?>

                            </td>

                            <td>

                                <span
                                    class="badge <?php echo status_class($event['status']); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $event['status']
                                    );
                                    ?>

                                </span>

                            </td>

                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/events/view.php?id=<?php echo (int) $event['id']; ?>"
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


    <!-- ========================================================= -->
    <!-- RECENT TEAM ACTIVITY -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h4 class="section-title mb-1">
                    Recent Team Activity
                </h4>

                <div class="hm-muted small">
                    Latest activity recorded by marketing staff
                </div>

            </div>

        </div>


        <?php if (empty($team_activities)): ?>

            <div class="alert alert-light border mb-0">
                No team activity found.
            </div>

        <?php else: ?>

            <?php foreach ($team_activities as $activity): ?>

                <div class="activity-item">

                    <div class="d-flex justify-content-between flex-wrap">

                        <div>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $activity['user_name']
                                );
                                ?>
                            </strong>

                            recorded

                            <span class="fw-semibold">

                                <?php
                                echo htmlspecialchars(
                                    $activity['activity_type']
                                );
                                ?>

                            </span>

                            for

                            <a
                                href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $activity['lead_id']; ?>"
                                class="text-decoration-none"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $activity['lead_name']
                                );
                                ?>

                            </a>

                        </div>

                        <div class="small hm-muted">

                            <?php
                            echo date(
                                'd M Y, h:i A',
                                strtotime(
                                    $activity['activity_at']
                                )
                            );
                            ?>

                        </div>

                    </div>

                    <?php if (!empty($activity['description'])): ?>

                        <div class="small hm-muted mt-1">

                            <?php
                            echo htmlspecialchars(
                                $activity['description']
                            );
                            ?>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>