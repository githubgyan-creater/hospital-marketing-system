<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role(
    'admin',
    'manager'
);

$page_title = 'Events Management';

/*

| Get Events

*/

$stmt = $pdo->query("
    SELECT
        e.*,
        u.name AS creator_name
    FROM events e
    LEFT JOIN users u
        ON e.created_by = u.id
    ORDER BY e.event_date DESC, e.created_at DESC
");

$events = $stmt->fetchAll();

/*

| Summary Counts

*/

$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total_events,

        SUM(status = 'Planned') AS planned_events,

        SUM(status = 'Ongoing') AS ongoing_events,

        SUM(status = 'Completed') AS completed_events

    FROM events
");

$summary = $stmt->fetch();

$total_events =
    (int) ($summary['total_events'] ?? 0);

$planned_events =
    (int) ($summary['planned_events'] ?? 0);

$ongoing_events =
    (int) ($summary['ongoing_events'] ?? 0);

$completed_events =
    (int) ($summary['completed_events'] ?? 0);

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                Events Management
            </h1>

            <p class="hm-muted mb-0">
                Manage hospital events, health camps and outreach activities.
            </p>

        </div>

        <div class="d-flex gap-2">


        <a
    href="<?php echo BASE_URL; ?>/manager/events/performance.php"
    class="btn btn-outline-primary"
>
    📊 Performance
</a>

            <a
                href="<?php echo BASE_URL; ?>/events/add.php"
                class="btn btn-hm-primary"
            >
                + Add Event
            </a>

            <?php if (current_user()['role'] === 'admin'): ?>

                <a
                    href="<?php echo BASE_URL; ?>/admin/dashboard.php"
                    class="btn btn-outline-secondary"
                >
                    Dashboard
                </a>

            <?php else: ?>

                <a
                    href="<?php echo BASE_URL; ?>/manager/dashboard.php"
                    class="btn btn-outline-secondary"
                >
                    Dashboard
                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- Summary Cards -->

    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Events
                </div>

                <h2 class="mb-0">
                    <?php echo $total_events; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Planned
                </div>

                <h2 class="mb-0 text-primary">
                    <?php echo $planned_events; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Ongoing
                </div>

                <h2 class="mb-0 text-warning">
                    <?php echo $ongoing_events; ?>
                </h2>

            </div>

        </div>


        <div class="col-md-3">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Completed
                </div>

                <h2 class="mb-0 text-success">
                    <?php echo $completed_events; ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- Events Table -->

    <div class="hm-card p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h5 class="mb-1">
                    Events
                </h5>

                <span class="hm-muted">
                    <?php echo count($events); ?> event(s)
                </span>

            </div>

        </div>


        <?php if (empty($events)): ?>

            <div class="alert alert-light mb-0">

                No events found.

                <a
                    href="<?php echo BASE_URL; ?>/events/add.php"
                    class="alert-link"
                >
                    Create the first event
                </a>

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>
                                Event
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created By
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($events as $event): ?>

                            <tr>

                                <!-- Event -->

                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $event['event_name']
                                        );

                                        ?>

                                    </strong>

                                </td>


                                <!-- Type -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $event['event_type']
                                    );

                                    ?>

                                </td>


                                <!-- Date -->

                                <td>

                                    <?php

                                    if (!empty($event['event_date'])) {

                                        echo date(
                                            'd M Y',
                                            strtotime(
                                                $event['event_date']
                                            )
                                        );

                                    } else {

                                        echo '-';

                                    }

                                    ?>

                                </td>


                                <!-- Location -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $event['location'] ?? '-'
                                    );

                                    ?>

                                </td>


                                <!-- Status -->

                                <td>

                                    <?php

                                    $status = $event['status'];

                                    if ($status === 'Completed') {

                                        $badge_class = 'bg-success';

                                    } elseif ($status === 'Ongoing') {

                                        $badge_class = 'bg-warning text-dark';

                                    } elseif ($status === 'Cancelled') {

                                        $badge_class = 'bg-danger';

                                    } else {

                                        $badge_class = 'bg-primary';

                                    }

                                    ?>

                                    <span
                                        class="badge <?php echo $badge_class; ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $status
                                        );

                                        ?>

                                    </span>

                                </td>


                                <!-- Created By -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $event['creator_name'] ?? '-'
                                    );

                                    ?>

                                </td>


                                <!-- Action -->

                                <td>

                                    <a
                                        href="<?php echo BASE_URL; ?>/events/view.php?id=<?php echo (int) $event['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="<?php echo BASE_URL; ?>/events/edit.php?id=<?php echo (int) $event['id']; ?>"
                                        class="btn btn-sm btn-outline-primary mt-1"
                                    >
                                        Edit
                                    </a>

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

require_once __DIR__ . '/../includes/footer.php';

?>