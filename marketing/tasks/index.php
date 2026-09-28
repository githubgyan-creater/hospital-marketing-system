<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('marketing');

$user = current_user();

$user_id = (int) $user['id'];

$page_title = 'My Tasks';


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$status_filter = trim(
    $_GET['status'] ?? ''
);

$allowed_statuses = [
    'Pending',
    'In Progress',
    'Completed',
    'Cancelled'
];

if (
    $status_filter !== ''
    && !in_array(
        $status_filter,
        $allowed_statuses,
        true
    )
) {
    $status_filter = '';
}


/*
|--------------------------------------------------------------------------
| Get My Tasks
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        t.id,
        t.title,
        t.description,
        t.task_type,
        t.due_date,
        t.priority,
        t.status,
        t.remarks,
        t.created_at,

        l.id AS lead_id,
        l.name AS lead_name,
        l.phone AS lead_phone,
        l.service_interest

    FROM tasks t

    LEFT JOIN leads l
        ON t.related_lead_id = l.id

    WHERE t.assigned_to = ?
";

$params = [
    $user_id
];


if ($status_filter !== '') {

    $sql .= "
        AND t.status = ?
    ";

    $params[] = $status_filter;
}


$sql .= "
    ORDER BY
        CASE
            WHEN t.status = 'Pending'
                THEN 1

            WHEN t.status = 'In Progress'
                THEN 2

            WHEN t.status = 'Completed'
                THEN 3

            ELSE 4
        END,

        t.due_date ASC,

        t.id DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$tasks = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$summary_stmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total_tasks,

        SUM(
            CASE
                WHEN status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_tasks,

        SUM(
            CASE
                WHEN status = 'In Progress'
                THEN 1
                ELSE 0
            END
        ) AS progress_tasks,

        SUM(
            CASE
                WHEN status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_tasks,

        SUM(
            CASE
                WHEN status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_tasks

    FROM tasks

    WHERE assigned_to = ?
");

$summary_stmt->execute([
    $user_id
]);

$summary = $summary_stmt->fetch();


$total_tasks =
    (int) ($summary['total_tasks'] ?? 0);

$pending_tasks =
    (int) ($summary['pending_tasks'] ?? 0);

$progress_tasks =
    (int) ($summary['progress_tasks'] ?? 0);

$completed_tasks =
    (int) ($summary['completed_tasks'] ?? 0);

$cancelled_tasks =
    (int) ($summary['cancelled_tasks'] ?? 0);


/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function marketing_task_status_class(
    string $status
): string {

    switch ($status) {

        case 'Pending':
            return 'bg-warning text-dark';

        case 'In Progress':
            return 'bg-primary';

        case 'Completed':
            return 'bg-success';

        case 'Cancelled':
            return 'bg-danger';

        default:
            return 'bg-secondary';
    }
}


function marketing_task_priority_class(
    string $priority
): string {

    switch ($priority) {

        case 'High':
            return 'bg-danger';

        case 'Medium':
            return 'bg-warning text-dark';

        case 'Low':
            return 'bg-secondary';

        default:
            return 'bg-secondary';
    }
}


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/header.php';

?>


<div class="container py-4">


    <!-- ========================================================= -->
    <!-- HEADER -->
    <!-- ========================================================= -->

    <div
        class="d-flex flex-wrap justify-content-between align-items-center mb-4"
    >

        <div>

            <!-- <div class="mb-2">

                <a
                    href="<?php echo BASE_URL; ?>/marketing/dashboard.php"
                    class="text-decoration-none"
                >
                    ← My Day
                </a>

            </div> -->

            <h1 class="hm-page-title mb-1">
            Tasks
            </h1>

            <p class="hm-muted mb-0">
                Tasks assigned to you by the marketing manager.
            </p>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- SUMMARY -->
    <!-- ========================================================= -->

    <div class="row g-4 mb-4">


        <div class="col-6 col-md">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    Total
                </div>

                <div class="fs-2 fw-bold">
                    <?php echo $total_tasks; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    Pending
                </div>

                <div class="fs-2 fw-bold text-warning">
                    <?php echo $pending_tasks; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    In Progress
                </div>

                <div class="fs-2 fw-bold text-primary">
                    <?php echo $progress_tasks; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md">

            <div class="hm-card p-4 h-100">

                <div class="hm-card p-0 border-0 shadow-none">

                    <div class="hm-muted small">
                        Completed
                    </div>

                    <div class="fs-2 fw-bold text-success">
                        <?php echo $completed_tasks; ?>
                    </div>

                </div>

            </div>

        </div>


        <div class="col-6 col-md">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    Cancelled
                </div>

                <div class="fs-2 fw-bold text-danger">
                    <?php echo $cancelled_tasks; ?>
                </div>

            </div>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- FILTER -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <form method="GET">

            <div class="row g-3 align-items-end">

                <div class="col-md-4">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All Tasks
                        </option>

                        <?php foreach ($allowed_statuses as $status): ?>

                            <option
                                value="<?php echo htmlspecialchars($status); ?>"
                                <?php
                                echo $status_filter === $status
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars($status);
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-2">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Filter
                    </button>

                </div>


                <div class="col-md-2">

                    <a
                        href="<?php echo BASE_URL; ?>/marketing/tasks/index.php"
                        class="btn btn-outline-secondary w-100"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    <!-- ========================================================= -->
    <!-- TASK LIST -->
    <!-- ========================================================= -->

    <div class="hm-card p-4">

        <div class="mb-3">

            <h4 class="mb-1">
                Assigned Tasks
            </h4>

            <div class="hm-muted small">
                <?php echo count($tasks); ?>
                task(s) found.
            </div>

        </div>


        <?php if (!empty($tasks)): ?>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>Task</th>

                            <th>Type</th>

                            <th>Lead</th>

                            <th>Due</th>

                            <th>Priority</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($tasks as $task): ?>

                        <tr>

                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $task['title']
                                    );
                                    ?>

                                </div>

                                <?php if (!empty($task['description'])): ?>

                                    <div class="small hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            mb_strimwidth(
                                                $task['description'],
                                                0,
                                                70,
                                                '...'
                                            )
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $task['task_type']
                                );
                                ?>

                            </td>


                            <td>

                                <?php if (!empty($task['lead_name'])): ?>

                                    <div class="fw-semibold">

                                        <?php
                                        echo htmlspecialchars(
                                            $task['lead_name']
                                        );
                                        ?>

                                    </div>

                                    <div class="small hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $task['lead_phone']
                                        );
                                        ?>

                                    </div>

                                <?php else: ?>

                                    <span class="hm-muted">
                                        No Lead
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $task['due_date']
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <span
                                    class="badge <?php
                                    echo marketing_task_priority_class(
                                        $task['priority']
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $task['priority']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <span
                                    class="badge <?php
                                    echo marketing_task_status_class(
                                        $task['status']
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $task['status']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/marketing/tasks/view.php?id=<?php echo (int) $task['id']; ?>"
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

        <?php else: ?>

            <div class="alert alert-light border mb-0">

                No tasks are currently assigned to you.

            </div>

        <?php endif; ?>

    </div>


</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>