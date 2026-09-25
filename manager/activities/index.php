<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$staff_filter = (int) ($_GET['staff_id'] ?? 0);

$outcome_filter = trim($_GET['outcome'] ?? '');

$period = trim($_GET['period'] ?? 'all');


$allowed_periods = [
    'all',
    'today',
    '7days',
    '30days'
];

if (!in_array($period, $allowed_periods, true)) {
    $period = 'all';
}


/*
|--------------------------------------------------------------------------
| Team Members
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        r.name AS role_name
    FROM users u
    INNER JOIN roles r
        ON u.role_id = r.id
    WHERE u.status = 'active'
      AND r.name IN ('telecaller', 'marketing')
    ORDER BY u.name ASC
");

$staff_members = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Summary Counts
|--------------------------------------------------------------------------
*/

$summary_where = "";
$summary_params = [];


/*
|--------------------------------------------------------------------------
| Summary Period
|--------------------------------------------------------------------------
*/

if ($period === 'today') {

    $summary_where = "
        WHERE DATE(call_at) = CURDATE()
    ";

} elseif ($period === '7days') {

    $summary_where = "
        WHERE call_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ";

} elseif ($period === '30days') {

    $summary_where = "
        WHERE call_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
}


$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_calls,

        SUM(call_outcome = 'Connected')
            AS connected_calls,

        SUM(call_outcome = 'Interested')
            AS interested_calls,

        SUM(call_outcome = 'Appointment Requested')
            AS appointment_requests

    FROM lead_calls
    $summary_where
");

$stmt->execute($summary_params);

$summary = $stmt->fetch();


$total_calls =
    (int) ($summary['total_calls'] ?? 0);

$connected_calls =
    (int) ($summary['connected_calls'] ?? 0);

$interested_calls =
    (int) ($summary['interested_calls'] ?? 0);

$appointment_requests =
    (int) ($summary['appointment_requests'] ?? 0);


/*
|--------------------------------------------------------------------------
| Build Call Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        lc.id,
        lc.call_outcome,
        lc.call_notes,
        lc.next_action_type,
        lc.next_action_at,
        lc.call_at,

        l.id AS lead_id,
        l.name AS lead_name,
        l.phone AS lead_phone,
        l.service_interest,
        l.status,

        u.name AS staff_name,

        r.name AS staff_role

    FROM lead_calls lc

    INNER JOIN leads l
        ON lc.lead_id = l.id

    INNER JOIN users u
        ON lc.user_id = u.id

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE r.name IN ('telecaller', 'marketing')
";

$params = [];


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            l.name LIKE ?
            OR l.phone LIKE ?
            OR l.email LIKE ?
            OR lc.call_notes LIKE ?
        )
    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
}


/*
|--------------------------------------------------------------------------
| Staff Filter
|--------------------------------------------------------------------------
*/

if ($staff_filter > 0) {

    $sql .= "
        AND lc.user_id = ?
    ";

    $params[] = $staff_filter;
}


/*
|--------------------------------------------------------------------------
| Outcome Filter
|--------------------------------------------------------------------------
*/

$allowed_outcomes = [
    'Connected',
    'Not Connected',
    'Call Back',
    'Interested',
    'Not Interested',
    'Appointment Requested'
];

if (
    $outcome_filter !== '' &&
    in_array($outcome_filter, $allowed_outcomes, true)
) {

    $sql .= "
        AND lc.call_outcome = ?
    ";

    $params[] = $outcome_filter;
}


/*
|--------------------------------------------------------------------------
| Period Filter
|--------------------------------------------------------------------------
*/

if ($period === 'today') {

    $sql .= "
        AND DATE(lc.call_at) = CURDATE()
    ";

} elseif ($period === '7days') {

    $sql .= "
        AND lc.call_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ";

} elseif ($period === '30days') {

    $sql .= "
        AND lc.call_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY lc.call_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$calls = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Recent General Activities
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        la.id,
        la.activity_type,
        la.description,
        la.activity_at,

        l.id AS lead_id,
        l.name AS lead_name,

        u.name AS user_name,

        r.name AS role_name

    FROM lead_activities la

    INNER JOIN leads l
        ON la.lead_id = l.id

    INNER JOIN users u
        ON la.user_id = u.id

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE r.name IN ('telecaller', 'marketing')

    ORDER BY la.activity_at DESC

    LIMIT 15
");

$activities = $stmt->fetchAll();


$page_title = 'Team Activity Monitoring';

require_once __DIR__ . '/../../includes/header.php';

?>


<div class="container py-4">


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Team Activity Monitoring
            </h2>

            <p class="hm-muted mb-0">
                Monitor team calls and marketing activities.
            </p>

        </div>


        <a
            href="<?php echo BASE_URL; ?>/manager/dashboard.php"
            class="btn btn-outline-secondary"
        >
            Back to Dashboard
        </a>

    </div>


    <!-- Summary Cards -->

    <div class="row g-3 mb-4">


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Calls
                </div>

                <h2 class="mb-0">
                    <?php echo $total_calls; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Connected
                </div>

                <h2 class="mb-0">
                    <?php echo $connected_calls; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Interested
                </div>

                <h2 class="mb-0 hm-gold">
                    <?php echo $interested_calls; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Appointment Requests
                </div>

                <h2 class="mb-0">
                    <?php echo $appointment_requests; ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- Filters -->

    <div class="hm-card p-4 mb-4">

        <form method="GET">

            <div class="row g-3 align-items-end">


                <div class="col-lg-3">

                    <label
                        for="search"
                        class="form-label"
                    >
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        placeholder="Lead, phone or notes"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <div class="col-lg-3">

                    <label
                        for="staff_id"
                        class="form-label"
                    >
                        Team Member
                    </label>

                    <select
                        name="staff_id"
                        id="staff_id"
                        class="form-select"
                    >

                        <option value="0">
                            All Staff
                        </option>

                        <?php foreach ($staff_members as $staff): ?>

                            <option
                                value="<?php echo (int) $staff['id']; ?>"
                                <?php
                                echo $staff_filter === (int) $staff['id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $staff['name']
                                );
                                ?>

                                -

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $staff['role_name']
                                    )
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-lg-2">

                    <label
                        for="outcome"
                        class="form-label"
                    >
                        Outcome
                    </label>

                    <select
                        name="outcome"
                        id="outcome"
                        class="form-select"
                    >

                        <option value="">
                            All Outcomes
                        </option>

                        <?php foreach ($allowed_outcomes as $outcome): ?>

                            <option
                                value="<?php echo htmlspecialchars($outcome); ?>"
                                <?php
                                echo $outcome_filter === $outcome
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars($outcome);
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-lg-2">

                    <label
                        for="period"
                        class="form-label"
                    >
                        Period
                    </label>

                    <select
                        name="period"
                        id="period"
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
                            value="7days"
                            <?php
                            echo $period === '7days'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Last 7 Days
                        </option>

                        <option
                            value="30days"
                            <?php
                            echo $period === '30days'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Last 30 Days
                        </option>

                    </select>

                </div>


                <div class="col-lg-2 d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Filter
                    </button>


                    <a
                        href="<?php echo BASE_URL; ?>/manager/activities/"
                        class="btn btn-outline-secondary"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    <!-- Call Monitoring -->

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="mb-0">
                Team Call History
            </h5>

            <span class="hm-muted">

                <?php echo count($calls); ?> call(s)

            </span>

        </div>


        <?php if (empty($calls)): ?>

            <div class="alert alert-light mb-0">
                No calls found for the selected filters.
            </div>

        <?php else: ?>


            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>
                                Date & Time
                            </th>

                            <th>
                                Lead
                            </th>

                            <th>
                                Team Member
                            </th>

                            <th>
                                Outcome
                            </th>

                            <th>
                                Notes
                            </th>

                            <th>
                                Next Action
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($calls as $call): ?>

                            <tr>

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


                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $call['lead_name']
                                        );
                                        ?>

                                    </strong>

                                    <small class="d-block hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $call['lead_phone']
                                        );
                                        ?>

                                    </small>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $call['staff_name']
                                    );
                                    ?>

                                    <small class="d-block hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $call['staff_role']
                                        );
                                        ?>

                                    </small>

                                </td>


                                <td>

                                    <?php

                                    $outcome_class = 'bg-secondary';

                                    if (
                                        $call['call_outcome']
                                        === 'Interested'
                                    ) {

                                        $outcome_class =
                                            'bg-success';

                                    } elseif (
                                        $call['call_outcome']
                                        === 'Not Interested'
                                    ) {

                                        $outcome_class =
                                            'bg-danger';

                                    } elseif (
                                        $call['call_outcome']
                                        === 'Appointment Requested'
                                    ) {

                                        $outcome_class =
                                            'bg-primary';
                                    }

                                    ?>

                                    <span
                                        class="badge <?php echo $outcome_class; ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $call['call_outcome']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td style="min-width: 220px;">

                                    <?php
                                    echo htmlspecialchars(
                                        $call['call_notes']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $call['next_action_type']
                                        ?: 'None'
                                    );
                                    ?>

                                    <?php if (!empty($call['next_action_at'])): ?>

                                        <small class="d-block hm-muted">

                                            <?php
                                            echo date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $call['next_action_at']
                                                )
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <a
                                        href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $call['lead_id']; ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        View Lead
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>

    </div>


    <!-- Recent Activities -->

    <div class="hm-card p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="mb-0">
                Recent Team Activities
            </h5>

            <span class="hm-muted">
                Latest 15
            </span>

        </div>


        <?php if (empty($activities)): ?>

            <div class="alert alert-light mb-0">
                No team activities found.
            </div>

        <?php else: ?>


            <?php foreach ($activities as $activity): ?>

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

                            -

                            <?php
                            echo htmlspecialchars(
                                $activity['role_name']
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


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>