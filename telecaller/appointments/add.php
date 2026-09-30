<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('telecaller');

$user = current_user();

$lead_id = isset($_GET['lead_id'])
    ? (int) $_GET['lead_id']
    : 0;

if ($lead_id <= 0) {
    exit('Invalid lead.');
}

/*

| Get Assigned Lead

*/

$stmt = $pdo->prepare("
    SELECT
        l.*,
        ms.name AS source_name
    FROM leads l
    LEFT JOIN marketing_sources ms
        ON l.source_id = ms.id
    WHERE l.id = ?
      AND l.assigned_to = ?
    LIMIT 1
");

$stmt->execute([
    $lead_id,
    $user['id']
]);

$lead = $stmt->fetch();

if (!$lead) {
    http_response_code(404);
    exit('Lead not found or not assigned to you.');
}


/*

| Form Processing

*/

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $appointment_date = trim(
        $_POST['appointment_date'] ?? ''
    );

    $appointment_type = trim(
        $_POST['appointment_type'] ?? ''
    );

    $notes = trim(
        $_POST['notes'] ?? ''
    );

    if ($appointment_date === '') {

        $error = 'Please select appointment date and time.';

    } else {

        $timestamp = strtotime($appointment_date);

        if ($timestamp === false) {

            $error = 'Invalid appointment date and time.';

        } elseif ($timestamp < time()) {

            $error = 'Appointment date and time cannot be in the past.';

        } else {

            $appointment_datetime =
                date(
                    'Y-m-d H:i:s',
                    $timestamp
                );

            try {

                $pdo->beginTransaction();

                /*
                
                | Create Appointment
                
                */

                $stmt = $pdo->prepare("
                    INSERT INTO appointments (
                        lead_id,
                        created_by,
                        appointment_date,
                        appointment_type,
                        notes,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, 'Scheduled')
                ");

                $stmt->execute([
                    $lead_id,
                    $user['id'],
                    $appointment_datetime,
                    $appointment_type !== ''
                        ? $appointment_type
                        : null,
                    $notes !== ''
                        ? $notes
                        : null
                ]);

                /*
                
                | Update Lead Status
                
                */

                $stmt = $pdo->prepare("
                    UPDATE leads
                    SET status = 'Appointment Requested'
                    WHERE id = ?
                ");

                $stmt->execute([
                    $lead_id
                ]);

                /*
                
                | Add Activity
                
                */

                $description =
                    'Appointment created for ' .
                    date(
                        'd M Y, h:i A',
                        $timestamp
                    );

                if ($appointment_type !== '') {

                    $description .=
                        '. Type: ' .
                        $appointment_type;
                }

                if ($notes !== '') {

                    $description .=
                        '. Notes: ' .
                        $notes;
                }

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
                    $lead_id,
                    $user['id'],
                    $description
                ]);

                $pdo->commit();

                /*
                
                | Redirect
                
                */

                header(
                    'Location: ' .
                    BASE_URL .
                    '/leads/view.php?id=' .
                    $lead_id
                );

                exit;

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error =
                    'Unable to create appointment. Please try again.';
            }
        }
    }
}


$page_title = 'Create Appointment';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <!-- Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Create Appointment
            </h2>

            <p class="hm-muted mb-0">
                Schedule an appointment for this lead.
            </p>

        </div>

        <a
            href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo $lead_id; ?>"
            class="btn btn-outline-secondary"
        >
            Back to Lead
        </a>

    </div>


    <!-- Lead Information -->

    <div class="hm-card p-4 mb-4">

        <h5 class="mb-3">
            Lead Information
        </h5>

        <div class="row g-3">

            <div class="col-md-6">

                <strong>Name</strong>

                <div class="mt-1">
                    <?php
                    echo htmlspecialchars(
                        $lead['name']
                    );
                    ?>
                </div>

            </div>


            <div class="col-md-6">

                <strong>Phone</strong>

                <div class="mt-1">
                    <?php
                    echo htmlspecialchars(
                        $lead['phone']
                    );
                    ?>
                </div>

            </div>


            <div class="col-md-6">

                <strong>Service Interest</strong>

                <div class="mt-1">
                    <?php
                    echo htmlspecialchars(
                        $lead['service_interest']
                        ?: '-'
                    );
                    ?>
                </div>

            </div>


            <div class="col-md-6">

                <strong>Source</strong>

                <div class="mt-1">
                    <?php
                    echo htmlspecialchars(
                        $lead['source_name']
                        ?: '-'
                    );
                    ?>
                </div>

            </div>

        </div>

    </div>


    <!-- Error -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            <?php
            echo htmlspecialchars($error);
            ?>
        </div>

    <?php endif; ?>


    <!-- Appointment Form -->

    <div class="hm-card p-4">

        <h5 class="mb-4">
            Appointment Details
        </h5>

        <form method="POST">

            <div class="row g-3">

                <!-- Appointment Date -->

                <div class="col-md-6">

                    <label
                        for="appointment_date"
                        class="form-label"
                    >
                        Appointment Date & Time
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="datetime-local"
                        name="appointment_date"
                        id="appointment_date"
                        class="form-control"
                        required
                    >

                </div>


                <!-- Appointment Type -->

                <div class="col-md-6">

                    <label
                        for="appointment_type"
                        class="form-label"
                    >
                        Appointment Type
                    </label>

                    <select
                        name="appointment_type"
                        id="appointment_type"
                        class="form-select"
                    >

                        <option value="">
                            Select Type
                        </option>

                        <option value="Consultation">
                            Consultation
                        </option>

                        <option value="Doctor Visit">
                            Doctor Visit
                        </option>

                        <option value="Hospital Visit">
                            Hospital Visit
                        </option>

                        <option value="Follow-up">
                            Follow-up
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>


                <!-- Notes -->

                <div class="col-12">

                    <label
                        for="notes"
                        class="form-label"
                    >
                        Notes
                    </label>

                    <textarea
                        name="notes"
                        id="notes"
                        rows="4"
                        class="form-control"
                        placeholder="Enter appointment-related notes..."
                    ></textarea>

                </div>


                <!-- Buttons -->

                <div class="col-12 mt-4">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Create Appointment
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo $lead_id; ?>"
                        class="btn btn-outline-secondary ms-2"
                    >
                        Cancel
                    </a>

                </div>

            </div>

        </form>

    </div>

</div>

<?php

require_once __DIR__ . '/../../includes/footer.php';

?>