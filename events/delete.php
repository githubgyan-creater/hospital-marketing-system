<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role(
    'admin',
    'manager'
);

$page_title = 'Delete Event';

$event_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (
    $event_id === false ||
    $event_id === null ||
    $event_id <= 0
) {
    exit('Invalid event ID.');
}

/*

 Get Event

*/

$stmt = $pdo->prepare("
    SELECT
        id,
        event_name,
        event_type,
        event_date,
        location,
        status
    FROM events
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $event_id
]);

$event = $stmt->fetch();

if (!$event) {
    exit('Event not found.');
}

$error = '';

/*

| Process Delete

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $stmt = $pdo->prepare("
            DELETE FROM events
            WHERE id = ?
        ");

        $stmt->execute([
            $event_id
        ]);

        header(
            'Location: ' .
            BASE_URL .
            '/events/'
        );

        exit;

    } catch (PDOException $e) {

        $error = 'Unable to delete the event.';
    }
}

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/events/"
            class="text-decoration-none"
        >
            ← Back to Events
        </a>

        <h1 class="hm-page-title mt-3 mb-1">
            Delete Event
        </h1>

        <p class="hm-muted mb-0">
            Confirm whether you want to permanently delete this event.
        </p>

    </div>

    <!-- Error -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>

    <!-- Confirmation Card -->

    <div class="hm-card p-4">

        <div class="alert alert-warning">

            <strong>
                Warning:
            </strong>

            This action will permanently delete the event.

        </div>

        <div class="row g-4 mb-4">

            <!-- Event Name -->

            <div class="col-md-6">

                <small class="text-muted">
                    Event Name
                </small>

                <h5>

                    <?php

                    echo htmlspecialchars(
                        $event['event_name']
                    );

                    ?>

                </h5>

            </div>

            <!-- Event Type -->

            <div class="col-md-6">

                <small class="text-muted">
                    Event Type
                </small>

                <h5>

                    <?php

                    echo htmlspecialchars(
                        $event['event_type']
                    );

                    ?>

                </h5>

            </div>

            <!-- Event Date -->

            <div class="col-md-6">

                <small class="text-muted">
                    Event Date
                </small>

                <h5>

                    <?php

                    echo date(
                        'd M Y',
                        strtotime(
                            $event['event_date']
                        )
                    );

                    ?>

                </h5>

            </div>

            <!-- Location -->

            <div class="col-md-6">

                <small class="text-muted">
                    Location
                </small>

                <h5>

                    <?php

                    echo htmlspecialchars(
                        $event['location'] ?? '-'
                    );

                    ?>

                </h5>

            </div>

            <!-- Status -->

            <div class="col-md-6">

                <small class="text-muted">
                    Status
                </small>

                <h5>

                    <?php

                    echo htmlspecialchars(
                        $event['status']
                    );

                    ?>

                </h5>

            </div>

        </div>

        <hr>

        <!-- Confirmation Form -->

        <form
            method="POST"
            action="<?php echo BASE_URL; ?>/events/delete.php?id=<?php echo (int) $event_id; ?>"
        >

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-danger"
                >
                    Yes, Delete Event
                </button>

                <a
                    href="<?php echo BASE_URL; ?>/events/view.php?id=<?php echo (int) $event_id; ?>"
                    class="btn btn-outline-secondary"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>