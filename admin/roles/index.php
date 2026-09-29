<?php

require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('admin');

$user = current_user();

/*
|--------------------------------------------------------------------------
| Fetch Roles
|--------------------------------------------------------------------------
|
| Count how many permissions are assigned to each role.
|
*/
$stmt = $pdo->query("
    SELECT
        r.id,
        r.name,
        r.display_name,
        r.created_at,
        COUNT(rp.permission_id) AS permission_count
    FROM roles r
    LEFT JOIN role_permissions rp
        ON rp.role_id = r.id
    GROUP BY
        r.id,
        r.name,
        r.display_name,
        r.created_at
    ORDER BY r.id ASC
");

$roles = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Roles & Permissions</title>

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
            box-shadow: 0 4px 18px rgba(23, 50, 77, 0.08);
        }

        .table thead th {
            background: #17324d;
            color: #ffffff;
            white-space: nowrap;
        }

        .role-badge {
            background: #168a87;
            color: #ffffff;
        }

        .permission-count {
            background: #b89a5a;
            color: #ffffff;
            font-weight: 600;
        }

        .btn-primary {
            background: #17324d;
            border-color: #17324d;
        }

        .btn-primary:hover {
            background: #12283e;
            border-color: #12283e;
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

    <div class="mb-4">

        <h2 class="page-title mb-1">
            Roles & Permissions
        </h2>

        <p class="text-muted mb-0">
            Manage role-based access for the hospital marketing team.
        </p>

    </div>

    <div class="card page-card">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle mb-0">

                    <thead>

                        <tr>
                            <th width="80">ID</th>
                            <th>Role</th>
                            <th>Display Name</th>
                            <th>Permissions</th>
                            <th>Created On</th>
                            <th width="130">Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (!empty($roles)): ?>

                        <?php foreach ($roles as $role): ?>

                            <tr>

                                <td>
                                    <?php echo (int) $role['id']; ?>
                                </td>

                                <td>

                                    <span class="badge role-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $role['name']
                                        );
                                        ?>

                                    </span>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $role['display_name']
                                    );
                                    ?>
                                </td>

                                <td>

                                    <span class="badge permission-count">

                                        <?php
                                        echo (int)
                                            $role['permission_count'];
                                        ?>

                                        permissions

                                    </span>

                                </td>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $role['created_at']
                                            )
                                        )
                                    );
                                    ?>

                                </td>

                                <td>

                                    <a
                                        href="<?php echo BASE_URL; ?>/admin/roles/edit.php?id=<?php echo (int) $role['id']; ?>"
                                        class="btn btn-sm btn-primary"
                                    >
                                        Manage
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center text-muted py-4"
                            >
                                No roles found.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>