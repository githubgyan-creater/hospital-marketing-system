<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permission_check.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');
require_permission('tasks.view');

$page_title = 'Task Details';

$task_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$task_id) {
    die('Invalid task ID.');
}


/*
|--------------------------------------------------------------------------
| Get Task
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        t.*,

        assigned_user.name AS assigned_name,
        assigned_user.email AS assigned_email,

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

    WHERE t.id = ?

    LIMIT 1
");

$stmt->execute([
    $task_id
]);

$task = $stmt->fetch();

if (!$task) {
    die('Task not found.');
}


require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/manager/tasks/index.php"
            class="text-decoration-none"
        >
            ← Back to Tasks
        </a>

        <h1 class="hm-page-title mt-3 mb-1">
            Task Details
        </h1>

        <p class="hm-muted">
            View task assignment and completion information.
        </p>

    </div>


    <div class="hm-card p-4">

        <div class="row g-4">

            <div class="col-md-8">

                <div class="hm-muted small">
                    Task Title
                </div>

                <div class="fs-5 fw-semibold">
                    <?php echo htmlspecialchars($task['title']); ?>
                </div>

            </div>


            <div class="col-md-4">

                <div class="hm-muted small">
                    Status
                </div>

                <span class="badge bg-secondary">
                    <?php echo htmlspecialchars($task['status']); ?>
                </span>

            </div>


            <div class="col-md-6">

                <div class="hm-muted small">
                    Task Type
                </div>

                <div class="fw-semibold">
                    <?php echo htmlspecialchars($task['task_type']); ?>
                </div>

            </div>


            <div class="col-md-6">

                <div class="hm-muted small">
                    Priority
                </div>

                <div class="fw-semibold">
                    <?php echo htmlspecialchars($task['priority']); ?>
                </div>

            </div>


            <div class="col-md-6">

                <div class="hm-muted small">
                    Assigned To
                </div>

                <div class="fw-semibold">
                    <?php echo htmlspecialchars($task['assigned_name']); ?>
                </div>

                <div class="small hm-muted">
                    <?php echo htmlspecialchars($task['assigned_email']); ?>
                </div>

            </div>


            <div class="col-md-6">

                <div class="hm-muted small">
                    Due Date
                </div>

                <div class="fw-semibold">
                    <?php
                    echo date(
                        'd M Y, h:i A',
                        strtotime($task['due_date'])
                    );
                    ?>
                </div>

            </div>


            <div class="col-12">

                <div class="hm-muted small">
                    Description
                </div>

                <div>
                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $task['description'] ?? 'No description.'
                        )
                    );
                    ?>
                </div>

            </div>


            <div class="col-md-6">

                <div class="hm-muted small">
                    Related Lead
                </div>

                <?php if (!empty($task['lead_name'])): ?>

                    <div class="fw-semibold">
                        <?php echo htmlspecialchars($task['lead_name']); ?>
                    </div>

                    <div class="small hm-muted">
                        <?php echo htmlspecialchars($task['lead_phone'] ?? ''); ?>
                    </div>

                <?php else: ?>

                    <span class="hm-muted">
                        No related lead
                    </span>

                <?php endif; ?>

            </div>


            <div class="col-md-6">

                <div class="hm-muted small">
                    Created By
                </div>

                <div class="fw-semibold">
                    <?php echo htmlspecialchars($task['created_by_name']); ?>
                </div>

            </div>


            <div class="col-12">

                <div class="hm-muted small">
                    Remarks
                </div>

                <div>
                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $task['remarks'] ?? 'No remarks.'
                        )
                    );
                    ?>
                </div>

            </div>


            <div class="col-12">

                <hr>

                <a
                    href="<?php echo BASE_URL; ?>/manager/tasks/edit.php?id=<?php echo (int) $task_id; ?>"
                    class="btn btn-hm-primary"
                >
                    Edit Task
                </a>

                <form
                    method="POST"
                    action="<?php echo BASE_URL; ?>/manager/tasks/delete.php"
                    class="d-inline"
                    onsubmit="return confirm('Delete this task?');"
                >

                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo (int) $task_id; ?>"
                    >

                    <button
                        type="submit"
                        class="btn btn-outline-danger"
                    >
                        Delete Task
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<?php

require_once __DIR__ . '/../../includes/footer.php';

?>