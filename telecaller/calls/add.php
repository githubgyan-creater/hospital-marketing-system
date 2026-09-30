 <?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/permission_check.php';

require_role('telecaller');
require_permission('calls.create');

$user = current_user();

$lead_id = isset($_GET['lead_id'])
    ? (int) $_GET['lead_id']
    : 0;

if ($lead_id <= 0) {

    http_response_code(400);

    exit('Invalid lead.');
}


/*
|--------------------------------------------------------------------------
| GET LEAD
|--------------------------------------------------------------------------
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

$lead = $stmt->fetch(
    PDO::FETCH_ASSOC
);

if (!$lead) {

    http_response_code(404);

    exit('Lead not found or not assigned to you.');
}


/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/

$error = '';

$call_outcome = '';
$call_notes = '';
$next_action_type = '';
$next_action_at = '';


/*
|--------------------------------------------------------------------------
| FORM PROCESSING
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $call_outcome = trim(
        $_POST['call_outcome'] ?? ''
    );

    $call_notes = trim(
        $_POST['call_notes'] ?? ''
    );

    $next_action_type = trim(
        $_POST['next_action_type'] ?? ''
    );

    $next_action_at = trim(
        $_POST['next_action_at'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | ALLOWED CALL OUTCOMES
    |--------------------------------------------------------------------------
    */

    $allowed_outcomes = [
        'Connected',
        'Not Connected',
        'Call Back',
        'Interested',
        'Not Interested',
        'Appointment Requested'
    ];


    /*
    |--------------------------------------------------------------------------
    | VALIDATE CALL OUTCOME
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $call_outcome,
            $allowed_outcomes,
            true
        )
    ) {

        $error =
            'Please select a valid call outcome.';

    } elseif ($call_notes === '') {

        $error =
            'Please enter call notes.';
    }


    /*
    |--------------------------------------------------------------------------
    | CONVERT NEXT ACTION DATETIME
    |--------------------------------------------------------------------------
    */

    $next_action_datetime = null;

    if (
        $error === '' &&
        $next_action_at !== ''
    ) {

        $timestamp = strtotime(
            str_replace(
                'T',
                ' ',
                $next_action_at
            )
        );

        if ($timestamp === false) {

            $error =
                'Invalid next action date/time.';

        } else {

            $next_action_datetime = date(
                'Y-m-d H:i:s',
                $timestamp
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE NEXT ACTION
    |--------------------------------------------------------------------------
    */

    if (
        $error === '' &&
        $next_action_type !== '' &&
        $next_action_datetime === null
    ) {

        $error =
            'Please select a date and time for the next action.';
    }


    /*
    |--------------------------------------------------------------------------
    | AUTOMATIC LEAD STATUS
    |--------------------------------------------------------------------------
    */

    $new_status = '';

    switch ($call_outcome) {

        case 'Connected':

            $new_status = 'Contacted';

            break;

        case 'Not Connected':

            $new_status = 'Follow-up Required';

            break;

        case 'Call Back':

            $new_status = 'Follow-up Required';

            break;

        case 'Interested':

            $new_status = 'Interested';

            break;

        case 'Not Interested':

            $new_status = 'Not Interested';

            break;

        case 'Appointment Requested':

            $new_status = 'Appointment Requested';

            break;
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE EVERYTHING
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | INSERT CALL
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO lead_calls
                (
                    lead_id,
                    user_id,
                    call_outcome,
                    call_notes,
                    next_action_type,
                    next_action_at,
                    call_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW()
                )
            ");

            $stmt->execute([
                $lead_id,
                $user['id'],
                $call_outcome,
                $call_notes,
                $next_action_type !== ''
                    ? $next_action_type
                    : null,
                $next_action_datetime
            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE LEAD STATUS + NEXT ACTION
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE leads
                SET
                    status = ?,
                    next_action_type = ?,
                    next_action_at = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
                  AND assigned_to = ?
            ");

            $stmt->execute([
                $new_status,
                $next_action_type !== ''
                    ? $next_action_type
                    : null,
                $next_action_datetime,
                $lead_id,
                $user['id']
            ]);


            /*
            |--------------------------------------------------------------------------
            | ADD CALL ACTIVITY
            |--------------------------------------------------------------------------
            */

            $activity_description =
                'Call outcome: '
                . $call_outcome
                . '. '
                . $call_notes;

            $stmt = $pdo->prepare("
                INSERT INTO lead_activities
                (
                    lead_id,
                    user_id,
                    activity_type,
                    description,
                    activity_at
                )
                VALUES
                (
                    ?,
                    ?,
                    'Call',
                    ?,
                    NOW()
                )
            ");

            $stmt->execute([
                $lead_id,
                $user['id'],
                $activity_description
            ]);


            /*
            |--------------------------------------------------------------------------
            | ADD STATUS CHANGE ACTIVITY
            |--------------------------------------------------------------------------
            */

            if (
                $lead['status'] !== $new_status
            ) {

                $status_description =
                    'Lead status changed from "'
                    . $lead['status']
                    . '" to "'
                    . $new_status
                    . '" after call outcome "'
                    . $call_outcome
                    . '".';


                $stmt = $pdo->prepare("
                    INSERT INTO lead_activities
                    (
                        lead_id,
                        user_id,
                        activity_type,
                        description,
                        activity_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        'Status Changed',
                        ?,
                        NOW()
                    )
                ");

                $stmt->execute([
                    $lead_id,
                    $user['id'],
                    $status_description
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | COMMIT TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            header(
                'Location: '
                . BASE_URL
                . '/leads/view.php?id='
                . $lead_id
            );

            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }

            $error =
                'Unable to save call. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

$page_title = 'Record Call';

require_once __DIR__ . '/../../includes/header.php';

?>


<div class="container py-4">


    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Record Call
            </h2>

            <p class="hm-muted mb-0">
                Record the communication outcome for this lead.
            </p>

        </div>


        <a
            href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo $lead_id; ?>"
            class="btn btn-outline-secondary"
        >
            Back to Lead
        </a>

    </div>


    <!-- LEAD INFORMATION -->

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


            <div class="col-md-6">

                <strong>Current Status</strong>

                <div class="mt-1">

                    <span class="badge bg-secondary">

                        <?php
                        echo htmlspecialchars(
                            $lead['status']
                        );
                        ?>

                    </span>

                </div>

            </div>

        </div>

    </div>


    <!-- ERROR -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- CALL FORM -->

    <div class="hm-card p-4">

        <h5 class="mb-4">
            Call Details
        </h5>


        <form method="POST">


            <div class="row g-3">


                <!-- CALL OUTCOME -->

                <div class="col-md-6">

                    <label
                        for="call_outcome"
                        class="form-label"
                    >

                        Call Outcome

                        <span class="text-danger">
                            *
                        </span>

                    </label>


                    <select
                        name="call_outcome"
                        id="call_outcome"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Outcome
                        </option>

                        <option
                            value="Connected"
                            <?php
                            echo $call_outcome === 'Connected'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Connected
                        </option>

                        <option
                            value="Not Connected"
                            <?php
                            echo $call_outcome === 'Not Connected'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Not Connected
                        </option>

                        <option
                            value="Call Back"
                            <?php
                            echo $call_outcome === 'Call Back'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Call Back
                        </option>

                        <option
                            value="Interested"
                            <?php
                            echo $call_outcome === 'Interested'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Interested
                        </option>

                        <option
                            value="Not Interested"
                            <?php
                            echo $call_outcome === 'Not Interested'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Not Interested
                        </option>

                        <option
                            value="Appointment Requested"
                            <?php
                            echo $call_outcome === 'Appointment Requested'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Appointment Requested
                        </option>

                    </select>

                </div>


                <!-- NEXT ACTION -->

                <div class="col-md-6">

                    <label
                        for="next_action_type"
                        class="form-label"
                    >
                        Next Action
                    </label>


                    <select
                        name="next_action_type"
                        id="next_action_type"
                        class="form-select"
                    >

                        <option value="">
                            No Next Action
                        </option>

                        <option
                            value="Call"
                            <?php
                            echo $next_action_type === 'Call'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Call
                        </option>

                        <option
                            value="Follow-up"
                            <?php
                            echo $next_action_type === 'Follow-up'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Follow-up
                        </option>

                        <option
                            value="WhatsApp"
                            <?php
                            echo $next_action_type === 'WhatsApp'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            WhatsApp
                        </option>

                        <option
                            value="Appointment"
                            <?php
                            echo $next_action_type === 'Appointment'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Appointment
                        </option>

                        <option
                            value="Visit"
                            <?php
                            echo $next_action_type === 'Visit'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Visit
                        </option>

                    </select>

                </div>


                <!-- NOTES -->

                <div class="col-12">

                    <label
                        for="call_notes"
                        class="form-label"
                    >

                        Call Notes

                        <span class="text-danger">
                            *
                        </span>

                    </label>


                    <textarea
                        name="call_notes"
                        id="call_notes"
                        rows="5"
                        class="form-control"
                        placeholder="Enter important details from the call..."
                        required
                    ><?php
                    echo htmlspecialchars(
                        $call_notes
                    );
                    ?></textarea>

                </div>


                <!-- NEXT ACTION DATE -->

                <div class="col-md-6">

                    <label
                        for="next_action_at"
                        class="form-label"
                    >
                        Next Action Date & Time
                    </label>


                    <input
                        type="datetime-local"
                        name="next_action_at"
                        id="next_action_at"
                        class="form-control"
                        value="<?php
                        echo htmlspecialchars(
                            $next_action_at
                        );
                        ?>"
                    >

                </div>


                <!-- SUBMIT -->

                <div class="col-12 mt-4">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Save Call
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