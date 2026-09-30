 <?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

require_login();

$user = current_user();

if (!$user || $user['role'] !== 'marketing') {
    http_response_code(403);
    exit('Access denied.');
}

$user_id = (int) $user['id'];


/*

| FILTERS

*/

$search   = trim($_GET['search'] ?? '');
$status   = trim($_GET['status'] ?? '');
$priority = trim($_GET['priority'] ?? '');


/*

| BUILD QUERY

*/

$where = [
    'l.assigned_to = ?'
];

$params = [
    $user_id
];


/*

| SEARCH

*/

if ($search !== '') {

    $where[] = "
        (
            l.name LIKE ?
            OR l.phone LIKE ?
            OR l.email LIKE ?
        )
    ";

    $search_param = '%' . $search . '%';

    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}


/*

| STATUS FILTER

*/

if ($status !== '') {

    $where[] = 'l.status = ?';

    $params[] = $status;
}


/*

| PRIORITY FILTER

*/

if ($priority !== '') {

    $where[] = 'l.priority = ?';

    $params[] = $priority;
}


/*

| LOAD LEADS

|
| marketing_sources actual column:
| id
| name
| status
| created_at
|
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
        l.next_action_type,
        l.next_action_at,
        l.created_at,
        l.updated_at,

        ms.name AS source_name

    FROM leads l

    LEFT JOIN marketing_sources ms
        ON l.source_id = ms.id

    WHERE "
    . implode(' AND ', $where)
    . "

    ORDER BY
        CASE

            WHEN
                l.next_action_at IS NOT NULL
                AND l.next_action_at < NOW()
                AND l.status NOT IN ('Converted', 'Lost')

            THEN 0

            WHEN
                l.next_action_at IS NOT NULL
                AND DATE(l.next_action_at) = CURDATE()

            THEN 1

            ELSE 2

        END,

        l.next_action_at ASC,

        l.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$leads = $stmt->fetchAll();


/*

| SUMMARY COUNTS

*/

$total_leads    = count($leads);
$overdue_count  = 0;
$today_count    = 0;
$new_count      = 0;
$converted_count = 0;


foreach ($leads as $lead) {


    /*
    
    | OVERDUE
    
    */

    if (
        !empty($lead['next_action_at']) &&
        strtotime($lead['next_action_at']) < time() &&
        !in_array(
            $lead['status'],
            ['Converted', 'Lost'],
            true
        )
    ) {

        $overdue_count++;
    }


    /*
    
    | TODAY
    
    */

    if (
        !empty($lead['next_action_at']) &&
        date(
            'Y-m-d',
            strtotime($lead['next_action_at'])
        ) === date('Y-m-d')
    ) {

        $today_count++;
    }


    /*
    
    | NEW
    
    */

    if ($lead['status'] === 'New') {

        $new_count++;
    }


    /*
    
    | CONVERTED
    
    */

    if ($lead['status'] === 'Converted') {

        $converted_count++;
    }
}

?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<div class="container py-4">


    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                My Leads
            </h1>

            <p class="text-muted mb-0">
                Leads assigned to you for marketing activities.
            </p>

        </div>

    </div>


    <!-- SUMMARY CARDS -->

    <div class="row g-4 mb-4">


        <!-- TOTAL LEADS -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Leads
                    </h6>

                    <h3 class="mb-0">
                        <?php
                        echo $total_leads;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- OVERDUE -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Overdue Actions
                    </h6>

                    <h3 class="mb-0 text-danger">
                        <?php
                        echo $overdue_count;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- TODAY -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Actions Today
                    </h6>

                    <h3 class="mb-0 text-warning">
                        <?php
                        echo $today_count;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- CONVERTED -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Converted
                    </h6>

                    <h3 class="mb-0 text-success">
                        <?php
                        echo $converted_count;
                        ?>
                    </h3>

                </div>

            </div>

        </div>


    </div>


    <!-- FILTERS -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Filter Leads
            </h5>

        </div>


        <div class="card-body">

            <form method="GET">

                <div class="row g-3 align-items-end">


                    <!-- SEARCH -->

                    <div class="col-md-4">

                        <label class="form-label">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Name, phone or email"
                            value="<?php
                            echo htmlspecialchars($search);
                            ?>"
                        >

                    </div>


                    <!-- STATUS -->

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


                            <?php

                            $statuses = [
                                'New',
                                'Contacted',
                                'Interested',
                                'Follow-up',
                                'Not Interested',
                                'Appointment',
                                'Visited',
                                'Converted',
                                'Lost'
                            ];

                            ?>


                            <?php foreach (
                                $statuses as $status_option
                            ): ?>

                                <option
                                    value="<?php
                                    echo htmlspecialchars(
                                        $status_option
                                    );
                                    ?>"
                                    <?php

                                    echo (
                                        $status ===
                                        $status_option
                                    )
                                        ? 'selected'
                                        : '';

                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $status_option
                                    );
                                    ?>

                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>


                    <!-- PRIORITY -->

                    <div class="col-md-3">

                        <label class="form-label">
                            Priority
                        </label>

                        <select
                            name="priority"
                            class="form-select"
                        >

                            <option value="">
                                All Priorities
                            </option>

                            <option
                                value="Low"
                                <?php
                                echo $priority === 'Low'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Low
                            </option>

                            <option
                                value="Medium"
                                <?php
                                echo $priority === 'Medium'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Medium
                            </option>

                            <option
                                value="High"
                                <?php
                                echo $priority === 'High'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                High
                            </option>

                        </select>

                    </div>


                    <!-- APPLY -->

                    <div class="col-md-2">

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Apply
                        </button>

                    </div>


                </div>


                <!-- RESET -->

                <div class="mt-3">

                    <a
                        href="<?php echo BASE_URL; ?>/marketing/leads/index.php"
                        class="btn btn-sm btn-outline-secondary"
                    >
                        Reset Filters
                    </a>

                </div>

            </form>

        </div>

    </div>


    <!-- LEADS TABLE -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Assigned Leads
            </h5>

        </div>


        <div class="card-body p-0">


            <?php if (empty($leads)): ?>


                <div class="p-4 text-muted">

                    No leads found.

                </div>


            <?php else: ?>


                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">


                        <thead>

                            <tr>

                                <th>
                                    Lead
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Source
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Priority
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


                            <?php foreach (
                                $leads as $lead
                            ): ?>


                                <tr>


                                    <!-- LEAD -->

                                    <td>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $lead['name']
                                            );
                                            ?>

                                        </strong>


                                        <div class="small text-muted">

                                            <?php
                                            echo htmlspecialchars(
                                                $lead['email']
                                                    ?: '-'
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <!-- PHONE -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['phone']
                                        );
                                        ?>

                                    </td>


                                    <!-- SERVICE -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $lead[
                                                'service_interest'
                                            ] ?: '-'
                                        );
                                        ?>

                                    </td>


                                    <!-- SOURCE -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $lead[
                                                'source_name'
                                            ] ?: '-'
                                        );
                                        ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>


                                        <?php

                                        $badge_class =
                                            'bg-secondary';


                                        if (
                                            $lead['status']
                                            === 'New'
                                        ) {

                                            $badge_class =
                                                'bg-primary';
                                        }


                                        if (
                                            $lead['status']
                                            === 'Interested'
                                        ) {

                                            $badge_class =
                                                'bg-info text-dark';
                                        }


                                        if (
                                            $lead['status']
                                            === 'Follow-up'
                                        ) {

                                            $badge_class =
                                                'bg-warning text-dark';
                                        }


                                        if (
                                            $lead['status']
                                            === 'Appointment'
                                        ) {

                                            $badge_class =
                                                'bg-primary';
                                        }


                                        if (
                                            $lead['status']
                                            === 'Visited'
                                        ) {

                                            $badge_class =
                                                'bg-info text-dark';
                                        }


                                        if (
                                            $lead['status']
                                            === 'Converted'
                                        ) {

                                            $badge_class =
                                                'bg-success';
                                        }


                                        if (
                                            $lead['status']
                                            === 'Lost'
                                        ) {

                                            $badge_class =
                                                'bg-danger';
                                        }

                                        ?>


                                        <span
                                            class="badge <?php
                                            echo $badge_class;
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $lead['status']
                                            );
                                            ?>

                                        </span>


                                    </td>


                                    <!-- PRIORITY -->

                                    <td>


                                        <?php

                                        $priority_class =
                                            'bg-secondary';


                                        if (
                                            $lead['priority']
                                            === 'High'
                                        ) {

                                            $priority_class =
                                                'bg-danger';
                                        }


                                        if (
                                            $lead['priority']
                                            === 'Medium'
                                        ) {

                                            $priority_class =
                                                'bg-warning text-dark';
                                        }


                                        if (
                                            $lead['priority']
                                            === 'Low'
                                        ) {

                                            $priority_class =
                                                'bg-success';
                                        }

                                        ?>


                                        <span
                                            class="badge <?php
                                            echo $priority_class;
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $lead['priority']
                                            );
                                            ?>

                                        </span>


                                    </td>


                                    <!-- NEXT ACTION -->

                                    <td>


                                        <?php if (
                                            !empty(
                                                $lead[
                                                    'next_action_at'
                                                ]
                                            )
                                        ): ?>


                                            <?php

                                            $action_time =
                                                strtotime(
                                                    $lead[
                                                        'next_action_at'
                                                    ]
                                                );


                                            $is_overdue =
                                                $action_time
                                                < time()
                                                &&
                                                !in_array(
                                                    $lead['status'],
                                                    [
                                                        'Converted',
                                                        'Lost'
                                                    ],
                                                    true
                                                );

                                            ?>


                                            <div>

                                                <?php
                                                echo htmlspecialchars(
                                                    $lead[
                                                        'next_action_type'
                                                    ]
                                                        ?: 'Action'
                                                );
                                                ?>

                                            </div>


                                            <div
                                                class="small <?php
                                                echo $is_overdue
                                                    ? 'text-danger'
                                                    : 'text-muted';
                                                ?>"
                                            >

                                                <?php
                                                echo date(
                                                    'd M Y, h:i A',
                                                    $action_time
                                                );
                                                ?>

                                            </div>


                                        <?php else: ?>


                                            <span class="text-muted">
                                                No next action
                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <a
                                            href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
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


            <?php endif; ?>


        </div>

    </div>


</div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>