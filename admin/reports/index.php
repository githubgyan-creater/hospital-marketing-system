<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('admin');

$user = current_user();


/*
|--------------------------------------------------------------------------
| System Summary
|--------------------------------------------------------------------------
*/


/* Total Users */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
");

$total_users = (int) $stmt->fetchColumn();


/* Active Users */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE status = 'active'
");

$active_users = (int) $stmt->fetchColumn();


/* Total Leads */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
");

$total_leads = (int) $stmt->fetchColumn();


/* New Leads */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
    WHERE status = 'New'
");

$new_leads = (int) $stmt->fetchColumn();


/* Converted Leads */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
    WHERE status = 'Converted'
");

$converted_leads = (int) $stmt->fetchColumn();


/* Total Events */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM events
");

$total_events = (int) $stmt->fetchColumn();


/* Active Events */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM events
    WHERE status IN ('Planned', 'Ongoing')
");

$active_events = (int) $stmt->fetchColumn();


/* Total Referrals */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM referrals
");

$total_referrals = (int) $stmt->fetchColumn();


/* Total Appointments */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM appointments
");

$total_appointments = (int) $stmt->fetchColumn();


/* Completed Appointments */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM appointments
    WHERE status = 'Completed'
");

$completed_appointments = (int) $stmt->fetchColumn();


/* Total Activities */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM lead_activities
");

$total_activities = (int) $stmt->fetchColumn();


/* Today's Activities */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM lead_activities
    WHERE DATE(activity_at) = CURDATE()
");

$today_activities = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Lead Status Summary
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        status,
        COUNT(*) AS total

    FROM leads

    GROUP BY status

    ORDER BY total DESC
");

$lead_status_summary = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Event Status Summary
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        status,
        COUNT(*) AS total

    FROM events

    GROUP BY status

    ORDER BY total DESC
");

$event_status_summary = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Recent Leads
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        l.id,
        l.name,
        l.phone,
        l.service_interest,
        l.status,
        l.priority,
        l.created_at,
        u.name AS assigned_name

    FROM leads l

    LEFT JOIN users u
        ON l.assigned_to = u.id

    ORDER BY l.created_at DESC

    LIMIT 10
");

$recent_leads = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Recent Activities
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        la.activity_type,
        la.description,
        la.activity_at,
        l.name AS lead_name,
        u.name AS user_name

    FROM lead_activities la

    INNER JOIN leads l
        ON la.lead_id = l.id

    INNER JOIN users u
        ON la.user_id = u.id

    ORDER BY la.activity_at DESC

    LIMIT 10
");

$recent_activities = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function report_status_class(string $status): string
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


$page_title = 'Admin Reports';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">


    <!-- ========================================================= -->
    <!-- PAGE HEADER -->
    <!-- ========================================================= -->

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>

            <div class="mb-2">

                <a
                    href="<?php echo BASE_URL; ?>/admin/dashboard.php"
                    class="text-decoration-none"
                >
                    ← Admin Dashboard
                </a>

            </div>

            <h1 class="hm-page-title mb-1">
                Reports
            </h1>

            <p class="hm-muted mb-0">
                System-wide hospital marketing performance overview.
            </p>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- MAIN SUMMARY -->
    <!-- ========================================================= -->

    <div class="row g-3 mb-4">


        <!-- Users -->

        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Users
                </div>

                <h2 class="mb-0">
                    <?php echo $total_users; ?>
                </h2>

                <div class="small text-success mt-1">
                    Active:
                    <?php echo $active_users; ?>
                </div>

            </div>

        </div>


        <!-- Leads -->

        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $total_leads; ?>
                </h2>

                <div class="small hm-muted mt-1">
                    New:
                    <?php echo $new_leads; ?>
                </div>

            </div>

        </div>


        <!-- Converted -->

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


        <!-- Events -->

        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Active Events
                </div>

                <h2 class="mb-0">
                    <?php echo $active_events; ?>
                </h2>

                <div class="small hm-muted mt-1">
                    Total:
                    <?php echo $total_events; ?>
                </div>

            </div>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- SECOND SUMMARY -->
    <!-- ========================================================= -->

    <div class="row g-3 mb-4">


        <!-- Referrals -->

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Referral Partners
                </div>

                <h2 class="mb-0">
                    <?php echo $total_referrals; ?>
                </h2>

            </div>

        </div>


        <!-- Appointments -->

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Appointments
                </div>

                <h2 class="mb-0">
                    <?php echo $total_appointments; ?>
                </h2>

                <div class="small text-success mt-1">
                    Completed:
                    <?php echo $completed_appointments; ?>
                </div>

            </div>

        </div>


        <!-- Activities -->

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Activities
                </div>

                <h2 class="mb-0">
                    <?php echo $total_activities; ?>
                </h2>

                <div class="small hm-muted mt-1">
                    Today:
                    <?php echo $today_activities; ?>
                </div>

            </div>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- LEAD STATUS + EVENT STATUS -->
    <!-- ========================================================= -->

    <div class="row g-4 mb-4">


        <!-- Lead Status -->

        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <h5 class="mb-3">
                    Lead Status Summary
                </h5>


                <?php if (empty($lead_status_summary)): ?>

                    <div class="alert alert-light border">
                        No lead data available.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($lead_status_summary as $item): ?>

                                <tr>

                                    <td>

                                        <span
                                            class="badge <?php echo report_status_class($item['status']); ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $item['status']
                                            );
                                            ?>

                                        </span>

                                    </td>

                                    <td>

                                        <strong>
                                            <?php
                                            echo (int) $item['total'];
                                            ?>
                                        </strong>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>


        <!-- Event Status -->

        <div class="col-lg-6">

            <div class="hm-card p-4 h-100">

                <h5 class="mb-3">
                    Event Status Summary
                </h5>


                <?php if (empty($event_status_summary)): ?>

                    <div class="alert alert-light border">
                        No event data available.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($event_status_summary as $item): ?>

                                <tr>

                                    <td>

                                        <span
                                            class="badge <?php echo report_status_class($item['status']); ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $item['status']
                                            );
                                            ?>

                                        </span>

                                    </td>

                                    <td>

                                        <strong>
                                            <?php
                                            echo (int) $item['total'];
                                            ?>
                                        </strong>

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
    <!-- RECENT LEADS -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h5 class="mb-1">
                    Recent Leads
                </h5>

                <div class="hm-muted small">
                    Latest leads entered into the system.
                </div>

            </div>

            <a
                href="<?php echo BASE_URL; ?>/leads/index.php"
                class="btn btn-sm btn-outline-primary"
            >
                View All Leads
            </a>

        </div>


        <?php if (empty($recent_leads)): ?>

            <div class="alert alert-light border mb-0">
                No leads found.
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
                            <th>Created</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($recent_leads as $lead): ?>

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
                                    class="badge <?php echo report_status_class($lead['status']); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['status']
                                    );
                                    ?>

                                </span>

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
                                echo htmlspecialchars(
                                    $lead['assigned_name']
                                    ?: 'Unassigned'
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

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>


    <!-- ========================================================= -->
    <!-- RECENT ACTIVITIES -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h5 class="mb-1">
                    Recent Team Activities
                </h5>

                <div class="hm-muted small">
                    Latest activity recorded by marketing staff.
                </div>

            </div>

        </div>


        <?php if (empty($recent_activities)): ?>

            <div class="alert alert-light border mb-0">

                No recent activities found.

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>Lead</th>
                            <th>Activity</th>
                            <th>Staff</th>
                            <th>Description</th>
                            <th>Date</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($recent_activities as $activity): ?>

                        <tr>

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $activity['lead_name']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <span class="badge bg-secondary">

                                    <?php
                                    echo htmlspecialchars(
                                        $activity['activity_type']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $activity['user_name']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $activity['description']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $activity['activity_at']
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


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>