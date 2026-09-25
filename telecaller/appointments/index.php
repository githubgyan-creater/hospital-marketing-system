<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('telecaller');

$user = current_user();

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Update Appointment Status
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $appointment_id = (int) ($_POST['appointment_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');

    $allowed_statuses = [
        'Scheduled',
        'Confirmed',
        'Completed',
        'Cancelled',
        'No Show'
    ];

    if (
        $appointment_id <= 0 ||
        !in_array($new_status, $allowed_statuses, true)
    ) {

        $error = 'Invalid appointment information.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Verify appointment belongs to this telecaller's lead
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                a.id,
                a.lead_id,
                a.status,
                l.name AS lead_name
            FROM appointments a
            INNER JOIN leads l
                ON a.lead_id = l.id
            WHERE a.id = ?
              AND l.assigned_to = ?
            LIMIT 1
        ");

        $stmt->execute([
            $appointment_id,
            $user['id']
        ]);

        $appointment = $stmt->fetch();

        if (!$appointment) {

            $error =
                'Appointment not found or not assigned to you.';

        } else {

            try {

                $pdo->beginTransaction();

                /*
                |--------------------------------------------------------------------------
                | Update Appointment
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    UPDATE appointments
                    SET status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $new_status,
                    $appointment_id
                ]);

                /*
                |--------------------------------------------------------------------------
                | Add Activity Timeline
                |--------------------------------------------------------------------------
                */

                $description =
                    'Appointment status changed to ' .
                    $new_status . '.';

                $stmt = $pdo->prepare("
                    INSERT INTO lead_activities (
                        lead_id,
                        user_id,
                        activity_type,
                        description,
                        activity_at
                    )
                    VALUES (?, ?, 'Appointment', ?, NOW())
                ");

                $stmt->execute([
                    $appointment['lead_id'],
                    $user['id'],
                    $description
                ]);

                $pdo->commit();

                $success =
                    'Appointment status updated successfully.';

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error =
                    'Unable to update appointment status.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get Telecaller's Appointments
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
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
        l.service_interest

    FROM appointments a

    INNER JOIN leads l
        ON a.lead_id = l.id

    WHERE l.assigned_to = ?

    ORDER BY
        CASE
            WHEN a.status = 'Scheduled' THEN 1
            WHEN a.status = 'Confirmed' THEN 2
            WHEN a.status = 'Completed' THEN 3
            WHEN a.status = 'No Show' THEN 4
            WHEN a.status = 'Cancelled' THEN 5
            ELSE 6
        END,

        a.appointment_date ASC
");

$stmt->execute([
    $user['id']
]);

$appointments = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Appointment Counts
|--------------------------------------------------------------------------
*/

$scheduled_count = 0;
$confirmed_count = 0;
$completed_count = 0;
$cancelled_count = 0;
$no_show_count = 0;

foreach ($appointments as $appointment) {

    switch ($appointment['status']) {

        case 'Scheduled':
            $scheduled_count++;
            break;

        case 'Confirmed':
            $confirmed_count++;
            break;

        case 'Completed':
            $completed_count++;
            break;

        case 'Cancelled':
            $cancelled_count++;
            break;

        case 'No Show':
            $no_show_count++;
            break;
    }
}


$page_title = 'Appointments';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Appointments
            </h2>

            <p class="hm-muted mb-0">
                Manage appointments for your assigned leads.
            </p>

        </div>

        <a
            href="<?php echo BASE_URL; ?>/telecaller/dashboard.php"
            class="btn btn-outline-secondary"
        >
            My Day
        </a>

    </div>


    <!-- Alerts -->

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


    <!-- Summary Cards -->

    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="hm-card p-4">

                <div class="hm-muted">
                    Scheduled
                </div>

                <h2 class="mb-0">
                    <?php echo $scheduled_count; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4">

                <div class="hm-muted">
                    Confirmed
                </div>

                <h2 class="mb-0 text-success">
                    <?php echo $confirmed_count; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4">

                <div class="hm-muted">
                    Completed
                </div>

                <h2 class="mb-0 hm-gold">
                    <?php echo $completed_count; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4">

                <div class="hm-muted">
                    Total
                </div>

                <h2 class="mb-0">
                    <?php echo count($appointments); ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- Appointment Table -->

    <div class="hm-card p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="mb-0">
                My Appointments
            </h5>

            <span class="hm-muted">
                <?php echo count($appointments); ?> appointment(s)
            </span>

        </div>


        <?php if (empty($appointments)): ?>

            <div class="alert alert-light mb-0">

                No appointments found for your assigned leads.

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>Lead</th>

                            <th>Phone</th>

                            <th>Service</th>

                            <th>Date & Time</th>

                            <th>Type</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($appointments as $appointment): ?>

                        <tr>

                            <!-- Lead -->

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['lead_name']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- Phone -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $appointment['lead_phone']
                                );
                                ?>

                            </td>


                            <!-- Service -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $appointment['service_interest']
                                    ?: '-'
                                );
                                ?>

                            </td>


                            <!-- Date -->

                            <td>

                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $appointment['appointment_date']
                                    )
                                );
                                ?>

                            </td>


                            <!-- Type -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $appointment['appointment_type']
                                    ?: '-'
                                );
                                ?>

                            </td>


                            <!-- Status -->

                            <td>

                                <?php

                                $status_class = 'bg-secondary';

                                if (
                                    $appointment['status']
                                    === 'Scheduled'
                                ) {
                                    $status_class = 'bg-primary';
                                }

                                if (
                                    $appointment['status']
                                    === 'Confirmed'
                                ) {
                                    $status_class = 'bg-success';
                                }

                                if (
                                    $appointment['status']
                                    === 'Completed'
                                ) {
                                    $status_class = 'bg-dark';
                                }

                                if (
                                    $appointment['status']
                                    === 'Cancelled'
                                ) {
                                    $status_class = 'bg-danger';
                                }

                                if (
                                    $appointment['status']
                                    === 'No Show'
                                ) {
                                    $status_class = 'bg-warning text-dark';
                                }

                                ?>

                                <span
                                    class="badge <?php echo $status_class; ?>"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['status']
                                    );
                                    ?>
                                </span>

                            </td>


                            <!-- Action -->

                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $appointment['lead_id']; ?>"
                                    class="btn btn-sm btn-outline-secondary mb-1"
                                >
                                    View Lead
                                </a>


                                <form
                                    method="POST"
                                    class="d-inline"
                                >

                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?php echo (int) $appointment['id']; ?>"
                                    >


                                    <select
                                        name="status"
                                        class="form-select form-select-sm d-inline-block"
                                        style="width: 140px;"
                                        onchange="this.form.submit()"
                                    >

                                        <option value="">
                                            Update Status
                                        </option>

                                        <option
                                            value="Scheduled"
                                            <?php
                                            echo $appointment['status']
                                                === 'Scheduled'
                                                ? 'selected'
                                                : '';
                                            ?>
                                        >
                                            Scheduled
                                        </option>

                                        <option
                                            value="Confirmed"
                                            <?php
                                            echo $appointment['status']
                                                === 'Confirmed'
                                                ? 'selected'
                                                : '';
                                            ?>
                                        >
                                            Confirmed
                                        </option>

                                        <option
                                            value="Completed"
                                            <?php
                                            echo $appointment['status']
                                                === 'Completed'
                                                ? 'selected'
                                                : '';
                                            ?>
                                        >
                                            Completed
                                        </option>

                                        <option
                                            value="Cancelled"
                                            <?php
                                            echo $appointment['status']
                                                === 'Cancelled'
                                                ? 'selected'
                                                : '';
                                            ?>
                                        >
                                            Cancelled
                                        </option>

                                        <option
                                            value="No Show"
                                            <?php
                                            echo $appointment['status']
                                                === 'No Show'
                                                ? 'selected'
                                                : '';
                                            ?>
                                        >
                                            No Show
                                        </option>

                                    </select>

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

require_once __DIR__ . '/../../includes/footer.php';

?>