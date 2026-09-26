 <?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('marketing');

$user = current_user();

$user_id = $user['id'];

$page_title = 'Marketing Dashboard';


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
        e.status
    FROM event_assignments ea
    INNER JOIN events e
        ON ea.event_id = e.id
    WHERE ea.user_id = ?
      AND e.status <> 'Cancelled'
    ORDER BY
        e.event_date ASC,
        e.id ASC
    LIMIT 10
");

$stmt->execute([$user_id]);

$assigned_events = $stmt->fetchAll();


require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Header -->

    <div class="mb-4">

        <span class="badge bg-light text-dark">
            MARKETING EXECUTIVE
        </span>

        <h1 class="hm-page-title mt-2">
            My Day
        </h1>

        <p class="hm-muted">

            Welcome,

            <?php

            echo htmlspecialchars(
                $user['name']
            );

            ?>.

        </p>

    </div>


    <!-- Existing Marketing Modules -->

    <div class="row g-4">

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5 class="fw-bold">
                    Leads
                </h5>

                <p class="hm-muted">
                    Manage assigned marketing enquiries.
                </p>

                <span class="badge bg-light text-dark">
                    Coming Next
                </span>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5 class="fw-bold">
                    Tasks
                </h5>

                <p class="hm-muted">
                    View and complete assigned marketing tasks.
                </p>

                <span class="badge bg-light text-dark">
                    Coming Next
                </span>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5 class="fw-bold">
                    Visits
                </h5>

                <p class="hm-muted">
                    Manage field visits and outreach activities.
                </p>

                <span class="badge bg-light text-dark">
                    Coming Next
                </span>

            </div>

        </div>

    </div>


    <!-- Assigned Events -->

    <div class="hm-card p-4 mt-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h5 class="mb-1">
                    Assigned Events
                </h5>

                <span class="hm-muted">
                    Events assigned to you by the manager.
                </span>

            </div>

            <span class="badge bg-primary">

                <?php

                echo count(
                    $assigned_events
                );

                ?>

            </span>

        </div>


        <?php if (empty($assigned_events)): ?>

            <div class="alert alert-light mb-0">

                No events are currently assigned to you.

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle mb-0">

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

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($assigned_events as $event): ?>

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
                                        $event['location']
                                        ?: '-'
                                    );

                                    ?>

                                </td>

                                <td>

                                    <?php

                                    $event_status =
                                        $event['status'];

                                    $event_badge =
                                        'bg-primary';

                                    if (
                                        $event_status
                                        === 'Ongoing'
                                    ) {

                                        $event_badge =
                                            'bg-warning text-dark';

                                    } elseif (
                                        $event_status
                                        === 'Completed'
                                    ) {

                                        $event_badge =
                                            'bg-success';

                                    }

                                    ?>

                                    <span
                                        class="badge <?php echo $event_badge; ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $event_status
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

<?php

require_once __DIR__ . '/../includes/footer.php';

?>