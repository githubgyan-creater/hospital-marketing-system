<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permission_check.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('admin');
require_permission('users.view');
$page_title = 'Staff Details';


/*

| Get User ID

*/

$user_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$user_id) {
    die('Invalid user ID.');
}


/*

| Get User Details

*/

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.name,
        u.email,
        u.status,
        u.created_at,
        r.name AS role_name,
        r.display_name AS role_display_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.id = ?

    LIMIT 1
");

$stmt->execute([
    $user_id
]);

$staff = $stmt->fetch();


/*

| Check User Exists

*/

if (!$staff) {
    die('Staff account not found.');
}


require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">


    
    <!-- BACK -->
    

    <div class="mb-3">

        <a
            href="<?php echo BASE_URL; ?>/admin/users/index.php"
            class="text-decoration-none"
        >
            ← Back to User Management
        </a>

    </div>


    
    <!-- PAGE HEADER -->
    

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                Staff Details
            </h1>

            <p class="hm-muted mb-0">
                View hospital marketing team account details.
            </p>

        </div>


        <a
            href="<?php echo BASE_URL; ?>/admin/users/edit.php?id=<?php echo (int) $staff['id']; ?>"
            class="btn btn-hm-primary"
        >
            Edit Staff
        </a>

    </div>


    
    <!-- STAFF DETAILS -->
    

    <div class="hm-card p-4">

        <div class="row g-4">


            <!-- ID -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    User ID
                </div>

                <div class="fw-semibold">

                    #
                    <?php
                    echo (int) $staff['id'];
                    ?>

                </div>

            </div>


            <!-- Name -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Full Name
                </div>

                <div class="fw-semibold">

                    <?php
                    echo htmlspecialchars(
                        $staff['name']
                    );
                    ?>

                </div>

            </div>


            <!-- Email -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Email
                </div>

                <div class="fw-semibold">

                    <?php
                    echo htmlspecialchars(
                        $staff['email']
                    );
                    ?>

                </div>

            </div>


            <!-- Role -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Role
                </div>

                <div>

                    <span class="badge bg-secondary">

                        <?php
                        echo htmlspecialchars(
                            $staff['role_display_name']
                        );
                        ?>

                    </span>

                </div>

            </div>


            <!-- Status -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Account Status
                </div>

                <div>

                    <?php if ($staff['status'] === 'active'): ?>

                        <span class="badge bg-success">
                            Active
                        </span>

                    <?php else: ?>

                        <span class="badge bg-secondary">
                            Inactive
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- Created -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    Account Created
                </div>

                <div class="fw-semibold">

                    <?php
                    echo date(
                        'd M Y, h:i A',
                        strtotime(
                            $staff['created_at']
                        )
                    );
                    ?>

                </div>

            </div>


            <!-- Internal Role -->

            <div class="col-md-6">

                <div class="hm-muted small">
                    System Role
                </div>

                <div class="fw-semibold">

                    <?php
                    echo htmlspecialchars(
                        $staff['role_name']
                    );
                    ?>

                </div>

            </div>


        </div>

    </div>


</div>

<?php

require_once __DIR__ . '/../../includes/footer.php';

?>