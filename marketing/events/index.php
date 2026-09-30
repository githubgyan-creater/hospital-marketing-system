<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/permission_check.php';

require_role('marketing');
require_permission('events.view');

$user = current_user();
$user_id = (int) $user['id'];

$page_title = 'My Events';

/*
|--------------------------------------------------------------------------
| Assigned Events
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        ea.id AS assignment_id,
        e.id AS event_id,
        e.event_name,
        e.event_type,
        e.event_date,
        e.location,
        e.description,
        e.status,

        (
            SELECT COUNT(*)
            FROM event_leads el
            WHERE el.event_id = e.id
        ) AS captured_leads

    FROM event_assignments ea

    INNER JOIN events e
        ON e.id = ea.event_id

    WHERE ea.user_id = ?

    ORDER BY
        e.event_date ASC,
        e.id ASC
");

$stmt->execute([
    $user_id
]);

$events = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                My Events
            </h1>

            <p class="hm-muted mb-0">
                Events assigned to you for field marketing activities.
            </p>

        </div>

        <span class="badge text-bg-light">
            <?php echo count($events); ?> Events
        </span>

    </div>


    <div class="hm-card p-4">

        <?php if (empty($events)): ?>

            <div class="alert alert-light border mb-0">
                No events are currently assigned to you.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>Event</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Leads</th>
                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($events as $event): ?>

                        <?php

                        $badge_class = 'bg-primary';

                        if ($event['status'] === 'Ongoing') {
                            $badge_class = 'bg-warning text-dark';
                        } elseif ($event['status'] === 'Completed') {
                            $badge_class = 'bg-success';
                        } elseif ($event['status'] === 'Cancelled') {
                            $badge_class = 'bg-danger';
                        }

                        ?>

                        <tr>

                            <td>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $event['event_name']
                                    );
                                    ?>
                                </strong>

                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $event['event_type']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $event['event_date']
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $event['location'] ?: '-'
                                );
                                ?>
                            </td>

                            <td>

                                <span
                                    class="badge <?php echo $badge_class; ?>"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $event['status']
                                    );
                                    ?>
                                </span>

                            </td>

                            <td>
                                <?php
                                echo (int) $event['captured_leads'];
                                ?>
                            </td>

                            <td>

                                <div class="d-flex gap-1">

                                    <a
                                        href="<?php echo BASE_URL; ?>/marketing/events/view.php?id=<?php echo (int) $event['event_id']; ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        View
                                    </a>

                                    <?php if (
                                        $event['status'] !== 'Completed' &&
                                        $event['status'] !== 'Cancelled'
                                    ): ?>

                                        <a
                                            href="<?php echo BASE_URL; ?>/events/capture-lead.php?event_id=<?php echo (int) $event['event_id']; ?>"
                                            class="btn btn-sm btn-hm-primary"
                                        >
                                            Capture Lead
                                        </a>

                                    <?php endif; ?>

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

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>