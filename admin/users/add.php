 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/lead_workflow.php';

require_role('admin');

$page_title = 'Add Staff';

$errors = [];

$name = '';
$email = '';
$role_id = '';
$status = 'active';


/*
|--------------------------------------------------------------------------
| Current Admin
|--------------------------------------------------------------------------
*/

$current_user = current_user();

$current_admin_id = (int) $current_user['id'];


/*
|--------------------------------------------------------------------------
| Get Available Staff Roles
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
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim(
        $_POST['name'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $password = $_POST['password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';

    $role_id = (int) (
        $_POST['role_id'] ?? 0
    );

    $status =
        $_POST['status'] ?? 'active';


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $errors[] =
            'Name is required.';
    }


    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            'Please enter a valid email address.';
    }


    if (
        strlen($password) < 8
    ) {

        $errors[] =
            'Password must be at least 8 characters.';
    }


    if (
        $password !==
        $confirm_password
    ) {

        $errors[] =
            'Passwords do not match.';
    }


    if ($role_id <= 0) {

        $errors[] =
            'Please select a role.';
    }


    if (
        !in_array(
            $status,
            [
                'active',
                'inactive'
            ],
            true
        )
    ) {

        $errors[] =
            'Invalid status selected.';
    }


    /*
    |--------------------------------------------------------------------------
    | Get Selected Role
    |--------------------------------------------------------------------------
    */

    $selected_role = null;

    if (
        empty($errors) &&
        $role_id > 0
    ) {

        $role_check_stmt =
            $pdo->prepare("
                SELECT
                    id,
                    name,
                    display_name
                FROM roles
                WHERE id = ?
                LIMIT 1
            ");

        $role_check_stmt->execute([
            $role_id
        ]);

        $selected_role =
            $role_check_stmt->fetch();

        if (!$selected_role) {

            $errors[] =
                'Invalid role selected.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Email
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $check_stmt = $pdo->prepare("
            SELECT
                id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $check_stmt->execute([
            $email
        ]);

        if ($check_stmt->fetch()) {

            $errors[] =
                'An account with this email already exists.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create Staff Account
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            /*
            | Start transaction.
            |
            | This is important because if a new active
            | Telecaller is created but redistribution fails,
            | the new user creation will also be rolled back.
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Hash Password
            |--------------------------------------------------------------------------
            */

            $hashed_password =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /*
            |--------------------------------------------------------------------------
            | Insert User
            |--------------------------------------------------------------------------
            */

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
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $insert_stmt->execute([
                $name,
                $email,
                $hashed_password,
                $role_id,
                $status
            ]);


            /*
            |--------------------------------------------------------------------------
            | New User ID
            |--------------------------------------------------------------------------
            */

            $new_user_id =
                (int) $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | Automatic Lead Redistribution
            |--------------------------------------------------------------------------
            |
            | Only an ACTIVE Telecaller triggers
            | complete active-lead redistribution.
            |
            | Example:
            |
            | A = 150
            | B = 150
            |
            | Add D
            |
            | A = 100
            | B = 100
            | D = 100
            |
            */

            if (
                $selected_role &&
                strtolower(
                    $selected_role['name']
                ) === 'telecaller' &&
                $status === 'active'
            ) {

                $rebalance_result =
                    rebalance_all_active_leads(
                        $pdo,
                        $current_admin_id
                    );


                /*
                |--------------------------------------------------------------------------
                | Verify Redistribution
                |--------------------------------------------------------------------------
                */

                if (
                    empty(
                        $rebalance_result['success']
                    )
                ) {

                    throw new RuntimeException(
                        'Unable to rebalance active leads.'
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Success Redirect
            |--------------------------------------------------------------------------
            */

            header(
                'Location: ' .
                BASE_URL .
                '/admin/users/index.php'
            );

            exit;


        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Rollback
            |--------------------------------------------------------------------------
            */

            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();
            }


            /*
            |--------------------------------------------------------------------------
            | User-Friendly Error
            |--------------------------------------------------------------------------
            */

            $errors[] =
                'Unable to create staff account. ' .
                $e->getMessage();
        }
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

            <strong>
                Please fix the following:
            </strong>

            <ul class="mb-0 mt-2">

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


    <div class="hm-card p-4">

        <form method="POST">

            <div class="row g-3">


                <!-- NAME -->

                <div class="col-md-6">

                    <label class="form-label">
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?php
                        echo htmlspecialchars(
                            $name
                        );
                        ?>"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="col-md-6">

                    <label class="form-label">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?php
                        echo htmlspecialchars(
                            $email
                        );
                        ?>"
                        required
                    >

                </div>


                <!-- PASSWORD -->

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


                <!-- CONFIRM PASSWORD -->

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


                <!-- ROLE -->

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

                        <?php foreach (
                            $roles as $role
                        ): ?>

                            <option
                                value="<?php
                                echo (int) $role['id'];
                                ?>"
                                <?php
                                echo (
                                    $role_id ==
                                    $role['id']
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


                <!-- STATUS -->

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


                <!-- INFO -->

                <div class="col-12">

                    <div class="alert alert-info mb-0">

                        <strong>
                            Telecaller workload balancing:
                        </strong>

                        When an active Telecaller is added,
                        all active leads will automatically
                        be redistributed equally among the
                        active Telecallers.

                        <br><br>

                        Converted and Lost leads are excluded
                        from redistribution.

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="col-12 mt-4">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Create Staff Account
                    </button>

                    <a
                        href="<?php
                        echo BASE_URL;
                        ?>/admin/users/index.php"
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