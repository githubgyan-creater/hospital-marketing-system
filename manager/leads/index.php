 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();

$error = '';
$success = '';


/*
|--------------------------------------------------------------------------
| Process Lead Deletion
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'delete_lead'
) {

    $delete_lead_id = (int) ($_POST['lead_id'] ?? 0);

    if ($delete_lead_id <= 0) {

        $error = 'Invalid lead selected.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Check Lead Exists
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    name
                FROM leads
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $delete_lead_id
            ]);

            $delete_lead = $stmt->fetch();

            if (!$delete_lead) {
                throw new Exception('Lead not found.');
            }


            /*
            |--------------------------------------------------------------------------
            | Start Transaction
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Delete Lead Activities
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                DELETE FROM lead_activities
                WHERE lead_id = ?
            ");

            $stmt->execute([
                $delete_lead_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | Delete Lead Calls
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                DELETE FROM lead_calls
                WHERE lead_id = ?
            ");

            $stmt->execute([
                $delete_lead_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | Delete Appointments
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                DELETE FROM appointments
                WHERE lead_id = ?
            ");

            $stmt->execute([
                $delete_lead_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | Delete Lead
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                DELETE FROM leads
                WHERE id = ?
            ");

            $stmt->execute([
                $delete_lead_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            $success = 'Lead deleted successfully.';

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Unable to delete lead.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Process Lead Assignment
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'assign_lead'
) {

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
        l.created_at,

        ms.name AS source_name,

        c.name AS campaign_name,

        u.name AS assigned_name,

        r.name AS assigned_role

    FROM leads l

    LEFT JOIN marketing_sources ms
        ON l.source_id = ms.id

    LEFT JOIN campaigns c
        ON l.campaign_id = c.id

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


<style>

/*
|--------------------------------------------------------------------------
| Lead Management Table
|--------------------------------------------------------------------------
*/

.leads-table-wrapper {
    width: 100%;
    overflow: hidden;
}

.leads-table {
    width: 100%;
    table-layout: fixed;
    margin-bottom: 0;
    font-size: 13px;
    line-height: 1.4;
}

.leads-table th {
    font-size: 13px;
    font-weight: 700;
    color: #212529;
    padding: 11px 8px;
    vertical-align: middle;
    white-space: nowrap;
}

.leads-table td {
    font-size: 13px;
    color: #212529;
    padding: 11px 8px;
    vertical-align: middle;
    overflow-wrap: anywhere;
    word-break: normal;
}

.leads-table tbody tr {
    border-bottom: 1px solid #dee2e6;
}

.leads-table tbody tr:last-child {
    border-bottom: none;
}


/*
|--------------------------------------------------------------------------
| Lead Name
|--------------------------------------------------------------------------
*/

.lead-name {
    font-weight: 700;
    font-size: 13px;
    line-height: 1.35;
}


/*
|--------------------------------------------------------------------------
| Contact
|--------------------------------------------------------------------------
*/

.lead-phone {
    font-weight: 600;
    white-space: nowrap;
}

.lead-email {
    display: block;
    margin-top: 3px;
    color: #6c757d;
    font-size: 12px;
    line-height: 1.35;
    overflow-wrap: anywhere;
}


/*
|--------------------------------------------------------------------------
| Service
|--------------------------------------------------------------------------
*/

.lead-service {
    font-weight: 500;
    line-height: 1.45;
}


/*
|--------------------------------------------------------------------------
| Campaign
|--------------------------------------------------------------------------
*/

.lead-campaign {
    line-height: 1.4;
}


/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

.lead-status {
    display: inline-block;
    max-width: 100%;
    padding: 4px 7px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.2;
    white-space: normal;
    text-align: center;
}


/*
|--------------------------------------------------------------------------
| Priority
|--------------------------------------------------------------------------
*/

.lead-priority {
    font-weight: 600;
}


/*
|--------------------------------------------------------------------------
| Assignment
|--------------------------------------------------------------------------
*/

.assignment-cell select {
    width: 100%;
    min-width: 0;
    font-size: 12px;
    padding: 6px 7px;
}


/*
|--------------------------------------------------------------------------
| Action Buttons
|--------------------------------------------------------------------------
*/

.action-cell {
    vertical-align: middle;
}

.lead-action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.lead-action-buttons .btn {
    font-size: 12px;
    font-weight: 500;
    padding: 5px 8px;
    white-space: nowrap;
}


/*
|--------------------------------------------------------------------------
| Table Card
|--------------------------------------------------------------------------
*/

.lead-table-card {
    overflow: hidden;
}


/*
|--------------------------------------------------------------------------
| Responsive
|--------------------------------------------------------------------------
*/

@media (max-width: 1100px) {

    .leads-table {
        font-size: 12px;
    }

    .leads-table th,
    .leads-table td {
        padding: 8px 5px;
    }

    .lead-phone {
        white-space: normal;
    }

    .lead-action-buttons {
        gap: 3px;
    }

    .lead-action-buttons .btn {
        font-size: 11px;
        padding: 4px 6px;
    }

}

</style>


<div class="container py-4">


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                Lead Management
            </h1>

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


    <!-- Success Message -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?php
            echo htmlspecialchars($success);
            ?>

        </div>

    <?php endif; ?>


    <!-- Error Message -->

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


                <!-- Search -->

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


                <!-- Status -->

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


                <!-- Priority -->

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


                        <?php foreach (
                            ['Low', 'Medium', 'High']
                            as $priority
                        ): ?>

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


                <!-- Assignment -->

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


                <!-- Filter Buttons -->

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

    <div class="hm-card p-4 lead-table-card">


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


            <div class="leads-table-wrapper">


                <table class="table align-middle leads-table">


                    <!-- Fixed Column Widths -->

                    <colgroup>

                        <!-- Lead -->
                        <col style="width: 13%;">

                        <!-- Contact -->
                        <col style="width: 17%;">

                        <!-- Service -->
                        <col style="width: 14%;">

                        <!-- Campaign -->
                        <col style="width: 9%;">

                        <!-- Status -->
                        <col style="width: 11%;">

                        <!-- Priority -->
                        <col style="width: 8%;">

                        <!-- Assigned To -->
                        <col style="width: 15%;">

                        <!-- Action -->
                        <col style="width: 13%;">

                    </colgroup>


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
                                Status
                            </th>

                            <th>
                                Priority
                            </th>

                            <th>
                                Assigned To
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

                                    <div class="lead-name">

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['name']
                                        );

                                        ?>

                                    </div>

                                </td>


                                <!-- Contact -->

                                <td>

                                    <div class="lead-phone">

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['phone']
                                        );

                                        ?>

                                    </div>


                                    <?php if (!empty($lead['email'])): ?>

                                        <span class="lead-email">

                                            <?php

                                            echo htmlspecialchars(
                                                $lead['email']
                                            );

                                            ?>

                                        </span>

                                    <?php endif; ?>


                                </td>


                                <!-- Service -->

                                <td>

                                    <div class="lead-service">

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['service_interest']
                                            ?: '-'
                                        );

                                        ?>

                                    </div>

                                </td>


                                <!-- Campaign -->

                                <td>

                                    <div class="lead-campaign">

                                        <?php if (
                                            !empty(
                                                $lead['campaign_name']
                                            )
                                        ): ?>

                                            <?php

                                            echo htmlspecialchars(
                                                $lead['campaign_name']
                                            );

                                            ?>

                                        <?php else: ?>

                                            <span class="hm-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- Status -->

                                <td>

                                    <span class="lead-status badge bg-secondary">

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['status']
                                        );

                                        ?>

                                    </span>

                                </td>


                                <!-- Priority -->

                                <td>

                                    <span class="lead-priority">

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['priority']
                                        );

                                        ?>

                                    </span>

                                </td>


                                <!-- Assigned To -->

                                <td class="assignment-cell">

                                    <form method="POST">


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="assign_lead"
                                        >


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


                                            <?php foreach (
                                                $staff_members
                                                as $staff
                                            ): ?>


                                                <option
                                                    value="<?php echo (int) $staff['id']; ?>"
                                                    <?php

                                                    echo (
                                                        (int)
                                                        $lead[
                                                            'assigned_to'
                                                        ]
                                                        ===
                                                        (int)
                                                        $staff['id']
                                                    )
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
                                                            $staff[
                                                                'role_name'
                                                            ]
                                                        )
                                                    );

                                                    ?>

                                                </option>


                                            <?php endforeach; ?>


                                        </select>


                                    </form>

                                </td>


                                <!-- Actions -->

                                <td class="action-cell">


                                    <div class="lead-action-buttons">


                                        <!-- View -->

                                        <a
                                            href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            View
                                        </a>


                                        <!-- Edit -->

                                        <a
                                            href="<?php echo BASE_URL; ?>/leads/edit.php?id=<?php echo (int) $lead['id']; ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>


                                        <!-- Delete -->

                                        <form
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this lead? This action cannot be undone.');"
                                        >


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete_lead"
                                            >


                                            <input
                                                type="hidden"
                                                name="lead_id"
                                                value="<?php echo (int) $lead['id']; ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                Delete
                                            </button>


                                        </form>


                                    </div>


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