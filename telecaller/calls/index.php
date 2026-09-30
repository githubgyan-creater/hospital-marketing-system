 <?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/permission_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('telecaller');
require_permission('calls.view');

$user = current_user();

$user_id = (int) $user['id'];

$error = '';
$success = '';

$selected_lead_id = '';
$call_outcome = '';
$call_notes = '';
$next_action_type = '';
$next_action_at = '';
$call_at = date('Y-m-d\TH:i');


/*
|--------------------------------------------------------------------------
| HANDLE CALL SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selected_lead_id = (int) ($_POST['lead_id'] ?? 0);

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

    $call_at = trim(
        $_POST['call_at'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALID OUTCOMES
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
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($selected_lead_id <= 0) {

        $error = 'Please select a lead.';

    } elseif (
        !in_array(
            $call_outcome,
            $allowed_outcomes,
            true
        )
    ) {

        $error = 'Please select a valid call outcome.';

    } elseif ($call_at === '') {

        $error = 'Please select the call date and time.';
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK LEAD ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $lead_check_stmt = $pdo->prepare("
            SELECT
                id,
                name,
                status
            FROM leads
            WHERE id = ?
              AND assigned_to = ?
            LIMIT 1
        ");

        $lead_check_stmt->execute([
            $selected_lead_id,
            $user_id
        ]);

        $lead = $lead_check_stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$lead) {

            $error =
                'Lead not found or not assigned to you.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE CALL
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Convert Date/Time Values
            |--------------------------------------------------------------------------
            */

            $call_at_mysql = null;
            $next_action_at_mysql = null;

            $call_timestamp = strtotime(
                str_replace('T', ' ', $call_at)
            );

            if ($call_timestamp === false) {

                throw new Exception(
                    'Invalid call date and time.'
                );
            }

            $call_at_mysql = date(
                'Y-m-d H:i:s',
                $call_timestamp
            );


            if ($next_action_at !== '') {

                $next_action_timestamp = strtotime(
                    str_replace('T', ' ', $next_action_at)
                );

                if ($next_action_timestamp === false) {

                    throw new Exception(
                        'Invalid next action date and time.'
                    );
                }

                $next_action_at_mysql = date(
                    'Y-m-d H:i:s',
                    $next_action_timestamp
                );
            }


            /*
            |--------------------------------------------------------------------------
            | INSERT CALL
            |--------------------------------------------------------------------------
            */

            $call_stmt = $pdo->prepare("
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
                    ?
                )
            ");

            $call_stmt->execute([
                $selected_lead_id,
                $user_id,
                $call_outcome,
                $call_notes !== ''
                    ? $call_notes
                    : null,
                $next_action_type !== ''
                    ? $next_action_type
                    : null,
                $next_action_at_mysql,
                $call_at_mysql
            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE LEAD STATUS
            |--------------------------------------------------------------------------
            */

            $new_lead_status = null;

            switch ($call_outcome) {

                case 'Connected':
                    $new_lead_status = 'Contacted';
                    break;

                case 'Interested':
                    $new_lead_status = 'Interested';
                    break;

                case 'Not Interested':
                    $new_lead_status = 'Not Interested';
                    break;

                case 'Call Back':
                    $new_lead_status = 'Follow-up';
                    break;

                case 'Appointment Requested':
                    $new_lead_status = 'Appointment';
                    break;

                case 'Not Connected':
                    $new_lead_status = 'Contacted';
                    break;
            }


            $lead_update_stmt = $pdo->prepare("
                UPDATE leads
                SET
                    status = ?,
                    next_action_type = ?,
                    next_action_at = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
                  AND assigned_to = ?
            ");

            $lead_update_stmt->execute([
                $new_lead_status,
                $next_action_type !== ''
                    ? $next_action_type
                    : null,
                $next_action_at_mysql,
                $selected_lead_id,
                $user_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | CREATE LEAD ACTIVITY
            |--------------------------------------------------------------------------
            */

            $activity_description =
                'Telecaller call logged. Outcome: '
                . $call_outcome;

            if ($call_notes !== '') {

                $activity_description .=
                    '. Notes: '
                    . $call_notes;
            }

            if ($next_action_type !== '') {

                $activity_description .=
                    '. Next Action: '
                    . $next_action_type;
            }


            $activity_stmt = $pdo->prepare("
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
                    ?,
                    ?,
                    ?
                )
            ");

            $activity_stmt->execute([
                $selected_lead_id,
                $user_id,
                'Telecaller Call',
                $activity_description,
                $call_at_mysql
            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            $success =
                'Call recorded successfully.';


            /*
            |--------------------------------------------------------------------------
            | RESET FORM
            |--------------------------------------------------------------------------
            */

            $selected_lead_id = '';
            $call_outcome = '';
            $call_notes = '';
            $next_action_type = '';
            $next_action_at = '';
            $call_at = date('Y-m-d\TH:i');

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }

            $error =
                'Unable to record call: '
                . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| LOAD ASSIGNED LEADS
|--------------------------------------------------------------------------
*/

$lead_stmt = $pdo->prepare("
    SELECT
        id,
        name,
        phone,
        status
    FROM leads
    WHERE assigned_to = ?
      AND status NOT IN ('Converted', 'Lost')
    ORDER BY name ASC
");

$lead_stmt->execute([
    $user_id
]);

$assigned_leads = $lead_stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| CALL SUMMARY
|--------------------------------------------------------------------------
*/

$summary_stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_calls,

        SUM(
            CASE
                WHEN DATE(call_at) = CURDATE()
                THEN 1
                ELSE 0
            END
        ) AS today_calls,

        SUM(
            CASE
                WHEN call_outcome = 'Connected'
                THEN 1
                ELSE 0
            END
        ) AS connected_calls,

        SUM(
            CASE
                WHEN call_outcome = 'Appointment Requested'
                THEN 1
                ELSE 0
            END
        ) AS appointment_requests

    FROM lead_calls
    WHERE user_id = ?
");

$summary_stmt->execute([
    $user_id
]);

$summary = $summary_stmt->fetch(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| CALL HISTORY
|--------------------------------------------------------------------------
*/

$history_stmt = $pdo->prepare("
    SELECT
        lc.id,
        lc.call_outcome,
        lc.call_notes,
        lc.next_action_type,
        lc.next_action_at,
        lc.call_at,
        l.name AS lead_name,
        l.phone AS lead_phone
    FROM lead_calls lc
    INNER JOIN leads l
        ON lc.lead_id = l.id
    WHERE lc.user_id = ?
    ORDER BY lc.call_at DESC
    LIMIT 50
");

$history_stmt->execute([
    $user_id
]);

$call_history = $history_stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

$page_title = 'My Calls';

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                My Calls
            </h1>

            <p class="text-muted mb-0">
                Record and manage your telecaller calls.
            </p>

        </div>

    </div>


    <!-- SUCCESS -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?php
            echo htmlspecialchars($success);
            ?>

        </div>

    <?php endif; ?>


    <!-- ERROR -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- SUMMARY -->

    <div class="row g-4 mb-4">


        <!-- Total Calls -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Calls
                    </h6>

                    <h3 class="mb-0">

                        <?php
                        echo (int) (
                            $summary['total_calls']
                            ?? 0
                        );
                        ?>

                    </h3>

                </div>

            </div>

        </div>


        <!-- Today's Calls -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Today's Calls
                    </h6>

                    <h3 class="mb-0 text-primary">

                        <?php
                        echo (int) (
                            $summary['today_calls']
                            ?? 0
                        );
                        ?>

                    </h3>

                </div>

            </div>

        </div>


        <!-- Connected -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Connected
                    </h6>

                    <h3 class="mb-0 text-success">

                        <?php
                        echo (int) (
                            $summary['connected_calls']
                            ?? 0
                        );
                        ?>

                    </h3>

                </div>

            </div>

        </div>


        <!-- Appointment Requests -->

        <div class="col-md-6 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h6 class="text-muted">
                        Appointment Requests
                    </h6>

                    <h3 class="mb-0 text-warning">

                        <?php
                        echo (int) (
                            $summary['appointment_requests']
                            ?? 0
                        );
                        ?>

                    </h3>

                </div>

            </div>

        </div>

    </div>


    <div class="row g-4">


        <!-- LOG CALL -->

        <div class="col-lg-5">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Log New Call
                    </h5>

                </div>


                <div class="card-body">

                    <form method="POST">


                        <!-- LEAD -->

                        <div class="mb-3">

                            <label class="form-label">
                                Lead
                            </label>

                            <select
                                name="lead_id"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select Lead
                                </option>

                                <?php foreach (
                                    $assigned_leads
                                    as $lead
                                ): ?>

                                    <option
                                        value="<?php
                                        echo (int) $lead['id'];
                                        ?>"
                                        <?php
                                        echo (
                                            (int) $selected_lead_id
                                            === (int) $lead['id']
                                        )
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['name']
                                        );
                                        ?>

                                        -

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['phone']
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- OUTCOME -->

                        <div class="mb-3">

                            <label class="form-label">
                                Call Outcome
                            </label>

                            <select
                                name="call_outcome"
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


                        <!-- CALL NOTES -->

                        <div class="mb-3">

                            <label class="form-label">
                                Call Notes
                            </label>

                            <textarea
                                name="call_notes"
                                class="form-control"
                                rows="3"
                                placeholder="Enter notes about the call..."
                            ><?php
                            echo htmlspecialchars(
                                $call_notes
                            );
                            ?></textarea>

                        </div>


                        <!-- NEXT ACTION -->

                        <div class="mb-3">

                            <label class="form-label">
                                Next Action
                            </label>

                            <input
                                type="text"
                                name="next_action_type"
                                class="form-control"
                                value="<?php
                                echo htmlspecialchars(
                                    $next_action_type
                                );
                                ?>"
                                placeholder="Example: Follow-up Call"
                            >

                        </div>


                        <!-- NEXT ACTION DATE -->

                        <div class="mb-3">

                            <label class="form-label">
                                Next Action Date & Time
                            </label>

                            <input
                                type="datetime-local"
                                name="next_action_at"
                                class="form-control"
                                value="<?php
                                echo htmlspecialchars(
                                    $next_action_at
                                );
                                ?>"
                            >

                        </div>


                        <!-- CALL DATE -->

                        <div class="mb-3">

                            <label class="form-label">
                                Call Date & Time
                            </label>

                            <input
                                type="datetime-local"
                                name="call_at"
                                class="form-control"
                                value="<?php
                                echo htmlspecialchars(
                                    $call_at
                                );
                                ?>"
                                required
                            >

                        </div>


                        <!-- BUTTONS -->

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Log Call
                            </button>

                            <a
                                href="<?php echo BASE_URL; ?>/telecaller/calls/index.php"
                                class="btn btn-outline-secondary"
                            >
                                Reset
                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>


        <!-- CALL HISTORY -->

        <div class="col-lg-7">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Recent Call History
                    </h5>

                </div>


                <div class="card-body p-0">

                    <?php if (empty($call_history)): ?>

                        <div class="p-4 text-muted">
                            No calls recorded yet.
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
                                            Outcome
                                        </th>

                                        <th>
                                            Notes
                                        </th>

                                        <th>
                                            Next Action
                                        </th>

                                        <th>
                                            Call Time
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $call_history
                                        as $call
                                    ): ?>

                                        <tr>

                                            <!-- Lead -->

                                            <td>

                                                <div class="fw-semibold">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $call['lead_name']
                                                    );
                                                    ?>

                                                </div>

                                                <div class="small text-muted">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $call['lead_phone']
                                                    );
                                                    ?>

                                                </div>

                                            </td>


                                            <!-- Outcome -->

                                            <td>

                                                <?php

                                                $outcome_class =
                                                    'bg-secondary';

                                                if (
                                                    $call['call_outcome']
                                                    === 'Connected'
                                                ) {

                                                    $outcome_class =
                                                        'bg-success';

                                                } elseif (
                                                    $call['call_outcome']
                                                    === 'Interested'
                                                ) {

                                                    $outcome_class =
                                                        'bg-primary';

                                                } elseif (
                                                    $call['call_outcome']
                                                    === 'Appointment Requested'
                                                ) {

                                                    $outcome_class =
                                                        'bg-warning text-dark';

                                                } elseif (
                                                    $call['call_outcome']
                                                    === 'Not Interested'
                                                ) {

                                                    $outcome_class =
                                                        'bg-danger';
                                                }

                                                ?>

                                                <span
                                                    class="badge <?php echo $outcome_class; ?>"
                                                >

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $call['call_outcome']
                                                    );
                                                    ?>

                                                </span>

                                            </td>


                                            <!-- Notes -->

                                            <td>

                                                <span class="small">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $call['call_notes']
                                                            ?: '-'
                                                    );
                                                    ?>

                                                </span>

                                            </td>


                                            <!-- Next Action -->

                                            <td>

                                                <?php
                                                echo htmlspecialchars(
                                                    $call['next_action_type']
                                                        ?: '-'
                                                );
                                                ?>

                                                <?php if (
                                                    !empty(
                                                        $call['next_action_at']
                                                    )
                                                ): ?>

                                                    <div class="small text-muted">

                                                        <?php
                                                        echo date(
                                                            'd M Y, h:i A',
                                                            strtotime(
                                                                $call[
                                                                    'next_action_at'
                                                                ]
                                                            )
                                                        );
                                                        ?>

                                                    </div>

                                                <?php endif; ?>

                                            </td>


                                            <!-- Call Time -->

                                            <td>

                                                <?php
                                                echo date(
                                                    'd M Y, h:i A',
                                                    strtotime(
                                                        $call['call_at']
                                                    )
                                                );
                                                ?>

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

    </div>

</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>