<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('admin', 'manager');


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$status_filter = trim($_GET['status'] ?? '');

$type_filter = trim($_GET['type'] ?? '');


/*
|--------------------------------------------------------------------------
| Campaign Types
|--------------------------------------------------------------------------
*/

$campaign_types = [
    'Digital',
    'Health Camp',
    'Corporate Outreach',
    'Referral',
    'Service Promotion',
    'Community Outreach',
    'Event',
    'Other'
];


/*
|--------------------------------------------------------------------------
| Campaign Statuses
|--------------------------------------------------------------------------
*/

$campaign_statuses = [
    'Draft',
    'Planned',
    'Active',
    'Completed',
    'Cancelled'
];


/*
|--------------------------------------------------------------------------
| Build Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.id,
        c.name,
        c.campaign_type,
        c.objective,
        c.start_date,
        c.end_date,
        c.budget,
        c.status,
        c.description,
        c.created_at,

        u.name AS created_by_name

    FROM campaigns c

    INNER JOIN users u
        ON c.created_by = u.id

    WHERE 1 = 1
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
            c.name LIKE ?
            OR c.objective LIKE ?
            OR c.description LIKE ?
        )
    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if (
    $status_filter !== '' &&
    in_array(
        $status_filter,
        $campaign_statuses,
        true
    )
) {

    $sql .= "
        AND c.status = ?
    ";

    $params[] = $status_filter;
}


/*
|--------------------------------------------------------------------------
| Type Filter
|--------------------------------------------------------------------------
*/

if (
    $type_filter !== '' &&
    in_array(
        $type_filter,
        $campaign_types,
        true
    )
) {

    $sql .= "
        AND c.campaign_type = ?
    ";

    $params[] = $type_filter;
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        c.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$campaigns = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Summary Counts
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total_campaigns,

        SUM(status = 'Draft') AS draft_campaigns,

        SUM(status = 'Planned') AS planned_campaigns,

        SUM(status = 'Active') AS active_campaigns,

        SUM(status = 'Completed') AS completed_campaigns

    FROM campaigns
");

$summary = $stmt->fetch();


$total_campaigns =
    (int) ($summary['total_campaigns'] ?? 0);

$draft_campaigns =
    (int) ($summary['draft_campaigns'] ?? 0);

$planned_campaigns =
    (int) ($summary['planned_campaigns'] ?? 0);

$active_campaigns =
    (int) ($summary['active_campaigns'] ?? 0);

$completed_campaigns =
    (int) ($summary['completed_campaigns'] ?? 0);


$page_title = 'Campaign Management';

require_once __DIR__ . '/../includes/header.php';

?>


<div class="container py-4">


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Campaign Management
            </h2>

            <p class="hm-muted mb-0">
                Plan and monitor hospital marketing campaigns.
            </p>

        </div>


        <div class="d-flex gap-2">

            <a
                href="<?php echo BASE_URL; ?>/campaigns/add.php"
                class="btn btn-hm-primary"
            >
                + Add Campaign
            </a>


            <?php if (current_user()['role'] === 'manager'): ?>

                <a
                    href="<?php echo BASE_URL; ?>/manager/dashboard.php"
                    class="btn btn-outline-secondary"
                >
                    Dashboard
                </a>

            <?php else: ?>

                <a
                    href="<?php echo BASE_URL; ?>/admin/dashboard.php"
                    class="btn btn-outline-secondary"
                >
                    Dashboard
                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- Summary Cards -->

    <div class="row g-3 mb-4">


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Campaigns
                </div>

                <h2 class="mb-0">
                    <?php echo $total_campaigns; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Draft
                </div>

                <h2 class="mb-0">
                    <?php echo $draft_campaigns; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Planned
                </div>

                <h2 class="mb-0 hm-gold">
                    <?php echo $planned_campaigns; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Active
                </div>

                <h2 class="mb-0 text-success">
                    <?php echo $active_campaigns; ?>
                </h2>

            </div>

        </div>

    </div>


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
                        Search Campaign
                    </label>

                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        placeholder="Campaign name or objective"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <!-- Type -->

                <div class="col-md-3">

                    <label
                        for="type"
                        class="form-label"
                    >
                        Campaign Type
                    </label>

                    <select
                        name="type"
                        id="type"
                        class="form-select"
                    >

                        <option value="">
                            All Types
                        </option>


                        <?php foreach ($campaign_types as $type): ?>

                            <option
                                value="<?php echo htmlspecialchars($type); ?>"
                                <?php
                                echo $type_filter === $type
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php echo htmlspecialchars($type); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Status -->

                <div class="col-md-3">

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
                            All Statuses
                        </option>


                        <?php foreach ($campaign_statuses as $status): ?>

                            <option
                                value="<?php echo htmlspecialchars($status); ?>"
                                <?php
                                echo $status_filter === $status
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php echo htmlspecialchars($status); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Buttons -->

                <div class="col-md-2 d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Filter
                    </button>


                    <a
                        href="<?php echo BASE_URL; ?>/campaigns/"
                        class="btn btn-outline-secondary"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    <!-- Campaign Table -->

    <div class="hm-card p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="mb-0">
                Campaigns
            </h5>

            <span class="hm-muted">
                <?php echo count($campaigns); ?> campaign(s)
            </span>

        </div>


        <?php if (empty($campaigns)): ?>

            <div class="alert alert-light mb-0">

                No campaigns found.

            </div>

        <?php else: ?>


            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>
                                Campaign
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Budget
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created By
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($campaigns as $campaign): ?>

                            <tr>

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $campaign['name']
                                        );
                                        ?>

                                    </strong>


                                    <?php if (!empty($campaign['objective'])): ?>

                                        <small class="d-block hm-muted">

                                            <?php
                                            echo htmlspecialchars(
                                                $campaign['objective']
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <span class="badge bg-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $campaign['campaign_type']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php

                                    $start_date =
                                        $campaign['start_date'];

                                    $end_date =
                                        $campaign['end_date'];

                                    if (
                                        !empty($start_date) &&
                                        !empty($end_date)
                                    ) {

                                        echo htmlspecialchars(
                                            date(
                                                'd M Y',
                                                strtotime($start_date)
                                            )
                                        );

                                        echo ' - ';

                                        echo htmlspecialchars(
                                            date(
                                                'd M Y',
                                                strtotime($end_date)
                                            )
                                        );

                                    } elseif (!empty($start_date)) {

                                        echo htmlspecialchars(
                                            date(
                                                'd M Y',
                                                strtotime($start_date)
                                            )
                                        );

                                    } else {

                                        echo '-';

                                    }

                                    ?>

                                </td>


                                <td>

                                    ₹<?php

                                    echo number_format(
                                        (float) $campaign['budget'],
                                        2
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    $status_class =
                                        'bg-secondary';

                                    if (
                                        $campaign['status']
                                        === 'Active'
                                    ) {

                                        $status_class =
                                            'bg-success';

                                    } elseif (
                                        $campaign['status']
                                        === 'Completed'
                                    ) {

                                        $status_class =
                                            'bg-primary';

                                    } elseif (
                                        $campaign['status']
                                        === 'Cancelled'
                                    ) {

                                        $status_class =
                                            'bg-danger';

                                    } elseif (
                                        $campaign['status']
                                        === 'Planned'
                                    ) {

                                        $status_class =
                                            'bg-warning text-dark';
                                    }

                                    ?>

                                    <span
                                        class="badge <?php echo $status_class; ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $campaign['status']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $campaign['created_by_name']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <a
                                        href="<?php echo BASE_URL; ?>/campaigns/view.php?id=<?php echo (int) $campaign['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        View
                                    </a>


                                    <a
                                        href="<?php echo BASE_URL; ?>/campaigns/edit.php?id=<?php echo (int) $campaign['id']; ?>"
                                        class="btn btn-sm btn-outline-primary mt-1"
                                    >
                                        Edit
                                    </a>
                                    <form
    method="POST"
    action="<?php echo BASE_URL; ?>/campaigns/delete.php"
    class="d-inline"
    onsubmit="return confirm('Are you sure you want to delete this campaign?');"
>

    <input
        type="hidden"
        name="id"
        value="<?php echo (int) $campaign['id']; ?>"
    >

    <button
        type="submit"
        class="btn btn-sm btn-outline-danger"
    >
        Delete
    </button>

</form>

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

require_once __DIR__ . '/../includes/footer.php';

?>