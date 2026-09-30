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

$visit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($visit_id <= 0) {
    exit('Invalid visit ID.');
}


/*

| LOAD VISIT

*/

$stmt = $pdo->prepare("
    SELECT
        mv.*,
        l.name AS lead_name,
        l.phone AS lead_phone,
        l.email AS lead_email
    FROM marketing_visits mv
    LEFT JOIN leads l
        ON mv.related_lead_id = l.id
    WHERE mv.id = ?
      AND mv.assigned_to = ?
    LIMIT 1
");

$stmt->execute([
    $visit_id,
    $user['id']
]);

$visit = $stmt->fetch();

if (!$visit) {
    exit('Visit not found or access denied.');
}


/*

| SAVE FEEDBACK

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $relationship_status =
        trim($_POST['relationship_status'] ?? '');

    $feedback =
        trim($_POST['feedback'] ?? '');

    $next_action =
        trim($_POST['next_action'] ?? '');

    $next_action_at =
        trim($_POST['next_action_at'] ?? '');

    $allowed_statuses = [
        'New Contact',
        'Positive',
        'Interested',
        'Follow-up Required',
        'Referral Potential',
        'Not Interested',
        'Inactive'
    ];

    if (!in_array($relationship_status, $allowed_statuses, true)) {
        exit('Invalid relationship status.');
    }

    /*
    
    | CHECK EXISTING FEEDBACK
    
    */

    $existing_stmt = $pdo->prepare("
        SELECT id
        FROM visit_feedback
        WHERE visit_id = ?
        LIMIT 1
    ");

    $existing_stmt->execute([
        $visit_id
    ]);

    $existing = $existing_stmt->fetch();

    if ($existing) {

        $update_stmt = $pdo->prepare("
            UPDATE visit_feedback
            SET
                relationship_status = ?,
                feedback = ?,
                next_action = ?,
                next_action_at = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $update_stmt->execute([
            $relationship_status,
            $feedback !== '' ? $feedback : null,
            $next_action !== '' ? $next_action : null,
            $next_action_at !== '' ? $next_action_at : null,
            $existing['id']
        ]);

    } else {

        $insert_stmt = $pdo->prepare("
            INSERT INTO visit_feedback
            (
                visit_id,
                relationship_status,
                feedback,
                next_action,
                next_action_at,
                created_by
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");

        $insert_stmt->execute([
            $visit_id,
            $relationship_status,
            $feedback !== '' ? $feedback : null,
            $next_action !== '' ? $next_action : null,
            $next_action_at !== '' ? $next_action_at : null,
            $user['id']
        ]);
    }


    /*
    
    | UPDATE LEAD NEXT ACTION
    
    */

    if (!empty($visit['related_lead_id'])) {

        $lead_stmt = $pdo->prepare("
            UPDATE leads
            SET
                next_action_type = ?,
                next_action_at = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $lead_stmt->execute([
            $next_action !== '' ? $next_action : null,
            $next_action_at !== '' ? $next_action_at : null,
            $visit['related_lead_id']
        ]);


        /*
        
        | RECORD ACTIVITY
        
        */

        $activity_notes =
            'Visit relationship feedback: ' .
            $relationship_status;

        if ($feedback !== '') {
            $activity_notes .= '. ' . $feedback;
        }

        $activity_stmt = $pdo->prepare("
            INSERT INTO lead_activities
            (
                lead_id,
                activity_type,
                notes,
                created_by,
                created_at
            )
            VALUES
            (
                ?,
                'Visit Relationship Feedback',
                ?,
                ?,
                CURRENT_TIMESTAMP
            )
        ");

        $activity_stmt->execute([
            $visit['related_lead_id'],
            $activity_notes,
            $user['id']
        ]);
    }


    header(
        'Location: ' .
        BASE_URL .
        '/marketing/visits/feedback.php?id=' .
        $visit_id .
        '&saved=1'
    );

    exit;
}


/*

| LOAD EXISTING FEEDBACK

*/

$feedback_stmt = $pdo->prepare("
    SELECT *
    FROM visit_feedback
    WHERE visit_id = ?
    LIMIT 1
");

$feedback_stmt->execute([
    $visit_id
]);

$feedback_data = $feedback_stmt->fetch();

?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="mb-1">Visit Feedback</h2>

            <p class="text-muted mb-0">
                Record relationship status and next action.
            </p>
        </div>

        <a
            href="<?php echo BASE_URL; ?>/marketing/visits/view.php?id=<?php echo $visit_id; ?>"
            class="btn btn-outline-secondary"
        >
            Back to Visit
        </a>

    </div>


    <?php if (isset($_GET['saved'])): ?>

        <div class="alert alert-success">
            Visit feedback saved successfully.
        </div>

    <?php endif; ?>


    <div class="row g-4">

        <div class="col-lg-5">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Visit Summary
                    </h5>

                </div>

                <div class="card-body">

                    <p>
                        <strong>Title:</strong><br>
                        <?php echo htmlspecialchars($visit['title']); ?>
                    </p>

                    <p>
                        <strong>Visit Type:</strong><br>
                        <?php echo htmlspecialchars($visit['visit_type']); ?>
                    </p>

                    <p>
                        <strong>Person:</strong><br>
                        <?php echo htmlspecialchars($visit['person_name'] ?: '-'); ?>
                    </p>

                    <p>
                        <strong>Organization:</strong><br>
                        <?php echo htmlspecialchars($visit['organization_name'] ?: '-'); ?>
                    </p>

                    <p>
                        <strong>Visit Status:</strong><br>
                        <?php echo htmlspecialchars($visit['status']); ?>
                    </p>

                    <?php if (!empty($visit['lead_name'])): ?>

                        <p>
                            <strong>Related Lead:</strong><br>
                            <?php echo htmlspecialchars($visit['lead_name']); ?>
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <div class="col-lg-7">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Relationship Feedback
                    </h5>

                </div>

                <div class="card-body">

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Relationship Status
                            </label>

                            <select
                                name="relationship_status"
                                class="form-select"
                                required
                            >

                                <?php
                                $statuses = [
                                    'New Contact',
                                    'Positive',
                                    'Interested',
                                    'Follow-up Required',
                                    'Referral Potential',
                                    'Not Interested',
                                    'Inactive'
                                ];
                                ?>

                                <?php foreach ($statuses as $status): ?>

                                    <option
                                        value="<?php echo htmlspecialchars($status); ?>"
                                        <?php
                                        if (
                                            ($feedback_data['relationship_status'] ?? 'New Contact')
                                            === $status
                                        ) {
                                            echo 'selected';
                                        }
                                        ?>
                                    >
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Feedback
                            </label>

                            <textarea
                                name="feedback"
                                class="form-control"
                                rows="5"
                                placeholder="Record the response, relationship status or important discussion."
                            ><?php
                            echo htmlspecialchars(
                                $feedback_data['feedback'] ?? ''
                            );
                            ?></textarea>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Next Action
                            </label>

                            <input
                                type="text"
                                name="next_action"
                                class="form-control"
                                placeholder="Example: Follow-up call"
                                value="<?php
                                echo htmlspecialchars(
                                    $feedback_data['next_action'] ?? ''
                                );
                                ?>"
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
                                value="<?php
                                echo !empty($feedback_data['next_action_at'])
                                    ? date(
                                        'Y-m-d\TH:i',
                                        strtotime(
                                            $feedback_data['next_action_at']
                                        )
                                    )
                                    : '';
                                ?>"
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Save Feedback
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>