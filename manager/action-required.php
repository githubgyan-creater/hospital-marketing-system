 <?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

require_login();

$user = current_user();

if (
    !$user ||
    !in_array(
        $user['role'],
        ['admin', 'manager'],
        true
    )
) {
    http_response_code(403);
    exit('Access denied.');
}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$page_title = 'Action Required';


/*
|--------------------------------------------------------------------------
| OVERDUE FOLLOW-UPS
|--------------------------------------------------------------------------
*/

$overdue_followups_stmt = $pdo->query("
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
      AND l.next_action_at < NOW()
      AND l.status NOT IN ('Converted', 'Lost')
    ORDER BY l.next_action_at ASC
");

$overdue_followups =
    $overdue_followups_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| UNASSIGNED LEADS
|--------------------------------------------------------------------------
*/

$unassigned_leads_stmt = $pdo->query("
    SELECT
        l.id,
        l.name,
        l.phone,
        l.email,
        l.service_interest,
        l.status,
        l.priority,
        l.created_at
    FROM leads l
    WHERE l.assigned_to IS NULL
      AND l.status NOT IN ('Converted', 'Lost')
    ORDER BY l.created_at DESC
");

$unassigned_leads =
    $unassigned_leads_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| OVERDUE TASKS
|--------------------------------------------------------------------------
*/

$overdue_tasks_stmt = $pdo->query("
    SELECT
        t.id,
        t.title,
        t.task_type,
        t.due_date,
        t.priority,
        t.status,
        u.name AS assigned_staff
    FROM tasks t
    INNER JOIN users u
        ON t.assigned_to = u.id
    WHERE t.due_date < NOW()
      AND t.status NOT IN ('Completed', 'Cancelled')
    ORDER BY t.due_date ASC
");

$overdue_tasks =
    $overdue_tasks_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PENDING APPOINTMENTS
|--------------------------------------------------------------------------
*/

$pending_appointments_stmt = $pdo->query("
    SELECT
        a.id,
        a.lead_id,
        a.appointment_date,
        a.appointment_type,
        a.status,
        l.name AS lead_name,
        l.phone AS lead_phone
    FROM appointments a
    INNER JOIN leads l
        ON a.lead_id = l.id
    WHERE a.status IN ('Scheduled', 'Confirmed')
      AND a.appointment_date <= NOW()
    ORDER BY a.appointment_date ASC
");

$pending_appointments =
    $pending_appointments_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| OVERDUE MARKETING VISITS
|--------------------------------------------------------------------------
*/

$overdue_visits_stmt = $pdo->query("
    SELECT
        mv.id,
        mv.title,
        mv.visit_type,
        mv.visit_date,
        mv.person_name,
        mv.organization_name,
        mv.status,
        u.name AS assigned_staff
    FROM marketing_visits mv
    INNER JOIN users u
        ON mv.assigned_to = u.id
    WHERE mv.status = 'Planned'
      AND mv.visit_date < NOW()
    ORDER BY mv.visit_date ASC
");

$overdue_visits =
    $overdue_visits_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| SUMMARY COUNTS
|--------------------------------------------------------------------------
*/

$overdue_followups_count =
    count($overdue_followups);

$unassigned_leads_count =
    count($unassigned_leads);

$overdue_tasks_count =
    count($overdue_tasks);

$pending_appointments_count =
    count($pending_appointments);

$overdue_visits_count =
    count($overdue_visits);

$total_actions =
    $overdue_followups_count
    + $unassigned_leads_count
    + $overdue_tasks_count
    + $pending_appointments_count
    + $overdue_visits_count;


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function action_status_class(string $status): string
{
    switch ($status) {

        case 'New':
            return 'bg-primary';

        case 'Contacted':
            return 'bg-info text-dark';

        case 'Interested':
            return 'bg-warning text-dark';

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

        default:
            return 'bg-secondary';
    }
}


function action_priority_class(string $priority): string
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


/*
|--------------------------------------------------------------------------
| SHARED HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>

<style>

    .action-summary-card {
        background: #ffffff;
        border: 1px solid #e5e1d7;
        border-radius: 14px;
        padding: 22px;
        height: 100%;
        box-shadow: 0 4px 18px rgba(23, 50, 77, 0.05);
    }

    .action-summary-label {
        color: #71808c;
        font-size: 0.9rem;
        margin-bottom: 5px;
    }

    .action-summary-number {
        color: #17324d;
        font-size: 2rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .action-section {
        background: #ffffff;
        border: 1px solid #e5e1d7;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(23, 50, 77, 0.05);
    }

    .action-section-header {
        padding: 20px 22px;
        border-bottom: 1px solid #eeeeee;
    }

    .action-section-body {
        background: #ffffff;
    }

    .action-table {
        margin-bottom: 0;
    }

    .action-table th {
        white-space: nowrap;
        background: #faf9f6;
    }

    .action-table td {
        vertical-align: middle;
    }

    .action-empty {
        padding: 24px;
    }

    .action-title {
        color: #17324d;
        font-weight: 700;
    }

</style>


<div class="container py-4">


    <!-- =========================================================
         PAGE HEADER
    ========================================================== -->

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                Action Required
            </h1>

            <p class="hm-muted mb-0">
                Items that require manager attention.
            </p>

        </div>


        <div class="d-flex gap-2 mt-3 mt-md-0">

            <a
                href="<?php echo BASE_URL; ?>/manager/control-center.php"
                class="btn btn-outline-secondary"
            >
                Back to Control Center
            </a>

        </div>

    </div>


    <!-- =========================================================
         TOTAL ACTION ITEMS
    ========================================================== -->

    <div class="action-summary-card mb-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

            <div>

                <div class="action-summary-label">
                    Total Action Items
                </div>

                <div class="action-summary-number">
                    <?php echo $total_actions; ?>
                </div>

                <div class="small hm-muted mt-1">
                    Combined items requiring attention.
                </div>

            </div>


            <div class="mt-3 mt-md-0">

                <?php if ($total_actions > 0): ?>

                    <span class="badge bg-warning text-dark px-3 py-2">
                        Review Required
                    </span>

                <?php else: ?>

                    <span class="badge bg-success px-3 py-2">
                        All Clear
                    </span>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- =========================================================
         SUMMARY CARDS
    ========================================================== -->

    <div class="row g-3 mb-4">


        <!-- OVERDUE FOLLOW-UPS -->

        <div class="col-md-6 col-xl">

            <div class="action-summary-card">

                <div class="action-summary-label">
                    Overdue Follow-ups
                </div>

                <div class="action-summary-number">
                    <?php echo $overdue_followups_count; ?>
                </div>

                <div class="small text-danger mt-1">
                    Immediate attention
                </div>

            </div>

        </div>


        <!-- UNASSIGNED LEADS -->

        <div class="col-md-6 col-xl">

            <div class="action-summary-card">

                <div class="action-summary-label">
                    Unassigned Leads
                </div>

                <div class="action-summary-number">
                    <?php echo $unassigned_leads_count; ?>
                </div>

                <div class="small text-danger mt-1">
                    Assignment required
                </div>

            </div>

        </div>


        <!-- OVERDUE TASKS -->

        <div class="col-md-6 col-xl">

            <div class="action-summary-card">

                <div class="action-summary-label">
                    Overdue Tasks
                </div>

                <div class="action-summary-number">
                    <?php echo $overdue_tasks_count; ?>
                </div>

                <div class="small text-danger mt-1">
                    Staff action required
                </div>

            </div>

        </div>


        <!-- PENDING APPOINTMENTS -->

        <div class="col-md-6 col-xl">

            <div class="action-summary-card">

                <div class="action-summary-label">
                    Pending Appointments
                </div>

                <div class="action-summary-number">
                    <?php echo $pending_appointments_count; ?>
                </div>

                <div class="small hm-muted mt-1">
                    Scheduled or confirmed
                </div>

            </div>

        </div>


        <!-- OVERDUE VISITS -->

        <div class="col-md-6 col-xl">

            <div class="action-summary-card">

                <div class="action-summary-label">
                    Overdue Visits
                </div>

                <div class="action-summary-number">
                    <?php echo $overdue_visits_count; ?>
                </div>

                <div class="small text-danger mt-1">
                    Visit follow-up required
                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         OVERDUE FOLLOW-UPS
    ========================================================== -->

    <div class="action-section mb-4">


        <div class="action-section-header">

            <h5 class="action-title mb-1">
                Overdue Follow-ups
            </h5>

            <div class="small hm-muted">
                Leads where the scheduled next action has already passed.
            </div>

        </div>


        <div class="action-section-body">

            <?php if (empty($overdue_followups)): ?>

                <div class="action-empty">

                    <div class="alert alert-success mb-0">
                        No overdue follow-ups.
                    </div>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table action-table align-middle">

                        <thead>

                            <tr>

                                <th>
                                    Lead
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Next Action
                                </th>

                                <th>
                                    Due
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Priority
                                </th>

                                <th>
                                    Assigned To
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($overdue_followups as $lead): ?>

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
                                            $lead['next_action_type'] ?: '-'
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span class="text-danger fw-semibold">

                                            <?php
                                            echo date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $lead['next_action_at']
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="badge <?php echo action_status_class($lead['status']); ?>"
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
                                            class="badge <?php echo action_priority_class($lead['priority']); ?>"
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
                                                ?: 'Unassigned'
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

    </div>


    <!-- =========================================================
         UNASSIGNED LEADS
    ========================================================== -->

    <div class="action-section mb-4">


        <div class="action-section-header">

            <h5 class="action-title mb-1">
                Unassigned Leads
            </h5>

            <div class="small hm-muted">
                Active leads that currently have no assigned staff member.
            </div>

        </div>


        <div class="action-section-body">

            <?php if (empty($unassigned_leads)): ?>

                <div class="action-empty">

                    <div class="alert alert-success mb-0">
                        No unassigned leads.
                    </div>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table action-table align-middle">

                        <thead>

                            <tr>

                                <th>
                                    Lead
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Priority
                                </th>

                                <th>
                                    Created
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($unassigned_leads as $lead): ?>

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
                                            class="badge <?php echo action_status_class($lead['status']); ?>"
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
                                            class="badge <?php echo action_priority_class($lead['priority']); ?>"
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
                                        echo date(
                                            'd M Y',
                                            strtotime(
                                                $lead['created_at']
                                            )
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

    </div>


    <!-- =========================================================
         OVERDUE TASKS
    ========================================================== -->

    <div class="action-section mb-4">


        <div class="action-section-header">

            <h5 class="action-title mb-1">
                Overdue Tasks
            </h5>

            <div class="small hm-muted">
                Tasks whose due date has passed without completion.
            </div>

        </div>


        <div class="action-section-body">

            <?php if (empty($overdue_tasks)): ?>

                <div class="action-empty">

                    <div class="alert alert-success mb-0">
                        No overdue tasks.
                    </div>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table action-table align-middle">

                        <thead>

                            <tr>

                                <th>
                                    Task
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Due
                                </th>

                                <th>
                                    Priority
                                </th>

                                <th>
                                    Assigned To
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($overdue_tasks as $task): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $task['title']
                                            );
                                            ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $task['task_type'] ?: '-'
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span class="text-danger fw-semibold">

                                            <?php
                                            echo date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $task['due_date']
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="badge <?php echo action_priority_class($task['priority']); ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $task['priority']
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $task['assigned_staff']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span class="badge bg-danger">
                                            <?php
                                            echo htmlspecialchars(
                                                $task['status']
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


    <!-- =========================================================
         PENDING APPOINTMENTS
    ========================================================== -->

    <div class="action-section mb-4">


        <div class="action-section-header">

            <h5 class="action-title mb-1">
                Pending Appointments Requiring Attention
            </h5>

            <div class="small hm-muted">
                Scheduled or confirmed appointments that have reached their appointment time.
            </div>

        </div>


        <div class="action-section-body">

            <?php if (empty($pending_appointments)): ?>

                <div class="action-empty">

                    <div class="alert alert-success mb-0">
                        No pending appointments requiring attention.
                    </div>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table action-table align-middle">

                        <thead>

                            <tr>

                                <th>
                                    Lead
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Appointment
                                </th>

                                <th>
                                    Type
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

                            <?php foreach ($pending_appointments as $appointment): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $appointment['lead_name']
                                            );
                                            ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $appointment['lead_phone']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo date(
                                            'd M Y, h:i A',
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
                                            class="badge <?php echo action_status_class($appointment['status']); ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $appointment['status']
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <a
                                            href="<?php echo BASE_URL; ?>/telecaller/appointments/index.php"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Open
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


    <!-- =========================================================
         OVERDUE MARKETING VISITS
    ========================================================== -->

    <div class="action-section mb-4">


        <div class="action-section-header">

            <h5 class="action-title mb-1">
                Overdue Marketing Visits
            </h5>

            <div class="small hm-muted">
                Planned field visits that have passed their scheduled date.
            </div>

        </div>


        <div class="action-section-body">

            <?php if (empty($overdue_visits)): ?>

                <div class="action-empty">

                    <div class="alert alert-success mb-0">
                        No overdue marketing visits.
                    </div>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table action-table align-middle">

                        <thead>

                            <tr>

                                <th>
                                    Visit
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Person / Organization
                                </th>

                                <th>
                                    Visit Date
                                </th>

                                <th>
                                    Assigned To
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

                            <?php foreach ($overdue_visits as $visit): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $visit['title']
                                            );
                                            ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $visit['visit_type'] ?: '-'
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        $visit_person =
                                            !empty($visit['person_name'])
                                                ? $visit['person_name']
                                                : $visit['organization_name'];

                                        echo htmlspecialchars(
                                            $visit_person ?: '-'
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <span class="text-danger fw-semibold">

                                            <?php
                                            echo date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $visit['visit_date']
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $visit['assigned_staff']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span class="badge bg-primary">
                                            <?php
                                            echo htmlspecialchars(
                                                $visit['status']
                                            );
                                            ?>
                                        </span>

                                    </td>


                                    <td>

                                        <a
                                            href="<?php echo BASE_URL; ?>/marketing/visits/view.php?id=<?php echo (int) $visit['id']; ?>"
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


<?php

require_once __DIR__ . '/../includes/footer.php';

?>