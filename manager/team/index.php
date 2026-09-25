<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');


/*
|--------------------------------------------------------------------------
| Role Filter
|--------------------------------------------------------------------------
*/

$role_filter = trim($_GET['role'] ?? '');


/*
|--------------------------------------------------------------------------
| Build Query
|--------------------------------------------------------------------------
*/

$sql = "
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
    WHERE u.status = 'active'
      AND r.name IN ('telecaller', 'marketing')
";

$params = [];


/*
|--------------------------------------------------------------------------
| Search Filter
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| Role Filter
|--------------------------------------------------------------------------
*/

if (
    $role_filter !== '' &&
    in_array(
        $role_filter,
        ['telecaller', 'marketing'],
        true
    )
) {

    $sql .= " AND r.name = ? ";

    $params[] = $role_filter;
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY u.name ASC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$team_members = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Team Counts
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        r.name AS role_name,
        COUNT(u.id) AS total
    FROM roles r
    LEFT JOIN users u
        ON u.role_id = r.id
        AND u.status = 'active'
    WHERE r.name IN ('telecaller', 'marketing')
    GROUP BY r.id, r.name
");

$role_counts = [];

foreach ($stmt->fetchAll() as $row) {

    $role_counts[$row['role_name']] =
        (int) $row['total'];
}


$telecaller_count =
    $role_counts['telecaller'] ?? 0;

$marketing_count =
    $role_counts['marketing'] ?? 0;

$total_team =
    $telecaller_count +
    $marketing_count;


$page_title = 'Marketing Team';

require_once __DIR__ . '/../../includes/header.php';

?>


<div class="container py-4">


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Marketing Team
            </h2>

            <p class="hm-muted mb-0">
                Monitor your active Telecaller and Marketing Executive team.
            </p>

        </div>

        <a
            href="<?php echo BASE_URL; ?>/manager/dashboard.php"
            class="btn btn-outline-secondary"
        >
            Back to Dashboard
        </a>

    </div>


    <!-- Team Summary -->

    <div class="row g-3 mb-4">


        <!-- Total Team -->

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Active Team
                </div>

                <h2 class="mb-0">
                    <?php echo $total_team; ?>
                </h2>

            </div>

        </div>


        <!-- Telecallers -->

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Telecallers
                </div>

                <h2 class="mb-0">
                    <?php echo $telecaller_count; ?>
                </h2>

            </div>

        </div>


        <!-- Marketing -->

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Marketing Executives
                </div>

                <h2 class="mb-0">
                    <?php echo $marketing_count; ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- Search -->

    <div class="hm-card p-4 mb-4">

        <form method="GET">

            <div class="row g-3 align-items-end">


                <div class="col-md-6">

                    <label
                        for="search"
                        class="form-label"
                    >
                        Search Team Member
                    </label>

                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        placeholder="Search by name or email"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <div class="col-md-3">

                    <label
                        for="role"
                        class="form-label"
                    >
                        Role
                    </label>

                    <select
                        name="role"
                        id="role"
                        class="form-select"
                    >

                        <option value="">
                            All Roles
                        </option>

                        <option
                            value="telecaller"
                            <?php
                            echo $role_filter === 'telecaller'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Telecaller
                        </option>

                        <option
                            value="marketing"
                            <?php
                            echo $role_filter === 'marketing'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Marketing Executive
                        </option>

                    </select>

                </div>


                <div class="col-md-3 d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Search
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/manager/team/"
                        class="btn btn-outline-secondary"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    <!-- Team Table -->

    <div class="hm-card p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="mb-0">
                Team Members
            </h5>

            <span class="hm-muted">
                <?php echo count($team_members); ?> member(s)
            </span>

        </div>


        <?php if (empty($team_members)): ?>

            <div class="alert alert-light mb-0">

                No active team members found.

            </div>

        <?php else: ?>


            <div class="table-responsive">

                <table class="table align-middle mb-0">

                    <thead>

                        <tr>

                            <th>
                                Name
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Joined
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($team_members as $member): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $member['name']
                                        );
                                        ?>
                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $member['email']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <span class="badge bg-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $member['role_display_name']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo date(
                                        'd M Y',
                                        strtotime(
                                            $member['created_at']
                                        )
                                    );
                                    ?>

                                </td>


                                <td>

                                    <span class="badge bg-success">
                                        Active
                                    </span>

                                </td>


                                <td>

                                    <a
                                        href="<?php echo BASE_URL; ?>/manager/team/profile.php?id=<?php echo (int) $member['id']; ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        View Profile
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>

    </div>

</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>