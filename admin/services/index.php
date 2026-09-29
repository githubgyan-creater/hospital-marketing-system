<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('admin');

$page_title = 'Services';

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
    | ADD SERVICE
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {

        $service_name = trim(
            $_POST['service_name'] ?? ''
        );

        $service_code = trim(
            $_POST['service_code'] ?? ''
        );

        $department_id = filter_input(
            INPUT_POST,
            'department_id',
            FILTER_VALIDATE_INT
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        $status = $_POST['status'] ?? 'ACTIVE';

        if ($service_name === '') {

            $message = 'Service name is required.';
            $message_type = 'danger';

        } elseif (
            !in_array(
                $status,
                ['ACTIVE', 'INACTIVE'],
                true
            )
        ) {

            $message = 'Invalid service status.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO services (
                        service_name,
                        service_code,
                        department_id,
                        description,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $service_name,
                    $service_code !== ''
                        ? $service_code
                        : null,
                    $department_id ?: null,
                    $description !== ''
                        ? $description
                        : null,
                    $status
                ]);

                $message =
                    'Service added successfully.';

                $message_type = 'success';

            } catch (PDOException $e) {

                if (
                    isset($e->errorInfo[1]) &&
                    (int) $e->errorInfo[1] === 1062
                ) {

                    $message =
                        'Service code already exists.';

                } else {

                    $message =
                        'Unable to add service: ' .
                        $e->getMessage();
                }

                $message_type = 'danger';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT SERVICE
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit') {

        $service_id = filter_input(
            INPUT_POST,
            'service_id',
            FILTER_VALIDATE_INT
        );

        $service_name = trim(
            $_POST['service_name'] ?? ''
        );

        $service_code = trim(
            $_POST['service_code'] ?? ''
        );

        $department_id = filter_input(
            INPUT_POST,
            'department_id',
            FILTER_VALIDATE_INT
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        $status = $_POST['status'] ?? 'ACTIVE';

        if (!$service_id) {

            $message = 'Invalid service.';
            $message_type = 'danger';

        } elseif ($service_name === '') {

            $message = 'Service name is required.';
            $message_type = 'danger';

        } elseif (
            !in_array(
                $status,
                ['ACTIVE', 'INACTIVE'],
                true
            )
        ) {

            $message = 'Invalid service status.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    UPDATE services
                    SET
                        service_name = ?,
                        service_code = ?,
                        department_id = ?,
                        description = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $service_name,
                    $service_code !== ''
                        ? $service_code
                        : null,
                    $department_id ?: null,
                    $description !== ''
                        ? $description
                        : null,
                    $status,
                    $service_id
                ]);

                $message =
                    'Service updated successfully.';

                $message_type = 'success';

            } catch (PDOException $e) {

                if (
                    isset($e->errorInfo[1]) &&
                    (int) $e->errorInfo[1] === 1062
                ) {

                    $message =
                        'Service code already exists.';

                } else {

                    $message =
                        'Unable to update service: ' .
                        $e->getMessage();
                }

                $message_type = 'danger';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE SERVICE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $service_id = filter_input(
            INPUT_POST,
            'service_id',
            FILTER_VALIDATE_INT
        );

        if (!$service_id) {

            $message = 'Invalid service.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    DELETE FROM services
                    WHERE id = ?
                ");

                $stmt->execute([
                    $service_id
                ]);

                $message =
                    'Service deleted successfully.';

                $message_type = 'success';

            } catch (PDOException $e) {

                $message =
                    'Unable to delete service: ' .
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

$edit_service = null;

$edit_id = filter_input(
    INPUT_GET,
    'edit',
    FILTER_VALIDATE_INT
);

if ($edit_id) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            service_name,
            service_code,
            department_id,
            description,
            status
        FROM services
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $edit_id
    ]);

    $edit_service = $stmt->fetch();
}

/*
|--------------------------------------------------------------------------
| Fetch Departments
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        department_name
    FROM departments
    WHERE status = 'ACTIVE'
    ORDER BY department_name ASC
");

$departments = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Fetch Services
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        s.id,
        s.service_name,
        s.service_code,
        s.department_id,
        s.description,
        s.status,
        s.created_at,
        s.updated_at,
        d.department_name
    FROM services s
    LEFT JOIN departments d
        ON d.id = s.department_id
    ORDER BY s.service_name ASC
");

$services = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';

?>

<main class="container py-5">

    <!-- PAGE HEADER -->

    <div class="mb-4">

        <span class="badge text-bg-light">
            ADMINISTRATOR
        </span>

        <h1 class="hm-page-title mt-2">
            Services
        </h1>

        <p class="hm-muted">
            Manage hospital services, treatment offerings and their departments.
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
                    echo $edit_service
                        ? 'Edit Service'
                        : 'Add Service';
                    ?>

                </h5>

                <form method="POST">

                    <input
                        type="hidden"
                        name="action"
                        value="<?php
                        echo $edit_service
                            ? 'edit'
                            : 'add';
                        ?>"
                    >

                    <?php if ($edit_service): ?>

                        <input
                            type="hidden"
                            name="service_id"
                            value="<?php
                            echo (int) $edit_service['id'];
                            ?>"
                        >

                    <?php endif; ?>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Service Name
                        </label>

                        <input
                            type="text"
                            name="service_name"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $edit_service['service_name']
                                ?? ''
                            );
                            ?>"
                            placeholder="e.g. Cardiac Consultation"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Service Code
                        </label>

                        <input
                            type="text"
                            name="service_code"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $edit_service['service_code']
                                ?? ''
                            );
                            ?>"
                            placeholder="e.g. CARD-CONS"
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Department
                        </label>

                        <select
                            name="department_id"
                            class="form-select"
                        >

                            <option value="">
                                Select Department
                            </option>

                            <?php foreach (
                                $departments
                                as $department
                            ): ?>

                                <option
                                    value="<?php
                                    echo (int) $department['id'];
                                    ?>"
                                    <?php
                                    echo (
                                        isset(
                                            $edit_service[
                                                'department_id'
                                            ]
                                        )
                                        &&
                                        (int)
                                        $edit_service[
                                            'department_id'
                                        ]
                                        ===
                                        (int) $department['id']
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $department[
                                            'department_name'
                                        ]
                                    );
                                    ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                            placeholder="Describe the service..."
                        ><?php
                        echo htmlspecialchars(
                            $edit_service['description']
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
                                    ($edit_service['status'] ?? 'ACTIVE')
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
                                    ($edit_service['status'] ?? '')
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
                            echo $edit_service
                                ? 'Update Service'
                                : 'Add Service';
                            ?>

                        </button>

                        <?php if ($edit_service): ?>

                            <a
                                href="<?php echo BASE_URL; ?>/admin/services/"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

        </div>


        <!-- SERVICE LIST -->

        <div class="col-lg-8">

            <div class="hm-card p-4">

                <div
                    class="d-flex justify-content-between align-items-center mb-3"
                >

                    <h5 class="fw-bold mb-0">
                        Service List
                    </h5>

                    <span class="badge text-bg-light">
                        <?php echo count($services); ?>
                        Services
                    </span>

                </div>

                <div class="table-responsive">

                    <table
                        class="table table-bordered align-middle"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Code
                                </th>

                                <th>
                                    Department
                                </th>

                                <th>
                                    Status
                                </th>

                                <th width="150">
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if (!empty($services)): ?>

                            <?php foreach (
                                $services
                                as $service
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $service[
                                                    'service_name'
                                                ]
                                            );
                                            ?>
                                        </strong>

                                        <?php
                                        if (
                                            !empty(
                                                $service[
                                                    'description'
                                                ]
                                            )
                                        ):
                                        ?>

                                            <div class="small text-muted">
                                                <?php
                                                echo htmlspecialchars(
                                                    $service[
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
                                            $service[
                                                'service_code'
                                            ] ?? '-'
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $service[
                                                'department_name'
                                            ] ?? '-'
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <?php if (
                                            $service['status']
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

                                        <div class="d-flex gap-1">

                                            <a
                                                href="<?php echo BASE_URL; ?>/admin/services/?edit=<?php echo (int) $service['id']; ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Delete this service?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="service_id"
                                                    value="<?php
                                                    echo (int)
                                                        $service['id'];
                                                    ?>"
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
                                    No services found.
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