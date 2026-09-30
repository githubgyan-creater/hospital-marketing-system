<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('marketing');

$user = current_user();

$user_id = (int) $user['id'];

$page_title = 'My Visits';


/*

| Filters

*/

$visit_type = trim($_GET['visit_type'] ?? '');
$status = trim($_GET['status'] ?? '');
$date = trim($_GET['date'] ?? '');


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


if (
    $visit_type !== ''
    && !in_array(
        $visit_type,
        $visit_types,
        true
    )
) {
    $visit_type = '';
}


if (
    $status !== ''
    && !in_array(
        $status,
        $visit_statuses,
        true
    )
) {
    $status = '';
}


/*

| Get Visits

*/

$sql = "
    SELECT

        mv.id,
        mv.visit_type,
        mv.title,
        mv.person_name,
        mv.organization_name,
        mv.phone,
        mv.location,
        mv.visit_date,
        mv.status,
        mv.outcome,
        mv.remarks,

        l.id AS lead_id,
        l.name AS lead_name,
        l.phone AS lead_phone,

        t.id AS task_id,
        t.title AS task_title

    FROM marketing_visits mv

    LEFT JOIN leads l
        ON mv.related_lead_id = l.id

    LEFT JOIN tasks t
        ON mv.related_task_id = t.id

    WHERE mv.assigned_to = ?
";

$params = [
    $user_id
];


/*

| Visit Type Filter

*/

if ($visit_type !== '') {

    $sql .= "
        AND mv.visit_type = ?
    ";

    $params[] = $visit_type;
}


/*

| Status Filter

*/

if ($status !== '') {

    $sql .= "
        AND mv.status = ?
    ";

    $params[] = $status;
}


/*

| Date Filter

*/

if ($date !== '') {

    $sql .= "
        AND DATE(mv.visit_date) = ?
    ";

    $params[] = $date;
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

$summary_stmt = $pdo->prepare("
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

    WHERE assigned_to = ?
");

$summary_stmt->execute([
    $user_id
]);

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

function visit_status_class(
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


function visit_type_class(
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

| Common Header

*/

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">


    
    <!-- PAGE HEADER -->
    

    <div
        class="d-flex flex-wrap justify-content-between align-items-center mb-4"
    >

        <div>

            <div class="mb-2">

                <a
                    href="<?php echo BASE_URL; ?>/marketing/dashboard.php"
                    class="text-decoration-none"
                >
                    ← My Day
                </a>

            </div>

            <h1 class="hm-page-title mb-1">
                My Visits
            </h1>

            <p class="hm-muted mb-0">
                Manage doctor, clinic, corporate and field visits assigned to you.
            </p>

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

                        <?php foreach (
                            $visit_types as $type
                        ): ?>

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

                        <?php foreach (
                            $visit_statuses as $visit_status
                        ): ?>

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
                        Visit Date
                    </label>

                    <input
                        type="date"
                        name="date"
                        class="form-control"
                        value="<?php echo htmlspecialchars($date); ?>"
                    >

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
                    href="<?php echo BASE_URL; ?>/marketing/visits/index.php"
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
                                Location
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Related Lead
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

                    <?php foreach (
                        $visits
                        as $visit
                    ): ?>

                        <tr>


                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $visit['title']
                                    );
                                    ?>

                                </div>

                            </td>


                            <td>

                                <span
                                    class="badge <?php
                                    echo visit_type_class(
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
                                    $visit['location']
                                    ?: '-'
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
                                        $visit['lead_id']
                                    )
                                ): ?>

                                    <div class="fw-semibold">

                                        <?php
                                        echo htmlspecialchars(
                                            $visit['lead_name']
                                        );
                                        ?>

                                    </div>

                                    <div class="small hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $visit['lead_phone']
                                        );
                                        ?>

                                    </div>

                                <?php else: ?>

                                    <span class="hm-muted">
                                        No Lead
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span
                                    class="badge <?php
                                    echo visit_status_class(
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
                                    href="<?php echo BASE_URL; ?>/marketing/visits/view.php?id=<?php echo (int) $visit['id']; ?>"
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

                No visits have been assigned to you yet.

            </div>

        <?php endif; ?>

    </div>


</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>