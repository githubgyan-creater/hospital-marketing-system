 <?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('admin');

$user = current_user();

/*
|--------------------------------------------------------------------------
| Fetch Roles
|--------------------------------------------------------------------------
| Count how many permissions are assigned to each role.
|--------------------------------------------------------------------------
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

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    <link
        rel="stylesheet"
        href="<?= BASE_URL; ?>/assets/css/style.css"
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

        .role-description {
            color: #71808c;
            font-size: 14px;
        }

    </style>

</head>

<body>

<?php

require_once __DIR__ . '/../../includes/navbar.php';

?>


<div class="container py-4">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="page-title mb-1">
                Roles & Permissions
            </h1>

            <p class="text-muted mb-0">
                Manage role-based access for the hospital marketing team.
            </p>

        </div>

    </div>


    <!-- =====================================================
         ROLES TABLE
    ====================================================== -->

    <div class="card page-card">

        <div class="card-body">


            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle mb-0">

                    <thead>

                        <tr>

                            <th width="80">
                                ID
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Display Name
                            </th>

                            <th>
                                Permissions
                            </th>

                            <th>
                                Created On
                            </th>

                            <th width="130">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (!empty($roles)): ?>


                        <?php foreach ($roles as $role): ?>

                            <tr>


                                <!-- ID -->

                                <td>

                                    <?= (int) $role['id']; ?>

                                </td>


                                <!-- Role Code -->

                                <td>

                                    <span class="badge role-badge">

                                        <?= htmlspecialchars(
                                            $role['name']
                                        ); ?>

                                    </span>

                                </td>


                                <!-- Display Name -->

                                <td>

                                    <div class="fw-semibold">

                                        <?= htmlspecialchars(
                                            $role['display_name']
                                        ); ?>

                                    </div>

                                </td>


                                <!-- Permission Count -->

                                <td>

                                    <span class="badge permission-count">

                                        <?= (int) $role['permission_count']; ?>

                                        permissions

                                    </span>

                                </td>


                                <!-- Created Date -->

                                <td>

                                    <?php

                                    $created_at = $role['created_at'];

                                    if (!empty($created_at)) {

                                        echo htmlspecialchars(
                                            date(
                                                'd M Y',
                                                strtotime($created_at)
                                            )
                                        );

                                    } else {

                                        echo '-';

                                    }

                                    ?>

                                </td>


                                <!-- Action -->

                                <td>

                                    <a
                                        href="<?= BASE_URL; ?>/admin/roles/edit.php?id=<?= (int) $role['id']; ?>"
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