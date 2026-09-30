<?php
 require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/permission_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('admin');
require_permission('sources.view');
$page_title = 'Marketing Sources';

$message = '';
$message_type = '';

/*
|--------------------------------------------------------------------------
| ADD / EDIT / DELETE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
    require_permission('sources.create');
}

if ($action === 'edit') {
    require_permission('sources.edit');
}

if ($action === 'delete') {
    require_permission('sources.delete');
}

    /*
    |--------------------------------------------------------------------------
    | ADD
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {

        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] ?? 'ACTIVE';

        if ($name === '') {

            $message = 'Source name is required.';
            $message_type = 'danger';

        } elseif (
            !in_array($status, ['ACTIVE', 'INACTIVE'], true)
        ) {

            $message = 'Invalid status.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO marketing_sources
                        (name, status)
                    VALUES
                        (?, ?)
                ");

                $stmt->execute([
                    $name,
                    $status
                ]);

                $message = 'Marketing source added successfully.';
                $message_type = 'success';

            } catch (PDOException $e) {

                if (
                    isset($e->errorInfo[1]) &&
                    (int)$e->errorInfo[1] === 1062
                ) {
                    $message = 'This source already exists.';
                } else {
                    $message = 'Unable to add source.';
                }

                $message_type = 'danger';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit') {

        $id = filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );

        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] ?? 'ACTIVE';

        if (!$id) {

            $message = 'Invalid source.';
            $message_type = 'danger';

        } elseif ($name === '') {

            $message = 'Source name is required.';
            $message_type = 'danger';

        } elseif (
            !in_array($status, ['ACTIVE', 'INACTIVE'], true)
        ) {

            $message = 'Invalid status.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    UPDATE marketing_sources
                    SET
                        name = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $name,
                    $status,
                    $id
                ]);

                $message = 'Marketing source updated successfully.';
                $message_type = 'success';

            } catch (PDOException $e) {

                $message = 'Unable to update source.';
                $message_type = 'danger';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id = filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$id) {

            $message = 'Invalid source.';
            $message_type = 'danger';

        } else {

            try {

                /*
                | Existing leads may reference this source.
                | The database foreign key uses SET NULL,
                | so deleting the source will not delete leads.
                */

                $stmt = $pdo->prepare("
                    DELETE FROM marketing_sources
                    WHERE id = ?
                ");

                $stmt->execute([
                    $id
                ]);

                $message = 'Marketing source deleted successfully.';
                $message_type = 'success';

            } catch (PDOException $e) {

                $message = 'Unable to delete source.';
                $message_type = 'danger';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| EDIT MODE
|--------------------------------------------------------------------------
*/

$edit_source = null;

$edit_id = filter_input(
    INPUT_GET,
    'edit',
    FILTER_VALIDATE_INT
);

if ($edit_id) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            status
        FROM marketing_sources
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $edit_id
    ]);

    $edit_source = $stmt->fetch();
}

/*
|--------------------------------------------------------------------------
| FETCH SOURCES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        name,
        status,
        created_at
    FROM marketing_sources
    ORDER BY name ASC
");

$sources = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';

?>

<main class="container py-5">

    <div class="mb-4">

        <span class="badge text-bg-light">
            ADMINISTRATOR
        </span>

        <h1 class="hm-page-title mt-2">
            Marketing Sources
        </h1>

        <p class="hm-muted">
            Manage the sources from which leads and enquiries are received.
        </p>

    </div>

    <?php if ($message !== ''): ?>

        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <div class="row g-4">

        <!-- FORM -->

        <div class="col-lg-4">

            <div class="hm-card p-4">

                <h5 class="fw-bold mb-3">

                    <?php
                    echo $edit_source
                        ? 'Edit Source'
                        : 'Add Source';
                    ?>

                </h5>

                <form method="POST">

                    <input
                        type="hidden"
                        name="action"
                        value="<?php
                        echo $edit_source ? 'edit' : 'add';
                        ?>"
                    >

                    <?php if ($edit_source): ?>

                        <input
                            type="hidden"
                            name="id"
                            value="<?php echo (int)$edit_source['id']; ?>"
                        >

                    <?php endif; ?>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Source Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            required
                            value="<?php
                            echo htmlspecialchars(
                                $edit_source['name'] ?? ''
                            );
                            ?>"
                            placeholder="e.g. Website"
                        >

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
                                    ($edit_source['status'] ?? 'ACTIVE')
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
                                    ($edit_source['status'] ?? '')
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
                            echo $edit_source
                                ? 'Update Source'
                                : 'Add Source';
                            ?>
                        </button>

                        <?php if ($edit_source): ?>

                            <a
                                href="<?php echo BASE_URL; ?>/admin/sources/"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

        </div>


        <!-- SOURCE LIST -->

        <div class="col-lg-8">

            <div class="hm-card p-4">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h5 class="fw-bold mb-0">
                        Source List
                    </h5>

                    <span class="badge text-bg-light">
                        <?php echo count($sources); ?>
                        Sources
                    </span>

                </div>

                <div class="table-responsive">

                    <table class="table table-bordered align-middle">

                        <thead>

                            <tr>
                                <th width="80">ID</th>
                                <th>Source Name</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th width="150">Action</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php if (!empty($sources)): ?>

                            <?php foreach ($sources as $source): ?>

                                <tr>

                                    <td>
                                        <?php echo (int)$source['id']; ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $source['name']
                                            );
                                            ?>
                                        </strong>
                                    </td>

                                    <td>

                                        <?php if (
                                            $source['status'] === 'ACTIVE'
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
                                                    $source['created_at']
                                                )
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <div class="d-flex gap-1">

                                            <a
                                                href="<?php echo BASE_URL; ?>/admin/sources/?edit=<?php echo (int)$source['id']; ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Delete this source?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo (int)$source['id']; ?>"
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
                                    No marketing sources found.
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