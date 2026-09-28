<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

require_login();

$user = current_user();

if (!$user || !in_array($user['role'], ['admin', 'manager'], true)) {
    http_response_code(403);
    exit('Access denied.');
}


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

$overdue_followups = $overdue_followups_stmt->fetchAll();


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
        l.status,
        l.priority,
        l.created_at

    FROM leads l

    WHERE l.assigned_to IS NULL

    ORDER BY l.created_at DESC
");

$unassigned_leads = $unassigned_leads_stmt->fetchAll();


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

$overdue_tasks = $overdue_tasks_stmt->fetchAll();


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
    $pending_appointments_stmt->fetchAll();


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
    $overdue_visits_stmt->fetchAll();


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

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>


<div class="container py-4">


    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Action Required
            </h2>

            <p class="text-muted mb-0">
                Items that require manager attention.
            </p>

        </div>


        <a
            href="<?php echo BASE_URL; ?>/manager/control-center.php"
            class="btn btn-outline-secondary"
        >
            Back to Control Center
        </a>

    </div>


    <!-- TOTAL -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h6 class="text-muted mb-1">
                        Total Action Items
                    </h6>

                    <h2 class="mb-0">
                        <?php echo $total_actions; ?>
                    </h2>

                </div>

                <div>
                    <span class="badge bg-warning text-dark fs-6">
                        Review Required
                    </span>
                </div>

            </div>

        </div>

    </div>


    <!-- SUMMARY CARDS -->

    <div class="row g-4 mb-4">


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Overdue Follow-ups
                    </h6>

                    <h3>
                        <?php echo $overdue_followups_count; ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Unassigned Leads
                    </h6>

                    <h3>
                        <?php echo $unassigned_leads_count; ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Overdue Tasks
                    </h6>

                    <h3>
                        <?php echo $overdue_tasks_count; ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Pending Appointments
                    </h6>

                    <h3>
                        <?php echo $pending_appointments_count; ?>
                    </h3>

                </div>

            </div>

        </div>


    </div>


    <!-- OVERDUE FOLLOW-UPS -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Overdue Follow-ups
            </h5>

        </div>


        <div class="card-body p-0">

            <?php if (empty($overdue_followups)): ?>

                <div class="p-4 text-muted">
                    No overdue follow-ups.
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

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
                                    Assigned To
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $overdue_followups
                                as $lead
                            ): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $lead['name']
                                        );
                                        ?>
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
                                            $lead['next_action_type']
                                            ?: '-'
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <?php
                                        echo date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $lead['next_action_at']
                                            )
                                        );
                                        ?>

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


    <!-- UNASSIGNED LEADS -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Unassigned Leads
            </h5>

        </div>


        <div class="card-body p-0">

            <?php if (empty($unassigned_leads)): ?>

                <div class="p-4 text-muted">
                    No unassigned leads.
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Lead
                                </th>

                                <th>
                                    Phone
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

                            <?php foreach (
                                $unassigned_leads
                                as $lead
                            ): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $lead['name']
                                        );
                                        ?>
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
                                            $lead['status']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $lead['priority']
                                        );
                                        ?>
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


    <!-- OVERDUE TASKS -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Overdue Tasks
            </h5>

        </div>


        <div class="card-body p-0">

            <?php if (empty($overdue_tasks)): ?>

                <div class="p-4 text-muted">
                    No overdue tasks.
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

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

                            <?php foreach (
                                $overdue_tasks
                                as $task
                            ): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $task['title']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $task['task_type']
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <?php
                                        echo date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $task['due_date']
                                            )
                                        );
                                        ?>

                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $task['priority']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $task['assigned_staff']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $task['status']
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


    <!-- PENDING APPOINTMENTS -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Pending Appointments Requiring Attention
            </h5>

        </div>


        <div class="card-body p-0">

            <?php if (empty($pending_appointments)): ?>

                <div class="p-4 text-muted">
                    No pending appointments requiring attention.
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

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

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $pending_appointments
                                as $appointment
                            ): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $appointment['lead_name']
                                        );
                                        ?>
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
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $appointment['status']
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


    <!-- OVERDUE VISITS -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Overdue Marketing Visits
            </h5>

        </div>


        <div class="card-body p-0">

            <?php if (empty($overdue_visits)): ?>

                <div class="p-4 text-muted">
                    No overdue marketing visits.
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

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
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $overdue_visits
                                as $visit
                            ): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $visit['title']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $visit['visit_type']
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $visit['person_name']
                                                ?: $visit[
                                                    'organization_name'
                                                ]
                                                ?: '-'
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <?php
                                        echo date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $visit['visit_date']
                                            )
                                        );
                                        ?>

                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $visit['assigned_staff']
                                        );
                                        ?>
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


<?php require_once __DIR__ . '/../includes/footer.php'; ?>