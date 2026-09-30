<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('manager');


/*

| Filters

*/

$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$staff_id = (int) ($_GET['staff_id'] ?? 0);


/*

| Validate Dates

*/

if (
    $date_from !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)
) {
    $date_from = '';
}

if (
    $date_to !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)
) {
    $date_to = '';
}


/*

| Team Members

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

$team_members = $stmt->fetchAll();


/*

| Build Date Conditions

*/

function build_date_condition(
    string $column,
    string $date_from,
    string $date_to,
    array &$params
): string {

    $conditions = '';

    if ($date_from !== '') {

        $conditions .= " AND DATE($column) >= ? ";

        $params[] = $date_from;
    }

    if ($date_to !== '') {

        $conditions .= " AND DATE($column) <= ? ";

        $params[] = $date_to;
    }

    return $conditions;
}


/*

| Total Leads

*/

$lead_params = [];

$lead_date_condition = build_date_condition(
    'l.created_at',
    $date_from,
    $date_to,
    $lead_params
);

$sql = "
    SELECT COUNT(*)
    FROM leads l
    WHERE 1 = 1
    $lead_date_condition
";

if ($staff_id > 0) {

    $sql .= "
        AND l.assigned_to = ?
    ";

    $lead_params[] = $staff_id;
}

$stmt = $pdo->prepare($sql);

$stmt->execute($lead_params);

$total_leads = (int) $stmt->fetchColumn();


/*

| Total Calls

*/

$call_params = [];

$call_date_condition = build_date_condition(
    'lc.call_at',
    $date_from,
    $date_to,
    $call_params
);

$sql = "
    SELECT COUNT(*)
    FROM lead_calls lc
    INNER JOIN users u
        ON lc.user_id = u.id
    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE r.name IN ('telecaller', 'marketing')

    $call_date_condition
";

if ($staff_id > 0) {

    $sql .= "
        AND lc.user_id = ?
    ";

    $call_params[] = $staff_id;
}

$stmt = $pdo->prepare($sql);

$stmt->execute($call_params);

$total_calls = (int) $stmt->fetchColumn();


/*

| Connected Calls

*/

$connected_params = [];

$connected_date_condition = build_date_condition(
    'lc.call_at',
    $date_from,
    $date_to,
    $connected_params
);

$sql = "
    SELECT COUNT(*)
    FROM lead_calls lc

    INNER JOIN users u
        ON lc.user_id = u.id

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE r.name IN ('telecaller', 'marketing')

      AND lc.call_outcome = 'Connected'

    $connected_date_condition
";

if ($staff_id > 0) {

    $sql .= "
        AND lc.user_id = ?
    ";

    $connected_params[] = $staff_id;
}

$stmt = $pdo->prepare($sql);

$stmt->execute($connected_params);

$connected_calls = (int) $stmt->fetchColumn();


/*

| Interested Calls

*/

$interested_params = [];

$interested_date_condition = build_date_condition(
    'lc.call_at',
    $date_from,
    $date_to,
    $interested_params
);

$sql = "
    SELECT COUNT(*)
    FROM lead_calls lc

    INNER JOIN users u
        ON lc.user_id = u.id

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE r.name IN ('telecaller', 'marketing')

      AND lc.call_outcome = 'Interested'

    $interested_date_condition
";

if ($staff_id > 0) {

    $sql .= "
        AND lc.user_id = ?
    ";

    $interested_params[] = $staff_id;
}

$stmt = $pdo->prepare($sql);

$stmt->execute($interested_params);

$interested_calls = (int) $stmt->fetchColumn();


/*

| Appointment Requests

*/

$appointment_request_params = [];

$appointment_date_condition = build_date_condition(
    'lc.call_at',
    $date_from,
    $date_to,
    $appointment_request_params
);

$sql = "
    SELECT COUNT(*)
    FROM lead_calls lc

    INNER JOIN users u
        ON lc.user_id = u.id

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE r.name IN ('telecaller', 'marketing')

      AND lc.call_outcome = 'Appointment Requested'

    $appointment_date_condition
";

if ($staff_id > 0) {

    $sql .= "
        AND lc.user_id = ?
    ";

    $appointment_request_params[] = $staff_id;
}

$stmt = $pdo->prepare($sql);

$stmt->execute($appointment_request_params);

$appointment_requests = (int) $stmt->fetchColumn();


/*

| Total Activities

*/

$activity_params = [];

$activity_date_condition = build_date_condition(
    'la.activity_at',
    $date_from,
    $date_to,
    $activity_params
);

$sql = "
    SELECT COUNT(*)
    FROM lead_activities la

    INNER JOIN users u
        ON la.user_id = u.id

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE r.name IN ('telecaller', 'marketing')

    $activity_date_condition
";

if ($staff_id > 0) {

    $sql .= "
        AND la.user_id = ?
    ";

    $activity_params[] = $staff_id;
}

$stmt = $pdo->prepare($sql);

$stmt->execute($activity_params);

$total_activities = (int) $stmt->fetchColumn();


/*

| Team Performance

*/

$team_params = [];

$team_call_date_condition = build_date_condition(
    'lc.call_at',
    $date_from,
    $date_to,
    $team_params
);

$team_activity_date_condition = build_date_condition(
    'la.activity_at',
    $date_from,
    $date_to,
    $team_params
);

$sql = "
    SELECT

        u.id,
        u.name,
        r.display_name AS role_display_name,

        (
            SELECT COUNT(*)
            FROM leads l
            WHERE l.assigned_to = u.id

            " .
            build_date_condition(
                'l.created_at',
                $date_from,
                $date_to,
                $team_params
            ) .
            "
        ) AS lead_count,

        (
            SELECT COUNT(*)
            FROM lead_calls lc2
            WHERE lc2.user_id = u.id
            " .
            build_date_condition(
                'lc2.call_at',
                $date_from,
                $date_to,
                $team_params
            ) .
            "
        ) AS call_count,

        (
            SELECT COUNT(*)
            FROM lead_calls lc3
            WHERE lc3.user_id = u.id
              AND lc3.call_outcome = 'Connected'
            " .
            build_date_condition(
                'lc3.call_at',
                $date_from,
                $date_to,
                $team_params
            ) .
            "
        ) AS connected_count,

        (
            SELECT COUNT(*)
            FROM lead_calls lc4
            WHERE lc4.user_id = u.id
              AND lc4.call_outcome = 'Interested'
            " .
            build_date_condition(
                'lc4.call_at',
                $date_from,
                $date_to,
                $team_params
            ) .
            "
        ) AS interested_count,

        (
            SELECT COUNT(*)
            FROM lead_activities la2
            WHERE la2.user_id = u.id
            " .
            build_date_condition(
                'la2.activity_at',
                $date_from,
                $date_to,
                $team_params
            ) .
            "
        ) AS activity_count

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.status = 'active'
      AND r.name IN ('telecaller', 'marketing')
";

if ($staff_id > 0) {

    $sql .= "
        AND u.id = ?
    ";

    $team_params[] = $staff_id;
}

$sql .= "
    ORDER BY u.name ASC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($team_params);

$team_performance = $stmt->fetchAll();


/*

| Lead Status Summary

*/

$status_params = [];

$status_date_condition = build_date_condition(
    'l.created_at',
    $date_from,
    $date_to,
    $status_params
);

$sql = "
    SELECT
        l.status,
        COUNT(*) AS total

    FROM leads l

    WHERE 1 = 1

    $status_date_condition
";

if ($staff_id > 0) {

    $sql .= "
        AND l.assigned_to = ?
    ";

    $status_params[] = $staff_id;
}

$sql .= "
    GROUP BY l.status
    ORDER BY total DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute($status_params);

$status_summary = $stmt->fetchAll();


$page_title = 'Team Performance Report';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">


    <!-- Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Team Performance Report
            </h2>

            <p class="hm-muted mb-0">
                Review marketing team activity and lead performance.
            </p>

        </div>


        <a
            href="<?php echo BASE_URL; ?>/manager/dashboard.php"
            class="btn btn-outline-secondary"
        >
            Back to Dashboard
        </a>

    </div>


    <!-- Filters -->

    <div class="hm-card p-4 mb-4">

        <form method="GET">

            <div class="row g-3 align-items-end">


                <div class="col-md-4">

                    <label
                        for="date_from"
                        class="form-label"
                    >
                        Date From
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        id="date_from"
                        class="form-control"
                        value="<?php echo htmlspecialchars($date_from); ?>"
                    >

                </div>


                <div class="col-md-4">

                    <label
                        for="date_to"
                        class="form-label"
                    >
                        Date To
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        id="date_to"
                        class="form-control"
                        value="<?php echo htmlspecialchars($date_to); ?>"
                    >

                </div>


                <div class="col-md-4">

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
                            All Team Members
                        </option>


                        <?php foreach ($team_members as $member): ?>

                            <option
                                value="<?php echo (int) $member['id']; ?>"
                                <?php
                                echo $staff_id === (int) $member['id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $member['name']
                                );
                                ?>

                                -

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $member['role_name']
                                    )
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-12">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Apply Report
                    </button>


                    <a
                        href="<?php echo BASE_URL; ?>/reports/team-performance.php"
                        class="btn btn-outline-secondary ms-2"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    <!-- KPI Cards -->

    <div class="row g-3 mb-4">


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $total_leads; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Calls
                </div>

                <h2 class="mb-0">
                    <?php echo $total_calls; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Activities
                </div>

                <h2 class="mb-0 hm-gold">
                    <?php echo $total_activities; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Connected Calls
                </div>

                <h2 class="mb-0">
                    <?php echo $connected_calls; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Interested Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $interested_calls; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-4">

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


    <!-- Team Performance -->

    <div class="hm-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="mb-0">
                Team Performance
            </h5>

            <span class="hm-muted">
                <?php echo count($team_performance); ?> member(s)
            </span>

        </div>


        <?php if (empty($team_performance)): ?>

            <div class="alert alert-light mb-0">
                No team performance data found.
            </div>

        <?php else: ?>


            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>
                                Team Member
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Leads
                            </th>

                            <th>
                                Calls
                            </th>

                            <th>
                                Connected
                            </th>

                            <th>
                                Interested
                            </th>

                            <th>
                                Activities
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($team_performance as $member): ?>

                            <tr>

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $member['name']
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <span class="badge bg-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $member['role_display_name']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo (int) $member['lead_count'];
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo (int) $member['call_count'];
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo (int) $member['connected_count'];
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo (int) $member['interested_count'];
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo (int) $member['activity_count'];
                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>

    </div>


    <!-- Lead Status Summary -->

    <div class="hm-card p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="mb-0">
                Lead Status Summary
            </h5>

        </div>


        <?php if (empty($status_summary)): ?>

            <div class="alert alert-light mb-0">
                No lead status data found.
            </div>

        <?php else: ?>


            <div class="row g-3">


                <?php foreach ($status_summary as $status): ?>

                    <div class="col-md-4 col-lg-3">

                        <div class="border rounded p-3">

                            <div class="hm-muted">

                                <?php
                                echo htmlspecialchars(
                                    $status['status']
                                );
                                ?>

                            </div>


                            <h4 class="mb-0">

                                <?php
                                echo (int) $status['total'];
                                ?>

                            </h4>

                        </div>

                    </div>

                <?php endforeach; ?>


            </div>


        <?php endif; ?>

    </div>

</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>