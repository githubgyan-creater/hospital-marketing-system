 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('admin');

$user = current_user();


/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
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


/*
|--------------------------------------------------------------------------
| STAFF SEARCH
|--------------------------------------------------------------------------
*/

$staff_search = isset($_GET['staff_search'])
    ? trim($_GET['staff_search'])
    : '';


/*
|--------------------------------------------------------------------------
| STAFF PERFORMANCE QUERY
|--------------------------------------------------------------------------
*/

$staff_performance_sql = "
    SELECT

        u.id,
        u.name,
        u.email,

        COALESCE(r.name, 'Staff') AS role_name,

        (
            SELECT COUNT(*)
            FROM leads l
            WHERE l.assigned_to = u.id
        ) AS total_leads,

        (
            SELECT COUNT(*)
            FROM leads l
            WHERE l.assigned_to = u.id
            AND l.status = 'New'
        ) AS new_leads,

        (
            SELECT COUNT(*)
            FROM leads l
            WHERE l.assigned_to = u.id
            AND l.status = 'Converted'
        ) AS converted_leads,

        (
            SELECT COUNT(*)
            FROM lead_activities la
            WHERE la.user_id = u.id
            AND LOWER(la.activity_type) = 'call'
        ) AS total_calls,

        (
            SELECT COUNT(*)
            FROM lead_activities la
            WHERE la.user_id = u.id
        ) AS total_activities,

        (
            SELECT COUNT(*)
            FROM appointments a
            WHERE a.created_by = u.id
        ) AS total_appointments,

        (
            SELECT COUNT(*)
            FROM leads l
            WHERE l.assigned_to = u.id
            AND l.next_action_at IS NOT NULL
            AND l.next_action_at < NOW()
            AND l.status NOT IN ('Converted', 'Lost')
        ) AS overdue_followups

    FROM users u

    LEFT JOIN roles r
        ON u.role_id = r.id

    WHERE u.status = 'active'
";


/*
|--------------------------------------------------------------------------
| STAFF SEARCH FILTER
|--------------------------------------------------------------------------
*/

$staff_params = [];

if ($staff_search !== '') {

    $staff_performance_sql .= "
        AND (
            u.name LIKE :staff_search
            OR u.email LIKE :staff_search
            OR r.name LIKE :staff_search
        )
    ";

    $staff_params[':staff_search'] = '%' . $staff_search . '%';
}


$staff_performance_sql .= "
    ORDER BY u.name ASC
";


$stmt = $pdo->prepare($staff_performance_sql);

$stmt->execute($staff_params);

$staff_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CSV EXPORT
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['export']) &&
    $_GET['export'] === 'csv'
) {

    try {

        /*
        |--------------------------------------------------------------------------
        | LEAD CSV EXPORT
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
                COALESCE(u.name, 'Unassigned') AS assigned_name
            FROM leads l
            LEFT JOIN users u
                ON l.assigned_to = u.id
            ORDER BY l.created_at DESC
        ");

        $filename =
            'hospital-marketing-leads-' .
            date('Y-m-d-H-i-s') .
            '.csv';


        header('Content-Type: text/csv; charset=utf-8');

        header(
            'Content-Disposition: attachment; filename="' .
            $filename .
            '"'
        );

        header('Pragma: no-cache');
        header('Expires: 0');


        $output = fopen('php://output', 'w');


        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        | No UTF-8 BOM is written here.
        | This prevents "ï»¿" from appearing before Lead ID.
        |--------------------------------------------------------------------------
        */


        fputcsv(
            $output,
            [
                'Lead ID',
                'Lead Name',
                'Phone',
                'Service Interest',
                'Status',
                'Priority',
                'Assigned To',
                'Created On'
            ]
        );


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            fputcsv(
                $output,
                [
                    $row['id'],
                    $row['name'],
                    $row['phone'],
                    $row['service_interest'],
                    $row['status'],
                    $row['priority'],
                    $row['assigned_name'],
                    $row['created_at']
                ]
            );
        }


        fclose($output);

        exit;

    } catch (Throwable $e) {

        http_response_code(500);

        exit('CSV export failed.');
    }
}


/*
|--------------------------------------------------------------------------
| STAFF PERFORMANCE CSV EXPORT
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['export']) &&
    $_GET['export'] === 'staff_csv'
) {

    try {

        $filename =
            'staff-performance-report-' .
            date('Y-m-d-H-i-s') .
            '.csv';


        header('Content-Type: text/csv; charset=utf-8');

        header(
            'Content-Disposition: attachment; filename="' .
            $filename .
            '"'
        );

        header('Pragma: no-cache');
        header('Expires: 0');


        $output = fopen('php://output', 'w');


        /*
        |--------------------------------------------------------------------------
        | REPORT HEADER
        |--------------------------------------------------------------------------
        */

        fputcsv(
            $output,
            [
                'Staff Performance Report'
            ]
        );


        fputcsv(
            $output,
            [
                'Generated On',
                date('d M Y, h:i A')
            ]
        );


        fputcsv(
            $output,
            [
                'Performance Period',
                'All Time'
            ]
        );


        fputcsv(
            $output,
            [
                'Search',
                $staff_search !== ''
                    ? $staff_search
                    : 'All Staff'
            ]
        );


        fputcsv($output, []);


        /*
        |--------------------------------------------------------------------------
        | STAFF PERFORMANCE HEADER
        |--------------------------------------------------------------------------
        */

        fputcsv(
            $output,
            [
                'Staff Name',
                'Email',
                'Role',
                'Leads',
                'New Leads',
                'Converted Leads',
                'Calls',
                'Activities',
                'Appointments',
                'Overdue Follow-ups',
                'Conversion Rate'
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | STAFF PERFORMANCE ROWS
        |--------------------------------------------------------------------------
        */

        foreach ($staff_performance as $staff) {

            $total_leads = (int) $staff['total_leads'];

            $converted_leads = (int) $staff['converted_leads'];


            $conversion_rate = $total_leads > 0
                ? ($converted_leads / $total_leads) * 100
                : 0;


            fputcsv(
                $output,
                [
                    $staff['name'],
                    $staff['email'],
                    $staff['role_name'],
                    $total_leads,
                    (int) $staff['new_leads'],
                    $converted_leads,
                    (int) $staff['total_calls'],
                    (int) $staff['total_activities'],
                    (int) $staff['total_appointments'],
                    (int) $staff['overdue_followups'],
                    number_format($conversion_rate, 2) . '%'
                ]
            );
        }


        fclose($output);

        exit;

    } catch (Throwable $e) {

        http_response_code(500);

        exit('Staff performance export failed.');
    }
}


/*
|--------------------------------------------------------------------------
| SYSTEM SUMMARY
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| TOTAL USERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
");

$total_users = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| ACTIVE USERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE status = 'active'
");

$active_users = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL LEADS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
");

$total_leads = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| NEW LEADS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
    WHERE status = 'New'
");

$new_leads = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| CONVERTED LEADS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM leads
    WHERE status = 'Converted'
");

$converted_leads = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL EVENTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM events
");

$total_events = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| ACTIVE EVENTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM events
    WHERE status IN ('Planned', 'Ongoing')
");

$active_events = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL REFERRALS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM referrals
");

$total_referrals = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL APPOINTMENTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM appointments
");

$total_appointments = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| COMPLETED APPOINTMENTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM appointments
    WHERE status = 'Completed'
");

$completed_appointments = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL ACTIVITIES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM lead_activities
");

$total_activities = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TODAY'S ACTIVITIES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM lead_activities
    WHERE DATE(activity_at) = CURDATE()
");

$today_activities = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| LEAD STATUS SUMMARY
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

$lead_status_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| EVENT STATUS SUMMARY
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

$event_status_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| RECENT LEADS
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
        COALESCE(u.name, 'Unassigned') AS assigned_name
    FROM leads l
    LEFT JOIN users u
        ON l.assigned_to = u.id
    ORDER BY l.created_at DESC
    LIMIT 10
");

$recent_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| RECENT ACTIVITIES
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

$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$page_title = 'Admin Reports';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">


    <!-- =========================================================
         PAGE HEADER
    ========================================================== -->

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">

        <div>

            <h1 class="hm-page-title mb-2">
                Admin Reports
            </h1>

            <div class="hm-muted">
                System overview, lead performance and team activity.
            </div>

        </div>


        <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">

            <a
                href="<?php echo BASE_URL; ?>/admin/reports/index.php?export=csv"
                class="btn btn-outline-success"
            >
                Export Leads CSV
            </a>

            <a
                href="<?php echo BASE_URL; ?>/admin/reports/index.php?export=staff_csv<?php echo $staff_search !== '' ? '&staff_search=' . urlencode($staff_search) : ''; ?>"
                class="btn btn-outline-primary"
            >
                Export Staff Report
            </a>

        </div>

    </div>


    <!-- =========================================================
         MAIN SUMMARY
    ========================================================== -->

    <div class="row g-3 mb-4">


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


    <!-- =========================================================
         SECOND SUMMARY
    ========================================================== -->

    <div class="row g-3 mb-4">


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


    <!-- =========================================================
         STAFF PERFORMANCE REPORT
    ========================================================== -->

    <div class="hm-card p-4 mb-4">


        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">

            <div>

                <h5 class="mb-1">
                    Staff Performance Report
                </h5>

                <div class="hm-muted small">
                    Staff-wise lead, activity, appointment and follow-up performance.
                </div>

            </div>


            <div class="mt-3 mt-lg-0">

                <span class="small hm-muted">
                    Generated On:
                    <?php echo date('d M Y, h:i A'); ?>
                </span>

            </div>

        </div>


        <!-- STAFF SEARCH -->

        <form
            method="GET"
            class="row g-2 mb-4"
        >

            <div class="col-md-8">

                <label class="form-label">
                    Search Staff
                </label>

                <input
                    type="text"
                    name="staff_search"
                    value="<?php echo htmlspecialchars($staff_search); ?>"
                    class="form-control"
                    placeholder="Search by name, email or role"
                >

            </div>


            <div class="col-md-4 d-flex align-items-end gap-2">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Search
                </button>

                <a
                    href="<?php echo BASE_URL; ?>/admin/reports/index.php"
                    class="btn btn-outline-secondary"
                >
                    Reset
                </a>

            </div>

        </form>


        <!-- PERFORMANCE PERIOD -->

        <div class="row g-3 mb-3">

            <div class="col-md-6">

                <div class="small hm-muted">
                    Performance Period
                </div>

                <strong>
                    All Time
                </strong>

            </div>


            <div class="col-md-6">

                <div class="small hm-muted">
                    Search
                </div>

                <strong>
                    <?php
                    echo $staff_search !== ''
                        ? htmlspecialchars($staff_search)
                        : 'All Staff';
                    ?>
                </strong>

            </div>

        </div>


        <?php if (empty($staff_performance)): ?>

            <div class="alert alert-light border mb-0">
                No staff performance data found.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle mb-0">

                    <thead>

                        <tr>

                            <th>
                                Staff Name
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Leads
                            </th>

                            <th>
                                New Leads
                            </th>

                            <th>
                                Converted Leads
                            </th>

                            <th>
                                Calls
                            </th>

                            <th>
                                Activities
                            </th>

                            <th>
                                Appointments
                            </th>

                            <th>
                                Overdue Follow-ups
                            </th>

                            <th>
                                Conversion Rate
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($staff_performance as $staff): ?>

                            <?php

                            $total_staff_leads =
                                (int) $staff['total_leads'];

                            $total_staff_converted =
                                (int) $staff['converted_leads'];

                            $staff_conversion_rate =
                                $total_staff_leads > 0
                                    ? (
                                        $total_staff_converted
                                        / $total_staff_leads
                                    ) * 100
                                    : 0;

                            ?>


                            <tr>


                                <!-- STAFF NAME -->

                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $staff['name']
                                        );
                                        ?>
                                    </strong>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $staff['email']
                                    );
                                    ?>

                                </td>


                                <!-- ROLE -->

                                <td>

                                    <span class="badge bg-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $staff['role_name']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- LEADS -->

                                <td>

                                    <?php
                                    echo $total_staff_leads;
                                    ?>

                                </td>


                                <!-- NEW LEADS -->

                                <td>

                                    <?php
                                    echo (int) $staff['new_leads'];
                                    ?>

                                </td>


                                <!-- CONVERTED LEADS -->

                                <td>

                                    <?php
                                    echo $total_staff_converted;
                                    ?>

                                </td>


                                <!-- CALLS -->

                                <td>

                                    <?php
                                    echo (int) $staff['total_calls'];
                                    ?>

                                </td>


                                <!-- ACTIVITIES -->

                                <td>

                                    <?php
                                    echo (int) $staff['total_activities'];
                                    ?>

                                </td>


                                <!-- APPOINTMENTS -->

                                <td>

                                    <?php
                                    echo (int) $staff['total_appointments'];
                                    ?>

                                </td>


                                <!-- OVERDUE FOLLOW-UPS -->

                                <td>

                                    <?php
                                    echo (int) $staff['overdue_followups'];
                                    ?>

                                </td>


                                <!-- CONVERSION RATE -->

                                <td>

                                    <strong>

                                        <?php
                                        echo number_format(
                                            $staff_conversion_rate,
                                            2
                                        );
                                        ?>%

                                    </strong>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>


    <!-- =========================================================
         LEAD STATUS + EVENT STATUS
    ========================================================== -->

    <div class="row g-4 mb-4">


        <!-- LEAD STATUS -->

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

                        <table class="table align-middle mb-0">

                            <thead>

                                <tr>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Total
                                    </th>

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


        <!-- EVENT STATUS -->

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

                        <table class="table align-middle mb-0">

                            <thead>

                                <tr>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Total
                                    </th>

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


    <!-- =========================================================
         RECENT LEADS
    ========================================================== -->

    <div class="hm-card p-4 mb-4">


        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">

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
                class="btn btn-sm btn-outline-primary mt-2 mt-md-0"
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

                <table class="table align-middle mb-0">

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
                                Created
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
                                        $lead['service_interest'] ?: '-'
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
                                        $lead['priority'] ?: '-'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['assigned_name'] ?: 'Unassigned'
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


    <!-- =========================================================
         RECENT TEAM ACTIVITIES
    ========================================================== -->

    <div class="hm-card p-4 mb-4">


        <div class="mb-3">

            <h5 class="mb-1">
                Recent Team Activities
            </h5>

            <div class="hm-muted small">
                Latest activity recorded by marketing staff.
            </div>

        </div>


        <?php if (empty($recent_activities)): ?>

            <div class="alert alert-light border mb-0">
                No recent activities found.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle mb-0">

                    <thead>

                        <tr>

                            <th>
                                Lead
                            </th>

                            <th>
                                Activity
                            </th>

                            <th>
                                Staff
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Date
                            </th>

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