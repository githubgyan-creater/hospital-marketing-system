 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('admin');

$page_title = 'User Management';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <!-- ========================================================= -->
    <!-- PAGE HEADER -->
    <!-- ========================================================= -->

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                User Management
            </h1>

            <p class="hm-muted mb-0">
                Manage hospital marketing team accounts.
            </p>

        </div>

        <a
            href="<?php echo BASE_URL; ?>/admin/users/add.php"
            class="btn btn-hm-primary"
        >
            + Add Staff
        </a>

    </div>


    <!-- ========================================================= -->
    <!-- USER TABLE -->
    <!-- ========================================================= -->

    <div class="hm-card p-4">

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>

                    <tr>

                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php

                $stmt = $pdo->query("
                    SELECT
                        u.id,
                        u.name,
                        u.email,
                        u.status,
                        u.created_at,
                        r.display_name AS role_name

                    FROM users u

                    INNER JOIN roles r
                        ON u.role_id = r.id

                    ORDER BY u.id DESC
                ");

                $users = $stmt->fetchAll();

                ?>


                <?php if (empty($users)): ?>

                    <tr>

                        <td
                            colspan="7"
                            class="text-center text-muted py-4"
                        >
                            No staff accounts found.
                        </td>

                    </tr>

                <?php else: ?>


                    <?php foreach ($users as $index => $user): ?>

                        <tr>

                            <!-- Number -->

                            <td>

                                <?php
                                echo $index + 1;
                                ?>

                            </td>


                            <!-- Name -->

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $user['name']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- Email -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $user['email']
                                );
                                ?>

                            </td>


                            <!-- Role -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $user['role_name']
                                );
                                ?>

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


                            <!-- Created -->

                            <td>

                                <?php
                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $user['created_at']
                                    )
                                );
                                ?>

                            </td>


                            <!-- Actions -->

                            <td>

                                <div class="d-flex flex-wrap gap-2">

                                    <!-- View -->

                                    <a
                                        href="<?php echo BASE_URL; ?>/admin/users/view.php?id=<?php echo (int) $user['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        View
                                    </a>


                                    <!-- Edit -->

                                    <a
                                        href="<?php echo BASE_URL; ?>/admin/users/edit.php?id=<?php echo (int) $user['id']; ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Edit
                                    </a>

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