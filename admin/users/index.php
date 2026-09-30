 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/permission_check.php';

require_role('admin');
require_permission('users.view');

$page_title = 'Staff Management';

/*
|--------------------------------------------------------------------------
| Get all staff users
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        u.email,
        u.status,
        u.created_at,
        r.name AS role_name,
        r.display_name AS role_display_name
    FROM users u
    LEFT JOIN roles r
        ON u.role_id = r.id
    ORDER BY u.id DESC
");

$users = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Current logged-in user
|--------------------------------------------------------------------------
*/
$current_user = current_user();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="hm-page-title mb-1">
                Staff Management
            </h1>

            <p class="hm-muted mb-0">
                Manage hospital marketing staff accounts.
            </p>
        </div>

        <?php if (has_permission('users.create')): ?>

            <a
                href="<?php echo BASE_URL; ?>/admin/users/add.php"
                class="btn btn-hm-primary"
            >
                + Add Staff
            </a>

        <?php endif; ?>

    </div>


    <!-- Staff Table -->
    <div class="hm-card">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Role</th>

                        <th>Status</th>

                        <th>Created</th>

                        <th class="text-end">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (empty($users)): ?>

                    <tr>

                        <td
                            colspan="7"
                            class="text-center py-5"
                        >

                            <div class="hm-muted">
                                No staff accounts found.
                            </div>

                        </td>

                    </tr>

                <?php else: ?>


                    <?php foreach ($users as $user): ?>

                        <tr>

                            <!-- ID -->
                            <td>
                                <?php echo (int) $user['id']; ?>
                            </td>


                            <!-- Name -->
                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $user['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- Email -->
                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $user['email'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </td>


                            <!-- Role -->
                            <td>

                                <?php

                                $role_display =
                                    !empty($user['role_display_name'])
                                        ? $user['role_display_name']
                                        : $user['role_name'];

                                if (empty($role_display)) {
                                    $role_display = 'Unknown';
                                }

                                ?>

                                <span class="badge bg-light text-dark">

                                    <?php
                                    echo htmlspecialchars(
                                        $role_display,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- Status -->
                            <td>

                                <?php if ($user['status'] === 'active'): ?>

                                    <span class="badge bg-success">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Created Date -->
                            <td>

                                <?php

                                if (!empty($user['created_at'])) {

                                    echo date(
                                        'd M Y',
                                        strtotime($user['created_at'])
                                    );

                                } else {

                                    echo '-';

                                }

                                ?>

                            </td>


                            <!-- Actions -->
                            <td class="text-end">

                                <div
                                    class="d-flex justify-content-end gap-1 flex-wrap"
                                >


                                    <!-- VIEW -->
                                    <?php if (has_permission('users.view')): ?>

                                        <a
                                            href="<?php echo BASE_URL; ?>/admin/users/view.php?id=<?php echo (int) $user['id']; ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            View
                                        </a>

                                    <?php endif; ?>


                                    <!-- EDIT -->
                                    <?php if (has_permission('users.edit')): ?>

                                        <a
                                            href="<?php echo BASE_URL; ?>/admin/users/edit.php?id=<?php echo (int) $user['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            Edit
                                        </a>

                                    <?php endif; ?>


                                    <?php

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Check if this is the currently logged-in user
                                    |--------------------------------------------------------------------------
                                    */

                                    $is_current_user = false;

                                    if (
                                        $current_user &&
                                        isset($current_user['id']) &&
                                        (int) $current_user['id'] === (int) $user['id']
                                    ) {

                                        $is_current_user = true;

                                    }

                                    ?>


                                    <!-- DELETE -->
                                    <?php
                                    if (
                                        has_permission('users.delete')
                                        && !$is_current_user
                                    ):
                                    ?>

                                        <form
                                            method="POST"
                                            action="<?php echo BASE_URL; ?>/admin/users/delete.php"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this staff account?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?php echo (int) $user['id']; ?>"
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