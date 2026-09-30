 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/permission_check.php';

require_role('manager');
require_permission('tasks.edit');

$page_title = 'Edit Task';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: ' . BASE_URL . '/manager/tasks/');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Task
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM tasks
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$task = $stmt->fetch();

if (!$task) {
    header('Location: ' . BASE_URL . '/manager/tasks/');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Marketing Staff
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        r.name AS role_name
    FROM users u
    LEFT JOIN roles r
        ON u.role_id = r.id
    WHERE u.status = 'active'
      AND r.name IN ('manager', 'telecaller', 'marketing')
    ORDER BY u.name ASC
");

$staff = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Get Leads
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        name,
        phone
    FROM leads
    ORDER BY name ASC
");

$leads = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/

$title = $task['title'] ?? '';
$description = $task['description'] ?? '';
$task_type = $task['task_type'] ?? '';
$assigned_to = $task['assigned_to'] ?? '';
$related_lead_id = $task['related_lead_id'] ?? '';
$due_date = $task['due_date'] ?? '';
$priority = $task['priority'] ?? 'medium';
$status = $task['status'] ?? 'pending';
$remarks = $task['remarks'] ?? '';

$errors = [];

/*
|--------------------------------------------------------------------------
| Update Task
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $task_type = trim($_POST['task_type'] ?? '');
    $assigned_to = !empty($_POST['assigned_to'])
        ? (int) $_POST['assigned_to']
        : null;

    $related_lead_id = !empty($_POST['related_lead_id'])
        ? (int) $_POST['related_lead_id']
        : null;

    $due_date = trim($_POST['due_date'] ?? '');
    $priority = trim($_POST['priority'] ?? 'medium');
    $status = trim($_POST['status'] ?? 'pending');
    $remarks = trim($_POST['remarks'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($title === '') {
        $errors[] = 'Task title is required.';
    }

    $allowed_priorities = [
        'low',
        'medium',
        'high'
    ];

    if (!in_array($priority, $allowed_priorities, true)) {
        $errors[] = 'Invalid priority selected.';
    }

    $allowed_statuses = [
        'pending',
        'in_progress',
        'completed',
        'cancelled'
    ];

    if (!in_array($status, $allowed_statuses, true)) {
        $errors[] = 'Invalid task status selected.';
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Assigned User
    |--------------------------------------------------------------------------
    */

    if ($assigned_to !== null) {

        $stmt = $pdo->prepare("
            SELECT u.id
            FROM users u
            LEFT JOIN roles r
                ON u.role_id = r.id
            WHERE u.id = ?
              AND u.status = 'active'
              AND r.name IN ('manager', 'telecaller', 'marketing')
            LIMIT 1
        ");

        $stmt->execute([$assigned_to]);

        if (!$stmt->fetch()) {
            $errors[] = 'Selected staff member is invalid.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Lead
    |--------------------------------------------------------------------------
    */

    if ($related_lead_id !== null) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM leads
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$related_lead_id]);

        if (!$stmt->fetch()) {
            $errors[] = 'Selected lead is invalid.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Database
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare("
                UPDATE tasks
                SET
                    title = ?,
                    description = ?,
                    task_type = ?,
                    assigned_to = ?,
                    related_lead_id = ?,
                    due_date = ?,
                    priority = ?,
                    status = ?,
                    remarks = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $title,
                $description !== '' ? $description : null,
                $task_type !== '' ? $task_type : null,
                $assigned_to,
                $related_lead_id,
                $due_date !== '' ? $due_date : null,
                $priority,
                $status,
                $remarks !== '' ? $remarks : null,
                $id
            ]);

            header(
                'Location: ' .
                BASE_URL .
                '/manager/tasks/view.php?id=' .
                $id .
                '&updated=1'
            );

            exit;

        } catch (PDOException $e) {

            $errors[] = 'Unable to update the task. Please try again.';
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                Edit Task
            </h1>

            <p class="hm-muted mb-0">
                Update task details, assignment and status.
            </p>

        </div>

        <div>

            <a
                href="<?php echo BASE_URL; ?>/manager/tasks/view.php?id=<?php echo (int) $id; ?>"
                class="btn btn-outline-primary"
            >
                View Task
            </a>

            <a
                href="<?php echo BASE_URL; ?>/manager/tasks/"
                class="btn btn-outline-secondary"
            >
                Back to Tasks
            </a>

        </div>

    </div>


    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <ul class="mb-0">

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?php echo htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <div class="hm-card">

        <div class="card-body p-4">

            <form method="POST">

                <div class="row g-3">


                    <!-- Task Title -->

                    <div class="col-md-8">

                        <label
                            for="title"
                            class="form-label"
                        >
                            Task Title
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="form-control"
                            value="<?php echo htmlspecialchars(
                                $title,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            required
                        >

                    </div>


                    <!-- Task Type -->

                    <div class="col-md-4">

                        <label
                            for="task_type"
                            class="form-label"
                        >
                            Task Type
                        </label>

                        <input
                            type="text"
                            id="task_type"
                            name="task_type"
                            class="form-control"
                            placeholder="Example: Follow-up"
                            value="<?php echo htmlspecialchars(
                                $task_type,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                        >

                    </div>


                    <!-- Description -->

                    <div class="col-12">

                        <label
                            for="description"
                            class="form-label"
                        >
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="form-control"
                        ><?php echo htmlspecialchars(
                            $description,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?></textarea>

                    </div>


                    <!-- Assigned To -->

                    <div class="col-md-6">

                        <label
                            for="assigned_to"
                            class="form-label"
                        >
                            Assign To
                        </label>

                        <select
                            id="assigned_to"
                            name="assigned_to"
                            class="form-select"
                        >

                            <option value="">
                                -- Unassigned --
                            </option>

                            <?php foreach ($staff as $member): ?>

                                <option
                                    value="<?php echo (int) $member['id']; ?>"
                                    <?php echo (
                                        (string) $assigned_to ===
                                        (string) $member['id']
                                    ) ? 'selected' : ''; ?>
                                >

                                    <?php echo htmlspecialchars(
                                        $member['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                    <?php if (!empty($member['role_name'])): ?>

                                        -
                                        <?php echo htmlspecialchars(
                                            ucfirst($member['role_name']),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- Related Lead -->

                    <div class="col-md-6">

                        <label
                            for="related_lead_id"
                            class="form-label"
                        >
                            Related Lead
                        </label>

                        <select
                            id="related_lead_id"
                            name="related_lead_id"
                            class="form-select"
                        >

                            <option value="">
                                -- No Lead --
                            </option>

                            <?php foreach ($leads as $lead): ?>

                                <option
                                    value="<?php echo (int) $lead['id']; ?>"
                                    <?php echo (
                                        (string) $related_lead_id ===
                                        (string) $lead['id']
                                    ) ? 'selected' : ''; ?>
                                >

                                    <?php echo htmlspecialchars(
                                        $lead['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                    <?php if (!empty($lead['phone'])): ?>

                                        -
                                        <?php echo htmlspecialchars(
                                            $lead['phone'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- Due Date -->

                    <div class="col-md-4">

                        <label
                            for="due_date"
                            class="form-label"
                        >
                            Due Date
                        </label>

                        <input
                            type="date"
                            id="due_date"
                            name="due_date"
                            class="form-control"
                            value="<?php echo htmlspecialchars(
                                $due_date,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                        >

                    </div>


                    <!-- Priority -->

                    <div class="col-md-4">

                        <label
                            for="priority"
                            class="form-label"
                        >
                            Priority
                        </label>

                        <select
                            id="priority"
                            name="priority"
                            class="form-select"
                        >

                            <option
                                value="low"
                                <?php echo $priority === 'low'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Low
                            </option>

                            <option
                                value="medium"
                                <?php echo $priority === 'medium'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Medium
                            </option>

                            <option
                                value="high"
                                <?php echo $priority === 'high'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                High
                            </option>

                        </select>

                    </div>


                    <!-- Status -->

                    <div class="col-md-4">

                        <label
                            for="status"
                            class="form-label"
                        >
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="form-select"
                        >

                            <option
                                value="pending"
                                <?php echo $status === 'pending'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Pending
                            </option>

                            <option
                                value="in_progress"
                                <?php echo $status === 'in_progress'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                In Progress
                            </option>

                            <option
                                value="completed"
                                <?php echo $status === 'completed'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Completed
                            </option>

                            <option
                                value="cancelled"
                                <?php echo $status === 'cancelled'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Cancelled
                            </option>

                        </select>

                    </div>


                    <!-- Remarks -->

                    <div class="col-12">

                        <label
                            for="remarks"
                            class="form-label"
                        >
                            Remarks
                        </label>

                        <textarea
                            id="remarks"
                            name="remarks"
                            rows="3"
                            class="form-control"
                        ><?php echo htmlspecialchars(
                            $remarks,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?></textarea>

                    </div>


                    <!-- Buttons -->

                    <div class="col-12">

                        <hr>

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-hm-primary"
                            >
                                Update Task
                            </button>

                            <a
                                href="<?php echo BASE_URL; ?>/manager/tasks/"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>