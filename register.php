<?php

require_once __DIR__ . '/config/database.php';

$page_title = 'Administrator Registration';

$message = '';
$message_type = '';

/*

Check whether an admin already exists

*/

$admin_check = $pdo->query("
    SELECT COUNT(*)
    FROM users u
    INNER JOIN roles r ON u.role_id = r.id
    WHERE r.name = 'admin'
")->fetchColumn();

$admin_exists = ((int) $admin_check > 0);


/*

Process registration form

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    /*
    
     Validation
    
    */

    if ($admin_exists) {

        $message = 'Administrator account already exists.';
        $message_type = 'danger';

    } elseif ($name === '' || $email === '' || $password === '' || $confirm_password === '') {

        $message = 'Please fill in all fields.';
        $message_type = 'danger';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = 'Please enter a valid email address.';
        $message_type = 'danger';

    } elseif (strlen($password) < 8) {

        $message = 'Password must contain at least 8 characters.';
        $message_type = 'danger';

    } elseif ($password !== $confirm_password) {

        $message = 'Passwords do not match.';
        $message_type = 'danger';

    } else {

        /*
        
         Check whether email already exists
        
        */

        $email_check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $email_check->execute([
            'email' => $email
        ]);

        $existing_user = $email_check->fetch();


        if ($existing_user) {

            $message = 'This email address is already registered.';
            $message_type = 'danger';

        } else {

            /*
            
             Get Admin Role
            
            */

            $role_stmt = $pdo->prepare("
                SELECT id
                FROM roles
                WHERE name = 'admin'
                LIMIT 1
            ");

            $role_stmt->execute();

            $role_id = $role_stmt->fetchColumn();


            if (!$role_id) {

                $message = 'Administrator role was not found.';
                $message_type = 'danger';

            } else {

                /*
                
                 Hash Password
                
                */

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /*
                
                 Insert Administrator
                
                */

                $insert = $pdo->prepare("
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
                        'active'
                    )
                ");

                $insert->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashed_password,
                    'role_id' => $role_id
                ]);


                $message = 'Administrator account created successfully.';
                $message_type = 'success';

                $admin_exists = true;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-5 col-md-7">

            <div class="hm-card p-4 p-md-5">

                <div class="text-center mb-4">

                    <span class="badge text-bg-light mb-3">
                        Initial Setup
                    </span>

                    <h1 class="hm-page-title h3">
                        Create Administrator
                    </h1>

                    <p class="hm-muted mb-0">
                        Create the first administrator account
                        for the Hospital Marketing System.
                    </p>

                </div>


                <?php if ($message !== ''): ?>

                    <div
                        class="alert alert-<?php echo htmlspecialchars($message_type); ?>"
                        role="alert"
                    >
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                <?php endif; ?>


                <?php if (!$admin_exists): ?>

                    <form method="POST">

                        <div class="mb-3">

                            <label
                                for="name"
                                class="form-label"
                            >
                                Full Name
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="name"
                                name="name"
                                placeholder="Enter full name"
                                required
                            >

                        </div>


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
                                placeholder="admin@hospital.com"
                                required
                            >

                        </div>


                        <div class="mb-3">

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
                                minlength="8"
                                placeholder="Minimum 8 characters"
                                required
                            >

                        </div>


                        <div class="mb-4">

                            <label
                                for="confirm_password"
                                class="form-label"
                            >
                                Confirm Password
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="8"
                                placeholder="Re-enter password"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-hm-primary w-100"
                        >
                            Create Administrator
                        </button>

                    </form>

                <?php else: ?>

                    <div class="text-center">

                        <a
                            href="<?php echo BASE_URL; ?>/login.php"
                            class="btn btn-hm-primary"
                        >
                            Go to Login
                        </a>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/includes/footer.php';

?>