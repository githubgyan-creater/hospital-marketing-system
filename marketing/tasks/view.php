<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('marketing');

$user = current_user();

$user_id = (int) $user['id'];

$page_title = 'Task Details';


/*
|--------------------------------------------------------------------------
| Get Task ID
|--------------------------------------------------------------------------
*/

$task_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$task_id) {

    exit('Invalid task ID.');
}


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$message = '';

$error = '';


/*
|--------------------------------------------------------------------------
| Handle Status Update
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |----------------------------------------------------------------------
    | Start Task
    |----------------------------------------------------------------------
    */

    if ($action === 'start') {

        $stmt = $pdo->prepare("
            UPDATE tasks

            SET
                status = 'In Progress'

            WHERE id = ?

              AND assigned_to = ?

              AND status = 'Pending'
        ");

        $stmt->execute([
            $task_id,
            $user_id
        ]);

        if ($stmt->rowCount() > 0) {

            $message =
                'Task started successfully.';

        } else {

            $error =
                'Task could not be started.';
        }
    }


    /*
    |----------------------------------------------------------------------
    | Complete Task
    |----------------------------------------------------------------------
    */

    if ($action === 'complete') {

        $remarks = trim(
            $_POST['remarks'] ?? ''
        );

        $stmt = $pdo->prepare("
            UPDATE tasks

            SET
                status = 'Completed',
                remarks = ?

            WHERE id = ?

              AND assigned_to = ?

              AND status IN (
                  'Pending',
                  'In Progress'
              )
        ");

        $stmt->execute([
            $remarks !== ''
                ? $remarks
                : null,
            $task_id,
            $user_id
        ]);

        if ($stmt->rowCount() > 0) {

            $message =
                'Task completed successfully.';

        } else {

            $error =
                'Task could not be completed.';
        }
    }


    /*
    |----------------------------------------------------------------------
    | Add Remarks
    |----------------------------------------------------------------------
    */

    if ($action === 'remarks') {

        $remarks = trim(
            $_POST['remarks'] ?? ''
        );

        $stmt = $pdo->prepare("
            UPDATE tasks

            SET remarks = ?

            WHERE id = ?

              AND assigned_to = ?
        ");

        $stmt->execute([
            $remarks !== ''
                ? $remarks
                : null,
            $task_id,
            $user_id
        ]);

        if ($stmt->rowCount() > 0) {

            $message =
                'Remarks updated successfully.';

        } else {

            $error =
                'Remarks could not be updated.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get Task
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
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
        t.updated_at,

        l.id AS lead_id,
        l.name AS lead_name,
        l.phone AS lead_phone,
        l.email AS lead_email,
        l.service_interest,

        creator.name AS created_by_name

    FROM tasks t

    INNER JOIN users creator
        ON t.created_by = creator.id

    LEFT JOIN leads l
        ON t.related_lead_id = l.id

    WHERE t.id = ?

      AND t.assigned_to = ?

    LIMIT 1
");

$stmt->execute([
    $task_id,
    $user_id
]);

$task = $stmt->fetch();

if (!$task) {

    http_response_code(403);

    exit(
        'Task not found or not assigned to you.'
    );
}


/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function task_view_status_class(
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

function task_view_priority_class(
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

            <div class="mb-2">

                <a
                    href="<?php echo BASE_URL; ?>/marketing/tasks/index.php"
                    class="text-decoration-none"
                >
                    ← My Tasks
                </a>

            </div>

            <h1 class="hm-page-title mb-1">
                Task Details
            </h1>

            <p class="hm-muted mb-0">
                View and update your assigned marketing task.
            </p>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- ALERTS -->
    <!-- ========================================================= -->

    <?php if ($message !== ''): ?>

        <div class="alert alert-success">
            <?php
            echo htmlspecialchars($message);
            ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            <?php
            echo htmlspecialchars($error);
            ?>
        </div>

    <?php endif; ?>


    <!-- ========================================================= -->
    <!-- TASK -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">


        <div
            class="d-flex flex-wrap justify-content-between align-items-start mb-4"
        >

            <div>

                <h3 class="mb-2">

                    <?php
                    echo htmlspecialchars(
                        $task['title']
                    );
                    ?>

                </h3>

                <div class="d-flex flex-wrap gap-2">

                    <span
                        class="badge <?php
                        echo task_view_status_class(
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


                    <span
                        class="badge <?php
                        echo task_view_priority_class(
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

                </div>

            </div>


            <div class="mt-3 mt-md-0">

                <?php if ($task['status'] === 'Pending'): ?>

                    <form method="POST" class="d-inline">

                        <input
                            type="hidden"
                            name="action"
                            value="start"
                        >

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Start Task
                        </button>

                    </form>

                <?php endif; ?>


                <?php if (
                    $task['status'] === 'Pending'
                    || $task['status'] === 'In Progress'
                ): ?>

                    <button
                        type="button"
                        class="btn btn-success"
                        data-bs-toggle="modal"
                        data-bs-target="#completeTaskModal"
                    >
                        Complete Task
                    </button>

                <?php endif; ?>

            </div>

        </div>


        <!-- Task Information -->

        <div class="row g-4">


            <div class="col-md-6">

                <div class="hm-muted small">
                    Task Type
                </div>

                <div class="fw-semibold">
                    <?php
                    echo htmlspecialchars(
                        $task['task_type']
                    );
                    ?>
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
                        strtotime(
                            $task['due_date']
                        )
                    );
                    ?>

                </div>

            </div>


            <div class="col-md-6">

                <div class="hm-muted small">
                    Created By
                </div>

                <div class="fw-semibold">

                    <?php
                    echo htmlspecialchars(
                        $task['created_by_name']
                    );
                    ?>

                </div>

            </div>


            <div class="col-md-6">

                <div class="hm-muted small">
                    Created On
                </div>

                <div class="fw-semibold">

                    <?php
                    echo date(
                        'd M Y, h:i A',
                        strtotime(
                            $task['created_at']
                        )
                    );
                    ?>

                </div>

            </div>


            <div class="col-12">

                <div class="hm-muted small mb-1">
                    Description
                </div>

                <div>

                    <?php if (!empty($task['description'])): ?>

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $task['description']
                            )
                        );
                        ?>

                    <?php else: ?>

                        <span class="hm-muted">
                            No description provided.
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- RELATED LEAD -->
    <!-- ========================================================= -->

    <?php if (!empty($task['lead_id'])): ?>

        <div class="hm-card p-4 mb-4">

            <h4 class="mb-3">
                Related Lead
            </h4>


            <div class="row g-4">


                <div class="col-md-6">

                    <div class="hm-muted small">
                        Lead Name
                    </div>

                    <div class="fw-semibold">

                        <?php
                        echo htmlspecialchars(
                            $task['lead_name']
                        );
                        ?>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="hm-muted small">
                        Phone
                    </div>

                    <div class="fw-semibold">

                        <?php
                        echo htmlspecialchars(
                            $task['lead_phone']
                        );
                        ?>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="hm-muted small">
                        Email
                    </div>

                    <div class="fw-semibold">

                        <?php
                        echo htmlspecialchars(
                            $task['lead_email']
                            ?: '-'
                        );
                        ?>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="hm-muted small">
                        Service
                    </div>

                    <div class="fw-semibold">

                        <?php
                        echo htmlspecialchars(
                            $task['service_interest']
                            ?: '-'
                        );
                        ?>

                    </div>

                </div>


                <div class="col-12">

                    <a
                        href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $task['lead_id']; ?>"
                        class="btn btn-outline-primary"
                    >
                        View Lead
                    </a>

                </div>

            </div>

        </div>

    <?php endif; ?>


    <!-- ========================================================= -->
    <!-- REMARKS -->
    <!-- ========================================================= -->

    <div class="hm-card p-4">

        <h4 class="mb-3">
            Task Remarks
        </h4>


        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="remarks"
            >


            <textarea
                name="remarks"
                class="form-control"
                rows="5"
                placeholder="Add task remarks, visit outcome or important notes..."
            ><?php
                echo htmlspecialchars(
                    $task['remarks'] ?? ''
                );
                ?></textarea>


            <button
                type="submit"
                class="btn btn-primary mt-3"
            >
                Save Remarks
            </button>

        </form>

    </div>


</div>


<!-- ============================================================= -->
<!-- COMPLETE TASK MODAL -->
<!-- ============================================================= -->

<div
    class="modal fade"
    id="completeTaskModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Complete Task
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <form method="POST">

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="action"
                        value="complete"
                    >


                    <label class="form-label">
                        Completion Remarks
                    </label>

                    <textarea
                        name="remarks"
                        class="form-control"
                        rows="5"
                        placeholder="What was done? What was the outcome?"
                    ></textarea>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        Complete Task
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>