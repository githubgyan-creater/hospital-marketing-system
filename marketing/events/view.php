<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/permission_check.php';

require_role('marketing');
require_permission('events.view');

$user = current_user();
$user_id = (int) $user['id'];

$event_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$event_id) {
    exit('Invalid event ID.');
}

/*
|--------------------------------------------------------------------------
| Verify Event Assignment + Load Event
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        e.id,
        e.event_name,
        e.event_type,
        e.event_date,
        e.location,
        e.description,
        e.status,
        e.created_at,
        ea.assigned_at

    FROM event_assignments ea

    INNER JOIN events e
        ON e.id = ea.event_id

    WHERE ea.event_id = ?
      AND ea.user_id = ?

    LIMIT 1
");

$stmt->execute([
    $event_id,
    $user_id
]);

$event = $stmt->fetch();

if (!$event) {

    http_response_code(403);

    exit(
        'You are not assigned to this event.'
    );
}


/*
|--------------------------------------------------------------------------
| Captured Leads
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        el.id,
        el.created_at,
        l.id AS lead_id,
        l.name,
        l.phone,
        l.email,
        l.service_interest,
        l.status,
        l.priority

    FROM event_leads el

    INNER JOIN leads l
        ON l.id = el.lead_id

    WHERE el.event_id = ?

    ORDER BY
        el.created_at DESC
");

$stmt->execute([
    $event_id
]);

$captured_leads = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Status Badge
|--------------------------------------------------------------------------
*/

$badge_class = 'bg-primary';

if ($event['status'] === 'Ongoing') {
    $badge_class = 'bg-warning text-dark';
} elseif ($event['status'] === 'Completed') {
    $badge_class = 'bg-success';
} elseif ($event['status'] === 'Cancelled') {
    $badge_class = 'bg-danger';
}

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-start mb-4">

        <div>

            <a
                href="<?php echo BASE_URL; ?>/marketing/events/"
                class="text-decoration-none"
            >
                ← My Events
            </a>

            <h1 class="hm-page-title mt-2 mb-1">
                <?php
                echo htmlspecialchars(
                    $event['event_name']
                );
                ?>
            </h1>

            <p class="hm-muted mb-0">
                <?php
                echo htmlspecialchars(
                    $event['event_type']
                );
                ?>
            </p>

        </div>

        <span class="badge <?php echo $badge_class; ?>">
            <?php
            echo htmlspecialchars(
                $event['status']
            );
            ?>
        </span>

    </div>


    <div class="row g-4">

        <!-- EVENT DETAILS -->

        <div class="col-lg-5">

            <div class="hm-card p-4">

                <h5 class="fw-bold mb-4">
                    Event Details
                </h5>

                <div class="mb-3">

                    <div class="hm-muted small">
                        Event Date
                    </div>

                    <strong>
                        <?php
                        echo date(
                            'd M Y',
                            strtotime(
                                $event['event_date']
                            )
                        );
                        ?>
                    </strong>

                </div>

                <div class="mb-3">

                    <div class="hm-muted small">
                        Location
                    </div>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $event['location'] ?: '-'
                        );
                        ?>
                    </strong>

                </div>

                <div class="mb-3">

                    <div class="hm-muted small">
                        Assigned On
                    </div>

                    <strong>
                        <?php
                        echo date(
                            'd M Y, h:i A',
                            strtotime(
                                $event['assigned_at']
                            )
                        );
                        ?>
                    </strong>

                </div>

                <div class="mb-4">

                    <div class="hm-muted small mb-1">
                        Description
                    </div>

                    <div>
                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $event['description'] ?: '-'
                            )
                        );
                        ?>
                    </div>

                </div>


                <?php if (
                    $event['status'] !== 'Completed' &&
                    $event['status'] !== 'Cancelled'
                ): ?>

                    <a
                        href="<?php echo BASE_URL; ?>/events/capture-lead.php?event_id=<?php echo (int) $event['id']; ?>"
                        class="btn btn-hm-primary"
                    >
                        Capture Lead
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <!-- CAPTURED LEADS -->

        <div class="col-lg-7">

            <div class="hm-card p-4">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>

                        <h5 class="mb-1">
                            Leads Captured
                        </h5>

                        <div class="hm-muted small">
                            Leads collected through this event.
                        </div>

                    </div>

                    <span class="badge bg-primary">
                        <?php
                        echo count(
                            $captured_leads
                        );
                        ?>
                    </span>

                </div>


                <?php if (empty($captured_leads)): ?>

                    <div class="alert alert-light border mb-0">
                        No leads have been captured from this event yet.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>Lead</th>
                                    <th>Phone</th>
                                    <th>Interest</th>
                                    <th>Status</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach (
                                $captured_leads
                                as $lead
                            ): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $lead['name']
                                            );
                                            ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $lead['phone']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $lead['service_interest']
                                            ?: '-'
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php
                                            echo htmlspecialchars(
                                                $lead['status']
                                            );
                                            ?>
                                        </span>
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

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>