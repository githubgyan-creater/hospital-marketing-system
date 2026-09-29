<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('admin');

$page_title = 'Departments';

$user = current_user();

$message = '';
$message_type = '';

/*
|--------------------------------------------------------------------------
| Handle Add / Edit / Delete
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | ADD DEPARTMENT
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {

        $department_name = trim(
            $_POST['department_name'] ?? ''
        );

        $department_code = trim(
            $_POST['department_code'] ?? ''
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        $status = $_POST['status'] ?? 'ACTIVE';

        if ($department_name === '') {

            $message = 'Department name is required.';
            $message_type = 'danger';

        } elseif (
            !in_array(
                $status,
                ['ACTIVE', 'INACTIVE'],
                true
            )
        ) {

            $message = 'Invalid department status.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO departments (
                        department_name,
                        department_code,
                        description,
                        status
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $department_name,
                    $department_code !== ''
                        ? $department_code
                        : null,
                    $description !== ''
                        ? $description
                        : null,
                    $status
                ]);

                $message =
                    'Department added successfully.';

                $message_type = 'success';

            } catch (PDOException $e) {

                if ((int) $e->errorInfo[1] === 1062) {

                    $message =
                        'Department name or code already exists.';

                } else {

                    $message =
                        'Unable to add department: ' .
                        $e->getMessage();
                }

                $message_type = 'danger';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT DEPARTMENT
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit') {

        $department_id = filter_input(
            INPUT_POST,
            'department_id',
            FILTER_VALIDATE_INT
        );

        $department_name = trim(
            $_POST['department_name'] ?? ''
        );

        $department_code = trim(
            $_POST['department_code'] ?? ''
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        $status = $_POST['status'] ?? 'ACTIVE';

        if (!$department_id) {

            $message = 'Invalid department.';
            $message_type = 'danger';

        } elseif ($department_name === '') {

            $message = 'Department name is required.';
            $message_type = 'danger';

        } elseif (
            !in_array(
                $status,
                ['ACTIVE', 'INACTIVE'],
                true
            )
        ) {

            $message = 'Invalid department status.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    UPDATE departments
                    SET
                        department_name = ?,
                        department_code = ?,
                        description = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $department_name,
                    $department_code !== ''
                        ? $department_code
                        : null,
                    $description !== ''
                        ? $description
                        : null,
                    $status,
                    $department_id
                ]);

                $message =
                    'Department updated successfully.';

                $message_type = 'success';

            } catch (PDOException $e) {

                if ((int) $e->errorInfo[1] === 1062) {

                    $message =
                        'Department name or code already exists.';

                } else {

                    $message =
                        'Unable to update department: ' .
                        $e->getMessage();
                }

                $message_type = 'danger';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE DEPARTMENT
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $department_id = filter_input(
            INPUT_POST,
            'department_id',
            FILTER_VALIDATE_INT
        );

        if (!$department_id) {

            $message = 'Invalid department.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    DELETE FROM departments
                    WHERE id = ?
                ");

                $stmt->execute([
                    $department_id
                ]);

                $message =
                    'Department deleted successfully.';

                $message_type = 'success';

            } catch (PDOException $e) {

                $message =
                    'Unable to delete department: ' .
                    $e->getMessage();

                $message_type = 'danger';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Edit Mode
|--------------------------------------------------------------------------
*/

$edit_department = null;

$edit_id = filter_input(
    INPUT_GET,
    'edit',
    FILTER_VALIDATE_INT
);

if ($edit_id) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            department_name,
            department_code,
            description,
            status
        FROM departments
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $edit_id
    ]);

    $edit_department = $stmt->fetch();
}

/*
|--------------------------------------------------------------------------
| Fetch Departments
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        department_name,
        department_code,
        description,
        status,
        created_at,
        updated_at
    FROM departments
    ORDER BY department_name ASC
");

$departments = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';

?>

<main class="container py-5">

    <!-- PAGE HEADER -->

    <div class="mb-4">

        <span class="badge text-bg-light">
            ADMINISTRATOR
        </span>

        <h1 class="hm-page-title mt-2">
            Departments
        </h1>

        <p class="hm-muted">
            Manage hospital departments and their active status.
        </p>

    </div>

    <?php if ($message !== ''): ?>

        <div
            class="alert alert-<?php echo htmlspecialchars($message_type); ?>"
        >
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <div class="row g-4">

        <!-- ADD / EDIT FORM -->

        <div class="col-lg-4">

            <div class="hm-card p-4">

                <h5 class="fw-bold mb-3">

                    <?php
                    echo $edit_department
                        ? 'Edit Department'
                        : 'Add Department';
                    ?>

                </h5>

                <form method="POST">

                    <input
                        type="hidden"
                        name="action"
                        value="<?php echo $edit_department ? 'edit' : 'add'; ?>"
                    >

                    <?php if ($edit_department): ?>

                        <input
                            type="hidden"
                            name="department_id"
                            value="<?php echo (int) $edit_department['id']; ?>"
                        >

                    <?php endif; ?>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Department Name
                        </label>

                        <input
                            type="text"
                            name="department_name"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $edit_department['department_name']
                                ?? ''
                            );
                            ?>"
                            placeholder="e.g. Cardiology"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Department Code
                        </label>

                        <input
                            type="text"
                            name="department_code"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $edit_department['department_code']
                                ?? ''
                            );
                            ?>"
                            placeholder="e.g. CARD"
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                        ><?php
                        echo htmlspecialchars(
                            $edit_department['description']
                            ?? ''
                        );
                        ?></textarea>

                    </div>

                    <div class="mb-4">

                        <label class="form-label fw-semibold">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option
                                value="ACTIVE"
                                <?php
                                echo (
                                    ($edit_department['status'] ?? 'ACTIVE')
                                    === 'ACTIVE'
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Active
                            </option>

                            <option
                                value="INACTIVE"
                                <?php
                                echo (
                                    ($edit_department['status'] ?? '')
                                    === 'INACTIVE'
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-hm-primary"
                        >

                            <?php
                            echo $edit_department
                                ? 'Update Department'
                                : 'Add Department';
                            ?>

                        </button>

                        <?php if ($edit_department): ?>

                            <a
                                href="<?php echo BASE_URL; ?>/admin/departments/"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

        </div>


        <!-- DEPARTMENT LIST -->

        <div class="col-lg-8">

            <div class="hm-card p-4">

                <div
                    class="d-flex justify-content-between align-items-center mb-3"
                >

                    <h5 class="fw-bold mb-0">
                        Department List
                    </h5>

                    <span class="badge text-bg-light">
                        <?php echo count($departments); ?>
                        Departments
                    </span>

                </div>

                <div class="table-responsive">

                    <table
                        class="table table-bordered align-middle"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Department
                                </th>

                                <th>
                                    Code
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Created
                                </th>

                                <th width="150">
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if (!empty($departments)): ?>

                            <?php foreach (
                                $departments
                                as $department
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $department[
                                                    'department_name'
                                                ]
                                            );
                                            ?>
                                        </strong>

                                        <?php
                                        if (
                                            !empty(
                                                $department[
                                                    'description'
                                                ]
                                            )
                                        ):
                                        ?>

                                            <div class="small text-muted">
                                                <?php
                                                echo htmlspecialchars(
                                                    $department[
                                                        'description'
                                                    ]
                                                );
                                                ?>
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $department[
                                                'department_code'
                                            ] ?? '-'
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <?php if (
                                            $department['status']
                                            === 'ACTIVE'
                                        ): ?>

                                            <span class="badge text-bg-success">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="badge text-bg-secondary">
                                                Inactive
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            date(
                                                'd M Y',
                                                strtotime(
                                                    $department[
                                                        'created_at'
                                                    ]
                                                )
                                            )
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <div class="d-flex gap-1">

                                            <a
                                                href="<?php echo BASE_URL; ?>/admin/departments/?edit=<?php echo (int) $department['id']; ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Delete this department?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="department_id"
                                                    value="<?php echo (int) $department['id']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                >
                                                    Delete
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted py-4"
                                >
                                    No departments found.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</main>

<?php

require_once __DIR__ . '/../../includes/footer.php';

?>