 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();

/*
|--------------------------------------------------------------------------
| Search & Filters
|--------------------------------------------------------------------------
*/
$search = trim($_GET['search'] ?? '');
$role_filter = trim($_GET['role'] ?? '');

/*
|--------------------------------------------------------------------------
| Team Members Query
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
| Search
|--------------------------------------------------------------------------
*/
if ($search !== '') {
    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $search_term = '%' . $search . '%';

    $params[] = $search_term;
    $params[] = $search_term;
}

/*
|--------------------------------------------------------------------------
| Role Filter
|--------------------------------------------------------------------------
*/
if (
    $role_filter !== '' &&
    in_array($role_filter, ['telecaller', 'marketing'], true)
) {
    $sql .= " AND r.name = ? ";
    $params[] = $role_filter;
}

$sql .= " ORDER BY u.name ASC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Team Summary Counts
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

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $role_counts[$row['role_name']] = (int) $row['total'];
}

$telecaller_count = $role_counts['telecaller'] ?? 0;
$marketing_count = $role_counts['marketing'] ?? 0;

$total_team = $telecaller_count + $marketing_count;

/*
|--------------------------------------------------------------------------
| Team Workload Statistics
|--------------------------------------------------------------------------
*/
$team_stats = [];

foreach ($team_members as $member) {

    $member_id = (int) $member['id'];

    /*
    |--------------------------------------------------------------------------
    | Assigned Leads
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM leads
        WHERE assigned_to = ?
    ");

    $stmt->execute([$member_id]);

    $assigned_leads = (int) $stmt->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Overdue Follow-ups
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM leads
        WHERE assigned_to = ?
          AND next_action_at IS NOT NULL
          AND next_action_at < NOW()
          AND status NOT IN ('Converted', 'Lost')
    ");

    $stmt->execute([$member_id]);

    $overdue_followups = (int) $stmt->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Assigned Tasks
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tasks
        WHERE assigned_to = ?
          AND status NOT IN ('Completed', 'Cancelled')
    ");

    $stmt->execute([$member_id]);

    $active_tasks = (int) $stmt->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Activities
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM lead_activities
        WHERE user_id = ?
    ");

    $stmt->execute([$member_id]);

    $activities = (int) $stmt->fetchColumn();

    $team_stats[$member_id] = [
        'assigned_leads' => $assigned_leads,
        'overdue_followups' => $overdue_followups,
        'active_tasks' => $active_tasks,
        'activities' => $activities
    ];
}

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/
$page_title = 'Marketing Team';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="hm-page-title mb-1">
                Marketing Team
            </h1>

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


    <!-- Search & Filter -->
    <div class="hm-card p-4 mb-4">

        <form method="GET">

            <div class="row g-3 align-items-end">

                <!-- Search -->
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


                <!-- Role -->
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


                <!-- Buttons -->
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


    <!-- Team Members -->
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
                                Team Member
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Leads
                            </th>

                            <th>
                                Overdue
                            </th>

                            <th>
                                Tasks
                            </th>

                            <th>
                                Activities
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

                            <?php
                            $member_id = (int) $member['id'];

                            $stats = $team_stats[$member_id] ?? [
                                'assigned_leads' => 0,
                                'overdue_followups' => 0,
                                'active_tasks' => 0,
                                'activities' => 0
                            ];
                            ?>

                            <tr>

                                <!-- Team Member -->
                                <td>

                                    <div>
                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $member['name']
                                            );
                                            ?>
                                        </strong>
                                    </div>

                                    <small class="text-muted">
                                        <?php
                                        echo htmlspecialchars(
                                            $member['email']
                                        );
                                        ?>
                                    </small>

                                </td>


                                <!-- Role -->
                                <td>

                                    <span class="badge bg-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $member['role_display_name']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- Leads -->
                                <td>

                                    <strong>
                                        <?php
                                        echo $stats['assigned_leads'];
                                        ?>
                                    </strong>

                                </td>


                                <!-- Overdue Follow-ups -->
                                <td>

                                    <?php if ($stats['overdue_followups'] > 0): ?>

                                        <span class="badge bg-danger">
                                            <?php
                                            echo $stats['overdue_followups'];
                                            ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-success">
                                            0
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Tasks -->
                                <td>

                                    <span class="badge bg-light text-dark border">
                                        <?php
                                        echo $stats['active_tasks'];
                                        ?>
                                    </span>

                                </td>


                                <!-- Activities -->
                                <td>

                                    <?php
                                    echo $stats['activities'];
                                    ?>

                                </td>


                                <!-- Joined -->
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


                                <!-- Status -->
                                <td>

                                    <span class="badge bg-success">
                                        Active
                                    </span>

                                </td>


                                <!-- Action -->
                                <td>

                                    <a
                                        href="<?php echo BASE_URL; ?>/manager/team/profile.php?id=<?php echo $member_id; ?>"
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