 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();

/*
|--------------------------------------------------------------------------
| Get Team Member ID
|--------------------------------------------------------------------------
*/
$member_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($member_id <= 0) {
    http_response_code(400);
    exit('Invalid team member.');
}

/*
|--------------------------------------------------------------------------
| Get Team Member
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.name,
        u.email,
        u.status,
        u.created_at,
        r.name AS role_name,
        r.display_name AS role_display_name
    FROM users u
    INNER JOIN roles r
        ON u.role_id = r.id
    WHERE u.id = ?
      AND r.name IN ('telecaller', 'marketing')
    LIMIT 1
");

$stmt->execute([$member_id]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    http_response_code(404);
    exit('Team member not found.');
}

/*
|--------------------------------------------------------------------------
| Assigned Leads
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE assigned_to = ?
");

$stmt->execute([$member_id]);

$total_leads = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| New Leads
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE assigned_to = ?
      AND status = 'New'
");

$stmt->execute([$member_id]);

$new_leads = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Converted Leads
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE assigned_to = ?
      AND status = 'Converted'
");

$stmt->execute([$member_id]);

$converted_leads = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Calls Today
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM lead_calls
    WHERE user_id = ?
      AND DATE(call_at) = CURDATE()
");

$stmt->execute([$member_id]);

$calls_today = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Activities Today
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM lead_activities
    WHERE user_id = ?
      AND DATE(activity_at) = CURDATE()
");

$stmt->execute([$member_id]);

$activities_today = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Today's Appointments
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM appointments a
    INNER JOIN leads l
        ON a.lead_id = l.id
    WHERE l.assigned_to = ?
      AND DATE(a.appointment_date) = CURDATE()
      AND a.status NOT IN ('Cancelled', 'No Show')
");

$stmt->execute([$member_id]);

$appointments_today = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Active Appointments
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM appointments a
    INNER JOIN leads l
        ON a.lead_id = l.id
    WHERE l.assigned_to = ?
      AND a.status IN ('Scheduled', 'Confirmed')
");

$stmt->execute([$member_id]);

$active_appointments = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Overdue Follow-ups
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE assigned_to = ?
      AND next_action_at IS NOT NULL
      AND next_action_at < NOW()
      AND status NOT IN ('Converted', 'Lost')
");

$stmt->execute([$member_id]);

$overdue_followups = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Active Tasks
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM tasks
    WHERE assigned_to = ?
      AND status NOT IN ('Completed', 'Cancelled')
");

$stmt->execute([$member_id]);

$active_tasks = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Recent Activities
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
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

$stmt->execute([$member_id]);

$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Recent Assigned Leads
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        phone,
        service_interest,
        status,
        priority,
        next_action_type,
        next_action_at,
        created_at
    FROM leads
    WHERE assigned_to = ?
    ORDER BY created_at DESC
    LIMIT 10
");

$stmt->execute([$member_id]);

$recent_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Upcoming Appointments
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
        l.phone AS lead_phone
    FROM appointments a
    INNER JOIN leads l
        ON a.lead_id = l.id
    WHERE l.assigned_to = ?
      AND a.appointment_date >= NOW()
      AND a.status IN ('Scheduled', 'Confirmed')
    ORDER BY a.appointment_date ASC
    LIMIT 10
");

$stmt->execute([$member_id]);

$upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Page Setup
|--------------------------------------------------------------------------
*/
$page_title = 'Team Member Profile';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="hm-page-title mb-1">
                Team Member Profile
            </h2>

            <p class="hm-muted mb-0">
                View team member information, workload and activity summary.
            </p>
        </div>

        <a
            href="<?php echo BASE_URL; ?>/manager/team/"
            class="btn btn-outline-secondary"
        >
            Back to Team
        </a>

    </div>


    <!-- Profile Card -->
    <div class="hm-card p-4 mb-4">

        <div class="row align-items-center">

            <div class="col-md-8">

                <h3 class="hm-page-title mb-1">
                    <?php echo htmlspecialchars($member['name'] ?? ''); ?>
                </h3>

                <p class="hm-muted mb-2">
                    <?php echo htmlspecialchars($member['email'] ?? ''); ?>
                </p>

                <div class="d-flex flex-wrap gap-2">

                    <span class="badge bg-secondary">
                        <?php
                        echo htmlspecialchars(
                            $member['role_display_name'] ?? $member['role_name'] ?? ''
                        );
                        ?>
                    </span>

                    <span class="badge bg-success">
                        <?php
                        echo htmlspecialchars(
                            ucfirst($member['status'] ?? '')
                        );
                        ?>
                    </span>

                </div>

            </div>

            <div class="col-md-4 text-md-end mt-3 mt-md-0">

                <div class="small hm-muted">
                    Joined
                </div>

                <strong>
                    <?php
                    echo date(
                        'd M Y',
                        strtotime($member['created_at'])
                    );
                    ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- Performance Summary -->
    <div class="row g-3 mb-4">

        <!-- Assigned Leads -->
        <div class="col-md-4 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Assigned Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $total_leads; ?>
                </h2>

            </div>

        </div>


        <!-- New Leads -->
        <div class="col-md-4 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    New Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $new_leads; ?>
                </h2>

            </div>

        </div>


        <!-- Converted Leads -->
        <div class="col-md-4 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Converted Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $converted_leads; ?>
                </h2>

            </div>

        </div>


        <!-- Calls Today -->
        <div class="col-md-4 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Calls Today
                </div>

                <h2 class="mb-0">
                    <?php echo $calls_today; ?>
                </h2>

            </div>

        </div>


        <!-- Activities Today -->
        <div class="col-md-4 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Activities Today
                </div>

                <h2 class="mb-0 hm-gold">
                    <?php echo $activities_today; ?>
                </h2>

            </div>

        </div>


        <!-- Active Tasks -->
        <div class="col-md-4 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Active Tasks
                </div>

                <h2 class="mb-0">
                    <?php echo $active_tasks; ?>
                </h2>

            </div>

        </div>


        <!-- Today's Appointments -->
        <div class="col-md-4 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Today's Appointments
                </div>

                <h2 class="mb-0">
                    <?php echo $appointments_today; ?>
                </h2>

            </div>

        </div>


        <!-- Overdue Follow-ups -->
        <div class="col-md-4 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Overdue Follow-ups
                </div>

                <h2
                    class="mb-0 <?php echo $overdue_followups > 0 ? 'text-danger' : ''; ?>"
                >
                    <?php echo $overdue_followups; ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- Second Summary -->
    <div class="row g-3 mb-4">

        <div class="col-md-6">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <div class="hm-muted">
                            Active Appointments
                        </div>

                        <h3 class="mb-0">
                            <?php echo $active_appointments; ?>
                        </h3>
                    </div>

                    <div class="text-end">
                        <small class="hm-muted">
                            Current scheduled/confirmed
                        </small>
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-6">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <div class="hm-muted">
                            Lead Conversion
                        </div>

                        <h3 class="mb-0">
                            <?php
                            echo $total_leads > 0
                                ? round(($converted_leads / $total_leads) * 100, 1) . '%'
                                : '0%';
                            ?>
                        </h3>
                    </div>

                    <div>
                        <small class="hm-muted">
                            Based on assigned leads
                        </small>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Activity + Appointments -->
    <div class="row g-4">

        <!-- Recent Activities -->
        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h5 class="mb-0">
                        Recent Activities
                    </h5>

                </div>


                <?php if (empty($recent_activities)): ?>

                    <div class="alert alert-light mb-0">
                        No activities found.
                    </div>

                <?php else: ?>

                    <?php foreach ($recent_activities as $activity): ?>

                        <div class="border-bottom py-3">

                            <div class="d-flex justify-content-between gap-3">

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $activity['lead_name'] ?? ''
                                    );
                                    ?>
                                </strong>

                                <span class="small hm-muted text-nowrap">
                                    <?php
                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime($activity['activity_at'])
                                    );
                                    ?>
                                </span>

                            </div>


                            <div class="mt-2">

                                <span class="badge bg-secondary">
                                    <?php
                                    echo htmlspecialchars(
                                        $activity['activity_type'] ?? ''
                                    );
                                    ?>
                                </span>

                            </div>


                            <div class="small hm-muted mt-2">

                                <?php
                                echo htmlspecialchars(
                                    $activity['description'] ?? ''
                                );
                                ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>


        <!-- Upcoming Appointments -->
        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h5 class="mb-0">
                        Upcoming Appointments
                    </h5>

                </div>


                <?php if (empty($upcoming_appointments)): ?>

                    <div class="alert alert-light mb-0">
                        No upcoming appointments.
                    </div>

                <?php else: ?>

                    <?php foreach ($upcoming_appointments as $appointment): ?>

                        <div class="border-bottom py-3">

                            <div class="d-flex justify-content-between gap-3">

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['lead_name'] ?? ''
                                    );
                                    ?>
                                </strong>

                                <span class="small text-nowrap">

                                    <?php
                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $appointment['appointment_date']
                                        )
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="small hm-muted mt-1">

                                <?php
                                echo htmlspecialchars(
                                    $appointment['lead_phone'] ?? ''
                                );
                                ?>

                            </div>


                            <div class="mt-2">

                                <span class="badge bg-success">

                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['status'] ?? ''
                                    );
                                    ?>

                                </span>


                                <?php if (!empty($appointment['appointment_type'])): ?>

                                    <span class="badge bg-light text-dark border ms-1">

                                        <?php
                                        echo htmlspecialchars(
                                            $appointment['appointment_type']
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>


                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $appointment['lead_id']; ?>"
                                    class="btn btn-sm btn-outline-secondary ms-2"
                                >
                                    View Lead
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>


        <!-- Recent Assigned Leads -->
        <div class="col-12">

            <div class="hm-card p-4">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h5 class="mb-0">
                        Recent Assigned Leads
                    </h5>

                    <a
                        href="<?php echo BASE_URL; ?>/leads/index.php"
                        class="btn btn-sm btn-outline-primary"
                    >
                        View All Leads
                    </a>

                </div>


                <?php if (empty($recent_leads)): ?>

                    <div class="alert alert-light mb-0">
                        No assigned leads found.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle mb-0">

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

                                <?php foreach ($recent_leads as $lead): ?>

                                    <tr>

                                        <!-- Lead -->
                                        <td>

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $lead['name'] ?? ''
                                                );
                                                ?>
                                            </strong>

                                        </td>


                                        <!-- Phone -->
                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $lead['phone'] ?? ''
                                            );
                                            ?>

                                        </td>


                                        <!-- Service -->
                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $lead['service_interest'] ?: '-'
                                            );
                                            ?>

                                        </td>


                                        <!-- Status -->
                                        <td>

                                            <span class="badge bg-secondary">

                                                <?php
                                                echo htmlspecialchars(
                                                    $lead['status'] ?? ''
                                                );
                                                ?>

                                            </span>

                                        </td>


                                        <!-- Priority -->
                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $lead['priority'] ?? '-'
                                            );
                                            ?>

                                        </td>


                                        <!-- Next Action -->
                                        <td>

                                            <?php if (!empty($lead['next_action_at'])): ?>

                                                <div>

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $lead['next_action_type'] ?: 'Action'
                                                    );
                                                    ?>

                                                </div>

                                                <small class="hm-muted">

                                                    <?php
                                                    echo date(
                                                        'd M Y, h:i A',
                                                        strtotime(
                                                            $lead['next_action_at']
                                                        )
                                                    );
                                                    ?>

                                                </small>

                                            <?php else: ?>

                                                <span class="hm-muted">
                                                    No next action
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- Action -->
                                        <td>

                                            <a
                                                href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                                class="btn btn-sm btn-outline-secondary"
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

</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>