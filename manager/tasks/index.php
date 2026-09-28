<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();

$page_title = 'Team Tasks';


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET['search'] ?? ''
);

$status_filter = trim(
    $_GET['status'] ?? ''
);

$priority_filter = trim(
    $_GET['priority'] ?? ''
);

$assigned_filter = !empty($_GET['assigned_to'])
    ? (int) $_GET['assigned_to']
    : 0;


/*
|--------------------------------------------------------------------------
| Allowed Values
|--------------------------------------------------------------------------
*/

$statuses = [
    'Pending',
    'In Progress',
    'Completed',
    'Cancelled'
];

$priorities = [
    'Low',
    'Medium',
    'High'
];


/*
|--------------------------------------------------------------------------
| Get Marketing Team
|--------------------------------------------------------------------------
*/

$staff_stmt = $pdo->query("
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

      AND LOWER(r.name) IN (
          'telecaller',
          'marketing',
          'marketing executive'
      )

    ORDER BY u.name ASC
");

$staff = $staff_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Build Task Query
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

        assigned_user.name AS assigned_name,

        created_user.name AS created_by_name,

        l.name AS lead_name,
        l.phone AS lead_phone

    FROM tasks t

    INNER JOIN users assigned_user
        ON t.assigned_to = assigned_user.id

    INNER JOIN users created_user
        ON t.created_by = created_user.id

    LEFT JOIN leads l
        ON t.related_lead_id = l.id

    WHERE 1 = 1
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
            t.title LIKE :search
            OR t.task_type LIKE :search
            OR assigned_user.name LIKE :search
            OR l.name LIKE :search
        )
    ";

    $params['search'] =
        '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if (
    $status_filter !== ''
    && in_array(
        $status_filter,
        $statuses,
        true
    )
) {

    $sql .= "
        AND t.status = :status
    ";

    $params['status'] =
        $status_filter;
}


/*
|--------------------------------------------------------------------------
| Priority Filter
|--------------------------------------------------------------------------
*/

if (
    $priority_filter !== ''
    && in_array(
        $priority_filter,
        $priorities,
        true
    )
) {

    $sql .= "
        AND t.priority = :priority
    ";

    $params['priority'] =
        $priority_filter;
}


/*
|--------------------------------------------------------------------------
| Staff Filter
|--------------------------------------------------------------------------
*/

if ($assigned_filter > 0) {

    $sql .= "
        AND t.assigned_to = :assigned_to
    ";

    $params['assigned_to'] =
        $assigned_filter;
}


/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Execute
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$tasks = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Summary Counts
|--------------------------------------------------------------------------
*/

$summary_stmt = $pdo->query("
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
");

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

function task_status_class(
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


function task_priority_class(
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
    <!-- PAGE HEADER -->
    <!-- ========================================================= -->

    <div
        class="d-flex flex-wrap justify-content-between align-items-center mb-4"
    >

        <div>

            <div class="mb-2">

                <a
                    href="<?php echo BASE_URL; ?>/manager/dashboard.php"
                    class="text-decoration-none"
                >
                    ← Manager Dashboard
                </a>

            </div>

            <h1 class="hm-page-title mb-1">
                Team Tasks
            </h1>

            <p class="hm-muted mb-0">
                Create, assign and monitor marketing team tasks.
            </p>

        </div>


        <div class="mt-3 mt-md-0">

            <a
                href="<?php echo BASE_URL; ?>/manager/tasks/add.php"
                class="btn btn-primary"
            >
                + Add Task
            </a>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- SUMMARY CARDS -->
    <!-- ========================================================= -->

    <div class="row g-4 mb-4">


        <div class="col-6 col-md-4 col-lg">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    Total Tasks
                </div>

                <div class="fs-2 fw-bold">
                    <?php echo $total_tasks; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-4 col-lg">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    Pending
                </div>

                <div class="fs-2 fw-bold text-warning">
                    <?php echo $pending_tasks; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-4 col-lg">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    In Progress
                </div>

                <div class="fs-2 fw-bold text-primary">
                    <?php echo $progress_tasks; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-4 col-lg">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    Completed
                </div>

                <div class="fs-2 fw-bold text-success">
                    <?php echo $completed_tasks; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-4 col-lg">

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
    <!-- FILTERS -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <h5 class="mb-3">
            Search & Filter
        </h5>


        <form method="GET">

            <div class="row g-3 align-items-end">


                <div class="col-md-4">

                    <label class="form-label">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Task, type, staff or lead"
                    >

                </div>


                <div class="col-md-2">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <?php foreach ($statuses as $task_status): ?>

                            <option
                                value="<?php echo htmlspecialchars($task_status); ?>"
                                <?php
                                echo $status_filter === $task_status
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $task_status
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-2">

                    <label class="form-label">
                        Priority
                    </label>

                    <select
                        name="priority"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <?php foreach ($priorities as $task_priority): ?>

                            <option
                                value="<?php echo htmlspecialchars($task_priority); ?>"
                                <?php
                                echo $priority_filter === $task_priority
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $task_priority
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-3">

                    <label class="form-label">
                        Assigned To
                    </label>

                    <select
                        name="assigned_to"
                        class="form-select"
                    >

                        <option value="">
                            All Staff
                        </option>


                        <?php foreach ($staff as $member): ?>

                            <option
                                value="<?php echo (int) $member['id']; ?>"
                                <?php
                                echo $assigned_filter ===
                                    (int) $member['id']
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
                                    $member['display_name']
                                    ?? $member['role_name']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-1">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Go
                    </button>

                </div>

            </div>


            <div class="mt-3">

                <a
                    href="<?php echo BASE_URL; ?>/manager/tasks/index.php"
                    class="btn btn-outline-secondary btn-sm"
                >
                    Reset Filters
                </a>

            </div>

        </form>

    </div>


    <!-- ========================================================= -->
    <!-- TASK TABLE -->
    <!-- ========================================================= -->

    <div class="hm-card p-4">

        <div
            class="d-flex justify-content-between align-items-center mb-3"
        >

            <div>

                <h4 class="mb-1">
                    Task List
                </h4>

                <div class="hm-muted small">
                    <?php echo count($tasks); ?>
                    task(s) found.
                </div>

            </div>

        </div>


        <?php if (!empty($tasks)): ?>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Task</th>

                            <th>Type</th>

                            <th>Assigned To</th>

                            <th>Lead</th>

                            <th>Due Date</th>

                            <th>Priority</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($tasks as $task): ?>

                        <tr>


                            <td>

                                <?php
                                echo (int) $task['id'];
                                ?>

                            </td>


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

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $task['assigned_name']
                                    );
                                    ?>

                                </div>

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
                                    echo task_priority_class(
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
                                    echo task_status_class(
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

                                <div class="d-flex gap-1">

                                    <a
                                        href="<?php echo BASE_URL; ?>/manager/tasks/view.php?id=<?php echo (int) $task['id']; ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="<?php echo BASE_URL; ?>/manager/tasks/edit.php?id=<?php echo (int) $task['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        Edit
                                    </a>

                                </div>

                            </td>


                        </tr>

                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="alert alert-light border mb-0">

                No tasks found.

                <a
                    href="<?php echo BASE_URL; ?>/manager/tasks/add.php"
                    class="alert-link"
                >
                    Create the first task.
                </a>

            </div>

        <?php endif; ?>


    </div>


</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>