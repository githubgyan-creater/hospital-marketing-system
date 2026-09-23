<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = 'Login';

$message = '';
$message_type = '';


/*
|--------------------------------------------------------------------------
| Process Login
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Basic validation
    |--------------------------------------------------------------------------
    */

    if ($email === '' || $password === '') {

        $message = 'Please enter your email and password.';
        $message_type = 'danger';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Find user
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.name,
                u.email,
                u.password,
                u.status,
                r.name AS role_name
            FROM users u
            INNER JOIN roles r
                ON u.role_id = r.id
            WHERE u.email = :email
            LIMIT 1
        ");

        $stmt->execute([
            'email' => $email
        ]);

        $user = $stmt->fetch();


        /*
        |--------------------------------------------------------------------------
        | Check user and password
        |--------------------------------------------------------------------------
        */

        if (
            $user &&
            $user['status'] === 'active' &&
            password_verify($password, $user['password'])
        ) {

            login_user($user);

            /*
            |--------------------------------------------------------------------------
            | Redirect Admin
            |--------------------------------------------------------------------------
            */

            if ($user['role_name'] === 'admin') {

                header(
                    'Location: ' .
                    BASE_URL .
                    '/admin/dashboard.php'
                );

                exit;
            }

            /*
            |--------------------------------------------------------------------------
            | Other roles will be added later
            |--------------------------------------------------------------------------
            */

            $message = 'Login successful, but this role dashboard is not ready yet.';
            $message_type = 'success';

        } else {

            $message = 'Invalid email or password.';
            $message_type = 'danger';
        }
    }
}


require_once __DIR__ . '/includes/header.php';

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-4 col-md-6">

            <div class="hm-card p-4 p-md-5">

                <div class="text-center mb-4">

                    <span class="badge text-bg-light mb-3">
                        Secure Access
                    </span>

                    <h1 class="hm-page-title h3">
                        Staff Login
                    </h1>

                    <p class="hm-muted mb-0">
                        Sign in to the Hospital Marketing System.
                    </p>

                </div>


                <?php if ($message !== ''): ?>

                    <div
                        class="alert alert-<?php echo htmlspecialchars($message_type); ?>"
                    >
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                <?php endif; ?>


                <form method="POST">

                    <div class="mb-3">

                        <label
                            for="email"
                            class="form-label"
                        >
                            Email Address
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            required
                        >

                    </div>


                    <div class="mb-4">

                        <label
                            for="password"
                            class="form-label"
                        >
                            Password
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="btn btn-hm-primary w-100"
                    >
                        Login
                    </button>

                </form>


                <div class="text-center mt-4">

                    <small class="hm-muted">
                        Administrator setup is available only
                        during initial system configuration.
                    </small>

                </div>

            </div>

        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/includes/footer.php';

?>