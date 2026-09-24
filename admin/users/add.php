 <?php

require_once __DIR__ .'/../../config/database.php';
require_once __DIR__ .'/../../includes/role_check.php';

require_role('admin');

$page_title = 'Add Staff';

$errors = [];

$name = '';
$email = '';
$role_id = '';
$status = 'active';


/*

 Get available staff roles

*/

$role_stmt = $pdo->query("
    SELECT id, name, display_name
    FROM roles
    WHERE name IN ('manager', 'telecaller', 'marketing')
    ORDER BY id ASC
");

$roles = $role_stmt->fetchAll();


/*

 Handle form submission

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $password = $_POST['password'] ?? '';

    $confirm_password = $_POST['confirm_password'] ?? '';

    $role_id = (int) ($_POST['role_id'] ?? 0);

    $status = $_POST['status'] ?? 'active';


    /*
    
     Validation
    
    */

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if ($role_id <= 0) {
        $errors[] = 'Please select a role.';
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Invalid status selected.';
    }


    /*
    
     Check duplicate email
    
    */

    if (empty($errors)) {

        $check_stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $check_stmt->execute([
            'email' => $email
        ]);

        if ($check_stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }


    /*
    
    Create staff account
    
    */

    if (empty($errors)) {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $insert_stmt = $pdo->prepare("
            INSERT INTO users
            (
                name,
                email,
                password,
                role_id,
                status
            )
            VALUES
            (
                :name,
                :email,
                :password,
                :role_id,
                :status
            )
        ");

        $insert_stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $hashed_password,
            'role_id' => $role_id,
            'status' => $status
        ]);

        header(
            'Location: ' .
            BASE_URL .
            '/admin/users/index.php'
        );

        exit;
    }
}


require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/admin/users/index.php"
            class="text-decoration-none"
        >
            ← Back to User Management
        </a>

        <h1 class="hm-page-title mt-3 mb-1">
            Add Staff
        </h1>

        <p class="hm-muted">
            Create a login account for a hospital marketing team member.
        </p>

    </div>


    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <strong>Please fix the following:</strong>

            <ul class="mb-0 mt-2">

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
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        required
                    >

                    <small class="text-muted">
                        Minimum 8 characters.
                    </small>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
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
                                value="<?php echo $role['id']; ?>"
                                <?php
                                echo (
                                    $role_id == $role['id']
                                )
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
                    >

                        <option
                            value="active"
                            <?php
                            echo $status === 'active'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?php
                            echo $status === 'inactive'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <div class="col-12 mt-4">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Create Staff Account
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/admin/users/index.php"
                        class="btn btn-outline-secondary ms-2"
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