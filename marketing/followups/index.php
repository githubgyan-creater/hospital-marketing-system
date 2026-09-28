<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

require_login();

$user = current_user();

if (!$user || $user['role'] !== 'marketing') {
    http_response_code(403);
    exit('Access denied.');
}

$user_id = (int) $user['id'];


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$view = $_GET['view'] ?? 'all';

$allowed_views = [
    'all',
    'overdue',
    'today',
    'upcoming'
];

if (!in_array($view, $allowed_views, true)) {
    $view = 'all';
}


/*
|--------------------------------------------------------------------------
| HANDLE COMPLETE FOLLOW-UP
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    $lead_id = isset($_POST['lead_id'])
        ? (int) $_POST['lead_id']
        : 0;

    if ($action === 'complete' && $lead_id > 0) {

        /*
        |--------------------------------------------------------------------------
        | Verify lead belongs to current marketing executive
        |--------------------------------------------------------------------------
        */

        $check_stmt = $pdo->prepare("
            SELECT
                id,
                name,
                status,
                next_action_type,
                next_action_at
            FROM leads
            WHERE id = ?
              AND assigned_to = ?
            LIMIT 1
        ");

        $check_stmt->execute([
            $lead_id,
            $user_id
        ]);

        $lead = $check_stmt->fetch();

        if (!$lead) {
            exit('Lead not found or not assigned to you.');
        }


        /*
        |--------------------------------------------------------------------------
        | Clear next action
        |--------------------------------------------------------------------------
        */

        $update_stmt = $pdo->prepare("
            UPDATE leads
            SET
                next_action_type = NULL,
                next_action_at = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
              AND assigned_to = ?
        ");

        $update_stmt->execute([
            $lead_id,
            $user_id
        ]);


        /*
        |--------------------------------------------------------------------------
        | Create activity
        |--------------------------------------------------------------------------
        */

        $description =
            'Marketing follow-up completed';

        if (!empty($lead['next_action_type'])) {

            $description .=
                '. Previous action: ' .
                $lead['next_action_type'];
        }


        $activity_stmt = $pdo->prepare("
            INSERT INTO lead_activities
            (
                lead_id,
                user_id,
                activity_type,
                description,
                activity_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                CURRENT_TIMESTAMP
            )
        ");

        $activity_stmt->execute([
            $lead_id,
            $user_id,
            'Marketing Follow-up Completed',
            $description
        ]);


        header(
            'Location: ' .
            BASE_URL .
            '/marketing/followups/index.php?view=' .
            urlencode($view) .
            '&completed=1'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| BUILD WHERE
|--------------------------------------------------------------------------
*/

$where = [
    'l.assigned_to = ?',
    'l.next_action_at IS NOT NULL',
    "l.status NOT IN ('Converted', 'Lost')"
];

$params = [
    $user_id
];


if ($view === 'overdue') {

    $where[] = 'l.next_action_at < NOW()';

}


if ($view === 'today') {

    $where[] = 'DATE(l.next_action_at) = CURDATE()';

}


if ($view === 'upcoming') {

    $where[] = 'l.next_action_at > NOW()';

}


/*
|--------------------------------------------------------------------------
| LOAD FOLLOW-UPS
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

        ms.name AS source_name

    FROM leads l

    LEFT JOIN marketing_sources ms
        ON l.source_id = ms.id

    WHERE "
    . implode(' AND ', $where)
    . "

    ORDER BY
        CASE
            WHEN l.next_action_at < NOW()
            THEN 0
            WHEN DATE(l.next_action_at) = CURDATE()
            THEN 1
            ELSE 2
        END,

        l.next_action_at ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$followups = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$count_stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_followups,

        SUM(
            CASE
                WHEN next_action_at < NOW()
                THEN 1
                ELSE 0
            END
        ) AS overdue_followups,

        SUM(
            CASE
                WHEN DATE(next_action_at) = CURDATE()
                THEN 1
                ELSE 0
            END
        ) AS today_followups,

        SUM(
            CASE
                WHEN next_action_at > NOW()
                THEN 1
                ELSE 0
            END
        ) AS upcoming_followups

    FROM leads

    WHERE assigned_to = ?
      AND next_action_at IS NOT NULL
      AND status NOT IN ('Converted', 'Lost')
");

$count_stmt->execute([
    $user_id
]);

$counts = $count_stmt->fetch();


?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<div class="container py-4">


    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                My Follow-ups
            </h2>

            <p class="text-muted mb-0">
                Manage your pending marketing follow-up actions.
            </p>

        </div>

    </div>


    <!-- SUCCESS -->

    <?php if (isset($_GET['completed'])): ?>

        <div class="alert alert-success">
            Follow-up completed successfully.
        </div>

    <?php endif; ?>


    <!-- SUMMARY -->

    <div class="row g-4 mb-4">


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Follow-ups
                    </h6>

                    <h3 class="mb-0">
                        <?php
                        echo (int) (
                            $counts['total_followups'] ?? 0
                        );
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Overdue
                    </h6>

                    <h3 class="mb-0 text-danger">
                        <?php
                        echo (int) (
                            $counts['overdue_followups'] ?? 0
                        );
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Today
                    </h6>

                    <h3 class="mb-0 text-warning">
                        <?php
                        echo (int) (
                            $counts['today_followups'] ?? 0
                        );
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Upcoming
                    </h6>

                    <h3 class="mb-0 text-success">
                        <?php
                        echo (int) (
                            $counts['upcoming_followups'] ?? 0
                        );
                        ?>
                    </h3>

                </div>

            </div>

        </div>


    </div>


    <!-- FILTER BUTTONS -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <div class="d-flex flex-wrap gap-2">

                <a
                    href="<?php echo BASE_URL; ?>/marketing/followups/index.php?view=all"
                    class="btn <?php echo $view === 'all'
                        ? 'btn-primary'
                        : 'btn-outline-primary'; ?>"
                >
                    All
                </a>


                <a
                    href="<?php echo BASE_URL; ?>/marketing/followups/index.php?view=overdue"
                    class="btn <?php echo $view === 'overdue'
                        ? 'btn-danger'
                        : 'btn-outline-danger'; ?>"
                >
                    Overdue
                </a>


                <a
                    href="<?php echo BASE_URL; ?>/marketing/followups/index.php?view=today"
                    class="btn <?php echo $view === 'today'
                        ? 'btn-warning'
                        : 'btn-outline-warning'; ?>"
                >
                    Today
                </a>


                <a
                    href="<?php echo BASE_URL; ?>/marketing/followups/index.php?view=upcoming"
                    class="btn <?php echo $view === 'upcoming'
                        ? 'btn-success'
                        : 'btn-outline-success'; ?>"
                >
                    Upcoming
                </a>

            </div>

        </div>

    </div>


    <!-- TABLE -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Follow-up Schedule
            </h5>

        </div>


        <div class="card-body p-0">


            <?php if (empty($followups)): ?>

                <div class="p-4 text-muted">
                    No follow-ups found.
                </div>


            <?php else: ?>


                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

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
                                    Action
                                </th>

                                <th>
                                    Due
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


                            <?php foreach (
                                $followups as $lead
                            ): ?>


                                <?php

                                $due_time = strtotime(
                                    $lead['next_action_at']
                                );

                                $is_overdue =
                                    $due_time < time();

                                $is_today =
                                    date(
                                        'Y-m-d',
                                        $due_time
                                    ) === date('Y-m-d');

                                ?>


                                <tr>


                                    <!-- LEAD -->

                                    <td>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $lead['name']
                                            );
                                            ?>

                                        </strong>

                                        <div class="small text-muted">

                                            <?php
                                            echo htmlspecialchars(
                                                $lead['email'] ?: '-'
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <!-- PHONE -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['phone']
                                        );
                                        ?>

                                    </td>


                                    <!-- SERVICE -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $lead[
                                                'service_interest'
                                            ] ?: '-'
                                        );
                                        ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $lead[
                                                'next_action_type'
                                            ] ?: 'Follow-up'
                                        );
                                        ?>

                                    </td>


                                    <!-- DUE -->

                                    <td>

                                        <span
                                            class="<?php
                                            echo $is_overdue
                                                ? 'text-danger fw-bold'
                                                : (
                                                    $is_today
                                                        ? 'text-warning fw-bold'
                                                        : 'text-muted'
                                                );
                                            ?>"
                                        >

                                            <?php
                                            echo date(
                                                'd M Y, h:i A',
                                                $due_time
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php if ($is_overdue): ?>

                                            <span class="badge bg-danger">
                                                Overdue
                                            </span>

                                        <?php elseif ($is_today): ?>

                                            <span class="badge bg-warning text-dark">
                                                Today
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-success">
                                                Upcoming
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACTION BUTTONS -->

                                    <td>

                                        <div class="d-flex gap-1">


                                            <a
                                                href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                View
                                            </a>


                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Mark this follow-up as completed?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="complete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="lead_id"
                                                    value="<?php
                                                    echo (int) $lead['id'];
                                                    ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-success"
                                                >
                                                    Complete
                                                </button>

                                            </form>


                                        </div>

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


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>