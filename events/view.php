 <?php

require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', 'manager');

$user = current_user();

/*

| Get Event ID

*/

$event_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$event_id) {
    die('Invalid event ID.');
}

/*

| Handle Staff Assignment / Removal

*/

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    
    | Assign Staff
    
    */

    if ($action === 'assign_staff') {

        $staff_id = filter_input(
            INPUT_POST,
            'staff_id',
            FILTER_VALIDATE_INT
        );

        if (!$staff_id) {

            $error = 'Please select a staff member.';

        } else {

            /*
            
            | Check Staff Role
            
            */

            $stmt = $pdo->prepare("
                SELECT
                    u.id,
                    u.name,
                    u.email,
                    u.status,
                    r.name AS role_name

                FROM users u

                INNER JOIN roles r
                    ON u.role_id = r.id

                WHERE u.id = ?
                  AND u.status = 'active'
                  AND LOWER(r.name) IN (
                      'telecaller',
                      'marketing executive',
                      'marketing'
                  )

                LIMIT 1
            ");

            $stmt->execute([
                $staff_id
            ]);

            $staff = $stmt->fetch();

            if (!$staff) {

                $error =
                    'Selected staff member is not available for event assignment.';

            } else {

                /*
                
                | Check Duplicate Assignment
                
                */

                $stmt = $pdo->prepare("
                    SELECT id

                    FROM event_assignments

                    WHERE event_id = ?
                      AND user_id = ?

                    LIMIT 1
                ");

                $stmt->execute([
                    $event_id,
                    $staff_id
                ]);

                $existing_assignment = $stmt->fetch();

                if ($existing_assignment) {

                    $error =
                        'This staff member is already assigned to this event.';

                } else {

                    /*
                    
                    | Insert Assignment
                    
                    */

                    $stmt = $pdo->prepare("
                        INSERT INTO event_assignments
                        (
                            event_id,
                            user_id,
                            assigned_by
                        )

                        VALUES
                        (?, ?, ?)
                    ");

                    $stmt->execute([
                        $event_id,
                        $staff_id,
                        $user['id']
                    ]);

                    $message =
                        'Staff member assigned successfully.';
                }
            }
        }
    }


    /*
    
    | Remove Staff
    
    */

    if ($action === 'remove_staff') {

        $assignment_id = filter_input(
            INPUT_POST,
            'assignment_id',
            FILTER_VALIDATE_INT
        );

        if (!$assignment_id) {

            $error = 'Invalid assignment.';

        } else {

            $stmt = $pdo->prepare("
                DELETE FROM event_assignments

                WHERE id = ?
                  AND event_id = ?
            ");

            $stmt->execute([
                $assignment_id,
                $event_id
            ]);

            if ($stmt->rowCount() > 0) {

                $message =
                    'Staff assignment removed successfully.';

            } else {

                $error =
                    'Assignment not found.';
            }
        }
    }
}


/*

| Get Event Details

*/

$stmt = $pdo->prepare("
    SELECT
        e.id,
        e.event_name,
        e.event_type,
        e.event_date,
        e.location,
        e.description,
        e.status,
        e.created_at,
        e.updated_at,

        u.name AS created_by_name,
        u.email AS created_by_email

    FROM events e

    LEFT JOIN users u
        ON e.created_by = u.id

    WHERE e.id = ?

    LIMIT 1
");

$stmt->execute([
    $event_id
]);

$event = $stmt->fetch();

if (!$event) {
    die('Event not found.');
}


/*

| Event Performance Summary

*/

/*
| Assigned Staff
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM event_assignments

    WHERE event_id = ?
");

$stmt->execute([
    $event_id
]);

$assigned_staff_count = (int) $stmt->fetchColumn();


/*
| Leads Generated
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM event_leads

    WHERE event_id = ?
");

$stmt->execute([
    $event_id
]);

$leads_generated_count = (int) $stmt->fetchColumn();


/*
| Total Appointments
*/

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT a.id)

    FROM appointments a

    INNER JOIN event_leads el
        ON a.lead_id = el.lead_id

    WHERE el.event_id = ?
");

$stmt->execute([
    $event_id
]);

$appointments_count = (int) $stmt->fetchColumn();


/*
| Completed Appointments
*/

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT a.id)

    FROM appointments a

    INNER JOIN event_leads el
        ON a.lead_id = el.lead_id

    WHERE el.event_id = ?
      AND a.status = 'Completed'
");

$stmt->execute([
    $event_id
]);

$completed_appointments_count = (int) $stmt->fetchColumn();


/*
| Converted Leads
*/

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT l.id)

    FROM event_leads el

    INNER JOIN leads l
        ON el.lead_id = l.id

    WHERE el.event_id = ?
      AND l.status = 'Converted'
");

$stmt->execute([
    $event_id
]);

$converted_leads_count = (int) $stmt->fetchColumn();


/*
| Conversion Rate
|
| Formula:
| Converted Leads / Total Event Leads * 100
*/

$conversion_rate = 0;

if ($leads_generated_count > 0) {

    $conversion_rate =
        ($converted_leads_count / $leads_generated_count) * 100;
}


/*

| Get Available Staff

*/

$stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        u.email,
        r.name AS role_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.status = 'active'

      AND LOWER(r.name) IN (
          'telecaller',
          'marketing executive',
          'marketing'
      )

    ORDER BY u.name ASC
");

$available_staff = $stmt->fetchAll();


/*

| Get Assigned Staff

*/

$stmt = $pdo->prepare("
    SELECT
        ea.id AS assignment_id,

        u.id AS user_id,
        u.name AS staff_name,
        u.email,

        r.name AS role_name,

        assigned_by_user.name AS assigned_by_name,

        ea.assigned_at

    FROM event_assignments ea

    INNER JOIN users u
        ON ea.user_id = u.id

    INNER JOIN roles r
        ON u.role_id = r.id

    LEFT JOIN users assigned_by_user
        ON ea.assigned_by = assigned_by_user.id

    WHERE ea.event_id = ?

    ORDER BY ea.assigned_at DESC
");

$stmt->execute([
    $event_id
]);

$assigned_staff = $stmt->fetchAll();


/*

| Get Event Leads

*/

$stmt = $pdo->prepare("
    SELECT

        el.id AS event_lead_id,

        l.id AS lead_id,
        l.name,
        l.phone,
        l.email,
        l.service_interest,
        l.status,
        l.priority,

        u.name AS captured_by_name,

        el.created_at

    FROM event_leads el

    INNER JOIN leads l
        ON el.lead_id = l.id

    LEFT JOIN users u
        ON el.captured_by = u.id

    WHERE el.event_id = ?

    ORDER BY el.created_at DESC
");

$stmt->execute([
    $event_id
]);

$event_leads = $stmt->fetchAll();


/*

| Get Event Appointments

*/

$stmt = $pdo->prepare("
    SELECT

        a.id AS appointment_id,

        a.lead_id,
        a.appointment_date,
        a.appointment_type,
        a.notes,
        a.status,
        a.created_at,

        l.name AS lead_name,
        l.phone AS lead_phone,
        l.service_interest

    FROM appointments a

    INNER JOIN event_leads el
        ON a.lead_id = el.lead_id

    INNER JOIN leads l
        ON a.lead_id = l.id

    WHERE el.event_id = ?

    ORDER BY
        a.appointment_date ASC,
        a.id ASC
");

$stmt->execute([
    $event_id
]);

$event_appointments = $stmt->fetchAll();


/*

| Helper Functions

*/

function event_status_class(string $status): string
{
    switch ($status) {

        case 'Planned':
            return 'bg-primary';

        case 'Ongoing':
            return 'bg-warning text-dark';

        case 'Completed':
            return 'bg-success';

        case 'Cancelled':
            return 'bg-danger';

        default:
            return 'bg-secondary';
    }
}


function lead_status_class(string $status): string
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


function appointment_status_class(string $status): string
{
    switch ($status) {

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

        default:
            return 'bg-secondary';
    }
}

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
        <?php
        echo htmlspecialchars(
            $event['event_name']
        );
        ?>
        - Event Details
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

        .performance-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
            color: #17324d;
        }

        .performance-label {
            color: #71808c;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .section-title {
            color: #17324d;
            font-weight: 700;
        }

        .table th {
            white-space: nowrap;
        }

        .event-description {
            white-space: pre-line;
        }

    </style>

</head>

<body>

<div class="container py-4">


    
    <!-- PAGE HEADER -->
    

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>

            <div class="mb-2">

                <a
                    href="<?php echo BASE_URL; ?>/events/index.php"
                    class="text-decoration-none"
                >
                    ← Back to Events
                </a>

            </div>

            <h2 class="mb-1">

                <?php
                echo htmlspecialchars(
                    $event['event_name']
                );
                ?>

            </h2>

            <div class="hm-muted">

                Event ID:
                #<?php echo (int) $event['id']; ?>

            </div>

        </div>


        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">

            <a
                href="<?php echo BASE_URL; ?>/events/capture-lead.php?event_id=<?php echo (int) $event['id']; ?>"
                class="btn hm-primary"
            >
                + Capture Lead
            </a>

            <a
                href="<?php echo BASE_URL; ?>/events/edit.php?id=<?php echo (int) $event['id']; ?>"
                class="btn btn-outline-primary"
            >
                Edit
            </a>

            <a
                href="<?php echo BASE_URL; ?>/events/delete.php?id=<?php echo (int) $event['id']; ?>"
                class="btn btn-outline-danger"
            >
                Delete
            </a>

        </div>

    </div>


    
    <!-- ALERTS -->
    

    <?php if ($message !== ''): ?>

        <div class="alert alert-success">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    
    <!-- EVENT PERFORMANCE -->
    

    <div class="mb-4">

        <h4 class="section-title mb-3">
            Event Performance
        </h4>

        <div class="row g-3">


            <!-- Assigned Staff -->

            <div class="col-md-6 col-xl">

                <div class="hm-card p-4 h-100">

                    <div class="performance-label">
                        Assigned Staff
                    </div>

                    <div class="performance-value">
                        <?php echo $assigned_staff_count; ?>
                    </div>

                    <div class="small hm-muted mt-1">
                        Staff assigned to this event
                    </div>

                </div>

            </div>


            <!-- Leads Generated -->

            <div class="col-md-6 col-xl">

                <div class="hm-card p-4 h-100">

                    <div class="performance-label">
                        Leads Generated
                    </div>

                    <div class="performance-value">
                        <?php echo $leads_generated_count; ?>
                    </div>

                    <div class="small hm-muted mt-1">
                        Leads captured from event
                    </div>

                </div>

            </div>


            <!-- Appointments -->

            <div class="col-md-6 col-xl">

                <div class="hm-card p-4 h-100">

                    <div class="performance-label">
                        Appointments
                    </div>

                    <div class="performance-value">
                        <?php echo $appointments_count; ?>
                    </div>

                    <div class="small hm-muted mt-1">
                        Appointments from event leads
                    </div>

                </div>

            </div>


            <!-- Completed Appointments -->

            <div class="col-md-6 col-xl">

                <div class="hm-card p-4 h-100">

                    <div class="performance-label">
                        Completed Appointments
                    </div>

                    <div class="performance-value">
                        <?php echo $completed_appointments_count; ?>
                    </div>

                    <div class="small hm-muted mt-1">
                        Completed event appointments
                    </div>

                </div>

            </div>


            <!-- Converted Leads -->

            <div class="col-md-6 col-xl">

                <div class="hm-card p-4 h-100">

                    <div class="performance-label">
                        Converted Leads
                    </div>

                    <div class="performance-value">
                        <?php echo $converted_leads_count; ?>
                    </div>

                    <div class="small hm-muted mt-1">
                        Leads marked as converted
                    </div>

                </div>

            </div>


            <!-- Conversion Rate -->

            <div class="col-md-6 col-xl">

                <div class="hm-card p-4 h-100">

                    <div class="performance-label">
                        Conversion Rate
                    </div>

                    <div class="performance-value">

                        <?php
                        echo number_format(
                            $conversion_rate,
                            1
                        );
                        ?>%

                    </div>

                    <div class="small hm-muted mt-1">
                        Converted leads ÷ total event leads
                    </div>

                </div>

            </div>

        </div>

    </div>


    
    <!-- EVENT INFORMATION -->
    

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h4 class="section-title mb-0">
                Event Details
            </h4>

            <span
                class="badge <?php echo event_status_class($event['status']); ?>"
            >

                <?php
                echo htmlspecialchars(
                    $event['status']
                );
                ?>

            </span>

        </div>


        <div class="row g-4">


            <!-- Event Name -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Event Name
                </div>

                <div class="fw-semibold">

                    <?php
                    echo htmlspecialchars(
                        $event['event_name']
                    );
                    ?>

                </div>

            </div>


            <!-- Event Type -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Event Type
                </div>

                <div class="fw-semibold">

                    <?php
                    echo htmlspecialchars(
                        $event['event_type']
                    );
                    ?>

                </div>

            </div>


            <!-- Event Date -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Event Date
                </div>

                <div class="fw-semibold">

                    <?php
                    echo date(
                        'd M Y',
                        strtotime(
                            $event['event_date']
                        )
                    );
                    ?>

                </div>

            </div>


            <!-- Location -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Location
                </div>

                <div class="fw-semibold">

                    <?php

                    if (!empty($event['location'])) {

                        echo htmlspecialchars(
                            $event['location']
                        );

                    } else {

                        echo '<span class="text-muted">
                            Not specified
                        </span>';

                    }

                    ?>

                </div>

            </div>


            <!-- Created By -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Created By
                </div>

                <div class="fw-semibold">

                    <?php
                    echo htmlspecialchars(
                        $event['created_by_name']
                        ?? 'Unknown'
                    );
                    ?>

                </div>

            </div>


            <!-- Created On -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Created On
                </div>

                <div class="fw-semibold">

                    <?php
                    echo date(
                        'd M Y, h:i A',
                        strtotime(
                            $event['created_at']
                        )
                    );
                    ?>

                </div>

            </div>


            <!-- Description -->

            <div class="col-12">

                <div class="hm-muted small mb-1">
                    Description
                </div>

                <div class="event-description">

                    <?php

                    if (!empty($event['description'])) {

                        echo nl2br(
                            htmlspecialchars(
                                $event['description']
                            )
                        );

                    } else {

                        echo '<span class="text-muted">
                            No description available.
                        </span>';

                    }

                    ?>

                </div>

            </div>

        </div>

    </div>


    
    <!-- STAFF ASSIGNMENT -->
    

    <div class="hm-card p-4 mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">

            <div>

                <h4 class="section-title mb-1">
                    Event Staff
                </h4>

                <div class="hm-muted small">
                    Assign Telecallers and Marketing Executives
                </div>

            </div>

        </div>


        <!-- Assign Staff Form -->

        <form
            method="POST"
            class="row g-3 align-items-end mb-4"
        >

            <input
                type="hidden"
                name="action"
                value="assign_staff"
            >


            <div class="col-md-8">

                <label class="form-label">
                    Select Staff
                </label>

                <select
                    name="staff_id"
                    class="form-select"
                    required
                >

                    <option value="">
                        -- Select Staff Member --
                    </option>

                    <?php foreach ($available_staff as $staff_member): ?>

                        <option
                            value="<?php echo (int) $staff_member['id']; ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $staff_member['name']
                            );
                            ?>

                            -

                            <?php
                            echo htmlspecialchars(
                                $staff_member['role_name']
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="col-md-4">

                <button
                    type="submit"
                    class="btn hm-primary w-100"
                >
                    Assign Staff
                </button>

            </div>

        </form>


        <!-- Assigned Staff Table -->

        <?php if (!empty($assigned_staff)): ?>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>Staff</th>
                            <th>Role</th>
                            <th>Assigned By</th>
                            <th>Assigned On</th>
                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($assigned_staff as $staff_member): ?>

                        <tr>

                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $staff_member['staff_name']
                                    );
                                    ?>

                                </div>

                                <div class="small hm-muted">

                                    <?php
                                    echo htmlspecialchars(
                                        $staff_member['email']
                                    );
                                    ?>

                                </div>

                            </td>


                            <td>

                                <span class="badge bg-info text-dark">

                                    <?php
                                    echo htmlspecialchars(
                                        $staff_member['role_name']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $staff_member['assigned_by_name']
                                    ?? 'Unknown'
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $staff_member['assigned_at']
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <form
                                    method="POST"
                                    onsubmit="return confirm(
                                        'Remove this staff member from the event?'
                                    );"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="remove_staff"
                                    >

                                    <input
                                        type="hidden"
                                        name="assignment_id"
                                        value="<?php echo (int) $staff_member['assignment_id']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                    >
                                        Remove
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="alert alert-light border mb-0">

                No staff members are assigned to this event yet.

            </div>

        <?php endif; ?>

    </div>


    
    <!-- EVENT LEADS -->
    

    <div class="hm-card p-4 mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">

            <div>

                <h4 class="section-title mb-1">
                    Event Leads
                </h4>

                <div class="hm-muted small">
                    Leads captured during this event
                </div>

            </div>


            <a
                href="<?php echo BASE_URL; ?>/events/capture-lead.php?event_id=<?php echo (int) $event['id']; ?>"
                class="btn hm-primary"
            >
                + Capture Lead
            </a>

        </div>


        <?php if (!empty($event_leads)): ?>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>Lead</th>
                            <th>Phone</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Captured By</th>
                            <th>Captured On</th>
                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($event_leads as $lead): ?>

                        <tr>

                            <!-- Lead -->

                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['name']
                                    );
                                    ?>

                                </div>

                                <?php if (!empty($lead['email'])): ?>

                                    <div class="small hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['email']
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- Phone -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $lead['phone']
                                );
                                ?>

                            </td>


                            <!-- Service -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $lead['service_interest']
                                    ?? '—'
                                );
                                ?>

                            </td>


                            <!-- Status -->

                            <td>

                                <span
                                    class="badge <?php echo lead_status_class($lead['status']); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['status']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- Priority -->

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


                            <!-- Captured By -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $lead['captured_by_name']
                                    ?? 'Unknown'
                                );
                                ?>

                            </td>


                            <!-- Captured On -->

                            <td>

                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $lead['created_at']
                                    )
                                );
                                ?>

                            </td>


                            <!-- Action -->

                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['lead_id']; ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    View Lead
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="alert alert-light border mb-0">

                No leads have been captured for this event yet.

            </div>

        <?php endif; ?>

    </div>


    
    <!-- EVENT APPOINTMENTS -->
    

    <div class="hm-card p-4 mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">

            <div>

                <h4 class="section-title mb-1">
                    Event Appointments
                </h4>

                <div class="hm-muted small">
                    Appointments connected to leads captured from this event
                </div>

            </div>


            <span class="badge bg-primary">

                <?php
                echo count($event_appointments);
                ?>

                Appointment(s)

            </span>

        </div>


        <?php if (!empty($event_appointments)): ?>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>Lead</th>
                            <th>Phone</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($event_appointments as $appointment): ?>

                        <tr>

                            <!-- Lead -->

                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['lead_name']
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- Phone -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $appointment['lead_phone']
                                );
                                ?>

                            </td>


                            <!-- Service -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $appointment['service_interest']
                                    ?: '-'
                                );
                                ?>

                            </td>


                            <!-- Date -->

                            <td>

                                <?php

                                if (!empty(
                                    $appointment['appointment_date']
                                )) {

                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $appointment['appointment_date']
                                        )
                                    );

                                } else {

                                    echo '-';

                                }

                                ?>

                            </td>


                            <!-- Type -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $appointment['appointment_type']
                                    ?: '-'
                                );
                                ?>

                            </td>


                            <!-- Status -->

                            <td>

                                <span
                                    class="badge <?php echo appointment_status_class($appointment['status']); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['status']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- Action -->

                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $appointment['lead_id']; ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    View Lead
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="alert alert-light border mb-0">

                No appointments have been created for leads from this event yet.

            </div>

        <?php endif; ?>

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>