<?php

require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('admin');

$user = current_user();

/*

| Get Role ID

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

| Fetch Role

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

$role = $stmt->fetch();

if (!$role) {
    http_response_code(404);
    exit('Role not found.');
}

/*

| Handle Permission Update

*/

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selected_permissions =
        $_POST['permissions'] ?? [];

    if (!is_array($selected_permissions)) {
        $selected_permissions = [];
    }

    $selected_permissions = array_map(
        'intval',
        $selected_permissions
    );

    $selected_permissions = array_unique(
        $selected_permissions
    );

    try {

        $pdo->beginTransaction();

        /*
        
        | Remove old permissions
        
        */

        $delete_stmt = $pdo->prepare("
            DELETE FROM role_permissions
            WHERE role_id = ?
        ");

        $delete_stmt->execute([
            $role_id
        ]);

        /*
        
        | Add selected permissions
        
        */

        if (!empty($selected_permissions)) {

            $insert_stmt = $pdo->prepare("
                INSERT INTO role_permissions
                    (role_id, permission_id)
                VALUES
                    (?, ?)
            ");

            foreach ($selected_permissions as $permission_id) {

                /*
                | Verify permission exists
                */
                $check_stmt = $pdo->prepare("
                    SELECT id
                    FROM permissions
                    WHERE id = ?
                    LIMIT 1
                ");

                $check_stmt->execute([
                    $permission_id
                ]);

                $permission_exists =
                    $check_stmt->fetch();

                if ($permission_exists) {

                    $insert_stmt->execute([
                        $role_id,
                        $permission_id
                    ]);
                }
            }
        }

        $pdo->commit();

        $message =
            'Permissions updated successfully.';

        $message_type = 'success';

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message =
            'Unable to update permissions: ' .
            $e->getMessage();

        $message_type = 'danger';
    }
}

/*

| Fetch All Permissions

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

$permissions = $stmt->fetchAll();

/*

| Fetch Current Role Permissions

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
    $stmt->fetchAll(
        PDO::FETCH_COLUMN
    );

$current_permission_ids =
    array_map(
        'intval',
        $current_permission_ids
    );

/*

| Group Permissions By Module

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

    </style>
    
    <link
    rel="stylesheet"
    href="<?php echo BASE_URL; ?>/assets/css/style.css"
>

</head>

<body>

 <?php
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container py-4">

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
            href="<?php echo BASE_URL; ?>/admin/roles/"
            class="btn btn-outline-secondary"
        >
            ← Back to Roles
        </a>

    </div>

    <?php if ($message !== ''): ?>

        <div
            class="alert alert-<?php echo htmlspecialchars($message_type); ?>"
        >

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>

    <div class="role-box mb-4">

        <div class="row align-items-center">

            <div class="col-md-6">

                <div class="text-muted small">
                    Role
                </div>

                <h4 class="mb-0">
                    <?php
                    echo htmlspecialchars(
                        $role['display_name']
                    );
                    ?>
                </h4>

            </div>

            <div class="col-md-6 mt-3 mt-md-0">

                <div class="text-muted small">
                    Role Code
                </div>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $role['name']
                    );
                    ?>
                </strong>

            </div>

        </div>

    </div>

    <form method="POST">

        <?php foreach (
            $grouped_permissions
            as $module => $module_permissions
        ): ?>

            <div class="module-card">

                <div class="module-header">

                    <?php
                    echo htmlspecialchars(
                        $module
                    );
                    ?>

                </div>

                <?php foreach (
                    $module_permissions
                    as $permission
                ): ?>

                    <div class="permission-row">

                        <div
                            class="form-check"
                        >

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="permissions[]"
                                value="<?php echo (int) $permission['id']; ?>"
                                id="permission_<?php echo (int) $permission['id']; ?>"
                                <?php
                                if (
                                    in_array(
                                        (int) $permission['id'],
                                        $current_permission_ids,
                                        true
                                    )
                                ) {
                                    echo 'checked';
                                }
                                ?>
                            >

                            <label
                                class="form-check-label fw-semibold"
                                for="permission_<?php echo (int) $permission['id']; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $permission['display_name']
                                );
                                ?>

                            </label>

                            <div class="permission-name">

                                <?php
                                echo htmlspecialchars(
                                    $permission['name']
                                );
                                ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

        <div class="d-flex justify-content-end gap-2 mb-5">

            <a
                href="<?php echo BASE_URL; ?>/admin/roles/"
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