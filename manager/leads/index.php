 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Process Lead Assignment
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lead_id = (int) ($_POST['lead_id'] ?? 0);
    $assigned_to = (int) ($_POST['assigned_to'] ?? 0);

    if ($lead_id <= 0) {

        $error = 'Invalid lead selected.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Get Current Lead
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                assigned_to
            FROM leads
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $lead_id
        ]);

        $lead = $stmt->fetch();

        if (!$lead) {

            $error = 'Lead not found.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Validate Assigned User
            |--------------------------------------------------------------------------
            */

            if ($assigned_to === 0) {

                $new_assignee = null;

            } else {

                $stmt = $pdo->prepare("
                    SELECT
                        u.id,
                        u.name,
                        r.name AS role_name
                    FROM users u
                    INNER JOIN roles r
                        ON u.role_id = r.id
                    WHERE u.id = ?
                      AND u.status = 'active'
                      AND r.name IN ('telecaller', 'marketing')
                    LIMIT 1
                ");

                $stmt->execute([
                    $assigned_to
                ]);

                $new_assignee = $stmt->fetch();

                if (!$new_assignee) {

                    $error =
                        'Please select a valid Telecaller or Marketing Executive.';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Save Assignment
            |--------------------------------------------------------------------------
            */

            if ($error === '') {

                try {

                    $pdo->beginTransaction();

                    /*
                    |--------------------------------------------------------------------------
                    | Update Lead
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $pdo->prepare("
                        UPDATE leads
                        SET assigned_to = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $assigned_to > 0
                            ? $assigned_to
                            : null,
                        $lead_id
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Create Activity
                    |--------------------------------------------------------------------------
                    */

                    if ($assigned_to > 0) {

                        $activity_description =
                            'Lead assigned to ' .
                            $new_assignee['name'] .
                            ' (' .
                            $new_assignee['role_name'] .
                            ').';

                    } else {

                        $activity_description =
                            'Lead assignment removed.';
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO lead_activities (
                            lead_id,
                            user_id,
                            activity_type,
                            description,
                            activity_at
                        )
                        VALUES (
                            ?,
                            ?,
                            'Lead Assigned',
                            ?,
                            NOW()
                        )
                    ");

                    $stmt->execute([
                        $lead_id,
                        $user['id'],
                        $activity_description
                    ]);

                    $pdo->commit();

                    $success =
                        'Lead assignment updated successfully.';

                } catch (PDOException $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $error =
                        'Unable to update lead assignment.';
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

$status_filter = trim($_GET['status'] ?? '');

/*
|--------------------------------------------------------------------------
| Priority Filter
|--------------------------------------------------------------------------
*/

$priority_filter = trim($_GET['priority'] ?? '');

/*
|--------------------------------------------------------------------------
| Assignment Filter
|--------------------------------------------------------------------------
*/

$assignment_filter = trim($_GET['assignment'] ?? '');

/*
|--------------------------------------------------------------------------
| Get Staff
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        r.name AS role_name
    FROM users u
    INNER JOIN roles r
        ON u.role_id = r.id
    WHERE u.status = 'active'
      AND r.name IN ('telecaller', 'marketing')
    ORDER BY u.name ASC
");

$staff_members = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Build Leads Query
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        l.id,
        l.name,
        l.phone,
        l.email,
        l.service_interest,
        l.status,
        l.priority,
        l.assigned_to,
        l.next_action_type,
        l.next_action_at,
        l.created_at,

        ms.name AS source_name,

        c.name AS campaign_name,

        ref.name AS referral_name,
        ref.referral_type AS referral_type,
        ref.organization AS referral_organization,

        u.name AS assigned_name,

        r.name AS assigned_role

    FROM leads l

    LEFT JOIN marketing_sources ms
        ON l.source_id = ms.id

    LEFT JOIN campaigns c
        ON l.campaign_id = c.id

    LEFT JOIN referrals ref
        ON l.referral_id = ref.id

    LEFT JOIN users u
        ON l.assigned_to = u.id

    LEFT JOIN roles r
        ON u.role_id = r.id

    WHERE 1 = 1

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

            l.name LIKE ?

            OR l.phone LIKE ?

            OR l.email LIKE ?

        )

    ";

    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if ($status_filter !== '') {

    $sql .= "

        AND l.status = ?

    ";

    $params[] = $status_filter;
}

/*
|--------------------------------------------------------------------------
| Priority Filter
|--------------------------------------------------------------------------
*/

if (
    $priority_filter !== '' &&
    in_array(
        $priority_filter,
        ['Low', 'Medium', 'High'],
        true
    )
) {

    $sql .= "

        AND l.priority = ?

    ";

    $params[] = $priority_filter;
}

/*
|--------------------------------------------------------------------------
| Assignment Filter
|--------------------------------------------------------------------------
*/

if ($assignment_filter === 'unassigned') {

    $sql .= "

        AND l.assigned_to IS NULL

    ";

} elseif ($assignment_filter === 'assigned') {

    $sql .= "

        AND l.assigned_to IS NOT NULL

    ";
}

/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "

    ORDER BY l.created_at DESC

";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$leads = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Summary Counts
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        COUNT(*) AS total_leads,

        SUM(status = 'New') AS new_leads,

        SUM(assigned_to IS NULL) AS unassigned_leads,

        SUM(
            next_action_at IS NOT NULL
            AND next_action_at < NOW()
        ) AS overdue_leads

    FROM leads
");

$summary = $stmt->fetch();

$total_leads =
    (int) ($summary['total_leads'] ?? 0);

$new_leads =
    (int) ($summary['new_leads'] ?? 0);

$unassigned_leads =
    (int) ($summary['unassigned_leads'] ?? 0);

$overdue_leads =
    (int) ($summary['overdue_leads'] ?? 0);

$page_title = 'Lead Management';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Lead Management
            </h2>

            <p class="hm-muted mb-0">
                Monitor leads and manage team assignments.
            </p>

        </div>

        <div class="d-flex gap-2">

            <a
                href="<?php echo BASE_URL; ?>/leads/add.php"
                class="btn btn-hm-primary"
            >
                + Add Lead
            </a>

            <a
                href="<?php echo BASE_URL; ?>/manager/dashboard.php"
                class="btn btn-outline-secondary"
            >
                Dashboard
            </a>

        </div>

    </div>

    <!-- Summary Cards -->

    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $total_leads; ?>
                </h2>

            </div>

        </div>

        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    New Leads
                </div>

                <h2 class="mb-0">
                    <?php echo $new_leads; ?>
                </h2>

            </div>

        </div>

        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Unassigned
                </div>

                <h2 class="mb-0 text-warning">
                    <?php echo $unassigned_leads; ?>
                </h2>

            </div>

        </div>

        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Overdue
                </div>

                <h2 class="mb-0 text-danger">
                    <?php echo $overdue_leads; ?>
                </h2>

            </div>

        </div>

    </div>

    <!-- Messages -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?php
            echo htmlspecialchars($success);
            ?>

        </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>

    <!-- Filters -->

    <div class="hm-card p-4 mb-4">

        <form method="GET">

            <div class="row g-3 align-items-end">

                <div class="col-lg-4">

                    <label
                        for="search"
                        class="form-label"
                    >
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        placeholder="Name, phone or email"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>

                <div class="col-md-2">

                    <label
                        for="status"
                        class="form-label"
                    >
                        Status
                    </label>

                    <select
                        name="status"
                        id="status"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <?php

                        $statuses = [
                            'New',
                            'Contacted',
                            'Follow-up Required',
                            'Interested',
                            'Not Interested',
                            'Appointment Requested'
                        ];

                        ?>

                        <?php foreach ($statuses as $status): ?>

                            <option
                                value="<?php echo htmlspecialchars($status); ?>"
                                <?php
                                echo $status_filter === $status
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars($status);
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-2">

                    <label
                        for="priority"
                        class="form-label"
                    >
                        Priority
                    </label>

                    <select
                        name="priority"
                        id="priority"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <?php foreach (['Low', 'Medium', 'High'] as $priority): ?>

                            <option
                                value="<?php echo htmlspecialchars($priority); ?>"
                                <?php
                                echo $priority_filter === $priority
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars($priority);
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-2">

                    <label
                        for="assignment"
                        class="form-label"
                    >
                        Assignment
                    </label>

                    <select
                        name="assignment"
                        id="assignment"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <option
                            value="assigned"
                            <?php
                            echo $assignment_filter === 'assigned'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Assigned
                        </option>

                        <option
                            value="unassigned"
                            <?php
                            echo $assignment_filter === 'unassigned'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Unassigned
                        </option>

                    </select>

                </div>

                <div class="col-md-2 d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Filter
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/manager/leads/"
                        class="btn btn-outline-secondary"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>

    <!-- Lead Table -->

    <div class="hm-card p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="mb-0">
                Leads
            </h5>

            <span class="hm-muted">
                <?php echo count($leads); ?> lead(s)
            </span>

        </div>

        <?php if (empty($leads)): ?>

            <div class="alert alert-light mb-0">
                No leads found.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>
                                Lead
                            </th>

                            <th>
                                Contact
                            </th>

                            <th>
                                Service
                            </th>

                            <th>
                                Campaign
                            </th>

                            <th>
                                Referral Partner
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Priority
                            </th>

                            <th>
                                Assigned To
                            </th>

                            <th>
                                Next Action
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($leads as $lead): ?>

                            <tr>

                                <!-- Lead -->

                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['name']
                                        );

                                        ?>

                                    </strong>

                                </td>

                                <!-- Contact -->

                                <td>

                                    <div>

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['phone']
                                        );

                                        ?>

                                    </div>

                                    <?php if (!empty($lead['email'])): ?>

                                        <small class="hm-muted">

                                            <?php

                                            echo htmlspecialchars(
                                                $lead['email']
                                            );

                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </td>

                                <!-- Service -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $lead['service_interest']
                                        ?: '-'
                                    );

                                    ?>

                                </td>

                                <!-- Campaign -->

                                <td>

                                    <?php if (!empty($lead['campaign_name'])): ?>

                                        <span>

                                            <?php

                                            echo htmlspecialchars(
                                                $lead['campaign_name']
                                            );

                                            ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="hm-muted">
                                            -
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- Referral Partner -->

                                <td style="min-width: 180px;">

                                    <?php if (!empty($lead['referral_name'])): ?>

                                        <div>

                                            <strong>

                                                <?php

                                                echo htmlspecialchars(
                                                    $lead['referral_name']
                                                );

                                                ?>

                                            </strong>

                                        </div>

                                        <?php if (!empty($lead['referral_type'])): ?>

                                            <small class="hm-muted">

                                                <?php

                                                echo htmlspecialchars(
                                                    $lead['referral_type']
                                                );

                                                ?>

                                            </small>

                                        <?php endif; ?>

                                        <?php if (!empty($lead['referral_organization'])): ?>

                                            <div>

                                                <small class="hm-muted">

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $lead['referral_organization']
                                                    );

                                                    ?>

                                                </small>

                                            </div>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        <span class="hm-muted">
                                            -
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- Status -->

                                <td>

                                    <span class="badge bg-secondary">

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['status']
                                        );

                                        ?>

                                    </span>

                                </td>

                                <!-- Priority -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $lead['priority']
                                    );

                                    ?>

                                </td>

                                <!-- Assignment -->

                                <td style="min-width: 210px;">

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="lead_id"
                                            value="<?php echo (int) $lead['id']; ?>"
                                        >

                                        <select
                                            name="assigned_to"
                                            class="form-select form-select-sm"
                                            onchange="this.form.submit()"
                                        >

                                            <option value="0">
                                                Unassigned
                                            </option>

                                            <?php foreach ($staff_members as $staff): ?>

                                                <option
                                                    value="<?php echo (int) $staff['id']; ?>"
                                                    <?php
                                                    echo (int) $lead['assigned_to']
                                                        === (int) $staff['id']
                                                        ? 'selected'
                                                        : '';
                                                    ?>
                                                >

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $staff['name']
                                                    );

                                                    ?>

                                                    -

                                                    <?php

                                                    echo htmlspecialchars(
                                                        ucfirst(
                                                            $staff['role_name']
                                                        )
                                                    );

                                                    ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </form>

                                </td>

                                <!-- Next Action -->

                                <td>

                                    <?php if (!empty($lead['next_action_at'])): ?>

                                        <div>

                                            <?php

                                            echo htmlspecialchars(
                                                $lead['next_action_type']
                                                ?: 'Action'
                                            );

                                            ?>

                                        </div>

                                        <small class="hm-muted">

                                            <?php

                                            echo date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $lead['next_action_at']
                                                )
                                            );

                                            ?>

                                        </small>

                                    <?php else: ?>

                                        <span class="hm-muted">
                                            No next action
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- Actions -->

                                <td>

                                    <a
                                        href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="<?php echo BASE_URL; ?>/leads/edit.php?id=<?php echo (int) $lead['id']; ?>"
                                        class="btn btn-sm btn-outline-primary mt-1"
                                    >
                                        Edit
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