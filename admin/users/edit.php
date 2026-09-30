<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permission_check.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('admin');
require_permission('users.edit');

$page_title = 'Edit Staff';

$errors = [];

$user_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$user_id) {
    die('Invalid user ID.');
}


/*
|--------------------------------------------------------------------------
| Get User
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.name,
        u.email,
        u.role_id,
        u.status
    FROM users u
    WHERE u.id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$staff = $stmt->fetch();

if (!$staff) {
    die('Staff account not found.');
}


/*
|--------------------------------------------------------------------------
| Get Available Roles
|--------------------------------------------------------------------------
*/

$role_stmt = $pdo->query("
    SELECT
        id,
        name,
        display_name
    FROM roles
    WHERE name IN (
        'manager',
        'telecaller',
        'marketing'
    )
    ORDER BY id ASC
");

$roles = $role_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$name = $staff['name'];
$email = $staff['email'];
$role_id = (int) $staff['role_id'];
$status = $staff['status'];


/*
|--------------------------------------------------------------------------
| Handle Update
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_id = (int) ($_POST['role_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    /*
    | Validation
    */

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($role_id <= 0) {
        $errors[] = 'Please select a role.';
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Invalid status selected.';
    }


    /*
    | Validate Role
    */

    if ($role_id > 0) {

        $role_check = $pdo->prepare("
            SELECT id
            FROM roles
            WHERE id = ?
              AND name IN (
                  'manager',
                  'telecaller',
                  'marketing'
              )
            LIMIT 1
        ");

        $role_check->execute([$role_id]);

        if (!$role_check->fetch()) {
            $errors[] = 'Selected role is not valid.';
        }
    }


    /*
    | Duplicate Email
    */

    if (empty($errors)) {

        $email_check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
              AND id != ?
            LIMIT 1
        ");

        $email_check->execute([
            $email,
            $user_id
        ]);

        if ($email_check->fetch()) {
            $errors[] = 'Another account already uses this email.';
        }
    }


    /*
    | Password Validation
    */

    if ($password !== '') {

        if (strlen($password) < 8) {
            $errors[] =
                'New password must be at least 8 characters.';
        }

        if ($password !== $confirm_password) {
            $errors[] =
                'New passwords do not match.';
        }
    }


    /*
    | Update User
    */

    if (empty($errors)) {

        if ($password !== '') {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $update = $pdo->prepare("
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    password = ?,
                    role_id = ?,
                    status = ?
                WHERE id = ?
            ");

            $update->execute([
                $name,
                $email,
                $hashed_password,
                $role_id,
                $status,
                $user_id
            ]);

        } else {

            $update = $pdo->prepare("
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    role_id = ?,
                    status = ?
                WHERE id = ?
            ");

            $update->execute([
                $name,
                $email,
                $role_id,
                $status,
                $user_id
            ]);
        }


        header(
            'Location: ' .
            BASE_URL .
            '/admin/users/view.php?id=' .
            $user_id
        );

        exit;
    }
}


require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/admin/users/view.php?id=<?php echo (int) $user_id; ?>"
            class="text-decoration-none"
        >
            ← Back to Staff Details
        </a>

        <h1 class="hm-page-title mt-3 mb-1">
            Edit Staff
        </h1>

        <p class="hm-muted">
            Update staff account information.
        </p>

    </div>


    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <ul class="mb-0">

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?php echo htmlspecialchars($error); ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <div class="hm-card p-4">

        <form method="POST">

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($name); ?>"
                        required
                    >

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required
                    >

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Role
                    </label>

                    <select
                        name="role_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Role
                        </option>

                        <?php foreach ($roles as $role): ?>

                            <option
                                value="<?php echo (int) $role['id']; ?>"
                                <?php
                                echo $role_id === (int) $role['id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                <?php
                                echo htmlspecialchars(
                                    $role['display_name']
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                        required
                    >

                        <option
                            value="active"
                            <?php echo $status === 'active' ? 'selected' : ''; ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?php echo $status === 'inactive' ? 'selected' : ''; ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <div class="col-12">

                    <hr>

                    <h5>
                        Change Password
                    </h5>

                    <p class="hm-muted small">
                        Leave these fields empty to keep the current password.
                    </p>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        New Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        minlength="8"
                    >

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
                        minlength="8"
                    >

                </div>


                <div class="col-12">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Update Staff
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/admin/users/index.php"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>

                </div>

            </div>

        </form>

    </div>

</div>

<?php

require_once __DIR__ . '/../../includes/footer.php';

?>