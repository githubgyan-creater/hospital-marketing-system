 <?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('telecaller');

$user = current_user();

$user_id = $user['id'];


/*
|--------------------------------------------------------------------------
| Calls Due Today
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        phone,
        service_interest,
        priority,
        next_action_type,
        next_action_at
    FROM leads
    WHERE assigned_to = ?
      AND next_action_at IS NOT NULL
      AND DATE(next_action_at) = CURDATE()
    ORDER BY next_action_at ASC
");

$stmt->execute([$user_id]);

$calls_today = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Overdue Follow-ups
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        phone,
        service_interest,
        priority,
        next_action_type,
        next_action_at
    FROM leads
    WHERE assigned_to = ?
      AND next_action_at IS NOT NULL
      AND next_action_at < NOW()
    ORDER BY next_action_at ASC
");

$stmt->execute([$user_id]);

$overdue_followups = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Upcoming Follow-ups
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        phone,
        service_interest,
        priority,
        next_action_type,
        next_action_at
    FROM leads
    WHERE assigned_to = ?
      AND next_action_at IS NOT NULL
      AND next_action_at > NOW()
      AND DATE(next_action_at) > CURDATE()
    ORDER BY next_action_at ASC
    LIMIT 10
");

$stmt->execute([$user_id]);

$upcoming_followups = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| New Leads
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        phone,
        service_interest,
        priority,
        created_at
    FROM leads
    WHERE assigned_to = ?
      AND status = 'New'
    ORDER BY created_at DESC
    LIMIT 10
");

$stmt->execute([$user_id]);

$new_leads = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Today's Appointments
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
      AND DATE(a.appointment_date) = CURDATE()
      AND a.status NOT IN ('Cancelled', 'No Show')
    ORDER BY a.appointment_date ASC
");

$stmt->execute([$user_id]);

$today_appointments = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Today's Activities
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        la.id,
        la.activity_type,
        la.description,
        la.activity_at,
        l.name AS lead_name
    FROM lead_activities la
    INNER JOIN leads l
        ON la.lead_id = l.id
    WHERE la.user_id = ?
      AND DATE(la.activity_at) = CURDATE()
    ORDER BY la.activity_at DESC
    LIMIT 10
");

$stmt->execute([$user_id]);

$today_activities = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Assigned Events
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        ea.id AS assignment_id,
        e.id AS event_id,
        e.event_name,
        e.event_type,
        e.event_date,
        e.location,
        e.description,
        e.status
    FROM event_assignments ea
    INNER JOIN events e
        ON ea.event_id = e.id
    WHERE ea.user_id = ?
      AND e.status <> 'Cancelled'
    ORDER BY
        e.event_date ASC,
        e.id ASC
    LIMIT 10
");

$stmt->execute([$user_id]);

$assigned_events = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Performance Counts
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE assigned_to = ?
");

$stmt->execute([$user_id]);

$total_leads = (int) $stmt->fetchColumn();


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM lead_activities
    WHERE user_id = ?
      AND DATE(activity_at) = CURDATE()
");

$stmt->execute([$user_id]);

$activities_today = (int) $stmt->fetchColumn();


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM appointments a
    INNER JOIN leads l
        ON a.lead_id = l.id
    WHERE l.assigned_to = ?
      AND a.status IN ('Scheduled', 'Confirmed')
");

$stmt->execute([$user_id]);

$active_appointments = (int) $stmt->fetchColumn();


$page_title = 'My Day';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                My Day
            </h2>

            <p class="hm-muted mb-0">

                Welcome,
                <?php echo htmlspecialchars($user['name']); ?>.

                Here is your telecaller work for today.

            </p>

        </div>

        <a
            href="<?php echo BASE_URL; ?>/leads/add.php"
            class="btn btn-hm-primary"
        >
            + Add Lead
        </a>

    </div>


    <!-- Performance Cards -->

    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Calls Today
                </div>

                <h2 class="mb-0">
                    <?php echo count($calls_today); ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Overdue
                </div>

                <h2 class="mb-0 text-danger">
                    <?php echo count($overdue_followups); ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Activities Today
                </div>

                <h2 class="mb-0 hm-gold">
                    <?php echo $activities_today; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    My Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $total_leads; ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- Quick Navigation -->

    <div class="hm-card p-4 mb-4">

        <h5 class="mb-3">
            Quick Actions
        </h5>

        <div class="d-flex flex-wrap gap-2">

            <a
                href="<?php echo BASE_URL; ?>/leads/index.php"
                class="btn btn-outline-primary"
            >
                My Leads
            </a>

            <a
                href="<?php echo BASE_URL; ?>/telecaller/calls/"
                class="btn btn-outline-primary"
            >
                Call History
            </a>

            <a
                href="<?php echo BASE_URL; ?>/telecaller/followups/"
                class="btn btn-outline-primary"
            >
                Follow-ups
            </a>

            <a
                href="<?php echo BASE_URL; ?>/telecaller/appointments/"
                class="btn btn-outline-primary"
            >
                Appointments
            </a>

        </div>

    </div>


    <div class="row g-4">


        <!-- Calls Due Today -->

        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between mb-3">

                    <h5 class="mb-0">
                        Calls Due Today
                    </h5>

                    <span class="badge bg-primary">
                        <?php echo count($calls_today); ?>
                    </span>

                </div>


                <?php if (empty($calls_today)): ?>

                    <div class="alert alert-light mb-0">
                        No calls scheduled for today.
                    </div>

                <?php else: ?>

                    <?php foreach ($calls_today as $lead): ?>

                        <div class="border-bottom py-3">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['name']
                                        );
                                        ?>

                                    </strong>

                                    <div class="small hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['phone']
                                        );
                                        ?>

                                    </div>

                                </div>

                                <span class="small">

                                    <?php

                                    echo date(
                                        'h:i A',
                                        strtotime(
                                            $lead['next_action_at']
                                        )
                                    );

                                    ?>

                                </span>

                            </div>


                            <div class="mt-2">

                                <a
                                    href="<?php echo BASE_URL; ?>/telecaller/calls/add.php?lead_id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-hm-primary"
                                >
                                    Record Call
                                </a>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    View
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>


        <!-- Overdue -->

        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between mb-3">

                    <h5 class="mb-0 text-danger">
                        Overdue Follow-ups
                    </h5>

                    <span class="badge bg-danger">
                        <?php echo count($overdue_followups); ?>
                    </span>

                </div>


                <?php if (empty($overdue_followups)): ?>

                    <div class="alert alert-success mb-0">
                        No overdue follow-ups.
                    </div>

                <?php else: ?>

                    <?php foreach ($overdue_followups as $lead): ?>

                        <div class="border-bottom py-3">

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $lead['name']
                                );
                                ?>
                            </strong>

                            <div class="small hm-muted">
                                <?php
                                echo htmlspecialchars(
                                    $lead['phone']
                                );
                                ?>
                            </div>

                            <div class="small text-danger mt-1">

                                Due:

                                <?php

                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $lead['next_action_at']
                                    )
                                );

                                ?>

                            </div>


                            <div class="mt-2">

                                <a
                                    href="<?php echo BASE_URL; ?>/telecaller/calls/add.php?lead_id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-hm-primary"
                                >
                                    Follow-up
                                </a>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    View
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>


        <!-- New Leads -->

        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between mb-3">

                    <h5 class="mb-0">
                        New Leads
                    </h5>

                    <span class="badge bg-success">
                        <?php echo count($new_leads); ?>
                    </span>

                </div>


                <?php if (empty($new_leads)): ?>

                    <div class="alert alert-light mb-0">
                        No new leads.
                    </div>

                <?php else: ?>

                    <?php foreach ($new_leads as $lead): ?>

                        <div class="border-bottom py-3">

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $lead['name']
                                );
                                ?>
                            </strong>

                            <div class="small hm-muted">
                                <?php
                                echo htmlspecialchars(
                                    $lead['phone']
                                );
                                ?>
                            </div>

                            <div class="small mt-1">
                                <?php
                                echo htmlspecialchars(
                                    $lead['service_interest']
                                    ?: 'No service specified'
                                );
                                ?>
                            </div>


                            <div class="mt-2">

                                <a
                                    href="<?php echo BASE_URL; ?>/telecaller/calls/add.php?lead_id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-hm-primary"
                                >
                                    Call Lead
                                </a>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    View
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>


        <!-- Today's Appointments -->

        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between mb-3">

                    <h5 class="mb-0">
                        Today's Appointments
                    </h5>

                    <span class="badge bg-success">
                        <?php echo count($today_appointments); ?>
                    </span>

                </div>


                <?php if (empty($today_appointments)): ?>

                    <div class="alert alert-light mb-0">
                        No appointments scheduled today.
                    </div>

                <?php else: ?>

                    <?php foreach ($today_appointments as $appointment): ?>

                        <div class="border-bottom py-3">

                            <div class="d-flex justify-content-between">

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['lead_name']
                                    );
                                    ?>
                                </strong>

                                <span class="small">

                                    <?php

                                    echo date(
                                        'h:i A',
                                        strtotime(
                                            $appointment['appointment_date']
                                        )
                                    );

                                    ?>

                                </span>

                            </div>


                            <div class="small hm-muted">
                                <?php
                                echo htmlspecialchars(
                                    $appointment['lead_phone']
                                );
                                ?>
                            </div>


                            <div class="mt-2">

                                <span class="badge bg-success">
                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['status']
                                    );
                                    ?>
                                </span>


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


        <!-- Assigned Events -->

        <div class="col-12">

            <div class="hm-card p-4">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>

                        <h5 class="mb-1">
                            Assigned Events
                        </h5>

                        <span class="hm-muted">
                            Events assigned to you by the manager.
                        </span>

                    </div>

                    <span class="badge bg-primary">
                        <?php echo count($assigned_events); ?>
                    </span>

                </div>


                <?php if (empty($assigned_events)): ?>

                    <div class="alert alert-light mb-0">

                        No events are currently assigned to you.

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle mb-0">

                            <thead>

                                <tr>

                                    <th>
                                        Event
                                    </th>

                                    <th>
                                        Type
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Location
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

                                <?php foreach ($assigned_events as $event): ?>

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

                                            <?php

                                            $event_status =
                                                $event['status'];

                                            $event_badge =
                                                'bg-primary';

                                            if (
                                                $event_status
                                                === 'Ongoing'
                                            ) {

                                                $event_badge =
                                                    'bg-warning text-dark';

                                            } elseif (
                                                $event_status
                                                === 'Completed'
                                            ) {

                                                $event_badge =
                                                    'bg-success';

                                            }

                                            ?>

                                            <span
                                                class="badge <?php echo $event_badge; ?>"
                                            >

                                                <?php

                                                echo htmlspecialchars(
                                                    $event_status
                                                );

                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <a
                                                href="<?php echo BASE_URL; ?>/events/capture-lead.php?event_id=<?php echo (int) $event['event_id']; ?>"
                                                class="btn btn-sm btn-hm-primary"
                                            >
                                                Capture Lead
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


        <!-- Upcoming Follow-ups -->

        <div class="col-lg-6">

            <div class="hm-card p-4">

                <div class="d-flex justify-content-between mb-3">

                    <h5 class="mb-0">
                        Upcoming Follow-ups
                    </h5>

                    <a
                        href="<?php echo BASE_URL; ?>/telecaller/followups/"
                        class="small"
                    >
                        View All
                    </a>

                </div>


                <?php if (empty($upcoming_followups)): ?>

                    <div class="alert alert-light mb-0">
                        No upcoming follow-ups.
                    </div>

                <?php else: ?>

                    <?php foreach ($upcoming_followups as $lead): ?>

                        <div class="border-bottom py-3">

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $lead['name']
                                );

                                ?>

                            </strong>

                            <div class="small hm-muted">

                                <?php

                                echo htmlspecialchars(
                                    $lead['next_action_type']
                                    ?: 'Follow-up'
                                );

                                ?>

                                ·

                                <?php

                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $lead['next_action_at']
                                    )
                                );

                                ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>


        <!-- Today's Activities -->

        <div class="col-lg-6">

            <div class="hm-card p-4">

                <div class="d-flex justify-content-between mb-3">

                    <h5 class="mb-0">
                        Today's Activities
                    </h5>

                    <span class="hm-muted">
                        <?php echo $activities_today; ?>
                    </span>

                </div>


                <?php if (empty($today_activities)): ?>

                    <div class="alert alert-light mb-0">

                        No activities recorded today.

                    </div>

                <?php else: ?>

                    <?php foreach ($today_activities as $activity): ?>

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
                                        'h:i A',
                                        strtotime(
                                            $activity['activity_at']
                                        )
                                    );

                                    ?>

                                </span>

                            </div>


                            <div class="small mt-1">

                                <span class="badge bg-secondary">

                                    <?php

                                    echo htmlspecialchars(
                                        $activity['activity_type']
                                    );

                                    ?>

                                </span>

                            </div>


                            <div class="small hm-muted mt-1">

                                <?php

                                echo htmlspecialchars(
                                    $activity['description']
                                );

                                ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>