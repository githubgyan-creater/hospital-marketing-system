 
<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

require_login();

$user = current_user();

/*
|--------------------------------------------------------------------------
| TELECALLER ACCESS CONTROL
|--------------------------------------------------------------------------
*/

if (!$user || strtolower((string) $user['role']) !== 'telecaller') {
    http_response_code(403);
    exit('Access denied.');
}

$user_id = (int) $user['id'];


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search   = trim($_GET['search'] ?? '');
$status   = trim($_GET['status'] ?? '');
$priority = trim($_GET['priority'] ?? '');


/*
|--------------------------------------------------------------------------
| BUILD QUERY
|--------------------------------------------------------------------------
|
| IMPORTANT:
| The Telecaller can ONLY see leads assigned to the
| currently logged-in Telecaller.
|
*/

$where = [
    'l.assigned_to = ?'
];

$params = [
    $user_id
];


/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $where[] = 'l.status = ?';

    $params[] = $status;
}


/*
|--------------------------------------------------------------------------
| PRIORITY FILTER
|--------------------------------------------------------------------------
*/

if ($priority !== '') {

    $where[] = 'l.priority = ?';

    $params[] = $priority;
}


/*
|--------------------------------------------------------------------------
| LOAD ONLY CURRENT TELECALLER'S LEADS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        l.id,
        l.name,
        l.phone,
        l.email,
        l.service_interest,
        d.department_name,
        sv.service_name,
        l.status,
        l.priority,
        l.next_action_type,
        l.next_action_at,
        l.created_at,
        ms.name AS source_name

    FROM leads l

    LEFT JOIN marketing_sources ms
        ON l.source_id = ms.id

    LEFT JOIN departments d
        ON d.id = l.department_id

    LEFT JOIN services sv
        ON sv.id = l.service_id

    WHERE "
    . implode(' AND ', $where)
    . "

    ORDER BY

        /*
        |--------------------------------------------------------------------------
        | Overdue actions first
        |--------------------------------------------------------------------------
        */

        CASE

            WHEN
                l.next_action_at IS NOT NULL
                AND l.next_action_at < NOW()
                AND l.status NOT IN ('Converted', 'Lost')

            THEN 0


            /*
            |--------------------------------------------------------------------------
            | Today's actions second
            |--------------------------------------------------------------------------
            */

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

$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| SUMMARY COUNTS
|--------------------------------------------------------------------------
*/

$total_leads     = count($leads);
$overdue_count   = 0;
$today_count     = 0;
$new_count       = 0;
$converted_count = 0;


foreach ($leads as $lead) {

    /*
    |--------------------------------------------------------------------------
    | OVERDUE
    |--------------------------------------------------------------------------
    */

    if (
        !empty($lead['next_action_at'])
        &&
        strtotime($lead['next_action_at']) < time()
        &&
        !in_array(
            $lead['status'],
            ['Converted', 'Lost'],
            true
        )
    ) {

        $overdue_count++;
    }


    /*
    |--------------------------------------------------------------------------
    | TODAY
    |--------------------------------------------------------------------------
    */

    if (
        !empty($lead['next_action_at'])
        &&
        date(
            'Y-m-d',
            strtotime($lead['next_action_at'])
        ) === date('Y-m-d')
    ) {

        $today_count++;
    }


    /*
    |--------------------------------------------------------------------------
    | NEW
    |--------------------------------------------------------------------------
    */

    if ($lead['status'] === 'New') {

        $new_count++;
    }


    /*
    |--------------------------------------------------------------------------
    | CONVERTED
    |--------------------------------------------------------------------------
    */

    if ($lead['status'] === 'Converted') {

        $converted_count++;
    }
}

?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<style>

/*
|--------------------------------------------------------------------------
| TELECALLER ACTION BUTTONS
|--------------------------------------------------------------------------
*/

.telecaller-action-buttons {
    width: min(100%, 170px);
    min-width: 150px;

    display: flex;
    flex-direction: column;

    gap: 5px;
}


/*
|--------------------------------------------------------------------------
| OPEN + CALL ROW
|--------------------------------------------------------------------------
*/

.telecaller-action-row {

    display: grid;

    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);

    gap: 5px;

    width: 100%;
}


/*
|--------------------------------------------------------------------------
| ALL ACTION BUTTONS
|--------------------------------------------------------------------------
*/

.telecaller-action-buttons .btn {

    width: 100%;

    min-height: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-align: center;

    white-space: nowrap;

    padding-left: 7px;
    padding-right: 7px;

    font-size: 0.82rem;
}


/*
|--------------------------------------------------------------------------
| FULL WIDTH BUTTONS
|--------------------------------------------------------------------------
*/

.telecaller-action-full {
    width: 100%;
}


/*
|--------------------------------------------------------------------------
| TABLET
|--------------------------------------------------------------------------
*/

@media (max-width: 992px) {

    .telecaller-action-buttons {

        width: 150px;

        min-width: 140px;
    }

    .telecaller-action-buttons .btn {

        font-size: 0.78rem;

        padding-left: 5px;
        padding-right: 5px;
    }
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 768px) {

    .telecaller-action-buttons {

        width: 100%;

        min-width: 145px;

        max-width: 180px;
    }

    .telecaller-action-buttons .btn {

        min-height: 34px;

        font-size: 0.8rem;
    }
}


/*
|--------------------------------------------------------------------------
| VERY SMALL MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 480px) {

    .telecaller-action-buttons {

        width: 100%;

        min-width: 135px;
    }

    .telecaller-action-row {

        grid-template-columns: 1fr 1fr;

        gap: 4px;
    }

    .telecaller-action-buttons .btn {

        min-height: 32px;

        font-size: 0.75rem;

        padding-left: 4px;
        padding-right: 4px;
    }
}

</style>


<div class="container py-4">


    <!-- =========================================================
         HEADER
    ========================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                My Leads
            </h1>

            <p class="text-muted mb-0">
                Leads assigned to you for telecalling activities.
            </p>

        </div>

    </div>



    <!-- =========================================================
         SUMMARY
    ========================================================== -->

    <div class="row g-4 mb-4">


        <!-- TOTAL -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Leads
                    </h6>

                    <h3 class="mb-0">
                        <?php echo $total_leads; ?>
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
                        <?php echo $overdue_count; ?>
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
                        <?php echo $today_count; ?>
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
                        <?php echo $converted_count; ?>
                    </h3>

                </div>

            </div>

        </div>

    </div>



    <!-- =========================================================
         FILTERS
    ========================================================== -->

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
                                echo htmlspecialchars(
                                    $search,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
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


                            <?php foreach ($statuses as $status_option): ?>

                                <option
                                    value="<?php
                                        echo htmlspecialchars(
                                            $status_option,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                    ?>"
                                    <?php
                                        echo $status === $status_option
                                            ? 'selected'
                                            : '';
                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $status_option,
                                        ENT_QUOTES,
                                        'UTF-8'
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
                        href="<?php
                            echo BASE_URL;
                        ?>/telecaller/leads/index.php"
                        class="btn btn-sm btn-outline-secondary"
                    >
                        Reset Filters
                    </a>

                </div>

            </form>

        </div>

    </div>



    <!-- =========================================================
         LEADS TABLE
    ========================================================== -->

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
                                    Service / Department
                                </th>

                                <th>
                                    Priority
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Follow-up
                                </th>

                                <th>
                                    Source
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>



                        <tbody>


                            <?php foreach ($leads as $lead): ?>

                                <tr>


                                    <!-- =================================================
                                         LEAD
                                    ================================================== -->

                                    <td>

                                        <strong>

                                            <?php

                                            echo htmlspecialchars(
                                                $lead['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );

                                            ?>

                                        </strong>


                                        <div class="small text-muted">

                                            <?php

                                            echo htmlspecialchars(
                                                $lead['email'] ?: '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );

                                            ?>

                                        </div>

                                    </td>



                                    <!-- =================================================
                                         PHONE
                                    ================================================== -->

                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['phone'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );

                                        ?>

                                    </td>



                                    <!-- =================================================
                                         SERVICE
                                    ================================================== -->

                                    <td>

                                        <strong>

                                            <?php

                                            echo htmlspecialchars(

                                                $lead['service_name']
                                                    ?: (
                                                        $lead['service_interest']
                                                        ?: '-'
                                                    ),

                                                ENT_QUOTES,

                                                'UTF-8'

                                            );

                                            ?>

                                        </strong>


                                        <div class="small text-muted">

                                            <?php

                                            echo htmlspecialchars(

                                                $lead['department_name']
                                                    ?: '-',

                                                ENT_QUOTES,

                                                'UTF-8'

                                            );

                                            ?>

                                        </div>

                                    </td>



                                    <!-- =================================================
                                         PRIORITY
                                    ================================================== -->

                                    <td>

                                        <span class="badge bg-light text-dark border">

                                            <?php

                                            echo htmlspecialchars(

                                                $lead['priority'] ?: '-',

                                                ENT_QUOTES,

                                                'UTF-8'

                                            );

                                            ?>

                                        </span>

                                    </td>



                                    <!-- =================================================
                                         STATUS
                                    ================================================== -->

                                    <td>

                                        <span class="badge bg-secondary">

                                            <?php

                                            echo htmlspecialchars(

                                                $lead['status'],

                                                ENT_QUOTES,

                                                'UTF-8'

                                            );

                                            ?>

                                        </span>

                                    </td>



                                    <!-- =================================================
                                         FOLLOW-UP
                                    ================================================== -->

                                    <td>

                                        <?php if (!empty($lead['next_action_at'])): ?>


                                            <div>

                                                <?php

                                                echo htmlspecialchars(

                                                    $lead['next_action_type']
                                                        ?: 'Action',

                                                    ENT_QUOTES,

                                                    'UTF-8'

                                                );

                                                ?>

                                            </div>


                                            <small class="text-muted">

                                                <?php

                                                echo htmlspecialchars(

                                                    date(

                                                        'd M Y, h:i A',

                                                        strtotime(

                                                            $lead['next_action_at']

                                                        )

                                                    ),

                                                    ENT_QUOTES,

                                                    'UTF-8'

                                                );

                                                ?>

                                            </small>


                                        <?php else: ?>


                                            <span class="text-muted">
                                                -
                                            </span>


                                        <?php endif; ?>

                                    </td>



                                    <!-- =================================================
                                         SOURCE
                                    ================================================== -->

                                    <td>

                                        <?php

                                        echo htmlspecialchars(

                                            $lead['source_name'] ?: '-',

                                            ENT_QUOTES,

                                            'UTF-8'

                                        );

                                        ?>

                                    </td>



                                    <!-- =================================================
                                         ACTIONS
                                    ================================================== -->

                                    <td>

                                        <div class="telecaller-action-buttons">


                                            <!-- OPEN + CALL -->

                                            <div class="telecaller-action-row">


                                                <!-- OPEN -->

                                                <a
                                                    href="<?php
                                                        echo BASE_URL;
                                                    ?>/leads/view.php?id=<?php
                                                        echo (int) $lead['id'];
                                                    ?>"
                                                    class="btn btn-sm btn-outline-secondary"
                                                >
                                                    Open
                                                </a>



                                                <?php

                                                /*
                                                |--------------------------------------------------------------------------
                                                | PHONE
                                                |--------------------------------------------------------------------------
                                                */

                                                $phone_digits = preg_replace(

                                                    '/\D+/',

                                                    '',

                                                    (string) $lead['phone']

                                                );


                                                /*
                                                |--------------------------------------------------------------------------
                                                | WHATSAPP NUMBER
                                                |--------------------------------------------------------------------------
                                                */

                                                $whatsapp_phone = $phone_digits;


                                                if (
                                                    strlen($whatsapp_phone) === 10
                                                ) {

                                                    $whatsapp_phone =
                                                        '91'
                                                        . $whatsapp_phone;

                                                }


                                                /*
                                                |--------------------------------------------------------------------------
                                                | WHATSAPP MESSAGE
                                                |--------------------------------------------------------------------------
                                                */

                                                $whatsapp_message = rawurlencode(

                                                    'Hello '
                                                    . $lead['name']
                                                    . ', this is from the hospital marketing team.'

                                                );

                                                ?>



                                                <!-- CALL -->

                                                <a
                                                    href="tel:<?php
                                                        echo htmlspecialchars(

                                                            $phone_digits,

                                                            ENT_QUOTES,

                                                            'UTF-8'

                                                        );
                                                    ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Call lead"
                                                >
                                                    Call
                                                </a>

                                            </div>



                                            <!-- WHATSAPP -->

                                            <a
                                                href="https://wa.me/<?php
                                                    echo htmlspecialchars(

                                                        $whatsapp_phone,

                                                        ENT_QUOTES,

                                                        'UTF-8'

                                                    );
                                                ?>?text=<?php
                                                    echo $whatsapp_message;
                                                ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn btn-sm btn-outline-success telecaller-action-full"
                                                title="Message lead on WhatsApp"
                                            >
                                                WhatsApp
                                            </a>



                                            <!-- APPOINTMENT -->

                                            <a
                                                href="<?php
                                                    echo BASE_URL;
                                                ?>/telecaller/appointments/add.php?lead_id=<?php
                                                    echo (int) $lead['id'];
                                                ?>"
                                                class="btn btn-sm btn-primary telecaller-action-full"
                                            >
                                                Appointment
                                            </a>


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


</div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
 
