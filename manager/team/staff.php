 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();


/*

| Get Staff ID

*/

$staff_id = filter_input(
    INPUT_GET,
    'staff_id',
    FILTER_VALIDATE_INT
);

if (!$staff_id) {
    die('Invalid staff ID.');
}


/*

| Get Staff Details

*/

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.name,
        u.email,
        u.status,
        r.name AS role_name,
        r.display_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.id = ?

    LIMIT 1
");

$stmt->execute([
    $staff_id
]);

$staff = $stmt->fetch();

if (!$staff) {
    die('Staff member not found.');
}


/*

| Validate Marketing Team Role

*/

$normalized_role = strtolower(
    trim($staff['role_name'])
);

$allowed_roles = [
    'telecaller',
    'marketing executive',
    'marketing'
];

if (!in_array(
    $normalized_role,
    $allowed_roles,
    true
)) {
    die('This user is not a marketing team member.');
}


/*

| Total Leads

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM leads

    WHERE assigned_to = ?
");

$stmt->execute([
    $staff_id
]);

$total_leads = (int) $stmt->fetchColumn();


/*

| New Leads

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM leads

    WHERE assigned_to = ?
      AND status = 'New'
");

$stmt->execute([
    $staff_id
]);

$new_leads = (int) $stmt->fetchColumn();


/*

| Converted Leads

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM leads

    WHERE assigned_to = ?
      AND status = 'Converted'
");

$stmt->execute([
    $staff_id
]);

$converted_leads = (int) $stmt->fetchColumn();


/*

| Lost Leads

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM leads

    WHERE assigned_to = ?
      AND status = 'Lost'
");

$stmt->execute([
    $staff_id
]);

$lost_leads = (int) $stmt->fetchColumn();


/*

| Total Calls

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM lead_calls

    WHERE user_id = ?
");

$stmt->execute([
    $staff_id
]);

$total_calls = (int) $stmt->fetchColumn();


/*

| Today's Calls

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM lead_calls

    WHERE user_id = ?
      AND DATE(call_at) = CURDATE()
");

$stmt->execute([
    $staff_id
]);

$today_calls = (int) $stmt->fetchColumn();


/*

| Total Activities

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM lead_activities

    WHERE user_id = ?
");

$stmt->execute([
    $staff_id
]);

$total_activities = (int) $stmt->fetchColumn();


/*

| Today's Activities

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM lead_activities

    WHERE user_id = ?
      AND DATE(activity_at) = CURDATE()
");

$stmt->execute([
    $staff_id
]);

$today_activities = (int) $stmt->fetchColumn();


/*

| Total Appointments

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM appointments a

    INNER JOIN leads l
        ON a.lead_id = l.id

    WHERE l.assigned_to = ?

      AND a.status NOT IN (
          'Cancelled',
          'No Show'
      )
");

$stmt->execute([
    $staff_id
]);

$total_appointments = (int) $stmt->fetchColumn();


/*

| Today's Appointments

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM appointments a

    INNER JOIN leads l
        ON a.lead_id = l.id

    WHERE l.assigned_to = ?

      AND DATE(a.appointment_date) = CURDATE()

      AND a.status NOT IN (
          'Cancelled',
          'No Show'
      )
");

$stmt->execute([
    $staff_id
]);

$today_appointments = (int) $stmt->fetchColumn();


/*

| Overdue Follow-ups

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM leads

    WHERE assigned_to = ?

      AND next_action_at IS NOT NULL

      AND next_action_at < NOW()

      AND status NOT IN (
          'Converted',
          'Lost'
      )
");

$stmt->execute([
    $staff_id
]);

$overdue_followups = (int) $stmt->fetchColumn();


/*

| Today's Follow-ups

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM leads

    WHERE assigned_to = ?

      AND next_action_at IS NOT NULL

      AND DATE(next_action_at) = CURDATE()

      AND status NOT IN (
          'Converted',
          'Lost'
      )
");

$stmt->execute([
    $staff_id
]);

$today_followups = (int) $stmt->fetchColumn();


/*

| Assigned Events

*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)

    FROM event_assignments ea

    INNER JOIN events e
        ON ea.event_id = e.id

    WHERE ea.user_id = ?

      AND e.status <> 'Cancelled'
");

$stmt->execute([
    $staff_id
]);

$assigned_events = (int) $stmt->fetchColumn();


/*

| Conversion Rate

*/

$conversion_rate = 0;

if ($total_leads > 0) {

    $conversion_rate =
        ($converted_leads / $total_leads) * 100;
}


/*

| Assigned Leads

*/

$stmt = $pdo->prepare("
    SELECT
        l.id,
        l.name,
        l.phone,
        l.email,
        l.service_interest,
        l.status,
        l.priority,
        l.next_action_type,
        l.next_action_at,
        l.created_at

    FROM leads l

    WHERE l.assigned_to = ?

    ORDER BY l.created_at DESC

    LIMIT 15
");

$stmt->execute([
    $staff_id
]);

$assigned_leads = $stmt->fetchAll();


/*

| Recent Calls

|
| Only columns already confirmed in the project are used here:
| id, lead_id, user_id, call_at
|
*/

$stmt = $pdo->prepare("
    SELECT
        lc.id,
        lc.call_at,

        l.id AS lead_id,
        l.name AS lead_name,
        l.phone AS lead_phone

    FROM lead_calls lc

    INNER JOIN leads l
        ON lc.lead_id = l.id

    WHERE lc.user_id = ?

    ORDER BY lc.call_at DESC

    LIMIT 10
");

$stmt->execute([
    $staff_id
]);

$recent_calls = $stmt->fetchAll();


/*

| Recent Activities

*/

$stmt = $pdo->prepare("
    SELECT
        la.id,
        la.activity_type,
        la.description,
        la.activity_at,

        l.id AS lead_id,
        l.name AS lead_name

    FROM lead_activities la

    INNER JOIN leads l
        ON la.lead_id = l.id

    WHERE la.user_id = ?

    ORDER BY la.activity_at DESC

    LIMIT 10
");

$stmt->execute([
    $staff_id
]);

$recent_activities = $stmt->fetchAll();


/*

| Appointments

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

    WHERE l.assigned_to = ?

    ORDER BY a.appointment_date DESC

    LIMIT 10
");

$stmt->execute([
    $staff_id
]);

$appointments = $stmt->fetchAll();


/*

| Assigned Events

*/

$stmt = $pdo->prepare("
    SELECT
        e.id,
        e.event_name,
        e.event_type,
        e.event_date,
        e.location,
        e.status

    FROM event_assignments ea

    INNER JOIN events e
        ON ea.event_id = e.id

    WHERE ea.user_id = ?

      AND e.status <> 'Cancelled'

    ORDER BY e.event_date DESC

    LIMIT 10
");

$stmt->execute([
    $staff_id
]);

$events = $stmt->fetchAll();


/*

| Helper Functions

*/

function staff_status_class(string $status): string
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

        default:
            return 'bg-secondary';
    }
}


function staff_priority_class(string $priority): string
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


function staff_appointment_status_class(string $status): string
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


function staff_event_status_class(string $status): string
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


$page_title = 'Staff Performance';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">


    
    <!-- PAGE HEADER -->
    

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>

            <div class="mb-2">

                <a
                    href="<?php echo BASE_URL; ?>/manager/team/performance.php"
                    class="text-decoration-none"
                >
                    ← Team Performance
                </a>

            </div>

            <h2 class="hm-page-title mb-1">

                <?php
                echo htmlspecialchars(
                    $staff['name']
                );
                ?>

            </h2>

            <p class="hm-muted mb-0">

                <?php
                echo htmlspecialchars(
                    $staff['display_name']
                    ?: $staff['role_name']
                );
                ?>

                ·

                <?php
                echo htmlspecialchars(
                    $staff['email']
                );
                ?>

            </p>

        </div>


        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">

            <a
                href="<?php echo BASE_URL; ?>/manager/control-center.php"
                class="btn btn-outline-primary"
            >
                ⚡ Control Center
            </a>

            <a
                href="<?php echo BASE_URL; ?>/manager/team/performance.php"
                class="btn btn-outline-secondary"
            >
                Team Performance
            </a>

        </div>

    </div>


    
    <!-- STAFF PROFILE -->
    

    <div class="hm-card p-4 mb-4">

        <div class="row g-4">

            <div class="col-md-4">

                <div class="hm-muted small">
                    Staff Name
                </div>

                <div class="fw-semibold">

                    <?php
                    echo htmlspecialchars(
                        $staff['name']
                    );
                    ?>

                </div>

            </div>


            <div class="col-md-4">

                <div class="hm-muted small">
                    Email
                </div>

                <div class="fw-semibold">

                    <?php
                    echo htmlspecialchars(
                        $staff['email']
                    );
                    ?>

                </div>

            </div>


            <div class="col-md-4">

                <div class="hm-muted small">
                    Role
                </div>

                <div>

                    <span class="badge bg-secondary">

                        <?php
                        echo htmlspecialchars(
                            $staff['display_name']
                            ?: $staff['role_name']
                        );
                        ?>

                    </span>

                </div>

            </div>

        </div>

    </div>


    
    <!-- PERFORMANCE CARDS -->
    

    <div class="row g-3 mb-4">


        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Assigned Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $total_leads; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Converted Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $converted_leads; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Appointments
                </div>

                <h2 class="mb-0">
                    <?php echo $total_appointments; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Conversion Rate
                </div>

                <h2 class="mb-0">

                    <?php
                    echo number_format(
                        $conversion_rate,
                        1
                    );
                    ?>%

                </h2>

            </div>

        </div>

    </div>


    
    <!-- TODAY / ACTION METRICS -->
    

    <div class="row g-3 mb-4">


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    New Leads
                </div>

                <h3 class="mb-0">
                    <?php echo $new_leads; ?>
                </h3>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Calls
                </div>

                <h3 class="mb-0">
                    <?php echo $total_calls; ?>
                </h3>

                <div class="small text-success">
                    Today: <?php echo $today_calls; ?>
                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Activities
                </div>

                <h3 class="mb-0">
                    <?php echo $total_activities; ?>
                </h3>

                <div class="small text-success">
                    Today: <?php echo $today_activities; ?>
                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Overdue Follow-ups
                </div>

                <h3
                    class="mb-0 <?php echo $overdue_followups > 0 ? 'text-danger' : 'text-success'; ?>"
                >
                    <?php echo $overdue_followups; ?>
                </h3>

                <div class="small">
                    Due Today: <?php echo $today_followups; ?>
                </div>

            </div>

        </div>

    </div>


    
    <!-- ASSIGNED LEADS -->
    

    <div class="hm-card p-4 mb-4">

        <h5 class="mb-3">
            Assigned Leads
        </h5>

        <?php if (empty($assigned_leads)): ?>

            <div class="alert alert-light border mb-0">
                No leads assigned to this staff member.
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
                            <th>Next Action</th>
                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($assigned_leads as $lead): ?>

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
                                    class="badge <?php echo staff_status_class($lead['status']); ?>"
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
                                    class="badge <?php echo staff_priority_class($lead['priority']); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['priority']
                                    );
                                    ?>

                                </span>

                            </td>

                            <td>

                                <?php if (!empty($lead['next_action_at'])): ?>

                                    <div>

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['next_action_type']
                                            ?: '-'
                                        );
                                        ?>

                                    </div>

                                    <div class="small text-muted">

                                        <?php
                                        echo date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $lead['next_action_at']
                                            )
                                        );
                                        ?>

                                    </div>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

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


    
    <!-- RECENT CALLS -->
    

    <div class="row g-4 mb-4">

        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <h5 class="mb-3">
                    Recent Calls
                </h5>

                <?php if (empty($recent_calls)): ?>

                    <div class="alert alert-light border">
                        No calls recorded.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>Lead</th>
                                    <th>Phone</th>
                                    <th>Call Date</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($recent_calls as $call): ?>

                                <tr>

                                    <td>

                                        <div class="fw-semibold">

                                            <?php
                                            echo htmlspecialchars(
                                                $call['lead_name']
                                            );
                                            ?>

                                        </div>

                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $call['lead_phone']
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <?php
                                        echo date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $call['call_at']
                                            )
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


        <!-- ===================================================== -->
        <!-- RECENT ACTIVITIES -->
        <!-- ===================================================== -->

        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <h5 class="mb-3">
                    Recent Activities
                </h5>

                <?php if (empty($recent_activities)): ?>

                    <div class="alert alert-light border">
                        No activities recorded.
                    </div>

                <?php else: ?>

                    <?php foreach ($recent_activities as $activity): ?>

                        <div class="border-bottom py-3">

                            <div class="d-flex justify-content-between">

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $activity['lead_name']
                                    );
                                    ?>

                                </strong>

                                <span class="small hm-muted">

                                    <?php
                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $activity['activity_at']
                                        )
                                    );
                                    ?>

                                </span>

                            </div>

                            <div class="mt-1">

                                <span class="badge bg-secondary">

                                    <?php
                                    echo htmlspecialchars(
                                        $activity['activity_type']
                                    );
                                    ?>

                                </span>

                            </div>

                            <?php if (!empty($activity['description'])): ?>

                                <div class="small mt-2">

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

    </div>


    
    <!-- APPOINTMENTS -->
    

    <div class="hm-card p-4 mb-4">

        <h5 class="mb-3">
            Appointments
        </h5>

        <?php if (empty($appointments)): ?>

            <div class="alert alert-light border mb-0">
                No appointments found.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>Lead</th>
                            <th>Phone</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($appointments as $appointment): ?>

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
                                echo htmlspecialchars(
                                    $appointment['service_interest']
                                    ?: '-'
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
                                    class="badge <?php echo staff_appointment_status_class($appointment['status']); ?>"
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


    
    <!-- ASSIGNED EVENTS -->
    

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h5 class="mb-1">
                    Assigned Events
                </h5>

                <div class="hm-muted small">
                    Events assigned to this staff member.
                </div>

            </div>

            <span class="badge bg-primary">

                <?php echo $assigned_events; ?>

                Event(s)

            </span>

        </div>

        <?php if (empty($events)): ?>

            <div class="alert alert-light border mb-0">
                No events assigned to this staff member.
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

                    <?php foreach ($events as $event): ?>

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
                                    class="badge <?php echo staff_event_status_class($event['status']); ?>"
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


</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>