<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

require_login();

$user = current_user();

if (!$user || empty($user['id'])) {
    http_response_code(403);
    exit('Access denied.');
}

$current_role = $user['role'];

if (!in_array($current_role, ['manager', 'marketing'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

$visit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($visit_id <= 0) {
    exit('Invalid visit ID.');
}


/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    $outcome = trim($_POST['outcome'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $next_action_type = trim($_POST['next_action_type'] ?? '');
    $next_action_at = trim($_POST['next_action_at'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | CHECK ACCESS TO VISIT
    |--------------------------------------------------------------------------
    */

    if ($current_role === 'marketing') {

        $check_stmt = $pdo->prepare("
            SELECT *
            FROM marketing_visits
            WHERE id = ?
              AND assigned_to = ?
            LIMIT 1
        ");

        $check_stmt->execute([
            $visit_id,
            $user['id']
        ]);

    } else {

        $check_stmt = $pdo->prepare("
            SELECT *
            FROM marketing_visits
            WHERE id = ?
            LIMIT 1
        ");

        $check_stmt->execute([
            $visit_id
        ]);
    }

    $visit = $check_stmt->fetch();

    if (!$visit) {
        exit('Visit not found or access denied.');
    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETE VISIT
    |--------------------------------------------------------------------------
    */

    if ($action === 'complete') {

        $next_action_at_db = null;

        if ($next_action_at !== '') {
            $next_action_at_db = $next_action_at;
        }


        if ($outcome === '') {
            exit('Please enter the visit outcome.');
        }


        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | UPDATE VISIT
            |--------------------------------------------------------------------------
            */

            $update_stmt = $pdo->prepare("
                UPDATE marketing_visits
                SET
                    status = 'Completed',
                    outcome = ?,
                    remarks = ?,
                    next_action_type = ?,
                    next_action_at = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $update_stmt->execute([
                $outcome,
                $remarks !== '' ? $remarks : null,
                $next_action_type !== ''
                    ? $next_action_type
                    : null,
                $next_action_at_db,
                $visit_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE RELATED LEAD
            |--------------------------------------------------------------------------
            */

            if (!empty($visit['related_lead_id'])) {

                $lead_id = (int) $visit['related_lead_id'];


                $lead_stmt = $pdo->prepare("
                    UPDATE leads
                    SET
                        status = CASE
                            WHEN status IN ('Converted', 'Lost')
                            THEN status
                            ELSE 'Visited'
                        END,
                        next_action_type = ?,
                        next_action_at = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");

                $lead_stmt->execute([
                    $next_action_type !== ''
                        ? $next_action_type
                        : null,

                    $next_action_at_db,

                    $lead_id
                ]);


                /*
                |--------------------------------------------------------------------------
                | CREATE LEAD ACTIVITY
                |--------------------------------------------------------------------------
                */

                $activity_notes =
                    'Marketing visit completed.';

                $activity_notes .=
                    ' Outcome: ' . $outcome;

                if ($remarks !== '') {

                    $activity_notes .=
                        ' Remarks: ' . $remarks;
                }

                if ($next_action_type !== '') {

                    $activity_notes .=
                        ' Next Action: ' .
                        $next_action_type;
                }


                /*
                | IMPORTANT:
                | Using the actual lead_activities columns:
                | lead_id
                | user_id
                | activity_type
                | description
                | activity_at
                */

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
                        CURRENT_TIMESTAMP
                    )
                ");

                $activity_stmt->execute([
                    $lead_id,
                    $user['id'],
                    'Marketing Visit Completed',
                    $activity_notes
                ]);
            }


            $pdo->commit();


            header(
                'Location: ' .
                BASE_URL .
                '/marketing/visits/view.php?id=' .
                $visit_id .
                '&success=completed'
            );

            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            exit(
                'Unable to complete visit: ' .
                htmlspecialchars($e->getMessage())
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CANCEL VISIT
    |--------------------------------------------------------------------------
    */

    if ($action === 'cancel') {

        $update_stmt = $pdo->prepare("
            UPDATE marketing_visits
            SET
                status = 'Cancelled',
                remarks = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $update_stmt->execute([
            $remarks !== '' ? $remarks : null,
            $visit_id
        ]);


        header(
            'Location: ' .
            BASE_URL .
            '/marketing/visits/view.php?id=' .
            $visit_id .
            '&success=cancelled'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| LOAD VISIT
|--------------------------------------------------------------------------
*/

if ($current_role === 'marketing') {

    $stmt = $pdo->prepare("
        SELECT
            mv.*,

            assigned_user.name AS assigned_staff_name,

            creator.name AS created_by_name,

            l.name AS lead_name,
            l.phone AS lead_phone,
            l.email AS lead_email,
            l.status AS lead_status,

            t.title AS task_title,
            t.status AS task_status

        FROM marketing_visits mv

        INNER JOIN users assigned_user
            ON mv.assigned_to = assigned_user.id

        INNER JOIN users creator
            ON mv.created_by = creator.id

        LEFT JOIN leads l
            ON mv.related_lead_id = l.id

        LEFT JOIN tasks t
            ON mv.related_task_id = t.id

        WHERE mv.id = ?
          AND mv.assigned_to = ?

        LIMIT 1
    ");

    $stmt->execute([
        $visit_id,
        $user['id']
    ]);

} else {

    $stmt = $pdo->prepare("
        SELECT
            mv.*,

            assigned_user.name AS assigned_staff_name,

            creator.name AS created_by_name,

            l.name AS lead_name,
            l.phone AS lead_phone,
            l.email AS lead_email,
            l.status AS lead_status,

            t.title AS task_title,
            t.status AS task_status

        FROM marketing_visits mv

        INNER JOIN users assigned_user
            ON mv.assigned_to = assigned_user.id

        INNER JOIN users creator
            ON mv.created_by = creator.id

        LEFT JOIN leads l
            ON mv.related_lead_id = l.id

        LEFT JOIN tasks t
            ON mv.related_task_id = t.id

        WHERE mv.id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $visit_id
    ]);
}

$visit = $stmt->fetch();

if (!$visit) {
    exit('Visit not found or access denied.');
}

?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<div class="container py-4">


    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Visit Details
            </h2>

            <p class="text-muted mb-0">
                View visit information, outcome and next action.
            </p>

        </div>


        <div>

            <?php if ($current_role === 'manager'): ?>

                <a
                    href="<?php echo BASE_URL; ?>/manager/visits/index.php"
                    class="btn btn-outline-secondary"
                >
                    Back to Visits
                </a>

            <?php else: ?>

                <a
                    href="<?php echo BASE_URL; ?>/marketing/visits/index.php"
                    class="btn btn-outline-secondary"
                >
                    Back to My Visits
                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- SUCCESS MESSAGE -->

    <?php if (
        isset($_GET['success']) &&
        $_GET['success'] === 'completed'
    ): ?>

        <div class="alert alert-success">
            Visit completed successfully.
        </div>

    <?php endif; ?>


    <?php if (
        isset($_GET['success']) &&
        $_GET['success'] === 'cancelled'
    ): ?>

        <div class="alert alert-warning">
            Visit cancelled successfully.
        </div>

    <?php endif; ?>


    <div class="row g-4">


        <!-- LEFT COLUMN -->

        <div class="col-lg-8">


            <!-- VISIT INFORMATION -->

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Visit Information
                    </h5>

                </div>


                <div class="card-body">

                    <div class="row g-3">


                        <div class="col-md-6">

                            <strong>
                                Visit Type
                            </strong>

                            <div>

                                <?php
                                echo htmlspecialchars(
                                    $visit['visit_type']
                                );
                                ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <strong>
                                Status
                            </strong>

                            <div>


                                <?php if (
                                    $visit['status'] === 'Completed'
                                ): ?>

                                    <span class="badge bg-success">
                                        Completed
                                    </span>


                                <?php elseif (
                                    $visit['status'] === 'Cancelled'
                                ): ?>

                                    <span class="badge bg-danger">
                                        Cancelled
                                    </span>


                                <?php else: ?>

                                    <span class="badge bg-warning text-dark">
                                        Planned
                                    </span>

                                <?php endif; ?>


                            </div>

                        </div>


                        <div class="col-md-6">

                            <strong>
                                Title
                            </strong>

                            <div>

                                <?php
                                echo htmlspecialchars(
                                    $visit['title']
                                );
                                ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <strong>
                                Person Name
                            </strong>

                            <div>

                                <?php
                                echo htmlspecialchars(
                                    $visit['person_name'] ?: '-'
                                );
                                ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <strong>
                                Organization
                            </strong>

                            <div>

                                <?php
                                echo htmlspecialchars(
                                    $visit['organization_name'] ?: '-'
                                );
                                ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <strong>
                                Phone
                            </strong>

                            <div>

                                <?php
                                echo htmlspecialchars(
                                    $visit['phone'] ?: '-'
                                );
                                ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <strong>
                                Email
                            </strong>

                            <div>

                                <?php
                                echo htmlspecialchars(
                                    $visit['email'] ?: '-'
                                );
                                ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <strong>
                                Visit Date
                            </strong>

                            <div>

                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $visit['visit_date']
                                    )
                                );
                                ?>

                            </div>

                        </div>


                        <div class="col-12">

                            <strong>
                                Location
                            </strong>

                            <div>

                                <?php
                                echo htmlspecialchars(
                                    $visit['location'] ?: '-'
                                );
                                ?>

                            </div>

                        </div>


                        <div class="col-12">

                            <strong>
                                Purpose
                            </strong>

                            <div>

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $visit['purpose'] ?: '-'
                                    )
                                );
                                ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <strong>
                                Assigned Staff
                            </strong>

                            <div>

                                <?php
                                echo htmlspecialchars(
                                    $visit['assigned_staff_name']
                                );
                                ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <strong>
                                Created By
                            </strong>

                            <div>

                                <?php
                                echo htmlspecialchars(
                                    $visit['created_by_name']
                                );
                                ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- RELATED LEAD -->

            <?php if (!empty($visit['related_lead_id'])): ?>

                <div class="card shadow-sm border-0 mt-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Related Lead
                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row g-3">


                            <div class="col-md-6">

                                <strong>
                                    Lead Name
                                </strong>

                                <div>

                                    <?php
                                    echo htmlspecialchars(
                                        $visit['lead_name'] ?: '-'
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="col-md-6">

                                <strong>
                                    Lead Status
                                </strong>

                                <div>

                                    <?php
                                    echo htmlspecialchars(
                                        $visit['lead_status'] ?: '-'
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="col-md-6">

                                <strong>
                                    Phone
                                </strong>

                                <div>

                                    <?php
                                    echo htmlspecialchars(
                                        $visit['lead_phone'] ?: '-'
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="col-md-6">

                                <strong>
                                    Email
                                </strong>

                                <div>

                                    <?php
                                    echo htmlspecialchars(
                                        $visit['lead_email'] ?: '-'
                                    );
                                    ?>

                                </div>

                            </div>


                        </div>


                        <div class="mt-3">

                            <a
                                href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $visit['related_lead_id']; ?>"
                                class="btn btn-sm btn-outline-primary"
                            >
                                View Lead
                            </a>

                        </div>

                    </div>

                </div>

            <?php endif; ?>


            <!-- RELATED TASK -->

            <?php if (!empty($visit['related_task_id'])): ?>

                <div class="card shadow-sm border-0 mt-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Related Task
                        </h5>

                    </div>


                    <div class="card-body">

                        <strong>
                            Task
                        </strong>

                        <div>

                            <?php
                            echo htmlspecialchars(
                                $visit['task_title'] ?: '-'
                            );
                            ?>

                        </div>


                        <div class="mt-2">

                            <strong>
                                Status:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $visit['task_status'] ?: '-'
                            );
                            ?>

                        </div>

                    </div>

                </div>

            <?php endif; ?>


        </div>


        <!-- RIGHT COLUMN -->

        <div class="col-lg-4">


            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Visit Outcome
                    </h5>

                </div>


                <div class="card-body">


                    <?php if (
                        $visit['status'] === 'Planned'
                    ): ?>


                        <!-- COMPLETE VISIT FORM -->

                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="complete"
                            >


                            <div class="mb-3">

                                <label class="form-label">
                                    Outcome
                                </label>

                                <textarea
                                    name="outcome"
                                    class="form-control"
                                    rows="4"
                                    placeholder="What happened during the visit?"
                                    required
                                ></textarea>

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Remarks
                                </label>

                                <textarea
                                    name="remarks"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Additional remarks"
                                ></textarea>

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Next Action
                                </label>

                                <input
                                    type="text"
                                    name="next_action_type"
                                    class="form-control"
                                    placeholder="Example: Follow-up Call"
                                >

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Next Action Date & Time
                                </label>

                                <input
                                    type="datetime-local"
                                    name="next_action_at"
                                    class="form-control"
                                >

                            </div>


                            <button
                                type="submit"
                                class="btn btn-success w-100 mb-2"
                            >
                                Complete Visit
                            </button>

                        </form>


                        <!-- CANCEL VISIT FORM -->

                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="cancel"
                            >

                            <button
                                type="submit"
                                class="btn btn-outline-danger w-100"
                                onclick="return confirm('Cancel this visit?');"
                            >
                                Cancel Visit
                            </button>

                        </form>


                    <?php else: ?>


                        <!-- SAVED OUTCOME -->

                        <div class="mb-3">

                            <strong>
                                Outcome
                            </strong>

                            <div class="mt-1">

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $visit['outcome'] ?: '-'
                                    )
                                );
                                ?>

                            </div>

                        </div>


                        <div class="mb-3">

                            <strong>
                                Remarks
                            </strong>

                            <div class="mt-1">

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $visit['remarks'] ?: '-'
                                    )
                                );
                                ?>

                            </div>

                        </div>


                        <div class="mb-3">

                            <strong>
                                Next Action
                            </strong>

                            <div class="mt-1">

                                <?php
                                echo htmlspecialchars(
                                    $visit['next_action_type'] ?: '-'
                                );
                                ?>

                            </div>

                        </div>


                        <div class="mb-3">

                            <strong>
                                Next Action Date
                            </strong>

                            <div class="mt-1">

                                <?php if (
                                    !empty(
                                        $visit['next_action_at']
                                    )
                                ): ?>

                                    <?php
                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $visit['next_action_at']
                                        )
                                    );
                                    ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- FEEDBACK BUTTON -->

                        <?php if (
                            $current_role === 'marketing' &&
                            $visit['status'] === 'Completed'
                        ): ?>

                            <a
                                href="<?php echo BASE_URL; ?>/marketing/visits/feedback.php?id=<?php echo (int) $visit['id']; ?>"
                                class="btn btn-primary w-100"
                            >
                                Add / Update Feedback
                            </a>

                        <?php endif; ?>


                    <?php endif; ?>


                </div>

            </div>


        </div>


    </div>

</div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>