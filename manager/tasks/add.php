<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();

$page_title = 'Add Task';

$errors = [];


/*
|--------------------------------------------------------------------------
| Allowed Task Types
|--------------------------------------------------------------------------
*/

$task_types = [
    'Lead Follow-up',
    'Doctor Visit',
    'Clinic Visit',
    'Corporate Visit',
    'Field Visit',
    'Event Activity',
    'Promotional Activity',
    'Other'
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
| Get Leads
|--------------------------------------------------------------------------
*/

$lead_stmt = $pdo->query("
    SELECT
        id,
        name,
        phone,
        service_interest,
        status

    FROM leads

    WHERE status NOT IN (
        'Converted',
        'Lost'
    )

    ORDER BY created_at DESC

    LIMIT 500
");

$leads = $lead_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$title = '';

$description = '';

$task_type = '';

$assigned_to = '';

$related_lead_id = '';

$due_date = '';

$priority = 'Medium';


/*
|--------------------------------------------------------------------------
| Handle Form
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |----------------------------------------------------------------------
    | Get Values
    |----------------------------------------------------------------------
    */

    $title = trim(
        $_POST['title'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    $task_type = trim(
        $_POST['task_type'] ?? ''
    );

    $assigned_to = !empty(
        $_POST['assigned_to']
    )
        ? (int) $_POST['assigned_to']
        : 0;

    $related_lead_id = !empty(
        $_POST['related_lead_id']
    )
        ? (int) $_POST['related_lead_id']
        : null;

    $due_date = trim(
        $_POST['due_date'] ?? ''
    );

    $priority = trim(
        $_POST['priority'] ?? 'Medium'
    );


    /*
    |----------------------------------------------------------------------
    | Validation
    |----------------------------------------------------------------------
    */

    if ($title === '') {

        $errors[] =
            'Task title is required.';
    }


    if (
        !in_array(
            $task_type,
            $task_types,
            true
        )
    ) {

        $errors[] =
            'Please select a valid task type.';
    }


    if ($assigned_to <= 0) {

        $errors[] =
            'Please select a staff member.';
    }


    if (
        !in_array(
            $priority,
            $priorities,
            true
        )
    ) {

        $errors[] =
            'Invalid priority selected.';
    }


    if ($due_date === '') {

        $errors[] =
            'Due date and time are required.';
    }


    /*
    |----------------------------------------------------------------------
    | Validate Staff
    |----------------------------------------------------------------------
    */

    $assigned_staff = null;

    if ($assigned_to > 0) {

        $staff_check = $pdo->prepare("
            SELECT
                u.id,
                u.name

            FROM users u

            INNER JOIN roles r
                ON u.role_id = r.id

            WHERE u.id = ?

              AND u.status = 'active'

              AND LOWER(r.name) IN (
                  'telecaller',
                  'marketing',
                  'marketing executive'
              )

            LIMIT 1
        ");

        $staff_check->execute([
            $assigned_to
        ]);

        $assigned_staff =
            $staff_check->fetch();

        if (!$assigned_staff) {

            $errors[] =
                'Selected staff member is not available.';
        }
    }


    /*
    |----------------------------------------------------------------------
    | Convert Date
    |----------------------------------------------------------------------
    */

    $due_date_mysql = null;

    if ($due_date !== '') {

        $due_date_normalized =
            str_replace(
                'T',
                ' ',
                $due_date
            );

        $timestamp =
            strtotime($due_date_normalized);

        if ($timestamp === false) {

            $errors[] =
                'Invalid due date and time.';

        } else {

            $due_date_mysql =
                date(
                    'Y-m-d H:i:s',
                    $timestamp
                );
        }
    }


    /*
    |----------------------------------------------------------------------
    | Validate Related Lead
    |----------------------------------------------------------------------
    */

    if (
        $related_lead_id !== null
        && $related_lead_id > 0
    ) {

        $lead_check = $pdo->prepare("
            SELECT
                id
            FROM leads
            WHERE id = ?
            LIMIT 1
        ");

        $lead_check->execute([
            $related_lead_id
        ]);

        if (!$lead_check->fetch()) {

            $errors[] =
                'Selected lead was not found.';
        }
    }


    /*
    |----------------------------------------------------------------------
    | Insert Task
    |----------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO tasks
            (
                title,
                description,
                task_type,
                assigned_to,
                related_lead_id,
                due_date,
                priority,
                status,
                created_by
            )

            VALUES
            (
                :title,
                :description,
                :task_type,
                :assigned_to,
                :related_lead_id,
                :due_date,
                :priority,
                'Pending',
                :created_by
            )
        ");


        $stmt->execute([

            'title' =>
                $title,

            'description' =>
                $description !== ''
                    ? $description
                    : null,

            'task_type' =>
                $task_type,

            'assigned_to' =>
                $assigned_to,

            'related_lead_id' =>
                $related_lead_id,

            'due_date' =>
                $due_date_mysql,

            'priority' =>
                $priority,

            'created_by' =>
                $user['id']
        ]);


        $task_id =
            (int) $pdo->lastInsertId();


        header(
            'Location: ' .
            BASE_URL .
            '/manager/tasks/index.php'
        );

        exit;
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

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/manager/tasks/index.php"
            class="text-decoration-none"
        >
            ← Back to Tasks
        </a>

        <h1 class="hm-page-title mt-2 mb-1">
            Add Task
        </h1>

        <p class="hm-muted mb-0">
            Assign a marketing activity to a team member.
        </p>

    </div>


    <!-- ========================================================= -->
    <!-- ERRORS -->
    <!-- ========================================================= -->

    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <div class="fw-semibold mb-2">
                Please correct the following:
            </div>

            <ul class="mb-0">

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?php
                        echo htmlspecialchars(
                            $error
                        );
                        ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <!-- ========================================================= -->
    <!-- FORM -->
    <!-- ========================================================= -->

    <div class="hm-card p-4">

        <form
            method="POST"
            action=""
        >


            <!-- Task Title -->

            <div class="mb-3">

                <label class="form-label">
                    Task Title
                    <span class="text-danger">
                        *
                    </span>
                </label>

                <input
                    type="text"
                    name="title"
                    class="form-control"
                    value="<?php echo htmlspecialchars($title); ?>"
                    placeholder="Example: Visit ABC Clinic"
                    required
                >

            </div>


            <!-- Task Type -->

            <div class="row g-3">


                <div class="col-md-6">

                    <label class="form-label">
                        Task Type
                        <span class="text-danger">
                            *
                        </span>
                    </label>

                    <select
                        name="task_type"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Select Task Type --
                        </option>


                        <?php foreach ($task_types as $type): ?>

                            <option
                                value="<?php echo htmlspecialchars($type); ?>"
                                <?php
                                echo $task_type === $type
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $type
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Assigned Staff -->

                <div class="col-md-6">

                    <label class="form-label">
                        Assign To
                        <span class="text-danger">
                            *
                        </span>
                    </label>

                    <select
                        name="assigned_to"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Select Staff Member --
                        </option>


                        <?php foreach ($staff as $member): ?>

                            <option
                                value="<?php echo (int) $member['id']; ?>"
                                <?php
                                echo $assigned_to ===
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

            </div>


            <!-- Lead + Priority -->

            <div class="row g-3 mt-1">


                <div class="col-md-8">

                    <label class="form-label">
                        Related Lead
                    </label>

                    <select
                        name="related_lead_id"
                        class="form-select"
                    >

                        <option value="">
                            -- No Related Lead --
                        </option>


                        <?php foreach ($leads as $lead): ?>

                            <option
                                value="<?php echo (int) $lead['id']; ?>"
                                <?php
                                echo $related_lead_id ===
                                    (int) $lead['id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $lead['name']
                                );
                                ?>

                                -

                                <?php
                                echo htmlspecialchars(
                                    $lead['phone']
                                );
                                ?>

                                <?php if (!empty($lead['service_interest'])): ?>

                                    -
                                    <?php
                                    echo htmlspecialchars(
                                        $lead['service_interest']
                                    );
                                    ?>

                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-4">

                    <label class="form-label">
                        Priority
                    </label>

                    <select
                        name="priority"
                        class="form-select"
                    >

                        <?php foreach ($priorities as $task_priority): ?>

                            <option
                                value="<?php echo htmlspecialchars($task_priority); ?>"
                                <?php
                                echo $priority === $task_priority
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

            </div>


            <!-- Due Date -->

            <div class="mt-3">

                <label class="form-label">
                    Due Date & Time
                    <span class="text-danger">
                        *
                    </span>
                </label>

                <input
                    type="datetime-local"
                    name="due_date"
                    class="form-control"
                    value="<?php echo htmlspecialchars($due_date); ?>"
                    required
                >

            </div>


            <!-- Description -->

            <div class="mt-3">

                <label class="form-label">
                    Description
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="4"
                    placeholder="Describe what needs to be done..."
                ><?php echo htmlspecialchars($description); ?></textarea>

            </div>


            <!-- Buttons -->

            <div class="d-flex gap-2 mt-4">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Create Task
                </button>

                <a
                    href="<?php echo BASE_URL; ?>/manager/tasks/index.php"
                    class="btn btn-outline-secondary"
                >
                    Cancel
                </a>

            </div>


        </form>

    </div>


</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>