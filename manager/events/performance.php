 <?php

require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('admin', 'manager');

$user = current_user();

/*

| Filter Inputs

*/

$search = trim($_GET['search'] ?? '');

$event_type = trim($_GET['event_type'] ?? '');

$status = trim($_GET['status'] ?? '');

$date_from = trim($_GET['date_from'] ?? '');

$date_to = trim($_GET['date_to'] ?? '');


/*

| Allowed Filter Values

*/

$allowed_event_types = [
    'Health Camp',
    'Outreach',
    'Seminar',
    'Workshop',
    'Doctor Meet',
    'Corporate Event',
    'Other'
];

$allowed_statuses = [
    'Planned',
    'Ongoing',
    'Completed',
    'Cancelled'
];


/*

| Build Event Query

*/

$sql = "
    SELECT

        e.id,
        e.event_name,
        e.event_type,
        e.event_date,
        e.location,
        e.status,

        (
            SELECT COUNT(*)
            FROM event_assignments ea
            WHERE ea.event_id = e.id
        ) AS assigned_staff_count,

        (
            SELECT COUNT(DISTINCT el.lead_id)
            FROM event_leads el
            WHERE el.event_id = e.id
        ) AS leads_count,

        (
            SELECT COUNT(DISTINCT a.id)

            FROM appointments a

            INNER JOIN event_leads el
                ON a.lead_id = el.lead_id

            WHERE el.event_id = e.id

        ) AS appointments_count,

        (
            SELECT COUNT(DISTINCT a.id)

            FROM appointments a

            INNER JOIN event_leads el
                ON a.lead_id = el.lead_id

            WHERE el.event_id = e.id
              AND a.status = 'Completed'

        ) AS completed_appointments_count,

        (
            SELECT COUNT(DISTINCT l.id)

            FROM leads l

            INNER JOIN event_leads el
                ON el.lead_id = l.id

            WHERE el.event_id = e.id
              AND l.status = 'Converted'

        ) AS converted_leads_count

    FROM events e

    WHERE 1 = 1
";


$params = [];


/*

| Search Event

*/

if ($search !== '') {

    $sql .= "
        AND (
            e.event_name LIKE ?
            OR e.location LIKE ?
        )
    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;
    $params[] = $search_value;
}


/*

| Filter Event Type

*/

if (
    $event_type !== ''
    && in_array(
        $event_type,
        $allowed_event_types,
        true
    )
) {

    $sql .= "
        AND e.event_type = ?
    ";

    $params[] = $event_type;
}


/*

| Filter Status

*/

if (
    $status !== ''
    && in_array(
        $status,
        $allowed_statuses,
        true
    )
) {

    $sql .= "
        AND e.status = ?
    ";

    $params[] = $status;
}


/*

| Filter From Date

*/

if ($date_from !== '') {

    $sql .= "
        AND e.event_date >= ?
    ";

    $params[] = $date_from;
}


/*

| Filter To Date

*/

if ($date_to !== '') {

    $sql .= "
        AND e.event_date <= ?
    ";

    $params[] = $date_to;
}


/*

| Order

*/

$sql .= "
    ORDER BY
        e.event_date DESC,
        e.id DESC
";


/*

| Execute Query

*/

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$events = $stmt->fetchAll();


/*

| Dashboard Summary

*/

$total_events = count($events);

$total_leads = 0;

$total_appointments = 0;

$total_completed = 0;

$total_converted = 0;

$planned_events = 0;

$ongoing_events = 0;

$completed_events = 0;

$cancelled_events = 0;


foreach ($events as $event) {

    $total_leads +=
        (int) $event['leads_count'];

    $total_appointments +=
        (int) $event['appointments_count'];

    $total_completed +=
        (int) $event['completed_appointments_count'];

    $total_converted +=
        (int) $event['converted_leads_count'];


    switch ($event['status']) {

        case 'Planned':
            $planned_events++;
            break;

        case 'Ongoing':
            $ongoing_events++;
            break;

        case 'Completed':
            $completed_events++;
            break;

        case 'Cancelled':
            $cancelled_events++;
            break;
    }
}


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


/*

| Page

*/

$page_title = 'Event Performance';

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
        Event Performance Dashboard
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

        .section-title {
            color: #17324d;
            font-weight: 700;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: #17324d;
        }

        .summary-label {
            color: #71808c;
            font-size: 0.9rem;
        }

        .rate-value {
            font-weight: 700;
        }

        .table th {
            white-space: nowrap;
        }

        .filter-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #243746;
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
                Event Performance Dashboard
            </h2>

            <p class="hm-muted mb-0">
                Monitor event leads, appointments and conversions.
            </p>

        </div>


        <div class="mt-3 mt-md-0">

            <a
                href="<?php echo BASE_URL; ?>/manager/dashboard.php"
                class="btn btn-outline-secondary"
            >
                Manager Dashboard
            </a>

        </div>

    </div>


    
    <!-- FILTERS -->
    

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h4 class="section-title mb-1">
                    Event Filters
                </h4>

                <div class="hm-muted small">
                    Find events by name, type, status or date.
                </div>

            </div>

        </div>


        <form
            method="GET"
            action=""
        >

            <div class="row g-3">


                <!-- Search -->

                <div class="col-md-6 col-lg-3">

                    <label class="form-label filter-label">
                        Search Event
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Event name or location"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <!-- Event Type -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label filter-label">
                        Event Type
                    </label>

                    <select
                        name="event_type"
                        class="form-select"
                    >

                        <option value="">
                            All Types
                        </option>

                        <?php foreach ($allowed_event_types as $type): ?>

                            <option
                                value="<?php echo htmlspecialchars($type); ?>"
                                <?php
                                echo $event_type === $type
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars($type);
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Status -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label filter-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        <?php foreach ($allowed_statuses as $status_value): ?>

                            <option
                                value="<?php echo htmlspecialchars($status_value); ?>"
                                <?php
                                echo $status === $status_value
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $status_value
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- From Date -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label filter-label">
                        From Date
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        class="form-control"
                        value="<?php echo htmlspecialchars($date_from); ?>"
                    >

                </div>


                <!-- To Date -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label filter-label">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        class="form-control"
                        value="<?php echo htmlspecialchars($date_to); ?>"
                    >

                </div>


                <!-- Buttons -->

                <div class="col-md-6 col-lg-1 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn hm-primary w-100"
                    >
                        Filter
                    </button>

                </div>

            </div>


            <div class="mt-3">

                <a
                    href="<?php echo BASE_URL; ?>/manager/events/performance.php"
                    class="btn btn-outline-secondary btn-sm"
                >
                    Reset Filters
                </a>

            </div>

        </form>

    </div>


    
    <!-- ACTIVE FILTER MESSAGE -->
    

    <?php

    $has_filters =
        $search !== ''
        || $event_type !== ''
        || $status !== ''
        || $date_from !== ''
        || $date_to !== '';

    ?>

    <?php if ($has_filters): ?>

        <div class="alert alert-info">

            Showing
            <strong>
                <?php echo $total_events; ?>
            </strong>
            event(s) matching the selected filters.

        </div>

    <?php endif; ?>


    
    <!-- SUMMARY CARDS -->
    

    <div class="row g-3 mb-4">


        <!-- Total Events -->

        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="summary-label">
                    Total Events
                </div>

                <div class="summary-value">
                    <?php echo $total_events; ?>
                </div>

            </div>

        </div>


        <!-- Planned -->

        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="summary-label">
                    Planned Events
                </div>

                <div class="summary-value">
                    <?php echo $planned_events; ?>
                </div>

            </div>

        </div>


        <!-- Leads -->

        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="summary-label">
                    Total Leads
                </div>

                <div class="summary-value">
                    <?php echo $total_leads; ?>
                </div>

            </div>

        </div>


        <!-- Converted -->

        <div class="col-md-6 col-xl-3">

            <div class="hm-card p-4 h-100">

                <div class="summary-label">
                    Converted Leads
                </div>

                <div class="summary-value">
                    <?php echo $total_converted; ?>
                </div>

            </div>

        </div>

    </div>


    
    <!-- EVENT STATUS SUMMARY -->
    

    <div class="row g-3 mb-4">


        <div class="col-md-3">

            <div class="hm-card p-3">

                <div class="small hm-muted">
                    Planned
                </div>

                <strong>
                    <?php echo $planned_events; ?>
                </strong>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-3">

                <div class="small hm-muted">
                    Ongoing
                </div>

                <strong>
                    <?php echo $ongoing_events; ?>
                </strong>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-3">

                <div class="small hm-muted">
                    Completed
                </div>

                <strong>
                    <?php echo $completed_events; ?>
                </strong>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-3">

                <div class="small hm-muted">
                    Cancelled
                </div>

                <strong>
                    <?php echo $cancelled_events; ?>
                </strong>

            </div>

        </div>

    </div>


    
    <!-- PERFORMANCE TABLE -->
    

    <div class="hm-card p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h4 class="section-title mb-1">
                    Event-wise Performance
                </h4>

                <div class="hm-muted small">
                    Track each event from lead generation to conversion.
                </div>

            </div>

            <span class="hm-muted small">

                <?php echo $total_events; ?> event(s)

            </span>

        </div>


        <?php if (empty($events)): ?>

            <div class="alert alert-light border mb-0">

                No events found for the selected filters.

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>
                                Event
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Staff
                            </th>

                            <th>
                                Leads
                            </th>

                            <th>
                                Appointments
                            </th>

                            <th>
                                Completed
                            </th>

                            <th>
                                Converted
                            </th>

                            <th>
                                Conversion Rate
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($events as $event): ?>

                        <?php

                        $leads =
                            (int) $event['leads_count'];

                        $converted =
                            (int) $event['converted_leads_count'];

                        $conversion_rate = 0;

                        if ($leads > 0) {

                            $conversion_rate =
                                ($converted / $leads) * 100;
                        }

                        ?>


                        <tr>


                            <!-- Event -->

                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $event['event_name']
                                    );
                                    ?>

                                </div>

                                <div class="small hm-muted">

                                    <?php
                                    echo htmlspecialchars(
                                        $event['event_type']
                                    );
                                    ?>

                                </div>

                                <?php if (!empty($event['location'])): ?>

                                    <div class="small text-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $event['location']
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- Date -->

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


                            <!-- Status -->

                            <td>

                                <span
                                    class="badge <?php echo event_status_class($event['status']); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $event['status']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- Staff -->

                            <td>

                                <?php
                                echo (int)
                                    $event['assigned_staff_count'];
                                ?>

                            </td>


                            <!-- Leads -->

                            <td>

                                <?php
                                echo $leads;
                                ?>

                            </td>


                            <!-- Appointments -->

                            <td>

                                <?php
                                echo (int)
                                    $event['appointments_count'];
                                ?>

                            </td>


                            <!-- Completed -->

                            <td>

                                <?php
                                echo (int)
                                    $event['completed_appointments_count'];
                                ?>

                            </td>


                            <!-- Converted -->

                            <td>

                                <?php
                                echo $converted;
                                ?>

                            </td>


                            <!-- Conversion Rate -->

                            <td>

                                <span class="rate-value">

                                    <?php
                                    echo number_format(
                                        $conversion_rate,
                                        1
                                    );
                                    ?>%

                                </span>

                            </td>


                            <!-- Action -->

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


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>