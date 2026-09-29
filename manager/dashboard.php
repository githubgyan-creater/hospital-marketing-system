 <?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('manager');

$user = current_user();

/*

| Total Leads

*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
");

$total_leads = (int) $stmt->fetchColumn();


/*

| New Leads

*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
    WHERE status = 'New'
");

$new_leads = (int) $stmt->fetchColumn();


/*

| Today's Follow-ups

*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
    WHERE next_action_at IS NOT NULL
      AND DATE(next_action_at) = CURDATE()
");

$today_followups = (int) $stmt->fetchColumn();


/*

| Overdue Follow-ups

*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
    WHERE next_action_at IS NOT NULL
      AND next_action_at < NOW()
");

$overdue_followups = (int) $stmt->fetchColumn();


/*

| Today's Calls

*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM lead_calls
    WHERE DATE(call_at) = CURDATE()
");

$today_calls = (int) $stmt->fetchColumn();


/*

| Today's Appointments

*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM appointments
    WHERE DATE(appointment_date) = CURDATE()
      AND status NOT IN ('Cancelled', 'No Show')
");

$today_appointments = (int) $stmt->fetchColumn();


/*

| Active Team Members

*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users u
    INNER JOIN roles r
        ON u.role_id = r.id
    WHERE u.status = 'active'
      AND r.name IN ('telecaller', 'marketing')
");

$active_team = (int) $stmt->fetchColumn();


/*

| Team Members

*/

$stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        u.email,
        r.display_name AS role_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.status = 'active'
      AND r.name IN ('telecaller', 'marketing')

    ORDER BY u.name ASC
");

$team_members = $stmt->fetchAll();


/*

| Recent Lead Activities

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

| Recent Leads

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


$page_title = 'Manager Dashboard';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">


    
    <!-- PAGE HEADER -->
    

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Manager Dashboard
            </h2>

            <p class="hm-muted mb-0">
                 Welcome ,
                 <?php echo htmlspecialchars($user['name']); ?>
            </p>

        </div>


        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">

            <!-- Manager Control Center -->

            <a
                href="<?php echo BASE_URL; ?>/manager/control-center.php"
                class="btn btn-outline-primary"
            >
                ⚡ Control Center
            </a>


            <!-- Add Lead -->

            <a
                href="<?php echo BASE_URL; ?>/leads/add.php"
                class="btn btn-hm-primary"
            >
                + Add Lead
            </a>

        </div>

    </div>


    
    <!-- CONTROL CENTER SHORTCUT -->
    

    <div class="hm-card p-4 mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center">

            <div>

                <h5 class="mb-1">
                    Manager Control Center
                </h5>

                <p class="hm-muted mb-0">
                    View today's leads, follow-ups, appointments,
                    active events and team activity in one place.
                </p>

            </div>


            <div class="mt-3 mt-md-0">

                <a
                    href="<?php echo BASE_URL; ?>/manager/control-center.php"
                    class="btn btn-hm-primary"
                >
                    Open Control Center →
                </a>

            </div>

        </div>

    </div>


    
    <!-- KPI CARDS -->
    

    <div class="row g-3 mb-4">


        <!-- Total Leads -->

        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $total_leads; ?>
                </h2>

            </div>

        </div>


        <!-- New Leads -->

        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    New Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $new_leads; ?>
                </h2>

            </div>

        </div>


        <!-- Today's Follow-ups -->

        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Today's Follow-ups
                </div>

                <h2 class="mb-0 hm-gold">
                    <?php echo $today_followups; ?>
                </h2>

            </div>

        </div>


        <!-- Overdue -->

        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Overdue
                </div>

                <h2 class="mb-0 text-danger">
                    <?php echo $overdue_followups; ?>
                </h2>

            </div>

        </div>

    </div>


    
    <!-- SECOND KPI ROW -->
    

    <div class="row g-3 mb-4">


        <!-- Today's Calls -->

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Calls Today
                </div>

                <h2 class="mb-0">
                    <?php echo $today_calls; ?>
                </h2>

                <a
                    href="<?php echo BASE_URL; ?>/reports/activities.php"
                    class="small"
                >
                    View Activities
                </a>

            </div>

        </div>


        <!-- Today's Appointments -->

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Today's Appointments
                </div>

                <h2 class="mb-0">
                    <?php echo $today_appointments; ?>
                </h2>

                <a
                    href="<?php echo BASE_URL; ?>/reports/leads.php"
                    class="small"
                >
                    View Leads
                </a>

            </div>

        </div>


        <!-- Team -->

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Active Marketing Team
                </div>

                <h2 class="mb-0">
                    <?php echo $active_team; ?>
                </h2>

                <a
                    href="<?php echo BASE_URL; ?>/manager/team/"
                    class="small"
                >
                    View Team
                </a>

            </div>

        </div>

    </div>


    
    <!-- CONTENT -->
    

    <div class="row g-4">


        <!-- ===================================================== -->
        <!-- TEAM MEMBERS -->
        <!-- ===================================================== -->

        <div class="col-lg-5">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between mb-3">

                    <h5 class="mb-0">
                        Marketing Team
                    </h5>

                    <a
                        href="<?php echo BASE_URL; ?>/manager/team/"
                        class="small"
                    >
                        View All
                    </a>

                </div>


                <?php if (empty($team_members)): ?>

                    <div class="alert alert-light">

                        No active marketing team members.

                    </div>

                <?php else: ?>


                    <?php foreach ($team_members as $member): ?>

                        <div class="border-bottom py-3">

                            <div class="d-flex justify-content-between">

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $member['name']
                                    );
                                    ?>

                                </strong>


                                <span class="badge bg-secondary">

                                    <?php
                                    echo htmlspecialchars(
                                        $member['role_name']
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="small hm-muted mt-1">

                                <?php
                                echo htmlspecialchars(
                                    $member['email']
                                );
                                ?>

                            </div>

                        </div>

                    <?php endforeach; ?>


                <?php endif; ?>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- RECENT ACTIVITIES -->
        <!-- ===================================================== -->

        <div class="col-lg-7">

            <div class="hm-card p-4 h-100">

                <div class="d-flex justify-content-between mb-3">

                    <h5 class="mb-0">
                        Recent Activities
                    </h5>

                    <a
                        href="<?php echo BASE_URL; ?>/reports/activities.php"
                        class="small"
                    >
                        View Report
                    </a>

                </div>


                <?php if (empty($recent_activities)): ?>

                    <div class="alert alert-light">

                        No activities recorded yet.

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


                                <span class="small hm-muted ms-2">

                                    by

                                    <?php
                                    echo htmlspecialchars(
                                        $activity['user_name']
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="small mt-2">

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


        <!-- ===================================================== -->
        <!-- RECENT LEADS -->
        <!-- ===================================================== -->

        <div class="col-12">

            <div class="hm-card p-4">

                <div class="d-flex justify-content-between mb-3">

                    <h5 class="mb-0">
                        Recent Leads
                    </h5>

                    <a
                        href="<?php echo BASE_URL; ?>/leads/index.php"
                        class="btn btn-sm btn-outline-primary"
                    >
                        View All Leads
                    </a>

                </div>


                <?php if (empty($recent_leads)): ?>

                    <div class="alert alert-light">

                        No leads found.

                    </div>

                <?php else: ?>


                    <div class="table-responsive">

                        <table class="table align-middle">

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
                                        Assigned To
                                    </th>

                                    <th>
                                        Action
                                    </th>

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

                                            <span class="badge bg-secondary">

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

require_once __DIR__ . '/../includes/footer.php';

?>