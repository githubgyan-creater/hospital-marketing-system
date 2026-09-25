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
$period = trim($_GET['period'] ?? 'overdue');


$allowed_periods = [
    'overdue',
    'today',
    'upcoming'
];

if (!in_array($period, $allowed_periods, true)) {
    $period = 'overdue';
}


/*
|--------------------------------------------------------------------------
| Get Marketing Team
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

$stmt = $pdo->query("
    SELECT
        SUM(
            next_action_at IS NOT NULL
            AND next_action_at < NOW()
        ) AS overdue_count,

        SUM(
            next_action_at IS NOT NULL
            AND DATE(next_action_at) = CURDATE()
        ) AS today_count,

        SUM(
            next_action_at IS NOT NULL
            AND next_action_at > NOW()
            AND DATE(next_action_at) > CURDATE()
        ) AS upcoming_count

    FROM leads
");

$summary = $stmt->fetch();

$overdue_count = (int) ($summary['overdue_count'] ?? 0);
$today_count = (int) ($summary['today_count'] ?? 0);
$upcoming_count = (int) ($summary['upcoming_count'] ?? 0);


/*
|--------------------------------------------------------------------------
| Build Follow-up Query
|--------------------------------------------------------------------------
*/

$sql = "
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

        u.name AS assigned_name,

        r.name AS assigned_role

    FROM leads l

    LEFT JOIN users u
        ON l.assigned_to = u.id

    LEFT JOIN roles r
        ON u.role_id = r.id

    WHERE l.next_action_at IS NOT NULL
";

$params = [];


/*
|--------------------------------------------------------------------------
| Period Filter
|--------------------------------------------------------------------------
*/

if ($period === 'overdue') {

    $sql .= "
        AND l.next_action_at < NOW()
    ";

} elseif ($period === 'today') {

    $sql .= "
        AND DATE(l.next_action_at) = CURDATE()
    ";

} elseif ($period === 'upcoming') {

    $sql .= "
        AND l.next_action_at > NOW()
        AND DATE(l.next_action_at) > CURDATE()
    ";
}


/*
|--------------------------------------------------------------------------
| Search Filter
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            l.name LIKE ?
            OR l.phone LIKE ?
            OR l.email LIKE ?
        )
    ";

    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| Staff Filter
|--------------------------------------------------------------------------
*/

if ($staff_filter > 0) {

    $sql .= "
        AND l.assigned_to = ?
    ";

    $params[] = $staff_filter;
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

if ($period === 'overdue') {

    $sql .= "
        ORDER BY l.next_action_at ASC
    ";

} else {

    $sql .= "
        ORDER BY l.next_action_at ASC
    ";
}


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$followups = $stmt->fetchAll();


$page_title = 'Follow-up Control Center';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Follow-up Control Center
            </h2>

            <p class="hm-muted mb-0">
                Monitor follow-ups across the marketing team.
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


        <!-- Overdue -->

        <div class="col-md-4">

            <a
                href="<?php echo BASE_URL; ?>/manager/followups/?period=overdue"
                class="text-decoration-none"
            >

                <div class="hm-card p-4 h-100">

                    <div class="hm-muted">
                        Overdue Follow-ups
                    </div>

                    <h2 class="mb-0 text-danger">
                        <?php echo $overdue_count; ?>
                    </h2>

                </div>

            </a>

        </div>


        <!-- Today -->

        <div class="col-md-4">

            <a
                href="<?php echo BASE_URL; ?>/manager/followups/?period=today"
                class="text-decoration-none"
            >

                <div class="hm-card p-4 h-100">

                    <div class="hm-muted">
                        Today's Follow-ups
                    </div>

                    <h2 class="mb-0 hm-gold">
                        <?php echo $today_count; ?>
                    </h2>

                </div>

            </a>

        </div>


        <!-- Upcoming -->

        <div class="col-md-4">

            <a
                href="<?php echo BASE_URL; ?>/manager/followups/?period=upcoming"
                class="text-decoration-none"
            >

                <div class="hm-card p-4 h-100">

                    <div class="hm-muted">
                        Upcoming Follow-ups
                    </div>

                    <h2 class="mb-0">
                        <?php echo $upcoming_count; ?>
                    </h2>

                </div>

            </a>

        </div>

    </div>


    <!-- Filters -->

    <div class="hm-card p-4 mb-4">

        <form method="GET">

            <div class="row g-3 align-items-end">


                <!-- Search -->

                <div class="col-lg-4">

                    <label
                        for="search"
                        class="form-label"
                    >
                        Search Lead
                    </label>

                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        placeholder="Name, phone or email"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <!-- Staff -->

                <div class="col-lg-3">

                    <label
                        for="staff_id"
                        class="form-label"
                    >
                        Assigned Staff
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


                <!-- Period -->

                <div class="col-lg-3">

                    <label
                        for="period"
                        class="form-label"
                    >
                        Follow-up Period
                    </label>

                    <select
                        name="period"
                        id="period"
                        class="form-select"
                    >

                        <option
                            value="overdue"
                            <?php
                            echo $period === 'overdue'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Overdue
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
                            value="upcoming"
                            <?php
                            echo $period === 'upcoming'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Upcoming
                        </option>

                    </select>

                </div>


                <!-- Buttons -->

                <div class="col-lg-2 d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Filter
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/manager/followups/"
                        class="btn btn-outline-secondary"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    <!-- Follow-up List -->

    <div class="hm-card p-4">


        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="mb-0">

                <?php

                if ($period === 'overdue') {

                    echo 'Overdue Follow-ups';

                } elseif ($period === 'today') {

                    echo "Today's Follow-ups";

                } else {

                    echo 'Upcoming Follow-ups';

                }

                ?>

            </h5>


            <span class="hm-muted">

                <?php echo count($followups); ?> record(s)

            </span>

        </div>


        <?php if (empty($followups)): ?>

            <div class="alert alert-light mb-0">

                No follow-ups found for the selected filters.

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
                                Contact
                            </th>

                            <th>
                                Service
                            </th>

                            <th>
                                Assigned To
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Next Action
                            </th>

                            <th>
                                Due
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($followups as $lead): ?>

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

                                    <?php
                                    echo htmlspecialchars(
                                        $lead['assigned_name']
                                        ?: 'Unassigned'
                                    );
                                    ?>

                                    <?php if (!empty($lead['assigned_role'])): ?>

                                        <small class="d-block hm-muted">

                                            <?php
                                            echo htmlspecialchars(
                                                $lead['assigned_role']
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

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
                                        $lead['next_action_type']
                                        ?: 'Follow-up'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php

                                    $due_timestamp =
                                        strtotime(
                                            $lead['next_action_at']
                                        );

                                    ?>

                                    <div
                                        class="<?php
                                        echo $due_timestamp < time()
                                            ? 'text-danger'
                                            : '';
                                        ?>"
                                    >

                                        <?php
                                        echo date(
                                            'd M Y, h:i A',
                                            $due_timestamp
                                        );
                                        ?>

                                    </div>

                                </td>


                                <td>

                                    <a
                                        href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        View
                                    </a>


                                    <a
                                        href="<?php echo BASE_URL; ?>/leads/edit.php?id=<?php echo (int) $lead['id']; ?>"
                                        class="btn btn-sm btn-outline-primary mt-1"
                                    >
                                        Update
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