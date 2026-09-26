 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();

/*
|--------------------------------------------------------------------------
| Filter Inputs
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$role_filter = trim($_GET['role'] ?? '');

$period = trim($_GET['period'] ?? 'all');


/*
|--------------------------------------------------------------------------
| Allowed Filters
|--------------------------------------------------------------------------
*/

$allowed_roles = [
    'telecaller',
    'marketing executive'
];

$allowed_periods = [
    'all',
    'today',
    '7',
    '30',
    'month'
];


/*
|--------------------------------------------------------------------------
| Validate Role
|--------------------------------------------------------------------------
*/

if (
    $role_filter !== ''
    && !in_array(
        $role_filter,
        $allowed_roles,
        true
    )
) {
    $role_filter = '';
}


/*
|--------------------------------------------------------------------------
| Validate Period
|--------------------------------------------------------------------------
*/

if (
    !in_array(
        $period,
        $allowed_periods,
        true
    )
) {
    $period = 'all';
}


/*
|--------------------------------------------------------------------------
| Performance Date Range
|--------------------------------------------------------------------------
*/

$date_start = null;
$date_end = null;

if ($period === 'today') {

    $date_start = date('Y-m-d 00:00:00');

    $date_end = date(
        'Y-m-d 00:00:00',
        strtotime('+1 day')
    );
}

if ($period === '7') {

    $date_start = date(
        'Y-m-d 00:00:00',
        strtotime('-6 days')
    );

    $date_end = date(
        'Y-m-d 00:00:00',
        strtotime('+1 day')
    );
}

if ($period === '30') {

    $date_start = date(
        'Y-m-d 00:00:00',
        strtotime('-29 days')
    );

    $date_end = date(
        'Y-m-d 00:00:00',
        strtotime('+1 day')
    );
}

if ($period === 'month') {

    $date_start = date(
        'Y-m-01 00:00:00'
    );

    $date_end = date(
        'Y-m-d 00:00:00',
        strtotime('+1 day')
    );
}


/*
|--------------------------------------------------------------------------
| Get Marketing Team
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        u.id,
        u.name,
        u.email,
        r.name AS role_name,
        r.display_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.status = 'active'

      AND (
          LOWER(r.name) = 'telecaller'
          OR LOWER(r.name) = 'marketing executive'
          OR LOWER(r.name) = 'marketing'
      )
";

$params = [];


/*
|--------------------------------------------------------------------------
| Search Staff
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;
    $params[] = $search_value;
}


/*
|--------------------------------------------------------------------------
| Role Filter
|--------------------------------------------------------------------------
*/

if ($role_filter === 'telecaller') {

    $sql .= "
        AND LOWER(r.name) = 'telecaller'
    ";
}

if ($role_filter === 'marketing executive') {

    $sql .= "
        AND (
            LOWER(r.name) = 'marketing executive'
            OR LOWER(r.name) = 'marketing'
        )
    ";
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY u.name ASC
";


/*
|--------------------------------------------------------------------------
| Execute Staff Query
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$team_members = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Prepare Performance
|--------------------------------------------------------------------------
*/

$team_performance = [];


foreach ($team_members as $member) {

    $staff_id = (int) $member['id'];


    /*
    |--------------------------------------------------------------------------
    | Total Leads
    |--------------------------------------------------------------------------
    */

    $lead_sql = "
        SELECT COUNT(*)

        FROM leads

        WHERE assigned_to = ?
    ";

    $lead_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $lead_sql .= "
            AND created_at >= ?
            AND created_at < ?
        ";

        $lead_params[] = $date_start;
        $lead_params[] = $date_end;
    }

    $stmt = $pdo->prepare($lead_sql);

    $stmt->execute($lead_params);

    $total_leads = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | New Leads
    |--------------------------------------------------------------------------
    */

    $new_lead_sql = "
        SELECT COUNT(*)

        FROM leads

        WHERE assigned_to = ?
          AND status = 'New'
    ";

    $new_lead_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $new_lead_sql .= "
            AND created_at >= ?
            AND created_at < ?
        ";

        $new_lead_params[] = $date_start;
        $new_lead_params[] = $date_end;
    }

    $stmt = $pdo->prepare($new_lead_sql);

    $stmt->execute($new_lead_params);

    $new_leads = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Converted Leads
    |--------------------------------------------------------------------------
    */

    $converted_sql = "
        SELECT COUNT(*)

        FROM leads

        WHERE assigned_to = ?
          AND status = 'Converted'
    ";

    $converted_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $converted_sql .= "
            AND updated_at >= ?
            AND updated_at < ?
        ";

        $converted_params[] = $date_start;
        $converted_params[] = $date_end;
    }

    $stmt = $pdo->prepare($converted_sql);

    $stmt->execute($converted_params);

    $converted_leads = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Calls
    |--------------------------------------------------------------------------
    */

    $calls_sql = "
        SELECT COUNT(*)

        FROM lead_calls

        WHERE user_id = ?
    ";

    $calls_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $calls_sql .= "
            AND call_at >= ?
            AND call_at < ?
        ";

        $calls_params[] = $date_start;
        $calls_params[] = $date_end;
    }

    $stmt = $pdo->prepare($calls_sql);

    $stmt->execute($calls_params);

    $total_calls = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Activities
    |--------------------------------------------------------------------------
    */

    $activities_sql = "
        SELECT COUNT(*)

        FROM lead_activities

        WHERE user_id = ?
    ";

    $activities_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $activities_sql .= "
            AND activity_at >= ?
            AND activity_at < ?
        ";

        $activities_params[] = $date_start;
        $activities_params[] = $date_end;
    }

    $stmt = $pdo->prepare($activities_sql);

    $stmt->execute($activities_params);

    $total_activities = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Appointments
    |--------------------------------------------------------------------------
    */

    $appointments_sql = "
        SELECT COUNT(*)

        FROM appointments a

        INNER JOIN leads l
            ON a.lead_id = l.id

        WHERE l.assigned_to = ?

          AND a.status NOT IN (
              'Cancelled',
              'No Show'
          )
    ";

    $appointments_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $appointments_sql .= "
            AND a.appointment_date >= ?
            AND a.appointment_date < ?
        ";

        $appointments_params[] = $date_start;
        $appointments_params[] = $date_end;
    }

    $stmt = $pdo->prepare($appointments_sql);

    $stmt->execute($appointments_params);

    $total_appointments = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Overdue Follow-ups
    |--------------------------------------------------------------------------
    */

    $overdue_sql = "
        SELECT COUNT(*)

        FROM leads

        WHERE assigned_to = ?

          AND next_action_at IS NOT NULL

          AND next_action_at < NOW()

          AND status NOT IN (
              'Converted',
              'Lost'
          )
    ";

    $overdue_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $overdue_sql .= "
            AND next_action_at >= ?
            AND next_action_at < ?
        ";

        $overdue_params[] = $date_start;
        $overdue_params[] = $date_end;
    }

    $stmt = $pdo->prepare($overdue_sql);

    $stmt->execute($overdue_params);

    $overdue_followups = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Today's Calls
    |--------------------------------------------------------------------------
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
    |--------------------------------------------------------------------------
    | Today's Activities
    |--------------------------------------------------------------------------
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
    |--------------------------------------------------------------------------
    | Conversion Rate
    |--------------------------------------------------------------------------
    */

    $conversion_rate = 0;

    if ($total_leads > 0) {

        $conversion_rate =
            ($converted_leads / $total_leads) * 100;
    }


    /*
    |--------------------------------------------------------------------------
    | Store Performance
    |--------------------------------------------------------------------------
    */

    $team_performance[] = [

        'id' => $staff_id,

        'name' => $member['name'],

        'email' => $member['email'],

        'role_name' =>
            $member['display_name']
            ?: $member['role_name'],

        'total_leads' =>
            $total_leads,

        'new_leads' =>
            $new_leads,

        'converted_leads' =>
            $converted_leads,

        'total_calls' =>
            $total_calls,

        'total_activities' =>
            $total_activities,

        'total_appointments' =>
            $total_appointments,

        'overdue_followups' =>
            $overdue_followups,

        'today_calls' =>
            $today_calls,

        'today_activities' =>
            $today_activities,

        'today_appointments' =>
            $today_appointments,

        'conversion_rate' =>
            $conversion_rate
    ];
}


/*
|--------------------------------------------------------------------------
| Overall Team Summary
|--------------------------------------------------------------------------
*/

$team_total_leads = 0;
$team_new_leads = 0;
$team_converted_leads = 0;
$team_calls = 0;
$team_activities = 0;
$team_appointments = 0;
$team_overdue = 0;

$team_today_calls = 0;
$team_today_activities = 0;
$team_today_appointments = 0;


foreach ($team_performance as $member) {

    $team_total_leads +=
        $member['total_leads'];

    $team_new_leads +=
        $member['new_leads'];

    $team_converted_leads +=
        $member['converted_leads'];

    $team_calls +=
        $member['total_calls'];

    $team_activities +=
        $member['total_activities'];

    $team_appointments +=
        $member['total_appointments'];

    $team_overdue +=
        $member['overdue_followups'];

    $team_today_calls +=
        $member['today_calls'];

    $team_today_activities +=
        $member['today_activities'];

    $team_today_appointments +=
        $member['today_appointments'];
}


$team_conversion_rate = 0;

if ($team_total_leads > 0) {

    $team_conversion_rate =
        ($team_converted_leads / $team_total_leads) * 100;
}


/*
|--------------------------------------------------------------------------
| Filter Status
|--------------------------------------------------------------------------
*/

$has_filters =
    $search !== ''
    || $role_filter !== ''
    || $period !== 'all';


/*
|--------------------------------------------------------------------------
| Period Label
|--------------------------------------------------------------------------
*/

$period_label = 'All Time';

switch ($period) {

    case 'today':
        $period_label = 'Today';
        break;

    case '7':
        $period_label = 'Last 7 Days';
        break;

    case '30':
        $period_label = 'Last 30 Days';
        break;

    case 'month':
        $period_label = 'This Month';
        break;
}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$page_title = 'Team Performance';

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
                    href="<?php echo BASE_URL; ?>/manager/dashboard.php"
                    class="text-decoration-none"
                >
                    ← Manager Dashboard
                </a>

            </div>

            <h2 class="hm-page-title mb-1">
                Team Performance
            </h2>

            <p class="hm-muted mb-0">
                Monitor Telecaller and Marketing Executive performance.
            </p>

        </div>


        <!-- ===================================================== -->
        <!-- HEADER ACTIONS -->
        <!-- ===================================================== -->

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">

            <a
                href="<?php echo BASE_URL; ?>/manager/control-center.php"
                class="btn btn-outline-primary"
            >
                ⚡ Control Center
            </a>

            <a
                href="<?php echo BASE_URL; ?>/manager/team/"
                class="btn btn-outline-secondary"
            >
                Team
            </a>

            <a
                href="<?php echo BASE_URL; ?>/manager/team/export.php?<?php echo http_build_query([
                    'search' => $search,
                    'role' => $role_filter,
                    'period' => $period
                ]); ?>"
                class="btn btn-success"
            >
                ↓ Export CSV
            </a>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- FILTERS -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h5 class="mb-1">
                    Performance Filters
                </h5>

                <div class="hm-muted small">
                    Filter staff performance by name, role and period.
                </div>

            </div>

        </div>


        <form
            method="GET"
            action=""
        >

            <div class="row g-3">


                <!-- Search -->

                <div class="col-md-6 col-lg-4">

                    <label class="form-label">
                        Search Staff
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Staff name or email"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <!-- Role -->

                <div class="col-md-6 col-lg-3">

                    <label class="form-label">
                        Role
                    </label>

                    <select
                        name="role"
                        class="form-select"
                    >

                        <option value="">
                            All Roles
                        </option>

                        <option
                            value="telecaller"
                            <?php
                            echo $role_filter === 'telecaller'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Telecaller
                        </option>

                        <option
                            value="marketing executive"
                            <?php
                            echo $role_filter === 'marketing executive'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Marketing Executive
                        </option>

                    </select>

                </div>


                <!-- Period -->

                <div class="col-md-6 col-lg-3">

                    <label class="form-label">
                        Performance Period
                    </label>

                    <select
                        name="period"
                        class="form-select"
                    >

                        <option
                            value="all"
                            <?php
                            echo $period === 'all'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            All Time
                        </option>

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

                        <option
                            value="month"
                            <?php
                            echo $period === 'month'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            This Month
                        </option>

                    </select>

                </div>


                <!-- Filter -->

                <div class="col-md-6 col-lg-2 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn btn-hm-primary w-100"
                    >
                        Filter
                    </button>

                </div>

            </div>


            <div class="mt-3">

                <a
                    href="<?php echo BASE_URL; ?>/manager/team/performance.php"
                    class="btn btn-sm btn-outline-secondary"
                >
                    Reset Filters
                </a>

            </div>

        </form>

    </div>


    <!-- ========================================================= -->
    <!-- FILTER INFORMATION -->
    <!-- ========================================================= -->

    <?php if ($has_filters): ?>

        <div class="alert alert-info">

            Showing performance for

            <strong>
                <?php echo htmlspecialchars($period_label); ?>
            </strong>

            <?php if ($search !== ''): ?>

                · Search:

                <strong>
                    <?php echo htmlspecialchars($search); ?>
                </strong>

            <?php endif; ?>


            <?php if ($role_filter !== ''): ?>

                · Role:

                <strong>
                    <?php echo htmlspecialchars($role_filter); ?>
                </strong>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- ========================================================= -->
    <!-- TEAM SUMMARY -->
    <!-- ========================================================= -->

    <div class="row g-3 mb-4">


        <!-- Team Members -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Team Members
                </div>

                <h2 class="mb-0">
                    <?php
                    echo count(
                        $team_performance
                    );
                    ?>
                </h2>

            </div>

        </div>


        <!-- Leads -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Leads
                </div>

                <h2 class="mb-0">
                    <?php
                    echo $team_total_leads;
                    ?>
                </h2>

            </div>

        </div>


        <!-- Converted -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Converted Leads
                </div>

                <h2 class="mb-0">
                    <?php
                    echo $team_converted_leads;
                    ?>
                </h2>

            </div>

        </div>


        <!-- Conversion -->

        <div class="col-md-6 col-lg-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Conversion Rate
                </div>

                <h2 class="mb-0">

                    <?php
                    echo number_format(
                        $team_conversion_rate,
                        1
                    );
                    ?>%

                </h2>

            </div>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- TODAY SUMMARY -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h5 class="mb-1">
                    Today's Team Activity
                </h5>

                <div class="hm-muted small">
                    Quick view of today's operational activity.
                </div>

            </div>

        </div>


        <div class="row g-3">


            <!-- Calls -->

            <div class="col-md-4">

                <div class="border rounded p-3">

                    <div class="hm-muted">
                        Calls Today
                    </div>

                    <h3 class="mb-0">

                        <?php
                        echo $team_today_calls;
                        ?>

                    </h3>

                </div>

            </div>


            <!-- Activities -->

            <div class="col-md-4">

                <div class="border rounded p-3">

                    <div class="hm-muted">
                        Activities Today
                    </div>

                    <h3 class="mb-0">

                        <?php
                        echo $team_today_activities;
                        ?>

                    </h3>

                </div>

            </div>


            <!-- Appointments -->

            <div class="col-md-4">

                <div class="border rounded p-3">

                    <div class="hm-muted">
                        Appointments Today
                    </div>

                    <h3 class="mb-0">

                        <?php
                        echo $team_today_appointments;
                        ?>

                    </h3>

                </div>

            </div>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- PERFORMANCE TABLE -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">

            <div>

                <h5 class="mb-1">
                    Staff-wise Performance
                </h5>

                <div class="hm-muted small">

                    Performance period:

                    <strong>
                        <?php echo htmlspecialchars($period_label); ?>
                    </strong>

                </div>

            </div>


            <span class="hm-muted small">

                <?php
                echo count(
                    $team_performance
                );
                ?>

                Staff Member(s)

            </span>

        </div>


        <?php if (empty($team_performance)): ?>

            <div class="alert alert-light border mb-0">

                No team members match the selected filters.

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>Staff</th>
                            <th>Role</th>
                            <th>Leads</th>
                            <th>New</th>
                            <th>Converted</th>
                            <th>Calls</th>
                            <th>Activities</th>
                            <th>Appointments</th>
                            <th>Overdue</th>
                            <th>Conversion</th>
                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($team_performance as $member): ?>

                        <tr>


                            <!-- Staff -->

                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $member['name']
                                    );
                                    ?>

                                </div>

                                <div class="small hm-muted">

                                    <?php
                                    echo htmlspecialchars(
                                        $member['email']
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- Role -->

                            <td>

                                <span class="badge bg-secondary">

                                    <?php
                                    echo htmlspecialchars(
                                        $member['role_name']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- Leads -->

                            <td>

                                <?php
                                echo $member['total_leads'];
                                ?>

                            </td>


                            <!-- New -->

                            <td>

                                <?php
                                echo $member['new_leads'];
                                ?>

                            </td>


                            <!-- Converted -->

                            <td>

                                <?php
                                echo $member['converted_leads'];
                                ?>

                            </td>


                            <!-- Calls -->

                            <td>

                                <?php
                                echo $member['total_calls'];
                                ?>

                                <?php if ($member['today_calls'] > 0): ?>

                                    <div class="small text-success">

                                        Today:

                                        <?php
                                        echo $member['today_calls'];
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- Activities -->

                            <td>

                                <?php
                                echo $member['total_activities'];
                                ?>

                                <?php if ($member['today_activities'] > 0): ?>

                                    <div class="small text-success">

                                        Today:

                                        <?php
                                        echo $member['today_activities'];
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- Appointments -->

                            <td>

                                <?php
                                echo $member['total_appointments'];
                                ?>

                                <?php if ($member['today_appointments'] > 0): ?>

                                    <div class="small text-success">

                                        Today:

                                        <?php
                                        echo $member['today_appointments'];
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- Overdue -->

                            <td>

                                <?php if (
                                    $member['overdue_followups'] > 0
                                ): ?>

                                    <span class="badge bg-danger">

                                        <?php
                                        echo $member['overdue_followups'];
                                        ?>

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-success">
                                        0
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Conversion -->

                            <td>

                                <strong>

                                    <?php
                                    echo number_format(
                                        $member['conversion_rate'],
                                        1
                                    );
                                    ?>%

                                </strong>

                            </td>


                            <!-- Action -->

                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/manager/team/staff.php?staff_id=<?php echo (int) $member['id']; ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    View
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>


                    <!-- ================================================= -->
                    <!-- TEAM TOTAL -->
                    <!-- ================================================= -->

                    <tfoot class="table-light">

                        <tr>

                            <th colspan="2">
                                Team Total
                            </th>

                            <th>
                                <?php
                                echo $team_total_leads;
                                ?>
                            </th>

                            <th>
                                <?php
                                echo $team_new_leads;
                                ?>
                            </th>

                            <th>
                                <?php
                                echo $team_converted_leads;
                                ?>
                            </th>

                            <th>
                                <?php
                                echo $team_calls;
                                ?>
                            </th>

                            <th>
                                <?php
                                echo $team_activities;
                                ?>
                            </th>

                            <th>
                                <?php
                                echo $team_appointments;
                                ?>
                            </th>

                            <th>
                                <?php
                                echo $team_overdue;
                                ?>
                            </th>

                            <th>

                                <?php
                                echo number_format(
                                    $team_conversion_rate,
                                    1
                                );
                                ?>%

                            </th>

                            <th>
                                —
                            </th>

                        </tr>

                    </tfoot>

                </table>

            </div>

        <?php endif; ?>

    </div>


    <!-- ========================================================= -->
    <!-- INFORMATION -->
    <!-- ========================================================= -->

    <div class="alert alert-light border">

        <strong>Performance period:</strong>

        <?php
        echo htmlspecialchars(
            $period_label
        );
        ?>.

        Leads are measured by lead creation date,
        calls by call date,
        activities by activity date,
        appointments by appointment date,
        and conversions by lead update date.

    </div>


</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>