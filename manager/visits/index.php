<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();

$page_title = 'Team Visits';


/*

| Filters

*/

$visit_type = trim($_GET['visit_type'] ?? '');
$status = trim($_GET['status'] ?? '');
$assigned_filter = !empty($_GET['assigned_to'])
    ? (int) $_GET['assigned_to']
    : 0;


/*

| Allowed Values

*/

$visit_types = [
    'Doctor Visit',
    'Clinic Visit',
    'Corporate Visit',
    'Field Visit'
];

$visit_statuses = [
    'Planned',
    'Completed',
    'Cancelled'
];


/*

| Staff

*/

$staff_stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        u.email,
        r.name AS role_name,
        r.display_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.status = 'active'

      AND (
          LOWER(r.name) = 'marketing'
          OR LOWER(r.name) = 'marketing executive'
      )

    ORDER BY u.name ASC
");

$staff = $staff_stmt->fetchAll();


/*

| Visit Query

*/

$sql = "
    SELECT

        mv.id,
        mv.visit_type,
        mv.title,
        mv.person_name,
        mv.organization_name,
        mv.location,
        mv.visit_date,
        mv.status,

        assigned_user.name AS assigned_name,

        l.name AS lead_name,

        t.title AS task_title

    FROM marketing_visits mv

    INNER JOIN users assigned_user
        ON mv.assigned_to = assigned_user.id

    LEFT JOIN leads l
        ON mv.related_lead_id = l.id

    LEFT JOIN tasks t
        ON mv.related_task_id = t.id

    WHERE 1 = 1
";

$params = [];


/*

| Visit Type Filter

*/

if (
    $visit_type !== ''
    && in_array(
        $visit_type,
        $visit_types,
        true
    )
) {

    $sql .= "
        AND mv.visit_type = ?
    ";

    $params[] = $visit_type;
}


/*

| Status Filter

*/

if (
    $status !== ''
    && in_array(
        $status,
        $visit_statuses,
        true
    )
) {

    $sql .= "
        AND mv.status = ?
    ";

    $params[] = $status;
}


/*

| Staff Filter

*/

if ($assigned_filter > 0) {

    $sql .= "
        AND mv.assigned_to = ?
    ";

    $params[] = $assigned_filter;
}


/*

| Order

*/

$sql .= "
    ORDER BY
        mv.visit_date ASC,
        mv.id DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$visits = $stmt->fetchAll();


/*

| Summary

*/

$summary_stmt = $pdo->query("
    SELECT

        COUNT(*) AS total_visits,

        SUM(
            CASE
                WHEN status = 'Planned'
                THEN 1
                ELSE 0
            END
        ) AS planned_visits,

        SUM(
            CASE
                WHEN status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_visits,

        SUM(
            CASE
                WHEN status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_visits

    FROM marketing_visits
");

$summary = $summary_stmt->fetch();

$total_visits =
    (int) ($summary['total_visits'] ?? 0);

$planned_visits =
    (int) ($summary['planned_visits'] ?? 0);

$completed_visits =
    (int) ($summary['completed_visits'] ?? 0);

$cancelled_visits =
    (int) ($summary['cancelled_visits'] ?? 0);


/*

| Helpers

*/

function manager_visit_status_class(
    string $status
): string {

    switch ($status) {

        case 'Planned':
            return 'bg-warning text-dark';

        case 'Completed':
            return 'bg-success';

        case 'Cancelled':
            return 'bg-danger';

        default:
            return 'bg-secondary';
    }
}


function manager_visit_type_class(
    string $type
): string {

    switch ($type) {

        case 'Doctor Visit':
            return 'bg-primary';

        case 'Clinic Visit':
            return 'bg-info text-dark';

        case 'Corporate Visit':
            return 'bg-success';

        case 'Field Visit':
            return 'bg-secondary';

        default:
            return 'bg-dark';
    }
}


/*

| Header

*/

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">


    
    <!-- HEADER -->
    

    <div
        class="d-flex flex-wrap justify-content-between align-items-center mb-4"
    >

        <div>

            <div class="mb-2">

                <a
                    href="<?php echo BASE_URL; ?>/manager/dashboard.php"
                    class="text-decoration-none"
                >
                    ← Manager Dashboard
                </a>

            </div>

            <h1 class="hm-page-title mb-1">
                Team Visits
            </h1>

            <p class="hm-muted mb-0">
                Plan and monitor field marketing visits.
            </p>

        </div>


        <div class="mt-3 mt-md-0">

            <a
                href="<?php echo BASE_URL; ?>/manager/visits/add.php"
                class="btn btn-primary"
            >
                + Add Visit
            </a>

        </div>

    </div>


    
    <!-- SUMMARY -->
    

    <div class="row g-4 mb-4">

        <div class="col-6 col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    Total Visits
                </div>

                <div class="fs-2 fw-bold">
                    <?php echo $total_visits; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    Planned
                </div>

                <div class="fs-2 fw-bold text-warning">
                    <?php echo $planned_visits; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    Completed
                </div>

                <div class="fs-2 fw-bold text-success">
                    <?php echo $completed_visits; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small">
                    Cancelled
                </div>

                <div class="fs-2 fw-bold text-danger">
                    <?php echo $cancelled_visits; ?>
                </div>

            </div>

        </div>

    </div>


    
    <!-- FILTERS -->
    

    <div class="hm-card p-4 mb-4">

        <h5 class="mb-3">
            Search & Filter
        </h5>

        <form method="GET">

            <div class="row g-3 align-items-end">


                <div class="col-md-4">

                    <label class="form-label">
                        Visit Type
                    </label>

                    <select
                        name="visit_type"
                        class="form-select"
                    >

                        <option value="">
                            All Visit Types
                        </option>

                        <?php foreach ($visit_types as $type): ?>

                            <option
                                value="<?php echo htmlspecialchars($type); ?>"
                                <?php
                                echo $visit_type === $type
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars($type);
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        <?php foreach ($visit_statuses as $visit_status): ?>

                            <option
                                value="<?php echo htmlspecialchars($visit_status); ?>"
                                <?php
                                echo $status === $visit_status
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $visit_status
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-3">

                    <label class="form-label">
                        Assigned To
                    </label>

                    <select
                        name="assigned_to"
                        class="form-select"
                    >

                        <option value="">
                            All Staff
                        </option>

                        <?php foreach ($staff as $member): ?>

                            <option
                                value="<?php echo (int) $member['id']; ?>"
                                <?php
                                echo $assigned_filter ===
                                    (int) $member['id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $member['name']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-2">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Filter
                    </button>

                </div>

            </div>


            <div class="mt-3">

                <a
                    href="<?php echo BASE_URL; ?>/manager/visits/index.php"
                    class="btn btn-outline-secondary btn-sm"
                >
                    Reset Filters
                </a>

            </div>

        </form>

    </div>


    
    <!-- VISIT LIST -->
    

    <div class="hm-card p-4">

        <div class="mb-3">

            <h4 class="mb-1">
                Visit List
            </h4>

            <div class="hm-muted small">
                <?php echo count($visits); ?>
                visit(s) found.
            </div>

        </div>


        <?php if (!empty($visits)): ?>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>
                                Visit
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Person / Organization
                            </th>

                            <th>
                                Assigned To
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Lead
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

                    <?php foreach ($visits as $visit): ?>

                        <tr>

                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $visit['title']
                                    );
                                    ?>

                                </div>

                                <?php if (
                                    !empty(
                                        $visit['location']
                                    )
                                ): ?>

                                    <div class="small hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $visit['location']
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span
                                    class="badge <?php
                                    echo manager_visit_type_class(
                                        $visit['visit_type']
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $visit['visit_type']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php if (
                                    !empty(
                                        $visit['person_name']
                                    )
                                ): ?>

                                    <div class="fw-semibold">

                                        <?php
                                        echo htmlspecialchars(
                                            $visit['person_name']
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $visit['organization_name']
                                    )
                                ): ?>

                                    <div class="small hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $visit['organization_name']
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>


                                <?php if (
                                    empty(
                                        $visit['person_name']
                                    )
                                    && empty(
                                        $visit['organization_name']
                                    )
                                ): ?>

                                    <span class="hm-muted">
                                        -
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $visit['assigned_name']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $visit['visit_date']
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <?php if (
                                    !empty(
                                        $visit['lead_name']
                                    )
                                ): ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $visit['lead_name']
                                    );
                                    ?>

                                <?php else: ?>

                                    <span class="hm-muted">
                                        No Lead
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span
                                    class="badge <?php
                                    echo manager_visit_status_class(
                                        $visit['status']
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $visit['status']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <a
                                    <a
    href="<?php echo BASE_URL; ?>/marketing/visits/view.php?id=<?php echo (int) $visit['id']; ?>"
    class="btn btn-sm btn-outline-primary"
>
    View
</a>
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    View
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="alert alert-light border mb-0">

                No visits have been created yet.

                <a
                    href="<?php echo BASE_URL; ?>/manager/visits/add.php"
                    class="alert-link"
                >
                    Add the first visit.
                </a>

            </div>

        <?php endif; ?>

    </div>


</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>