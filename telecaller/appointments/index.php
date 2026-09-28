 <?php

require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role(
    'admin',
    'manager',
    'telecaller',
    'marketing'
);

$user = current_user();

$role = $user['role'] ?? '';

$user_id = (int) $user['id'];

$page_title = 'Appointments';


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$date   = trim($_GET['date'] ?? '');


/*
|--------------------------------------------------------------------------
| Allowed Statuses
|--------------------------------------------------------------------------
*/

$allowed_statuses = [
    'Scheduled',
    'Confirmed',
    'Completed',
    'Cancelled',
    'No Show'
];

if (
    $status !== ''
    && !in_array($status, $allowed_statuses, true)
) {
    $status = '';
}


/*
|--------------------------------------------------------------------------
| Appointment Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        a.id,
        a.lead_id,
        a.appointment_date,
        a.appointment_type,
        a.notes,
        a.status,
        a.created_at,

        l.name AS lead_name,
        l.phone AS lead_phone,
        l.email AS lead_email,
        l.service_interest,
        l.assigned_to

    FROM appointments a

    INNER JOIN leads l
        ON a.lead_id = l.id

    WHERE 1 = 1
";

$params = [];


/*
|--------------------------------------------------------------------------
| Role Based Access
|--------------------------------------------------------------------------
|
| Admin / Manager
| -> See all appointments
|
| Telecaller / Marketing
| -> See appointments for their assigned leads
|
*/

if (
    $role === 'telecaller'
    || $role === 'marketing'
) {

    $sql .= "
        AND l.assigned_to = ?
    ";

    $params[] = $user_id;
}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            l.name LIKE ?
            OR l.phone LIKE ?
            OR l.email LIKE ?
            OR l.service_interest LIKE ?
        )
    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $sql .= "
        AND a.status = ?
    ";

    $params[] = $status;
}


/*
|--------------------------------------------------------------------------
| Date Filter
|--------------------------------------------------------------------------
*/

if ($date !== '') {

    $sql .= "
        AND DATE(a.appointment_date) = ?
    ";

    $params[] = $date;
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        a.appointment_date ASC,
        a.id DESC
";


/*
|--------------------------------------------------------------------------
| Execute Appointment Query
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$appointments = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$summary_sql = "
    SELECT

        COUNT(*) AS total_appointments,

        SUM(
            CASE
                WHEN a.status = 'Scheduled'
                THEN 1
                ELSE 0
            END
        ) AS scheduled_count,

        SUM(
            CASE
                WHEN a.status = 'Confirmed'
                THEN 1
                ELSE 0
            END
        ) AS confirmed_count,

        SUM(
            CASE
                WHEN a.status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_count,

        SUM(
            CASE
                WHEN a.status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_count,

        SUM(
            CASE
                WHEN a.status = 'No Show'
                THEN 1
                ELSE 0
            END
        ) AS no_show_count

    FROM appointments a

    INNER JOIN leads l
        ON a.lead_id = l.id

    WHERE 1 = 1
";

$summary_params = [];


if (
    $role === 'telecaller'
    || $role === 'marketing'
) {

    $summary_sql .= "
        AND l.assigned_to = ?
    ";

    $summary_params[] = $user_id;
}


$stmt = $pdo->prepare($summary_sql);

$stmt->execute($summary_params);

$summary = $stmt->fetch();


$total_appointments =
    (int) ($summary['total_appointments'] ?? 0);

$scheduled_count =
    (int) ($summary['scheduled_count'] ?? 0);

$confirmed_count =
    (int) ($summary['confirmed_count'] ?? 0);

$completed_count =
    (int) ($summary['completed_count'] ?? 0);

$cancelled_count =
    (int) ($summary['cancelled_count'] ?? 0);

$no_show_count =
    (int) ($summary['no_show_count'] ?? 0);


/*
|--------------------------------------------------------------------------
| Appointment Status Class
|--------------------------------------------------------------------------
*/

function appointment_status_class(
    string $status
): string {

    switch ($status) {

        case 'Scheduled':
            return 'bg-primary';

        case 'Confirmed':
            return 'bg-success';

        case 'Completed':
            return 'bg-dark';

        case 'Cancelled':
            return 'bg-danger';

        case 'No Show':
            return 'bg-warning text-dark';

        default:
            return 'bg-secondary';
    }
}


/*
|--------------------------------------------------------------------------
| Common Header
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/header.php';

?>


<div class="container py-4">


    <!-- ============================================================= -->
    <!-- PAGE HEADER -->
    <!-- ============================================================= -->

    <div class="mb-4">

        <div
            class="d-flex flex-wrap justify-content-between align-items-center"
        >

            <div>

                <span class="badge bg-light text-dark">
                    APPOINTMENTS
                </span>

                <h1 class="hm-page-title mt-2 mb-1">
                    Appointments
                </h1>

                <p class="hm-muted mb-0">
                    Manage and monitor lead appointments.
                </p>

            </div>


            <div class="mt-3 mt-md-0">

                <?php if ($role === 'manager'): ?>

                    <a
                        href="<?php echo BASE_URL; ?>/manager/control-center.php"
                        class="btn btn-outline-primary"
                    >
                        Control Center
                    </a>

                <?php elseif ($role === 'telecaller'): ?>

                    <a
                        href="<?php echo BASE_URL; ?>/telecaller/dashboard.php"
                        class="btn btn-outline-primary"
                    >
                        My Day
                    </a>

                <?php elseif ($role === 'marketing'): ?>

                    <a
                        href="<?php echo BASE_URL; ?>/marketing/dashboard.php"
                        class="btn btn-outline-primary"
                    >
                        My Day
                    </a>

                <?php else: ?>

                    <a
                        href="<?php echo BASE_URL; ?>/admin/dashboard.php"
                        class="btn btn-outline-primary"
                    >
                        Dashboard
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- ============================================================= -->
    <!-- SUMMARY CARDS -->
    <!-- ============================================================= -->

    <div class="row g-4 mb-4">


        <div class="col-6 col-md-4 col-lg-2">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small mb-1">
                    Total
                </div>

                <div class="fs-2 fw-bold">
                    <?php echo $total_appointments; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-4 col-lg-2">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small mb-1">
                    Scheduled
                </div>

                <div class="fs-2 fw-bold">
                    <?php echo $scheduled_count; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-4 col-lg-2">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small mb-1">
                    Confirmed
                </div>

                <div class="fs-2 fw-bold">
                    <?php echo $confirmed_count; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-4 col-lg-2">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small mb-1">
                    Completed
                </div>

                <div class="fs-2 fw-bold">
                    <?php echo $completed_count; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-4 col-lg-2">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small mb-1">
                    Cancelled
                </div>

                <div class="fs-2 fw-bold">
                    <?php echo $cancelled_count; ?>
                </div>

            </div>

        </div>


        <div class="col-6 col-md-4 col-lg-2">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted small mb-1">
                    No Show
                </div>

                <div class="fs-2 fw-bold">
                    <?php echo $no_show_count; ?>
                </div>

            </div>

        </div>

    </div>


    <!-- ============================================================= -->
    <!-- FILTERS -->
    <!-- ============================================================= -->

    <div class="hm-card p-4 mb-4">

        <h4 class="mb-3">
            Search & Filter
        </h4>


        <form method="GET">

            <div class="row g-3 align-items-end">


                <div class="col-md-5">

                    <label class="form-label">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Name, phone, email or service"
                    >

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
                            $allowed_statuses
                            as $appointment_status
                        ): ?>

                            <option
                                value="<?php echo htmlspecialchars($appointment_status); ?>"
                                <?php
                                echo (
                                    $status ===
                                    $appointment_status
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $appointment_status
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-2">

                    <label class="form-label">
                        Date
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
                        Apply
                    </button>

                </div>

            </div>


            <div class="mt-3">

                <a
                    href="<?php echo BASE_URL; ?>/telecaller/appointments/index.php"
                    class="btn btn-outline-secondary btn-sm"
                >
                    Reset Filters
                </a>

            </div>

        </form>

    </div>


    <!-- ============================================================= -->
    <!-- APPOINTMENT LIST -->
    <!-- ============================================================= -->

    <div class="hm-card p-4">

        <div
            class="d-flex flex-wrap justify-content-between align-items-center mb-3"
        >

            <div>

                <h4 class="mb-1">
                    Appointment List
                </h4>

                <p class="hm-muted mb-0">
                    <?php echo count($appointments); ?>
                    appointment(s) found.
                </p>

            </div>

        </div>


        <?php if (!empty($appointments)): ?>


            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

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
                                Date & Time
                            </th>

                            <th>
                                Type
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
                        $appointments
                        as $appointment
                    ): ?>

                        <tr>


                            <td>

                                <?php
                                echo (int)
                                    $appointment['id'];
                                ?>

                            </td>


                            <td>

                                <div class="fw-semibold">

                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['lead_name']
                                    );
                                    ?>

                                </div>


                                <?php
                                if (
                                    !empty(
                                        $appointment['lead_email']
                                    )
                                ):
                                ?>

                                    <div class="small hm-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $appointment['lead_email']
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $appointment['lead_phone']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $appointment[
                                        'service_interest'
                                    ] ?: '-'
                                );
                                ?>

                            </td>


                            <td>

                                <?php

                                if (
                                    !empty(
                                        $appointment[
                                            'appointment_date'
                                        ]
                                    )
                                ) {

                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $appointment[
                                                'appointment_date'
                                            ]
                                        )
                                    );

                                } else {

                                    echo '-';

                                }

                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $appointment[
                                        'appointment_type'
                                    ] ?: '-'
                                );
                                ?>

                            </td>


                            <td>

                                <span
                                    class="badge <?php
                                    echo appointment_status_class(
                                        $appointment['status']
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['status']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $appointment['lead_id']; ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    View Lead
                                </a>

                            </td>


                        </tr>

                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>

            <div class="alert alert-light border mb-0">

                No appointments found.

            </div>

        <?php endif; ?>


    </div>


</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>