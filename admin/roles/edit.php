 <?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('admin');

$user = current_user();


/*
|--------------------------------------------------------------------------
| Get Role ID
|--------------------------------------------------------------------------
*/

$role_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$role_id) {

    header(
        'Location: ' .
        BASE_URL .
        '/admin/roles/'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch Role
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        display_name
    FROM roles
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $role_id
]);

$role = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {

    http_response_code(404);

    exit('Role not found.');

}


/*
|--------------------------------------------------------------------------
| Handle Permission Update
|--------------------------------------------------------------------------
*/

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | Get Selected Permissions
    |--------------------------------------------------------------------------
    */

    $selected_permissions =
        $_POST['permissions'] ?? [];

    if (!is_array($selected_permissions)) {

        $selected_permissions = [];

    }


    /*
    |--------------------------------------------------------------------------
    | Convert IDs To Integers
    |--------------------------------------------------------------------------
    */

    $selected_permissions = array_map(
        'intval',
        $selected_permissions
    );

    $selected_permissions = array_filter(
        $selected_permissions,
        function ($permission_id) {
            return $permission_id > 0;
        }
    );

    $selected_permissions = array_unique(
        $selected_permissions
    );


    try {

        /*
        |--------------------------------------------------------------------------
        | Start Transaction
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | Remove Existing Permissions
        |--------------------------------------------------------------------------
        */

        $delete_stmt = $pdo->prepare("
            DELETE FROM role_permissions
            WHERE role_id = ?
        ");

        $delete_stmt->execute([
            $role_id
        ]);


        /*
        |--------------------------------------------------------------------------
        | Verify Permission IDs
        |--------------------------------------------------------------------------
        */

        if (!empty($selected_permissions)) {

            $check_stmt = $pdo->prepare("
                SELECT id
                FROM permissions
                WHERE id = ?
                LIMIT 1
            ");


            $insert_stmt = $pdo->prepare("
                INSERT INTO role_permissions
                    (role_id, permission_id)
                VALUES
                    (?, ?)
            ");


            foreach ($selected_permissions as $permission_id) {


                /*
                |--------------------------------------------------------------------------
                | Confirm Permission Exists
                |--------------------------------------------------------------------------
                */

                $check_stmt->execute([
                    $permission_id
                ]);

                $permission_exists =
                    $check_stmt->fetchColumn();


                if ($permission_exists) {

                    $insert_stmt->execute([
                        $role_id,
                        $permission_id
                    ]);

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Commit
        |--------------------------------------------------------------------------
        */

        $pdo->commit();


        $message =
            'Permissions updated successfully.';

        $message_type = 'success';


    } catch (Throwable $e) {


        /*
        |--------------------------------------------------------------------------
        | Rollback On Error
        |--------------------------------------------------------------------------
        */

        if ($pdo->inTransaction()) {

            $pdo->rollBack();

        }


        $message =
            'Unable to update permissions.';

        $message_type = 'danger';

    }

}


/*
|--------------------------------------------------------------------------
| Fetch All Permissions
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        name,
        display_name,
        module
    FROM permissions
    ORDER BY
        module ASC,
        id ASC
");

$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Fetch Current Role Permissions
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT permission_id
    FROM role_permissions
    WHERE role_id = ?
");

$stmt->execute([
    $role_id
]);

$current_permission_ids =
    $stmt->fetchAll(PDO::FETCH_COLUMN);


$current_permission_ids = array_map(
    'intval',
    $current_permission_ids
);


/*
|--------------------------------------------------------------------------
| Group Permissions By Module
|--------------------------------------------------------------------------
*/

$grouped_permissions = [];


foreach ($permissions as $permission) {

    $module = $permission['module'];

    if (!isset($grouped_permissions[$module])) {

        $grouped_permissions[$module] = [];

    }

    $grouped_permissions[$module][] =
        $permission;

}


/*
|--------------------------------------------------------------------------
| Count Selected Permissions
|--------------------------------------------------------------------------
*/

$selected_permission_count =
    count($current_permission_ids);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage Role Permissions
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="<?= BASE_URL; ?>/assets/css/style.css"
    >


    <style>

        body {
            background: #f7f5ef;
            color: #243746;
        }

        .navbar-custom {
            background: #17324d;
        }

        .navbar-brand,
        .nav-link {
            color: #ffffff !important;
        }

        .page-title {
            color: #17324d;
            font-weight: 700;
        }

        .page-card {
            border: none;
            border-radius: 14px;
            box-shadow:
                0 4px 18px
                rgba(23, 50, 77, 0.08);
        }

        .module-card {
            border: 1px solid #e4e0d7;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            background: #ffffff;
        }

        .module-header {
            background: #17324d;
            color: #ffffff;
            padding: 12px 16px;
            font-weight: 600;
        }

        .permission-row {
            padding: 12px 16px;
            border-bottom: 1px solid #eeeae2;
        }

        .permission-row:last-child {
            border-bottom: none;
        }

        .permission-name {
            color: #71808c;
            font-size: 13px;
            margin-top: 2px;
        }

        .role-box {
            background: #ffffff;
            border-left: 4px solid #168a87;
            border-radius: 10px;
            padding: 15px 18px;
            box-shadow:
                0 3px 12px
                rgba(23, 50, 77, 0.06);
        }

        .btn-primary {
            background: #17324d;
            border-color: #17324d;
        }

        .btn-primary:hover {
            background: #12283e;
            border-color: #12283e;
        }

        .btn-teal {
            background: #168a87;
            color: #ffffff;
            border-color: #168a87;
        }

        .btn-teal:hover {
            background: #11716f;
            color: #ffffff;
        }

        .selected-count {
            background: #b89a5a;
            color: #ffffff;
            font-weight: 600;
        }

    </style>

</head>

<body>


<?php

require_once __DIR__ . '/../../includes/navbar.php';

?>


<div class="container py-4">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="page-title mb-1">
                Manage Role Permissions
            </h2>

            <p class="text-muted mb-0">
                Select the permissions available to this role.
            </p>

        </div>


        <a
            href="<?= BASE_URL; ?>/admin/roles/"
            class="btn btn-outline-secondary"
        >
            ← Back to Roles
        </a>

    </div>


    <!-- =====================================================
         MESSAGE
    ====================================================== -->

    <?php if ($message !== ''): ?>

        <div
            class="alert alert-<?= htmlspecialchars($message_type); ?>"
            role="alert"
        >

            <?= htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ROLE INFORMATION
    ====================================================== -->

    <div class="role-box mb-4">


        <div class="row align-items-center">


            <div class="col-md-5">

                <div class="text-muted small">
                    Role
                </div>

                <h4 class="mb-0">

                    <?= htmlspecialchars(
                        $role['display_name']
                    ); ?>

                </h4>

            </div>


            <div class="col-md-4 mt-3 mt-md-0">

                <div class="text-muted small">
                    Role Code
                </div>

                <strong>

                    <?= htmlspecialchars(
                        $role['name']
                    ); ?>

                </strong>

            </div>


            <div class="col-md-3 mt-3 mt-md-0">

                <div class="text-muted small">
                    Assigned Permissions
                </div>

                <span class="badge selected-count">

                    <?= $selected_permission_count; ?>

                </span>

            </div>


        </div>


    </div>


    <!-- =====================================================
         PERMISSIONS FORM
    ====================================================== -->

    <form method="POST">


        <?php if (!empty($grouped_permissions)): ?>


            <?php foreach (
                $grouped_permissions
                as $module => $module_permissions
            ): ?>


                <div class="module-card">


                    <!-- Module Header -->

                    <div class="module-header">

                        <?= htmlspecialchars(
                            $module
                        ); ?>

                    </div>


                    <!-- Module Permissions -->

                    <?php foreach (
                        $module_permissions
                        as $permission
                    ): ?>


                        <div class="permission-row">


                            <div class="form-check">


                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="permissions[]"
                                    value="<?= (int) $permission['id']; ?>"
                                    id="permission_<?= (int) $permission['id']; ?>"
                                    <?= in_array(
                                        (int) $permission['id'],
                                        $current_permission_ids,
                                        true
                                    ) ? 'checked' : ''; ?>
                                >


                                <label
                                    class="form-check-label fw-semibold"
                                    for="permission_<?= (int) $permission['id']; ?>"
                                >

                                    <?= htmlspecialchars(
                                        $permission['display_name']
                                    ); ?>

                                </label>


                                <div class="permission-name">

                                    <?= htmlspecialchars(
                                        $permission['name']
                                    ); ?>

                                </div>


                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="card page-card mb-4">

                <div class="card-body text-center text-muted py-5">

                    No permissions found.

                </div>

            </div>


        <?php endif; ?>


        <!-- =================================================
             FORM ACTIONS
        ================================================== -->

        <div class="d-flex justify-content-end gap-2 mb-5">


            <a
                href="<?= BASE_URL; ?>/admin/roles/"
                class="btn btn-outline-secondary"
            >
                Cancel
            </a>


            <button
                type="submit"
                class="btn btn-teal"
            >
                Save Permissions
            </button>


        </div>


    </form>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>