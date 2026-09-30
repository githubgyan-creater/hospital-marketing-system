 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/permission_check.php';

require_role('manager');
require_permission('tasks.view');

$page_title = 'Task Management';

/*
|--------------------------------------------------------------------------
| Get all tasks
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("
    SELECT
        t.id,
        t.title,
        t.description,
        t.task_type,
        t.assigned_to,
        t.related_lead_id,
        t.due_date,
        t.priority,
        t.status,
        t.remarks,
        t.created_by,
        t.created_at,
        t.updated_at,

        assigned_user.name AS assigned_user_name,
        created_user.name AS created_user_name,

        l.name AS lead_name,
        l.phone AS lead_phone

    FROM tasks t

    LEFT JOIN users assigned_user
        ON t.assigned_to = assigned_user.id

    LEFT JOIN users created_user
        ON t.created_by = created_user.id

    LEFT JOIN leads l
        ON t.related_lead_id = l.id

    ORDER BY
        CASE
            WHEN t.status = 'pending' THEN 1
            WHEN t.status = 'in_progress' THEN 2
            WHEN t.status = 'completed' THEN 3
            ELSE 4
        END,
        t.due_date ASC,
        t.id DESC
");

$tasks = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Current user
|--------------------------------------------------------------------------
*/
$current_user = current_user();

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <!-- =========================================================
         PAGE HEADER
         ========================================================= -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                Task Management
            </h1>

            <p class="hm-muted mb-0">
                Create, assign and manage marketing team tasks.
            </p>

        </div>


        <?php if (has_permission('tasks.create')): ?>

            <a
                href="<?php echo BASE_URL; ?>/manager/tasks/add.php"
                class="btn btn-hm-primary"
            >
                + Add Task
            </a>

        <?php endif; ?>

    </div>


    <!-- =========================================================
         TASK TABLE
         ========================================================= -->

    <div class="hm-card">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Task</th>

                        <th>Assigned To</th>

                        <th>Lead</th>

                        <th>Due Date</th>

                        <th>Priority</th>

                        <th>Status</th>

                        <th class="text-end">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (empty($tasks)): ?>

                    <tr>

                        <td
                            colspan="8"
                            class="text-center py-5"
                        >

                            <div class="hm-muted">
                                No tasks found.
                            </div>

                        </td>

                    </tr>

                <?php else: ?>


                    <?php foreach ($tasks as $task): ?>

                        <tr>


                            <!-- =================================================
                                 TASK ID
                                 ================================================= -->

                            <td>

                                <?php
                                echo (int) $task['id'];
                                ?>

                            </td>


                            <!-- =================================================
                                 TASK
                                 ================================================= -->

                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $task['title'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </div>


                                <?php if (!empty($task['task_type'])): ?>

                                    <div class="small hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $task['task_type'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- =================================================
                                 ASSIGNED TO
                                 ================================================= -->

                            <td>

                                <?php if (!empty($task['assigned_user_name'])): ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $task['assigned_user_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                <?php else: ?>

                                    <span class="hm-muted">
                                        Unassigned
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- =================================================
                                 LEAD
                                 ================================================= -->

                            <td>

                                <?php if (!empty($task['lead_name'])): ?>

                                    <div class="fw-semibold">

                                        <?php
                                        echo htmlspecialchars(
                                            $task['lead_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </div>


                                    <?php if (!empty($task['lead_phone'])): ?>

                                        <div class="small hm-muted">

                                            <?php
                                            echo htmlspecialchars(
                                                $task['lead_phone'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                        </div>

                                    <?php endif; ?>


                                <?php else: ?>

                                    <span class="hm-muted">
                                        No lead
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- =================================================
                                 DUE DATE
                                 ================================================= -->

                            <td>

                                <?php

                                if (!empty($task['due_date'])) {

                                    echo date(
                                        'd M Y',
                                        strtotime($task['due_date'])
                                    );

                                } else {

                                    echo '-';

                                }

                                ?>

                            </td>


                            <!-- =================================================
                                 PRIORITY
                                 ================================================= -->

                            <td>

                                <?php

                                $priority = strtolower(
                                    trim((string) $task['priority'])
                                );

                                ?>


                                <?php if ($priority === 'high'): ?>

                                    <span class="badge bg-danger">
                                        High
                                    </span>

                                <?php elseif ($priority === 'medium'): ?>

                                    <span class="badge bg-warning text-dark">
                                        Medium
                                    </span>

                                <?php elseif ($priority === 'low'): ?>

                                    <span class="badge bg-success">
                                        Low
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        <?php
                                        echo htmlspecialchars(
                                            $task['priority'] ?: 'Normal',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- =================================================
                                 STATUS
                                 ================================================= -->

                            <td>

                                <?php

                                $status = strtolower(
                                    trim((string) $task['status'])
                                );

                                ?>


                                <?php if ($status === 'completed'): ?>

                                    <span class="badge bg-success">
                                        Completed
                                    </span>


                                <?php elseif (
                                    $status === 'in_progress'
                                    || $status === 'in progress'
                                ): ?>

                                    <span class="badge bg-primary">
                                        In Progress
                                    </span>


                                <?php elseif ($status === 'pending'): ?>

                                    <span class="badge bg-warning text-dark">
                                        Pending
                                    </span>


                                <?php elseif ($status === 'cancelled'): ?>

                                    <span class="badge bg-danger">
                                        Cancelled
                                    </span>


                                <?php else: ?>

                                    <span class="badge bg-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $task['status'] ?: 'Unknown',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- =================================================
                                 ACTIONS
                                 ================================================= -->

                            <td class="text-end">

                                <div
                                    class="d-flex gap-1 flex-wrap justify-content-end"
                                >


                                    <!-- VIEW -->
                                    <?php if (has_permission('tasks.view')): ?>

                                        <a
                                            href="<?php echo BASE_URL; ?>/manager/tasks/view.php?id=<?php echo (int) $task['id']; ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            View
                                        </a>

                                    <?php endif; ?>


                                    <!-- EDIT -->
                                    <?php if (has_permission('tasks.edit')): ?>

                                        <a
                                            href="<?php echo BASE_URL; ?>/manager/tasks/edit.php?id=<?php echo (int) $task['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            Edit
                                        </a>

                                    <?php endif; ?>


                                    <!-- DELETE -->
                                    <?php if (has_permission('tasks.delete')): ?>

                                        <form
                                            method="POST"
                                            action="<?php echo BASE_URL; ?>/manager/tasks/delete.php"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this task?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?php echo (int) $task['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    <?php endif; ?>


                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>